<?php namespace Bedard\Shop\Models;

use DB;
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
    public $hasMany = [
        'inventories' => ['Bedard\Shop\Models\Inventory', 'table' => 'bedard_shop_inventories', 'order' => 'position asc']
    ];
    public $belongsToMany = [
        'categories' => ['Bedard\Shop\Models\Category', 'table' => 'bedard_shop_products_categories', 'scope' => 'nonPseudo']
    ];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $morphToMany = [
        'discounts' => ['Bedard\Shop\Models\Discount', 'table' => 'bedard_shop_discountables',
            'name' => 'discountable', 'foreignKey' => 'discount_id', 'scope' => 'isActive'
        ],
    ];
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
        // Make sure "all" is attached
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
    public function scopeWithDiscounts($query)
    {
        $query->with('discounts');
    }

    /**
     * Returns a string of a product's "real" categories
     * @return  string
     */
    public function getCategoryStringAttribute()
    {
        $categories = [];
        foreach ($this->categories as $category)
            if (!$category->pseudo) $categories[] = $category->name;
        return implode(', ', $categories);
    }

    /**
     * Runs a query to synchronize the "stock" column
     */
    public function syncInventories()
    {
        $sync = DB::statement('
            UPDATE bedard_shop_products
            SET stock = (
                SELECT sum(quantity)
                FROM bedard_shop_inventories
                where product_id = '.$this->id.'
                and is_active = 1
            )
            WHERE id = '.$this->id
        );

        // $this->syncCategories();
        
        return $sync;
    }

    public static function syncProductInventories($inventories)
    {

    }
}