<?php namespace Bedard\Shop\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Bedard\Shop\Models\PaySettings;

/**
 * Codes Back-end Controller
 */
class Codes extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    /**
     * Codes Constructor
     */
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Bedard.Shop', 'shop', 'codes');

        $this->addCss('/plugins/bedard/shop/assets/css/backend.css');
    }

    /**
     * Codes Index
     */
    public function index()
    {
        $this->vars['currency'] = PaySettings::get('currency');
        $this->asExtension('ListController')->index();
    }

    /**
     * Extend the list query to eager load carts
     */
    public function listExtendQuery($query, $definition = null)
    {
        //$query->with('carts');
    }
}