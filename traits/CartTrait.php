<?php namespace Bedard\Shop\Traits;

use Bedard\Shop\Models\Cart as CartModel;
use Bedard\Shop\Models\Settings;
use Cookie;
use Request;

trait CartTrait
{

    /**
     * @var Bedard\Shop\Models\Cart
     */
    private $cart;

    /**
     * @var Collection  Bedard\Shop\Models\CartItem
     */
    public $items;

    /**
     * @var Bedard\Shop\Models\Coupon
     */
    public $coupon;

    /**
     * Creates a new shopping cart and cookie
     */
    private function makeCart()
    {
        $this->cart = CartModel::create(['key' => str_random(40)]);
        $this->refreshCartCookie();
    }

    /**
     * Loads the cart and refreshes the cookie
     */
    private function loadCart($withRelationships = FALSE)
    {
        // Look for a cart cookie
        if (!$this->cookie = Cookie::get('bedard_shop_cart'))
            return FALSE;

        // Load the cart with relationships
        if ($withRelationships) {
            $this->cart = CartModel::where('key', $this->cookie['key'])
                ->whereNull('transaction_id')
                ->with('items.inventory.product.discounts')
                ->with('items.inventory.product.categories.discounts')
                ->with(['items' => function($item) {
                    $item->inCart();
                }])
                ->find($this->cookie['id']);

            if ($this->cart && !is_null($this->cart->coupon_id))
                $this->cart->load('coupon');
        }

        // Load the cart without relationships
        else {
            $this->cart = CartModel::where('key', $this->cookie['key'])
                ->whereNull('transaction_id')
                ->find($this->cookie['id']);
        }

        // Refresh the cookie
        if ($this->cart) {
            Cookie::queue('bedard_shop_cart', [
                'id'    => $this->cart->id,
                'key'   => $this->cart->key
            ], Settings::get('cart_life'));
        }
    }

}