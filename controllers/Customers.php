<?php namespace Bedard\Shop\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Bedard\Shop\Models\Customer;
use Bedard\Shop\Models\PaySettings;
use Carbon\Carbon;

/**
 * Customers Back-end Controller
 */
class Customers extends Controller
{
    public $implement = [
        'Backend.Behaviors.ListController'
    ];

    public $listConfig = 'config_list.yaml';

    /**
     * Products Constructor
     */
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Bedard.Shop', 'shop', 'customers');

        $this->addCss('/plugins/bedard/shop/assets/css/backend.css');

        // Load currency
        $this->vars['currency'] = PaySettings::get('currency');
    }

    /**
     * Customers Index
     */
    public function index()
    {
        // Count our repeat / new customers
        $this->vars['repeatCustomers']  = Customer::has('transactions', '>', 1)->count();
        $this->vars['newCustomers'] = Customer::has('transactions', '<=', 1)->count();
        $this->vars['totalCustomers'] = $this->vars['repeatCustomers'] + $this->vars['newCustomers'];

        // Determine how many new customers we've had this month and last
        $this->vars['newCustomersCurrent'] = Customer::
            where('created_at', '>=', Carbon::now()->startOfMonth())
            ->count();

        $this->vars['newCustomersPrevious'] = Customer::
            where('created_at', '>=', Carbon::now()->subMonth()->startOfMonth())
            ->where('created_at', '<', Carbon::now()->startOfMonth())
            ->count();

        $this->vars['newCustomersClass'] = $this->vars['newCustomersCurrent'] >= $this->vars['newCustomersPrevious']
            ? 'positive'
            : 'negative';

        // Determine the repeat customers we've had this month and last
        $this->vars['customersCurrent'] = Customer::has('transactions', '>', 1)
            ->where('updated_at', '>=', Carbon::now()->startOfMonth())
            ->count();

        $this->vars['customersPrevious'] = Customer::
            where('updated_at', '>=', Carbon::now()->subMonth()->startOfMonth())
            ->where('updated_at', '<', Carbon::now()->startOfMonth())
            ->count();

        $this->vars['customersClass'] = $this->vars['customersCurrent'] >= $this->vars['customersPrevious']
            ? 'positive'
            : 'negative';

        //$this->vars['currency'] = Settings::get('currency');
        $this->asExtension('ListController')->index();
    }

    /**
     * Extend the list query to eager load transactions
     */
    public function listExtendQuery($query, $definition = null)
    {
        $query->with('transactions');
    }
}