<?php namespace Bedard\Shop\Models;

use Bedard\Shop\Models\Discount;
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
     * @var Bedard\Shop\Models\Discount
     */
    private $discountCache;

    /**
     * @var boolean
     */
    private $discountCalculated = FALSE;

    /**
     * @var array Relations
     */
    public $hasMany = [
        'inventories' => ['Bedard\Shop\Models\Inventory', 'table' => 'bedard_shop_inventories', 'order' => 'position asc']
    ];
    public $belongsToMany = [
        'categories' => ['Bedard\Shop\Models\Category', 'table' => 'bedard_shop_products_categories', 'scope' => 'nonPseudo']
    ];
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
        // Selects active products
        $query->where('is_active', TRUE);
    }

    public function scopeIsVisible($query)
    {
        // Selects visible products
        $query->where('is_visible', TRUE);
    }

    public function scopeIsActiveAndVisible($query)
    {
        // Selects active and visible products
        $query->isActive()->isVisible();
    }

    public function scopeInCategory($query, $categoryId)
    {
        // Selects products by category ID
        $query->whereHas('categories', function($categories) use ($categoryId) {
            $categories->where('id', $categoryId);
        });
    }

    public function scopeIsDiscounted($query)
    {
        // Selects products currently discounted
        $query->whereHas('discounts', function($discounts) {
            $discounts->isActive();
        })
        ->orWhereHas('categories', function($categories) {
            $categories->whereHas('discounts', function($discounts) {
                $discounts->isActive();
            });
        });
    }

    public function scopeInStock($query)
    {
        // Selects in stock products
        $query->where('stock', '>', 0);
    }
    public function scopeOutOfStock($query)
    {
        // Selects out of stock products
        $query->where('stock', 0);
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

    /**
     * Determine which discount should be applied to the product
     * @return  Bedard\Shop\Models\Discount
     */
    public function getDiscountAttribute()
    {
        // Return the cached result if we've already done this
        if ($this->discountCalculated)
            return $this->discountCache;

        // First look for a product discount, they take priority over everything
        if (count($this->discounts) > 0) {
            foreach ($this->discounts as $discount) {
                $this->discountCache = $discount;
                break;
            }
        }

        // Otherwise calculate which category discount provides the best value
        else {
            $bestPrice = $this->full_price;
            $bestDiscount = FALSE;
            foreach ($this->categories as $category) {
                if ($discount = $category->discount) {
                    $discountPrice = $this->calculatePrice($discount);
                    if ($discountPrice < $bestPrice) {
                        $bestPrice = $discountPrice;
                        $bestDiscount = $discount;
                    }
                }
            }
            $this->discountCache = $bestDiscount;
        }

        $this->discountCalculated = TRUE;
        return $this->discountCache;
    }

    /**
     * Returns the discounted price of the product
     * @param   Discount $discount
     * @return  float
     */
    private function calculatePrice(Discount $discount)
    {
        $price = $discount->is_percentage
            ? $this->full_price * ((100 - $discount->amount) / 100)
            : $this->full_price - $discount->amount;

        return $price > 0
            ? $price
            : 0;
    }

    /**
     * Returns the calculated price of the product
     * @return  string (numeric)
     */
    public function getPriceAttribute()
    {
        $price = $this->discount
            ? $this->calculatePrice($this->discount)
            : $this->full_price;

        return number_format($price, 2);
    }

    /**
     * Returns a boolean if the product is discounted or not
     * @return  boolean
     */
    public function getIsDiscountedAttribute()
    {
        return $this->discount != FALSE;
    }
}