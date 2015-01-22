<?php namespace Bedard\Shop\Components;

use Bedard\Shop\Classes\Paypal\ExtendedExpressGateway;
use Bedard\Shop\Models\PaySettings;
use Bedard\Shop\Models\Order;
use Cms\Classes\ComponentBase;
use Exception;
use Omnipay\Omnipay;
use Session;

class PaypalExpressCallback extends ComponentBase
{
    use \Bedard\Shop\Traits\CheckoutTrait;

    public function componentDetails()
    {
        return [
            'name'        => 'Paypal Callback',
            'description' => 'Callback handler for Paypal Express checkouts.'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    /**
     * Attempt to complete the purchase
     * @return  Redirect
     */
    public function onRun()
    {
        try {
            $gateway = $this->openPaypalExpressGateway();
            $session = Session::get('bedard_shop_order');
            $order = Order::where('hash', $session['hash'])
                ->where('is_complete', false)
                ->find($session['id']);

            $response = $gateway->completePurchase([
                'amount' => $order->amount,
                'currency' => PaySettings::get('currency_code'),
            ])->send();

            $data = $response->getData();
            $order->gateway_code = $data['PAYMENTINFO_0_TRANSACTIONID'];

            if ($response->isSuccessful())
                $order->cart->markAsComplete($order);
            
            else
                throw new Exception('Failed response from PayPal.');
        }

        catch (Exception $e) {
            var_dump ($e->getMessage());
        }
    }

}