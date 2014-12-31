<?php namespace Bedard\Shop\Components;

use Bedard\Shop\Models\Category;
use Cms\Classes\ComponentBase;

class Categories extends ComponentBase
{

    /**
     * @var Collection  Bedard\Shop\Models\Category
     */
    public $categories;

    /**
     * @var integer     The number of visible categories
     */

    /**
     * Categories List
     * @return  array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'Categories List',
            'description' => 'Provides a list of visible categories'
        ];
    }

    /**
     * Query the visible categories and put them in order
     */
    public function onRun()
    {
        $this->categories = Category::isVisible()->inOrder()->get();
        $this->categoryCount = count($this->categories);
    }
    
}