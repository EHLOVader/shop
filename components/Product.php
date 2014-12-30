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
     * These variables are here for convenience. Rather than declare a public
     * "product" variable, we can reference the information directly by calling
     * "Product.price" rather than "Product.product.price"
     * @var string
     */
    public $slug;
    public $name;
    public $description;
    public $price;
    public $fullPrice;
    public $ounces;
    public $thumbnail;
    public $thumbnailAlt;
    public $isVisible;
    public $isDiscounted;

    /**
     * The discount being applied                                             
     * @var Bedard\Shop\Models\Discount
     */
    public $discount;

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
            ->isActive()
            ->first();

        // Check if the product was found
        $this->exists = (bool) $product;
        if (!$this->exists) return;

        // Load product variables
        $this->name = $product->name;
        $this->slug = $product->slug;
        $this->description = $product->description;
        $this->price = $product->price;
        $this->fullPrice = $product->fullPrice;
        $this->isDiscounted = $product->isDiscounted;

        // Load the discount
        $this->discount = $product->discount;
    }


}