<?php namespace Bedard\Shop\Components;

use Bedard\Shop\Models\Category;
use Cms\Classes\ComponentBase;

class CategoriesList extends ComponentBase
{

    /**
     * @var Collection  Bedard\Shop\Models\Category
     */
    private $categories;

    /**
     * Categories List
     * @return  array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'Category List',
            'description' => 'Provides a list of visible categories'
        ];
    }

    /**
     * Cache and return the categories list
     * @return  Collection  Bedard\Shop\Models\Category
     */
    public function categories()
    {
        if ($this->categories)
            return $this->categories;
        else 
            return $this->categories = Category::isVisible()->inOrder()->get();
    }
    
}