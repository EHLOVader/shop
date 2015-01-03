<?php namespace Bedard\Shop\Components;

use Bedard\Shop\Models\Transaction;
use Bedard\Shop\Classes\Paypal;
use Cms\Classes\ComponentBase;
use Cookie;
use Input;
use Session;

class PaypalCallback extends ComponentBase
{
    use \Bedard\Shop\Traits\AjaxResponderTrait;
    use \Bedard\Shop\Traits\CartTrait;

    /**
     * Component Details
     * @return  array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'PayPal Callback',
            'description' => 'Responds to callbacks from PayPal.'
        ];
    }

    /**
     * Component Properties
     * @return  array
     */
    public function defineProperties()
    {
        return [
            'callback' => [
                'title'         => 'Callback Type',
                'description'   => 'Success, canceled, or failed.',
                'type'          => 'dropdown',
                'options' => [
                    'success'   => 'Success',
                    'canceled'  => 'Canceled',
                    'failed'    => 'Failed'
                ],
                'default' => '{{ :callback }}'
            ]
        ];
    }

    /**
     * Route the callback to a callback processor
     */
    public function onRun()
    {
        $callback = $this->property('callback');

        if($callback == 'success')
            $this->processSuccess();

        elseif ($callback == 'canceled')
            $this->processCanceled();

        elseif ($callback == 'failed')
            $this->processFailed();
    }

    /**
     * Process success callbacks
     */
    private function processSuccess()
    {
        $payerId = Input::get('PayerID');
        $transaction = Transaction::where('payment_id', Input::get('paymentId'))
            ->where('hash', Session::get('bedard_shop_paypal_hash'))
            ->where('is_complete', FALSE)
            ->first();
        if (!$payerId || !$transaction)
            return $this->response('Transaction not found', FALSE);

        // Load the cart with it's relationships
        $this->loadCart(TRUE);
        if (!$this->cart)
            return $this->response('Cart not found', FALSE);

        // Set up the API, and execute the payment
        $paypal = new Paypal;
        $paypal->executePayment($payerId, $transaction['payment_id']);
    }

    /**
     * Process canceled callbacks
     */
    private function processCanceled()
    {
        // Nothing for now
    }

    /**
     * Process failed callbacks
     */
    private function processFailed()
    {
        var_dump ('failed');
    }

}