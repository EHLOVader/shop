<?php namespace Bedard\Shop\Components;

use Bedard\Shop\Models\Category as CategoryModel;
use Cms\Classes\ComponentBase;
use Request;

class Category extends ComponentBase
{
    /**
     * Determines if the category was found or not
     * @var boolean
     */
    public $exists;

    /**
     * Determines if the category is empty or not
     * @var boolean
     */
    public $isEmpty;

    /**
     * These variables exist to make the Twig markup a little cleaner. Rather
     * then declaring a public category variable, we can call "Component.name"
     * instead of "Component.category.name"
     */
    public $slug;           // string
    public $name;           // string
    public $description;    // string
    public $rows;           // integer
    public $columns;        // integer
    public $pseudo;         // string
    public $isVisible;      // boolean
    public $pagination;     // array [ current, last, previous, next ]

    /**
     * @var Collection      Bedard\Shop\Models\Product
     */
    public $products;

    /**
     * Component Details
     * @return  array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'Category',
            'description' => 'Provides a page of products for a given category'
        ];
    }

    /**
     * Component Properties
     * @return  array
     */
    public function defineProperties()
    {
        return [
            'slug' => [
                'title'             => 'Slug',
                'description'       => 'The category being viewed.',
                'type'              => 'string',
                'default'           => '{{ :slug }}'
            ],
            'default' => [
                'title'             => 'Default',
                'description'       => 'Default category to use if no category slug is provided.',
                'type'              => 'dropdown',
                'showExternalParam' => FALSE
            ],
            'page' => [
                'title'             => 'Page',
                'description'       => 'The page being viewed.',
                'type'              => 'string',
                'default'           => '{{ :page }}'
            ]
        ];
    }

    /**
     * Load category options
     * @return  array
     */
    public function getDefaultOptions()
    {
        $categories = CategoryModel::isActive()->orderBy('name', 'asc')->get();
        $options = [];
        foreach ($categories as $category) {
            $options[$category->slug] = $category->name;
        }
        return $options;
    }

    /**
     * Category
     */
    public function onRun()
    {
        // Load the current slug
        $this->slug = $this->property('slug')
            ? $this->property('slug')
            : $this->property('default');

        // Load the category
        $category = CategoryModel::where('slug', $this->slug)
            ->with('products')
            ->first();

        // Stop here if no category was found
        $this->exists = (bool) $category;
        if (!$this->exists) return;

        // Load the category variables
        $this->name         = $category->name;
        $this->description  = $category->description;
        $this->rows         = $category->arrangement_rows;
        $this->columns      = $category->arrangement_columns;
        $this->pseudo       = (bool) $category->pseudo;
        $this->isVisible    = (bool) $category->isVisible;

        // Calculate the pagination
        $this->pagination = $this->calculatePagination($category);

        // Lastly, query the products
        $this->products = $category->getArrangedProducts($this->pagination['current']);

        // Set the isEmpty flag
        $this->isEmpty = count($this->products) == 0;
    }

    /**
     * Calculates the pagination for the category
     * @return  array
     */
    private function calculatePagination(CategoryModel $category)
    {
        // Return all products when rows is set to zero
        if (!$category->arrangement_rows) {
            return [
                'current' => 0,
                'last' => 0,
                'previous' => FALSE,
                'next' => FALSE
            ];
        }

        // Calculate the last page
        $lastPage = ceil(count($category->products) / ($category->ProductsPerPage));

        // Load the current page, using 1 if none was provided
        $currentPage = intval($this->property('page')) >= 1 ? intval($this->property('page')) : 1;

        // Set current page to last page if it's too high
        if ($currentPage > $lastPage) $currentPage = $lastPage;

        // Load previous and next page
        $previousPage = $currentPage > 1 ? $currentPage - 1 : FALSE;
        $nextPage = $currentPage < $lastPage ? $currentPage + 1 : FALSE;

        return [
            'current'   => $currentPage,
            'last'      => $lastPage,
            'previous'  => $previousPage,
            'next'      => $nextPage
        ];
    }

}