<?php namespace Bedard\Shop\Components;

use Bedard\Shop\Models\PaySettings;
use Bedard\Shop\Models\Transaction as TransactionModel;
use Cms\Classes\ComponentBase;
use Exception;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Payer;
use PayPal\Api\Details;
use PayPal\Api\Amount;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Transaction;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Exception\PPConnectionException;
use Redirect;
use Session;

class PaypalCheckout extends ComponentBase
{
    use \Bedard\Shop\Traits\CartTrait;
    use \Bedard\Shop\Traits\AjaxResponderTrait;

    /**
     * PayPal Credentials
     * @var string  $paypal_clientId
     * @var string  $paypal_secret
     */
    private $paypal_clientId;
    private $paypal_secret;

    /**
     * Component Details
     * @return  array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'Paypal Checkout',
            'description' => 'Checkout functionality for PayPal'
        ];
    }

    /**
     * Component Properties
     * @return  array
     */
    public function defineProperties()
    {
        return [
            'api_mode' => [
                'title'             => 'API Mode',
                'description'       => 'PayPal API mode',
                'type'              => 'dropdown',
                'options' => [
                    'live'      => 'Live',
                    'sandbox'   => 'Sandbox'
                ],
                'default'               => 'live',
                'showExternalParam' => FALSE
            ],
            'callback_success' => [
                'title'             => 'Success URL',
                'description'       => 'Callback URL for successful checkouts.',
                'validationPattern' => '^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$',
                'validationMessage' => 'Invalid callback URL.',
                'showExternalParam' => FALSE
            ],
            'callback_canceled' => [
                'title'             => 'Canceled URL',
                'description'       => 'Callback URL for canceled checkouts.',
                'validationPattern' => '^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$',
                'validationMessage' => 'Invalid callback URL.',
                'showExternalParam' => FALSE
            ],
            'callback_failed' => [
                'title'             => 'Failed URL',
                'description'       => 'Callback URL for failed PayPal requests.',
                'validationPattern' => '^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$',
                'validationMessage' => 'Invalid callback URL.',
                'showExternalParam' => FALSE
            ]
        ];
    }

    /**
     * Require paypal credentials and a non-empty shopping cart
     */
    public function onInit()
    {
        $this->paypal_clientId = PaySettings::get('paypal_client_id');
        $this->paypal_secret = PaySettings::get('paypal_secret');
        if (empty($this->paypal_clientId) || empty($this->paypal_secret))
            throw new Exception('A client ID and secret is required to use the PayPal checkout component.');
    }

    /**
     * Begin the checkout process, and redirect the user to PayPal
     * @return  Redirect
     */
    public function onPaypalCheckout()
    {
        // Load the cart with it's relationships
        $this->loadCart(TRUE);
        if (!$this->cart)
            return $this->response('Cart not found', FALSE);

        // Validate the cart item quantities
        if ($this->cart->fixQuantities())
            return $this->response('Fixed quantities', FALSE);

        // Set up the PayPal API
        $api = new ApiContext(
            new OAuthTokenCredential(
                PaySettings::get('paypal_client_id'),
                PaySettings::get('paypal_secret')
            )
        );
        $api->setConfig([
            'mode'                  => $this->property('api_mode'),
            'http.ConnectionTimeOut'=> 30,
            'log.logEnabled'        => FALSE,
            'log.FileName'          => '',
            'log.LogLevel'          => 'FINE',
            'validation.level'      => 'log'
        ]);

        // Start building up our conversation with PayPal
        $payer          = new Payer();
        $details        = new Details();
        $amount         = new Amount();
        $itemList       = new ItemList();
        $transaction    = new Transaction();
        $payment        = new Payment();
        $redirectUrls   = new RedirectUrls();

        // Payer
        $payer->setPayment_method('paypal');

        // Details
        $details
            //->setShipping(0)
            ->setTax('0.00')
            ->setSubtotal($this->cart->total);

        // Amount
        $currency = strtoupper(PaySettings::get('currency'));
        $amount->setCurrency($currency)
            ->setTotal($this->cart->total)
            ->setDetails($details);

        // Item List
        $items = [];
        foreach ($this->cart->items as $cartItem) {
            $item = new Item();
            $item->setName($cartItem->productName)
                ->setDescription($cartItem->inventoryName)
                ->setCurrency($currency)
                ->setQuantity($cartItem->quantity)
                ->setPrice($cartItem->price);
            $items[] = $item;
        }


        // Coupon
        if (!is_null($this->cart->coupon_id) && $this->cart->coupon) {
            $couponSavings = ($this->cart->totalBeforeCoupon - $this->cart->total) * -1;
            $item = new Item();
            $item->setName('Coupon "'.$this->cart->coupon->name.'"')
                 ->setCurrency($currency)
                 ->setQuantity(1)
                 ->setPrice($couponSavings);
            $items[] = $item;
        }

        $itemList->setItems($items);

        // Transaction
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription('Checkout');

        // Payment
        $payment->setIntent('sale')
            ->setPayer($payer)
            ->setTransactions([$transaction]);

        // Redirect Urls
        $redirectUrls->setReturnUrl($this->property('callback_success'))
            ->setCancelUrl($this->property('callback_canceled'));

        $payment->setRedirectUrls($redirectUrls);

        // Run the payment
        try {
            $payment->create($api);

            // Generate a hash and store the transaction
            $hash = md5($payment->getId());
            Session::put('bedard_shop_paypal_hash', $hash);
            $receipt = TransactionModel::create([
                'service'   => 'paypal',
                'payment_id'=> $payment->getId(),
                'hash'      => $hash
            ]);

        } catch (PPConnectionException $e) {
            // Log the error?
            return Redirect::to($this->property('callback_error'));
        }

        // Send the user off to PayPal to make the payment
        foreach ($payment->getLinks() as $link) {
            if ($link->getRel() == 'approval_url')
                return Redirect::to($link->getHref());
        }

    }

}