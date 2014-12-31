<?php namespace Bedard\Shop\Components;

use Bedard\Shop\Models\Product as ProductModel;
use Cms\Classes\ComponentBase;

class Product extends ComponentBase
{

    /**
     * Determines if the product was found or not
     * @var boolean
     */
    public $exists;

    /**
     * These variables exist to make the Twig markup a little cleaner. Rather
     * then declaring a public product variable, we can call "Component.price"
     * instead of "Component.product.price"
     */
    public $name;           // string
    public $slug;           // string
    public $description;    // string
    public $price;          // float
    public $fullPrice;      // float
    public $ounces;         // integer
    public $isVisible;      // boolean
    public $isDiscounted;   // boolean
    public $inStock;        // boolean

    /**
     * The discount being applied                                             
     * @var Bedard\Shop\Models\Discount
     */
    public $discount;

    /**
     * Product images
     * @var Collection
     */
    public $images;

    /**
     * The active inventories
     * @var Collection  Bedard\Shop\Models\Inventory
     */
    public $inventories;

    /**
     * Returns true if the product has multiple inventories
     * @var boolean
     */
    public $hasMultipleInventories;

    /**
     * Component Details
     * @return  array
     */
    public function componentDetails()
    {
        return [
            'name'        => 'Product',
            'description' => 'A product in the shop'
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
                'description'       => 'The product being viewed.',
                'type'              => 'string',
                'default'           => '{{ :slug }}'
            ]
        ];
    }

    /**
     * Product
     */
    public function onRun()
    {
        // Load the product
        $product = ProductModel::where('slug', $this->property('slug'))
            ->with('discounts')
            ->with('categories.discounts')
            ->with('inventories')
            ->with('images')
            ->isActive()
            ->first();

        // Check if the product was found
        $this->exists = (bool) $product;
        if (!$this->exists) return;

        // Load product variables
        $this->name         = $product->name;
        $this->slug         = $product->slug;
        $this->description  = $product->description;
        $this->price        = $product->price;
        $this->fullPrice    = $product->fullPrice;
        $this->isDiscounted = $product->isDiscounted;
        $this->discount     = $product->discount;
        $this->images       = $product->images;
        $this->inventories  = $product->inventories;
        $this->inStock      = $product->inStock;

        // Check if the product has multiple inventories
        $this->hasMultipleInventories = count($product->inventories) > 1;
    }


}