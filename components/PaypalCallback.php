<?php namespace Bedard\Shop\Components;

use Cms\Classes\ComponentBase;

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Exception\PPConnectionException;

use Cookie;
use Input;
use Session;

class PaypalCallback extends ComponentBase
{

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

        var_dump ($payerId);
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