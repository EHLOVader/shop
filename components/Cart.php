<?php namespace Bedard\Shop\Components;

use Bedard\Shop\Models\Cart as CartModel;
use Bedard\Shop\Models\CartItem;
use Bedard\Shop\Models\Coupon;
use Bedard\Shop\Models\Inventory;
use Bedard\Shop\Models\Product;
use Bedard\Shop\Models\Settings;
use Cms\Classes\ComponentBase;
use Cookie;
use DB;
use Session;

class Cart extends ComponentBase
{
    use \Bedard\Shop\Traits\CartTrait;
    use \Bedard\Shop\Traits\AjaxResponderTrait;

    /**
     * These variables exist to make the Twig markup a little cleaner. Rather
     * then declaring a public cart variable, we can call "Component.total"
     * instead of "Component.cart.total"
     */
    public $total;              // string (numeric)
    public $totalBeforeCoupon;  // string (numeric)
    public $fullTotal;          // string (numeric)
    public $isDiscounted;       // boolean
    public $isEmpty = TRUE;     // boolean
    public $itemCount = 0;      // integer
    public $hasCoupon;          // boolean
    public $couponIsApplied;    // boolean
    public $couponThreshold;    // string (numeric)

    /**
     * Component Details
     * @return  array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'Shopping Cart',
            'description' => 'Provides core shopping cart functionality.'
        ];
    }

    /**
     * Calls loadCart()
     */
    public function onInit()
    {
        // For ajax requests, don't load the relationships
        $withRelationships = !$this->isAjax();

        // Load the cart
        $this->loadCart($withRelationships);
        if ($withRelationships)
            $this->storeCartValues();
    }

    /**
     * Load the cart values
     */
    private function storeCartValues()
    {
        if ($this->cart) {
            // Load the coupon
            if (!is_null($this->cart->coupon_id) && $this->cart->coupon) {
                $this->coupon = $this->cart->coupon;
                $this->couponIsApplied = $this->cart->couponIsApplied;
                $this->couponThreshold = $this->coupon->cart_value;
            }

            // Load the cart variables and refresh the cookie
            $this->items = $this->cart->items;

            $this->itemCount = array_sum(array_column($this->cart->items->toArray(), 'quantity'));
            $this->isEmpty = $this->itemCount == 0;
            $this->total = $this->cart->total;
            $this->totalBeforeCoupon = $this->cart->totalBeforeCoupon;
            $this->fullTotal = $this->cart->fullTotal;
            $this->isDiscounted = $this->cart->isDiscounted;
            $this->hasCoupon = (bool) $this->cart->coupon_id;
        }
    }

    /**
     * Loads a product by inventory ID or product slug
     * @param   integer $inventoryId
     * @param   string  $slug
     * @return  Bedard\Shop\Models\Inventory
     */
    private function loadInventory($inventoryId, $slug)
    {
        // If an inventory ID was passed in, find and return it
        if ($inventoryId)
            return Inventory::inStock()->find($inventoryId);

        // Otherwise, check the product and return it's first inventory
        $product = Product::where('slug', $slug)
            ->with('inventories')
            ->whereHas('inventories', function($inventory) {
                $inventory->inStock();
            })
            ->isActive()
            ->first();

        // Grab the first inventory
        foreach ($product->inventories as $inventory)
            return $inventory;
    }

    /**
     * Adds a product to the cart
     */
    public function onAddToCart()
    {
        // Make a cart if we don't have one
        if (!$this->cart)
            $this->makeCart();

        // Load the post variables that may have come in
        $slug = post('bedard_shop_product');
        $inventoryId = post('bedard_shop_inventory');
        $quantity = post('bedard_shop_quantity') ?: 1;
        
        // Load the inventory
        $inventory = $this->loadInventory($inventoryId, $slug);

        // If no inventory was found, send back a failure message
        if (!$inventory)
            return $this->response('Inventory not found', FALSE);

        // FirstOrCreate the cart item, and add the quantity
        $cartItem = CartItem::firstOrCreate([
            'cart_id' => $this->cart->id,
            'inventory_id' => $inventory->id
        ]);
        $cartItem->quantity += $quantity;

        // Attempt to save our results
        if (!$cartItem->save())
            return $this->response('Failed to save cart item', FALSE);

        // Forget the calculated shipping
        $this->forgetShipping();

        // Refresh the item count, and send back a response
        $this->itemCount = CartItem::where('cart_id', $this->cart->id)
            ->sum('quantity');
        $this->isEmpty = $this->itemCount == 0;
        return $this->response('Product added to cart');
    }

    /**
     * Remove an item from the cart
     * @post   integer bedard_shop_item_id (post)
     */
    public function onRemoveFromCart()
    {
        // Make sure we have a cart loaded
        if (!$this->cart)
            return $this->response('Cart not found', FALSE);

        // Load the CartItem ID
        if (!$itemId = post('bedard_shop_item_id'))
            return $this->response('Missing "bedard_shop_item_id"', FALSE);

        // Find the item being removed
        $item = CartItem::where('cart_id', $this->cart->id)
            ->find($itemId);

        // If the item wasn't found, send back a failure message
        if (!$item)
            return $this->response('Item not found', FALSE);

        // Cart items are never fully deleted, instead just set the quantity
        // to zero. This way we have a record of what was "almost bought".
        $item->quantity = 0;

        // Attempt to save the item
        if (!$item->save())
            return $this->response('Failed to delete item', FALSE);

        // Forget the calculated shipping
        $this->forgetShipping();

        // Refresh the cart, and send back a success message
        $this->loadCart(TRUE);
        $this->storeCartValues();
        return $this->response('Item deleted');
    }

    /**
     * Updated the cart items
     */
    public function onUpdateCart()
    {
        // Make sure we have a cart loaded
        if (!$this->cart)
            return $this->response('Cart not found', FALSE);

        // Load the cart items, and the desired quantities
        $this->cart->load('items.inventory');
        $quantities = post('bedard_shop_item');

        // Update the inventories
        foreach ($this->cart->items as $item) {
            if (!array_key_exists($item->id, $quantities)) continue;
            if ($item->quantity != $quantities[$item->id])
                $item->quantity = $quantities[$item->id];
        }

        // Check if a coupon code is being applied
        if ($couponCode = post('bedard_shop_coupon')) {
            $coupon = Coupon::where('name', $couponCode)
                ->isActive()
                ->first();
            if ($coupon)
                $this->cart->coupon()->associate($coupon);
        }

        // Save the cart, fix the quantities, and update cart items
        $this->cart->push();
        $fixedQuantities = $this->cart->fixQuantities();
        $this->loadCart(TRUE);
        $this->storeCartValues();

        // Forget the calculated shipping
        $this->forgetShipping();

        // Coupon not found
        if ($couponCode && !$coupon)
            return $this->response('Coupon not found', FALSE);

        elseif ($fixedQuantities)
            return $this->response('Fixed quantities', FALSE);

        else
            return $this->response('Cart updated');
    }

    /**
     * Removes a coupon from a cart
     */
    public function onRemoveCoupon()
    {
        if (!$this->cart)
            return $this->response('Cart not found', FALSE);

        if (!$this->cart->coupon_id)
            return $this->response('No coupon to remove', FALSE);

        $this->cart->coupon_id = NULL;
        $this->cart->save();

        $this->loadCart(TRUE);
        $this->storeCartValues();
        return $this->response('Cart updated');
    }
}