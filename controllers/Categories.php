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

    /**
     * Categories Controller
     */
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Bedard.Shop', 'shop', 'categories');
        $this->addCss('/plugins/bedard/shop/assets/css/backend.css');
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
        $query->orderBy('is_visible', 'desc')->orderBy('position', 'asc');
    }

    /**
     * Manage Category Positions
     */
    public function position()
    {
        // Add html5sortable
        $this->addJs('/plugins/bedard/shop/assets/js/html5sortable.min.js');

        $this->pageTitle = "Manage Category Order";
        $this->vars['categories'] = Category::defaultOrder()->get();
    }

    /**
     * Update category positions, and redirect back to the index
     */
    public function position_onUpdatePosition()
    {
        $position = post('Category')['position'];

        $i = 0;
        foreach ($position as $categoryId) {
            $category = Category::find($categoryId);
            if (!$category) {
                Flash::error('An unknown error has occured.');
                return Redirect::refresh();
            }
            $category->position = $i;
            $category->save();
            $i++;
        }

        Flash::success('Category orders have been successfully updated.');
        return Redirect::to(Backend::url('bedard/shop/categories'));
    }
}