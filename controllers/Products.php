<?php namespace Bedard\Shop\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Bedard\Shop\Models\PaySettings;
use Bedard\Shop\Models\Product;
use Bedard\Shop\Widgets\Inventories as InventoriesWidget;
use Flash;

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
        $this->addCss('/plugins/bedard/shop/assets/css/tooltip.css');
    }

    /**
     * Products Index
     */
    public function index()
    {
        // Scoreboard queries
        $this->vars['products']['total'] = Product::all()->count();
        $this->vars['products']['isActive'] = Product::isActive()->count();
        $this->vars['products']['isInactive'] = $this->vars['products']['total'] - $this->vars['products']['isActive'];
        $this->vars['products']['inStock'] = Product::inStock()->count();
        $this->vars['products']['outOfStock'] = $this->vars['products']['total'] - $this->vars['products']['inStock'];
        $this->vars['products']['isDiscounted'] = Product::isDiscounted()->isActive()->count();
        $this->vars['products']['isFullPrice'] = $this->vars['products']['isActive'] - $this->vars['products']['isDiscounted'];

        // Load currency
        $this->vars['currency'] = PaySettings::get('currency');

        // Extend list controller
        $this->asExtension('ListController')->index();
    }

    /**
     * Extend the list query to eager load categories and discounts
     */
    public function listExtendQuery($query, $definition = null)
    {
        $query->with('categories.discounts')->with('discounts');
    }

    /**
     * Inventory management
     * @param   integer $productId
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

    /**
     * Delete list rows
     */
    public function index_onDelete()
    {
        $successful = true;
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {
            foreach ($checkedIds as $recordId) {
                if (!$record = Product::find($recordId)) continue;
                if (!$record->delete()) $successful = FALSE;
            }
        }
        if ($successful) Flash::success('Products successfully deleted.');
        return $this->listRefresh();
    }
}