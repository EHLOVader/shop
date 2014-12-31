<?php namespace Bedard\Shop\Components;

use Bedard\Shop\Models\Cart as CartModel;
use Bedard\Shop\Models\CartItem;
use Bedard\Shop\Models\Inventory;
use Bedard\Shop\Models\Product;
use Bedard\Shop\Models\Settings;
use Cms\Classes\ComponentBase;
use Cookie;
use Request;

class Cart extends ComponentBase
{
    /**
     * True if the current request is to an ajax handler
     * @var boolean
     */
    private $isAjax;

    /**
     * The user's shopping cart
     * @var Bedard\Shop\Models\Cart
     */
    private $cart;

    /**
     * These variables exist to make the Twig markup a little cleaner. Rather
     * then declaring a public cart variable, we can call "Component.total"
     * instead of "Component.cart.total"
     */
    public $total;
    public $fullTotal;
    public $isDiscounted;

    /**
     * Determines if the cart is empty or not
     * @var boolean
     */
    public $isEmpty;

    /**
     * The number of items in the cart
     * @var integer
     */
    public $itemCount;

    /**
     * Items in the user's cart
     * @var Collection  Bedard\Shop\Models\CartItem
     */
    public $items;

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
     * Response builder
     * @param   string  $message    The message being sent back to the page
     * @param   boolean $result     True / false on if the request was ok
     * @param   boolean $error      Sets a 406 status code if something unexpected happened
     */
    private function response($message, $success = TRUE, $error = FALSE)
    {
        // Set the response message and status
        $response['message'] = $message;
        $response['success'] = $success;

        // If we have a actual error, set the status code to 406
        if ($error) $this->setStatusCode(406);

        return $response;
    }

    /**
     * Calls loadCart()
     */
    public function onInit()
    {
        // Determine if this is an ajax handler
        $handler = trim(Request::header('X_OCTOBER_REQUEST_HANDLER'));
        $this->isAjax = preg_match('/^(?:\w+\:{2})?on[A-Z]{1}[\w+]*$/', $handler) && method_exists($this, $handler);

        // Load the cart
        $this->loadCart();
    }

    /**
     * Creates a new shopping cart and cookie
     */
    private function makeCart()
    {
        $this->cart = CartModel::create(['key' => str_random(40)]);
        $this->refreshCartCookie();
    }


    /**
     * Loads / refreshes the current cart
     */
    private function loadCart()
    {
        // Look for a cart cookie
        if (!$this->cookie = Cookie::get('bedard_shop_cart'))
            return FALSE;

        // If this is an ajax request, just load the cart
        if ($this->isAjax) {
            $this->cart = CartModel::where('key', $this->cookie['key'])
                ->whereNull('transaction_id')
                ->find($this->cookie['id']);
        }

        // For regular requests, we've got some extra work to do
        else {
            $this->cart = CartModel::where('key', $this->cookie['key'])
                ->whereNull('transaction_id')
                ->with('items.inventory.product.discounts')
                ->with('items.inventory.product.categories.discounts')
                ->with(['items' => function($item) {
                    $item->inCart();
                }])
                ->find($this->cookie['id']);

            $this->items = $this->cart->items;
            $this->calculateCartValues();
        }

        // Refresh the cookie
        $this->refreshCartCookie();
    }

    /**
     * Refresh the cart cookie with a new expiration
     */
    private function refreshCartCookie()
    {
        Cookie::queue('bedard_shop_cart', [
            'id'    => $this->cart->id,
            'key'   => $this->cart->key
        ], Settings::get('cart_life'));
    }

    private function refreshCart()
    {

    }

    /**
     * Load the cart values
     */
    private function calculateCartValues()
    {
        $this->itemCount = array_sum(array_column($this->cart->items->toArray(), 'quantity'));
        $this->isEmpty = (bool) !$this->itemCount;

        $this->total = $this->cart->total;
        $this->fullTotal = $this->cart->fullTotal;
        $this->isDiscounted = $this->cart->isDiscounted;
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

        // Refresh the cart, and send back a success message
        $this->loadCart();
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

        // Refresh the cart, and send back a success message
        $this->loadCart();
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
            $item->quantity = $quantities[$item->id];
        }

        $this->cart->push();
    }
}