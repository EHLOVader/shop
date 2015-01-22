<?php namespace Bedard\Shop\Components;

use Bedard\Shop\Models\Cart as CartModel;
use Bedard\Shop\Models\Order;
use Bedard\Shop\Models\PaySettings;
use Cms\Classes\ComponentBase;
use Exception;
use Log;
use Omnipay\Omnipay;
use Omnipay\Common\CreditCard;
use Redirect;
use Session;

class PaypalExpressCheckout extends ComponentBase
{
    use \Bedard\Shop\Traits\CheckoutTrait;

    /**
     * Define component details
     * @return  array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'Paypal Checkout',
            'description' => 'Simple checkouts with Paypal Express.'
        ];
    }

    /**
     * Define component properties
     * @return  array
     */
    public function defineProperties()
    {
        return [
            'returnUrl' => [
                'title'             => 'Return URL',
                'description'       => 'URL to a page with the Paypal Express Callback component attached.',
                'type'              => 'string',
            ],
            'canceledUrl' => [
                'title'             => 'Canceled URL',
                'description'       => 'URL to redirect to upon payment cancellation.',
                'type'              => 'string',
            ],
            'failedUrl' => [
                'title'             => 'Failed URL',
                'description'       => 'URL to redirect to upon payment failure.',
                'type'              => 'String'
            ]
        ];
    }

    /**
     * Accepts an address form, and redirects to Paypal
     * @return  Redirect
     */
    public function onCheckoutWithAddress()
    {
        $cart = $this->getActiveCart();
        $order = $this->getActiveOrder($cart);
        $order = $this->attachShippingAddress($order, post('bedard_shop_address'));
        $card = $this->getCardAddress($order);

        return $this->beginPaypalCheckout($order, $cart, $card);
    }

    /**
     * Calls the purchase method with Omnipay
     * @param   Order       $order
     * @param   CartModel   $cart
     * @param   CreditCard  $card
     * @return  Redirect
     */
    private function beginPaypalCheckout(Order $order, CartModel $cart, CreditCard $card)
    {
        try {
            $gateway = $this->openPaypalExpressGateway();
            $items = $this->getItems($order, $cart);
            
            $response = $gateway->purchase([
                    'returnUrl' => $this->property('returnUrl'),
                    'cancelUrl' => $this->property('canceledUrl'),
                    'amount'    => number_format($cart->total, 2),
                    'currency'  => PaySettings::get('currency_code'),
                ])
                ->setItems($items)
                ->setShippingAmount('0.00')
                ->setCard($card)
                ->setAddressOverride(1)
                ->setNoShipping(2)
                ->send();

            if ($response->isRedirect())
                return Redirect::to($response->getRedirectUrl());
            
            else
                throw new Exception('Invalid response');
        }

        catch (Exception $e) {
            Log::error('Failed to begin checkout on cart #'.$cart->id, [
                'gateway'   => 'Paypal Express',
                'message'   => $e->getMessage(),
                'timestamp' => time()
            ]);
            return Redirect::to($this->property('canceledUrl'));
        }
    }
}