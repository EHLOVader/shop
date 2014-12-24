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
    public $attachOne = [
        'thumbnail' => ['System\Models\File'],
        'thumbnail_alt' => ['System\Models\File']
    ];
    public $attachMany = [
        'images' => ['System\Models\File']
    ];
    
    /**
     * Attach every product to the "all" pseudo category
     */
    public function afterSave()
    {
        foreach ($this->categories as $category)
            if ($category->pseudo == 'all') return;
        $this->categories()->attach(1);
    }

    /**
     * Query Scopes
     */
    public function scopeIsActive($query)
    {
        $query->where('is_active', TRUE);
    }
    public function scopeIsVisible($query)
    {
        $query->where('is_active', TRUE);
    }
}