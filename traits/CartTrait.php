<?php namespace Bedard\Shop\Traits;

use Bedard\Shop\Models\Cart as CartModel;
use Bedard\Shop\Models\Settings;
use Cookie;
use DB;
use Request;
use Session;

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
                ->whereNull('order_id')
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
                ->whereNull('order_id')
                ->find($this->cookie['id']);
        }

        // Refresh the cookie
        $this->refreshCartCookie();
    }

    /**
     * Refresh the cart cookie
     */
    private function refreshCartCookie()
    {
        if ($this->cart) {
            Cookie::queue('bedard_shop_cart', [
                'id'    => $this->cart->id,
                'key'   => $this->cart->key
            ], Settings::get('cart_life'));
        }
    }

    /**
     * Restarts the checkout process
     */
    private function restartCheckoutProcess()
    {
        // Forget the shipping rates
        Session::forget('bedard_shop_shipping');

        // Kill any orders that were started
        $delete = DB::table('bedard_shop_orders')
            ->where('is_complete', 0)
            ->where('cart_id', $this->cart->id)
            ->delete();
    }

}