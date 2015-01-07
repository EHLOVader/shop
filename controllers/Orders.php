<?php namespace Bedard\Shop\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Bedard\Shop\Models\PaySettings;
use Bedard\Shop\Models\Order;
use Flash;

/**
 * Orders Back-end Controller
 */
class Orders extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    /**
     * Orders Constructor
     */
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Bedard.Shop', 'shop', 'orders');;

        $this->addCss('/plugins/bedard/shop/assets/css/backend.css');
        $this->addCss('/plugins/bedard/shop/assets/css/order.css');
        $this->vars['currency'] = PaySettings::get('currency_symbol');
    }

    /**
     * Orders Index
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
     * Order Details
     * @param   integer $order_id
     */
    public function details( $order_id = 0 )
    {
        $this->pageTitle = 'Order Details';

        $this->vars['order'] = Order::with('customer')
            ->with('cart.items')
            ->find($order_id);

        if (!$this->vars['order'])
            $this->fatalError = 'A order with an ID of '.$order_id.' could not be found.';
    }

    /**
     * Mark selected records as shipped
     */
    public function index_onMarkAsShipped()
    {
        $successful = TRUE;
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {
            foreach ($checkedIds as $recordId) {
                if (!$record = Order::find($recordId)) continue;
                if (!$record->touchShipped()) $successful = FALSE;
            }
        }
        if ($successful) Flash::success('Successfully marked orders as shipped.');
        else Flash::error('An unknown error has occured.');
        return $this->listRefresh();
    }
}