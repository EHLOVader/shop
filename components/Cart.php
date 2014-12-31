<?php namespace Bedard\Shop\Components;

use Bedard\Shop\Models\Cart as CartModel;
use Bedard\Shop\Models\CartItem;
use Bedard\Shop\Models\Inventory;
use Bedard\Shop\Models\Product;
use Bedard\Shop\Models\Settings;
use Cms\Classes\ComponentBase;
use Cookie;

class Cart extends ComponentBase
{

    /**
     * The user's shopping cart
     * @var Bedard\Shop\Models\Cart
     */
    private $cart;

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
     * Initialize the shopping cart if one exists
     */
    public function onInit()
    {
        // Get our cart cookie
        $this->cookie = Cookie::get('bedard_shop_cart');

        if ($this->cookie) {
            // Load the cart
            $this->cart = CartModel::where('key', $this->cookie['key'])
                ->whereNull('transaction_id')
                ->with(['items' => function($item) {
                    $item->inCart();
                }])
                ->find($this->cookie['id']);

            // Sum up the items in our cart
            $this->itemCount = array_sum(array_column($this->cart->items->toArray(), 'quantity'));
        }

        // Set the isEmpty flag
        $this->isEmpty = (bool) !$this->itemCount;
    }

    /**
     * Creates a new shopping cart and cookie
     */
    private function makeCart()
    {
        $this->cart = CartModel::create(['key' => str_random(40)]);
        Cookie::queue('bedard_shop_cart', [
            'id'    => $this->cart->id,
            'key'   => $this->cart->key
        ], Settings::get('cart_life'));
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
        if (!$this->cart) $this->makeCart();

        // Load the post variables that may have come in
        $slug = post('bedard_shop_product');
        $inventoryId = post('bedard_shop_inventory');
        $quantity = post('bedard_shop_quantity') ?: 1;

        // Load the inventory
        $inventory = $this->loadInventory($inventoryId, $slug);

        if (!$inventory) {
            // Inventory not found, set a 406 and bail
            return;
        }

        // FirstOrCreate the cart item
        $cartItem = CartItem::firstOrCreate([
            'cart_id' => $this->cart->id,
            'inventory_id' => $inventory->id
        ]);
        $cartItem->quantity += $quantity;

        // Prevent over-adding
        if ($cartItem->quantity > $inventory->quantity)
            $cartItem->quantity = $inventory->quantity;

        // Save the results
        $cartItem->save();
    }
}