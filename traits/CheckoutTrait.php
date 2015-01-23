<?php namespace Bedard\Shop\Traits;

use Bedard\Shop\Models\Cart as CartModel;
use Bedard\Shop\Models\Customer;
use Bedard\Shop\Models\Order;
use Bedard\Shop\Models\PaySettings;
use Cookie;
use Exception;
use Omnipay\Omnipay;
use Omnipay\Common\CreditCard;
use Session;

trait CheckoutTrait {

    /**
     * Opens an omnipay gateway for Paypal Express
     * @return  Omnipay
     */
    public function openPaypalExpressGateway()
    {
        $gateway = Omnipay::create('PayPal_Express');
        $gateway->setUsername(PaySettings::get('paypal_express_username'));
        $gateway->setPassword(PaySettings::get('paypal_express_password'));
        $gateway->setSignature(PaySettings::get('paypal_express_signature'));

        if (PaySettings::get('paypal_express_sandbox'))
            $gateway->setTestMode(true);

        return $gateway;
    }

    /**
     * Returns the active cart
     * @return  Bedard\Shop\Models\Cart
     */
    public function getActiveCart()
    {
        if (!$cookie = Cookie::get('bedard_shop_cart'))
            return false;

        return CartModel::where('key', $cookie['key'])
            ->whereNull('order_id')
            ->with('items.inventory.product.discounts')
            ->with('items.inventory.product.categories.discounts')
            ->with(['items' => function($items) {
                $items->inCart();
            }])
            ->find($cookie['id']);
    }

    /**
     * Returns the active order
     * @return  Bedard\Shop\Models\Order
     */
    public function getActiveOrder(CartModel $cart)
    {
        $session = Session::get('bedard_shop_order');

        if (!isset($session['hash']))
            $session['hash'] = str_random(40);

        $order = Order::firstOrCreate([
            'cart_id'   => $cart->id,
            'hash'      => $session['hash'],
            'gateway'   => 'PayPal_Express',
            'amount'    => $cart->total
        ]);

        $session['id'] = $order->id;
        Session::put('bedard_shop_order', $session);

        return $order;
    }

    /**
     * Attach a user's shipping address to their order
     * 
     * @param   Order   $order
     * @param   array   $address
     * 
     * @return  Order
     */
    private function attachShippingAddress(Order $order, array $address)
    {
        // Make sure all required keys are present
        if (!array_key_exists('first_name', $address) || empty($address['first_name']) ||
            !array_key_exists('last_name', $address) || empty($address['last_name']) ||
            !array_key_exists('email', $address) || empty($address['email']) ||
            !array_key_exists('address1', $address) || empty($address['address1']) ||
            !array_key_exists('address2', $address) ||
            !array_key_exists('city', $address) || empty($address['city']) ||
            !array_key_exists('state', $address) || empty($address['state']) ||
            !array_key_exists('postcode', $address) || empty($address['postcode']) ||
            !array_key_exists('country', $address) || empty($address['country'])) {
            throw new Exception('Missing required shipping information.');
        }

        // Address cleaning
        foreach ($address as $key => $value)
            $address[$key] = trim($value);
    
        $address['first_name'] = ucfirst(strtolower($address['first_name']));
        $address['last_name'] = ucfirst(strtolower($address['last_name']));

        // Make the customer
        $customer = Customer::firstOrCreate([
            'first_name'    => $address['first_name'],
            'last_name'     => $address['last_name'],
            'email'         => $address['email']
        ]);

        // Attach the customer to the order
        $order->shipping_address = $address;
        $order->customer_id = $customer->id;
        $order->save();

        return $order;
    }

    /**
     * Returns a credit cart object with the user's shipping address
     * @param   Order       $order
     * @return  CreditCard
     */
    private function getCardAddress(Order $order)
    {
        $address = $order->shipping_address;

        return new CreditCard([
            'name' => $address['first_name'].' '.$address['last_name'],
            'address1' => $address['address1'],
            'address2' => $address['address2'],
            'city' => $address['city'],
            'state' => $address['state'],
            'country' => $address['country'],
            'postcode' => $address['postcode'],
            // 'phone' => '555-555-5555',
            'email' => $address['email'],
        ]);
    }

    /**
     * Returns a list of cart items, and backs up their information
     * @param   Order       $order
     * @param   CartModel   $cart
     * @return  array
     */
    private function getItems(Order $order, CartModel $cart)
    {
        $items = [];
        foreach ($cart->items as $item) {
            $items[] = [
                'name'          => $item->productName,
                'description'   => $item->inventoryName,
                'quantity'      => $item->quantity,
                'price'         => number_format($item->price, 2)
            ];

            // Backup the item
            $item->backup_price = $item->price;
            $item->backup_full_price = $item->full_price;
            $item->backup_inventory = $item->inventoryName;
            $item->backup_product = $item->productName;
            $item->save();
        }

        // Apply the promo code if there is one
        if ($cart->couponIsApplied) {
            $items[] = [
                'name'  => 'Coupon: '.$cart->coupon->name,
                'quantity'      => 1,
                'price'         => $cart->couponValue
            ];
        }

        return $items;
    }
}