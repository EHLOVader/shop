<?php namespace Bedard\Shop\Models;

use Model;

/**
 * Product Model
 */
class Product extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'bedard_shop_products';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsToMany = [
        'categories' => ['Bedard\Shop\Models\Category', 'table' => 'bedard_shop_products_categories', 'scope' => 'nonPseudo']
    ];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    /**
     * Attach every product to the "all" pseudo category
     */
    public function afterSave()
    {
        foreach ($this->categories as $category)
            if ($category->pseudo == 'all') return;
        $this->categories()->attach(1);
    }
}