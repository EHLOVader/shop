<?php namespace Bedard\Shop\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Bedard\Shop\Models\Coupon;
use Bedard\Shop\Models\PaySettings;
use Flash;

/**
 * Coupons Back-end Controller
 */
class Coupons extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    /**
     * Coupons Constructor
     */
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Bedard.Shop', 'shop', 'coupons');

        $this->addCss('/plugins/bedard/shop/assets/css/backend.css');
    }

    /**
     * Coupons Index
     */
    public function index()
    {
        $this->vars['currency'] = PaySettings::get('currency_symbol');
        $this->asExtension('ListController')->index();
    }

    /**
     * Extend the list query to eager load carts
     */
    public function listExtendQuery($query, $definition = null)
    {
        $query->with('carts');
    }

    /**
     * Delete list rows
     */
    public function index_onDelete()
    {
        $successful = true;
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {
            foreach ($checkedIds as $recordId) {
                if (!$record = Coupon::find($recordId)) continue;
                if (!$record->delete()) $successful = FALSE;
            }
        }
        if ($successful) Flash::success('Successfully deleted coupons.');
        return $this->listRefresh();
    }
}