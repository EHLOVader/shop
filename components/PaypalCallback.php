<?php namespace Bedard\Shop\Components;

use Bedard\Shop\Models\Customer;
use Bedard\Shop\Models\Transaction;
use Bedard\Shop\Classes\PaymentException;
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
     * Status variable for the order being successfully completed
     * @var boolean
     */
    public $completed = FALSE;

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
        // Grab our transaction details
        $payerId = Input::get('PayerID');
        $transaction = Transaction::where('payment_id', Input::get('paymentId'))
            ->where('hash', Session::get('bedard_shop_paypal_hash'))
            ->where('is_complete', FALSE)
            ->first();
        if (!$payerId || !$transaction)
            return $this->processFailed('Invalid response from PayPal.');

        // Load the cart being completed, along with it's relationships
        $this->loadCart(TRUE);
        if (!$this->cart)
            return $this->processFailed('Cart not found.');

        // Set up the API, and execute the payment
        try {
            $paypal = new Paypal;
            $paypal->executePayment($payerId, $transaction['payment_id']);
        }
        catch (PaymentException $e) {
            return $this->processFailed('Failed to execute payment with PayPal.');
        }
        
        // First or Create the customer
        $customer = Customer::firstOrCreate([
            'first_name' => $paypal->response->payer->payer_info->first_name,
            'last_name' => $paypal->response->payer->payer_info->last_name,
            'email' => $paypal->response->payer->payer_info->email
        ]);

        // Complete the shopping cart
        $this->cart->complete($transaction);
        $this->completed = TRUE;
        // Unset the cart component?
    }

    /**
     * Process canceled callbacks
     */
    private function processCanceled()
    {
        // Re-activate the cart
    }

    /**
     * Process failed callbacks
     */
    private function processFailed($reason)
    {
        // Log the failure
        var_dump ('failed - '.$reason);
    }

}