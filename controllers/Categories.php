<?php namespace Bedard\Shop\Controllers;

use Backend;
use BackendMenu;
use Backend\Classes\Controller;
use Bedard\Shop\Models\Category;
use Flash;
use Redirect;

/**
 * Categories Back-end Controller
 */
class Categories extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $bodyClass = 'compact-container';

    /**
     * Categories Controller
     */
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Bedard.Shop', 'shop', 'categories');

        $this->addCss('/plugins/bedard/shop/assets/css/backend.css');
        $this->addCss('/plugins/bedard/shop/assets/css/sortable.css');

        $this->addJs('/plugins/bedard/shop/assets/js/html5sortable.min.js');
        $this->addJs('/plugins/bedard/shop/assets/js/backend.js');

    }

    /**
     * Categories Index
     */
    public function index()
    {
        $this->asExtension('ListController')->index();
    }

    /**
     * Extend the index list query
     */
    public function listExtendQuery($query, $definition = null)
    {
        $query->defaultOrder()
            ->with('discounts')
            ->with('products');
    }

    /**
     * Manage Category Positions
     */
    public function position()
    {
        $this->pageTitle = "Manage Category Order";
        $this->vars['categories'] = Category::defaultOrder()->get();
    }

    /**
     * Update category positions, and redirect back to the index
     */
    public function position_onUpdatePosition()
    {
        $positions = post('Category')['position'];
        foreach ($positions as $position => $categoryId) {
            $category = Category::find($categoryId);
            if (!$category) {
                Flash::error('An unknown error has occured.');
                return Redirect::refresh();
            }
            $category->position = $position;
            $category->save();
        }
        Flash::success('Category orders have been successfully updated.');
        return Redirect::to(Backend::url('bedard/shop/categories'));
    }


    /**
     * Delete list rows
     */
    public function index_onDelete()
    {
        $successful = true;
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {
            foreach ($checkedIds as $recordId) {
                if (!$record = Category::find($recordId)) continue;
                if (!$record->delete()) $successful = FALSE;
            }
        }
        if ($successful) Flash::success('Categories successfully deleted.');
        return $this->listRefresh();
    }
}