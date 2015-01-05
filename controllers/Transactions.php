<?php namespace Bedard\Shop\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

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

        BackendMenu::setContext('Bedard.Shop', 'shop', 'transactions');
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
}