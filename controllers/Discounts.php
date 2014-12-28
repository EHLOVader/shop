<?php namespace Bedard\Shop\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Bedard\Shop\Models\Discount;
use Flash;

/**
 * Discounts Back-end Controller
 */
class Discounts extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];
    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    /**
     * Discounts
     */
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Bedard.Shop', 'shop', 'discounts');

        $this->addCss('/plugins/bedard/shop/assets/css/backend.css');
        $this->addCss('/plugins/bedard/shop/assets/css/tooltip.css');

        $this->prepareVars();
    }

    /**
     * Prepare controller variables
     */
    public function prepareVars()
    {
        $this->vars['currency'] = 'usd';
    }

    /**
     * Discounts Index
     */
    public function index()
    {
        $this->asExtension('ListController')->index();
    }

    /**
     * Extend the list query to eager load categories and products
     */
    public function listExtendQuery($query, $definition = null)
    {
        $query->with('categories')->with('products');
    }

    /**
     * Delete list rows
     */
    public function index_onDelete()
    {
        $successful = true;
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {
            foreach ($checkedIds as $recordId) {
                if (!$record = Discount::find($recordId)) continue;
                if (!$record->delete()) $successful = FALSE;
            }
        }
        if ($successful) Flash::success('Successfully deleted discounts.');
        return $this->listRefresh();
    }

}