<?php namespace Bedard\Shop\Classes;

use Bedard\Shop\Classes\PaymentException;
use Bedard\Shop\Models\Cart;
use Bedard\Shop\Models\PaySettings;

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
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Exception\PPConnectionException;

class Paypal
{
    /**
     * The current PayPal API
     * @var PayPal\Rest\ApiContext
     */
    public $api;

    /**
     * Shopping cart objects
     * @var PayPal\Api\Amount       $amount
     * @var Bedard\Shop\Models\Cart $cart
     * @var PayPal\Api\Details      $details
     * @var PayPal\Api\ItemList     $itemList
     * @var PayPal\Api\Payer        $payer
     * @var PayPal\Api\Payment      $payment
     * @var PayPal\Api\RedirectUrls $redirectUrls
     * @var PayPal\Api\Transaction  $transaction
     */
    public $amount;
    public $cart;
    public $details;
    public $itemList;
    public $payer;
    public $payment;
    public $redirectUrls;
    public $transaction;

    /**
     * Callback URLs
     * @var array   [ success, canceled, failed ]
     */
    public $callbackUrls;

    /**
     * Payment Response
     */
    public $response;

    /**
     * @var string
     */
    public $currency;

    /**
     * Paypal
     */
    public function __construct()
    {
        $this->setApi();
        $this->currency = strtoupper(PaySettings::get('currency'));
    }

    /**
     * Sets up a new API
     * @return  PayPal\Rest\ApiContext
     */
    public function setApi()
    {
        $this->api = new ApiContext(
            new OAuthTokenCredential(
                PaySettings::get('paypal_client_id'),
                PaySettings::get('paypal_secret')
            )
        );

        $this->api->setConfig([
            'mode'                  => PaySettings::get('paypal_mode'),
            'http.ConnectionTimeOut'=> 30,
            'log.logEnabled'        => FALSE,
            'log.FileName'          => '',
            'log.LogLevel'          => 'FINE',
            'validation.level'      => 'log'
        ]);
    }

    /**
     * Sets up new shopping cart objects
     */
    public function createNewCart(Cart $cart)
    {
        // Cart
        $this->cart = $cart;

        // Build up our paypal shopping cart
        $this->setPayer();
        $this->setDetails();

        $items = $this->addItems();
        $this->setItems($items);
        $this->setAmount();
        $this->setTransaction();
        $this->setPayment();
    }

    /**
     * Sets up callback Urls
     */
    public function setCallbackUrls($callback)
    {
        if (!array_key_exists('success', $callback) ||
            !array_key_exists('canceled', $callback) ||
            !array_key_exists('failed', $callback)) {
            throw new Exception('A success, canceled, and failed callback URL must be provided.');
        }

        $this->callbackUrls = $callback;

        $this->redirectUrls = new RedirectUrls();
        $this->redirectUrls->setReturnUrl($callback['success'])
            ->setCancelUrl($callback['canceled']);

        $this->payment->setRedirectUrls($this->redirectUrls);
    }

    /**
     * Returns the payment ID and url from PayPal
     * @return  array   [ id, url ]
     */
    public function checkout()
    {
        try {
            $this->payment->create($this->api);
        } catch (PPConnectionException $e) {
            // Log the error?
            return ['id' => FALSE, 'url' => $this->callbackUrls['failed']];
        }

        // If everything worked out, return the approval_url
        foreach ($this->payment->getLinks() as $link) {
            if ($link->getRel() == 'approval_url')
                return ['id' => $this->payment->getId(), 'url' => $link->getHref()];
        }
    }

    /**
     * Executes a payment
     * @param   string
     * @param   string
     */
    public function executePayment($payerId, $paymentId)
    {
        $payment = Payment::get($paymentId, $this->api);
        $execution = new PaymentExecution();
        $execution->setPayerId($payerId);

        try {
            $this->response = $payment->execute($execution, $this->api);
        } catch (PPConnectionException $e) {
            throw new PaymentException($e->getMessage());
        }
    }

    /**
     * Sets the Payer object
     */
    private function setPayer()
    {
        $this->payer = new Payer();
        $this->payer->setPayment_method('paypal');
    }

    /**
     * Sets the Details object
     */
    private function setDetails()
    {
        $this->details = new Details();
        $this->details//->setShipping(0)
            ->setTax('0.00')
            ->setSubtotal($this->cart->total);
    }

    /**
     * Sets the PayPal amount object
     */
    private function setAmount()
    {
        $this->amount = new Amount();
        $this->amount->setCurrency($this->currency)
            ->setTotal($this->cart->total)
            ->setDetails($this->details);
    }

    /**
     * Adds cart items to the itemList object
     * @return  Collection  PayPal\Api\Item
     */
    private function addItems()
    {
        $items = [];

        // Add the normal items
        foreach ($this->cart->items as $cartItem) {
            $item = new Item();
            $item->setName($cartItem->productName)
                ->setDescription($cartItem->inventoryName)
                ->setCurrency($this->currency)
                ->setQuantity($cartItem->quantity)
                ->setPrice($cartItem->price);
            $items[] = $item;
        }

        // Add the coupon as an item
        if (!is_null($this->cart->coupon_id) && $this->cart->coupon) {
            $couponSavings = ($this->cart->totalBeforeCoupon - $this->cart->total) * -1;

            $item = new Item();
            $item->setName('Coupon "'.$this->cart->coupon->name.'"')
                ->setCurrency($this->currency)
                ->setQuantity(1)
                ->setPrice($couponSavings);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Sets up the ItemList object
     */
    private function setItems($items)
    {
        $this->itemList = new ItemList();
        $this->itemList->setItems($items);
    }

    /**
     * Sets up the Transaction object
     */
    private function setTransaction()
    {
        $this->transaction = new Transaction();
        $this->transaction->setAmount($this->amount)
            ->setItemList($this->itemList)
            ->setDescription('Checkout');
    }

    /**
     * Sets up the Payment object
     */
    private function setPayment()
    {
        $this->payment = new Payment();
        $this->payment->setIntent('sale')
            ->setPayer($this->payer)
            ->setTransactions([$this->transaction]);
    }
}