<?php namespace Bedard\Shop\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Bedard\Shop\FormWidgets\Inventories as InventoriesWidget;
use Bedard\Shop\Models\Product;

/**
 * Products Back-end Controller
 */
class Products extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $bodyClass = 'compact-container';

    /**
     * Products Controller
     */
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Bedard.Shop', 'shop', 'products');

        $this->addCss('/plugins/bedard/shop/assets/css/backend.css');
    }

    public function index()
    {
        $this->asExtension('ListController')->index();
    }

    /**
     * Extend the list query to eager load categories and product
     */
    public function listExtendQuery($query, $definition = null)
    {
        $query->with('categories');//with('product_discount')->with('categories.discount');
    }

    /**
     * Inventory management
     * @param integer $productId
     */
    public function inventory($productId)
    {
        $this->pageTitle = 'Manage Inventory';

        // Load the product, and fire up the inventories widget
        if ($product = Product::with('inventories')->find($productId)) {

            $this->vars['product'] = [
                'id'    => $product->id,
                'name'  => $product->name
            ];

            $inventoriesWidget = new InventoriesWidget($this);
            $inventoriesWidget->alias = 'inventoriesWidget';
            $inventoriesWidget->bindToController();
            $inventoriesWidget->setProduct($product);
        }

        else {
            $this->fatalError = 'There is no product with an ID of '.intval($productId).'.';
        }
    }
}