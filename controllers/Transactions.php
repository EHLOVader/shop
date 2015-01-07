<?php namespace Bedard\Shop\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Bedard\Shop\Models\PaySettings;
use Bedard\Shop\Models\Transaction;
use Flash;

/**
 * Transactions Back-end Controller
 */
class Transactions extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    /**
     * Transactions Constructor
     */
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Bedard.Shop', 'shop', 'transactions');;

        $this->addCss('/plugins/bedard/shop/assets/css/backend.css');
        $this->addCss('/plugins/bedard/shop/assets/css/transaction.css');
        $this->vars['currency'] = PaySettings::get('currency_symbol');
    }

    /**
     * Transactions Index
     */
    public function index()
    {
        $this->asExtension('ListController')->index();
    }

    /**
     * Extend the list query to eager load customers, cart items, and coupons
     */
    public function listExtendQuery($query, $definition = null)
    {
        $query->with('customer')
            ->with('cart.items')
            ->with('cart.coupon')
            ->isComplete();
    }

    /**
     * Transaction Details
     * @param   integer $transaction_id
     */
    public function details( $transaction_id = 0 )
    {
        $this->pageTitle = 'Order Details';

        $this->vars['transaction'] = Transaction::with('customer')
            ->with('cart.items')
            ->find($transaction_id);

        if (!$this->vars['transaction'])
            $this->fatalError = 'A transaction with an ID of '.$transaction_id.' could not be found.';
    }

    /**
     * Mark selected records as shipped
     */
    public function index_onMarkAsShipped()
    {
        $successful = TRUE;
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {
            foreach ($checkedIds as $recordId) {
                if (!$record = Transaction::find($recordId)) continue;
                if (!$record->touchShipped()) $successful = FALSE;
            }
        }
        if ($successful) Flash::success('Successfully marked orders as shipped.');
        else Flash::error('An unknown error has occured.');
        return $this->listRefresh();
    }
}