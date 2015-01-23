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
    public $isEmpty = true;     // boolean
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
        if (!$inventory = $this->loadInventory($inventoryId, $slug))
            return $this->failedResponse('Inventory not found.');

        // FirstOrCreate the cart item, and add the quantity
        $cartItem = CartItem::firstOrNew([
            'cart_id' => $this->cart->id,
            'inventory_id' => $inventory->id
        ]);

        if ($cartItem->quantity >= $inventory->quantity) {
            $this->itemCount = CartItem::where('cart_id', $this->cart->id)->sum('quantity');
            $this->isEmpty = $this->itemCount == 0;
            return $this->failedResponse('All available inventory is already in your cart.');
        }

        // Update and save the cart item quantity
        $cartItem->quantity += $quantity;
        if (!$cartItem->save()) {
            $this->itemCount = CartItem::where('cart_id', $this->cart->id)->sum('quantity');
            $this->isEmpty = $this->itemCount == 0;
            return $this->failedResponse('Failed to save cart item.');
        }

        // Restart the checkout process
        $this->restartCheckoutProcess();

        // Refresh the item count, and send back a response
        $this->itemCount = CartItem::where('cart_id', $this->cart->id)->sum('quantity');
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
            return $this->failedResponse('Cart not found.');

        // Load the CartItem ID
        if (!$itemId = post('bedard_shop_item_id'))
            return $this->failedResponse('Missing "bedard_shop_item_id".');

        // Find the item being removed
        $item = CartItem::where('cart_id', $this->cart->id)->find($itemId);

        // If the item wasn't found, send back a failure message
        if (!$item)
            return $this->failedResponse('Item not found');

        // Cart items are never fully deleted, instead just set the quantity
        // to zero. This way we have a record of what was "almost bought".
        $item->quantity = 0;

        // Attempt to save the item
        if (!$item->save())
            return $this->failedResponse('Failed to delete item');

        // Restart the checkout process
        $this->restartCheckoutProcess();

        // Refresh the cart, and send back a success message
        $this->loadCart(true);
        $this->storeCartValues();
        return $this->response('Item successfully removed.');
    }

    /**
     * Updated the cart items
     */
    public function onUpdateCart()
    {
        // Make sure we have a cart loaded
        if (!$this->cart)
            return $this->failedResponse('Cart not found.');

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
            $coupon = Coupon::where('name', $couponCode)->isActive()->first();
            if ($coupon)
                $this->cart->coupon()->associate($coupon);
        }

        // Save the cart, fix the quantities, and update cart items
        $this->cart->push();
        $fixedQuantities = $this->cart->fixQuantities();
        $this->loadCart(true);
        $this->storeCartValues();

        // Restart the checkout process
        $this->restartCheckoutProcess();

        // Coupon not found
        if ($couponCode && !$coupon)
            return $this->failedResponse('Coupon not found.');

        elseif ($fixedQuantities)
            return $this->failedResponse('Item quantities fixed.');

        return $this->response('Cart updated.');
    }

    /**
     * Removes a coupon from a cart
     */
    public function onRemoveCoupon()
    {
        if (!$this->cart)
            return $this->failedResponse('Cart not found.');

        if (!$this->cart->coupon_id)
            return $this->failedResponse('No coupon to remove.');

        $this->cart->coupon_id = null;
        $this->cart->save();

        $this->loadCart(true);
        $this->storeCartValues();
        return $this->response('Cart updated.');
    }
}