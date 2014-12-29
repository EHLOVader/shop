<?php namespace Bedard\Shop\Models;

use Bedard\Shop\Models\Settings;
use Bedard\Shop\Models\Product;
use DB;
use Flash;
use Model;

/**
 * Category Model
 */
class Category extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'bedard_shop_categories';

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
    public $belongsToMany = [
        'products' => ['Bedard\Shop\Models\Product', 'table' => 'bedard_shop_products_categories', 'scope' => 'isVisible']
    ];
    public $morphToMany = [
        'discounts' => ['Bedard\Shop\Models\Discount', 'table' => 'bedard_shop_discountables',
                'name' => 'discountable', 'foreignKey' => 'discount_id', 'scope' => 'isActive'
        ],
    ];

    /**
     * @var array   Json encodes product arrangements
     */
    public $jsonable = ['arrangement_order'];

    /*
     * Validation
     */
    public $rules = [
        'name' => 'required',
        'slug' => 'required|between:3,64|unique:bedard_shop_categories|regex:/^[a-z0-9\-]+$/i'
    ];

    /**
     * Prevent pseudo categories from being deleted
     */
    public static function boot()
    {
        parent::boot();
        static::deleting(function ($model) {
            if ($model->pseudo) {
                $model->is_active = 0;
                $model->save();
                Flash::warning('Pseudo categories cannot be deleted, '.$model->name.' has been set to inactive.');
                return FALSE;
            }
        });
    }

    /**
     * Query Scopes
     */
    public function scopeNonPseudo($query)
    {
        // Filters out pseudo categories
        return $query->whereNull('pseudo');
    }

    public function scopeBackendOrder($query)
    {
        // Puts categories in backend order
        $query->orderBy('is_active', 'desc')
              ->orderBy('is_visible', 'desc')
              ->orderBy('position', 'asc');
    }

    public function scopeNonPseudoBackendOrder($query)
    {
        // Filters out pseudos and orders categories
        $query->nonPseudo()->backendOrder();
    }

    public function scopeIsVisible($query)
    {
        // Returns categories visible in the categories list
        $query->where('is_active', TRUE)
              ->where('is_visible', TRUE);
        if (!Settings::get('show_empty_categories')) {
            $query->whereHas('products', function($product) {
                $product->inStock();
            });
            if (Product::isDiscounted()->count() > 0)
                $query->orWhere('pseudo', 'sale');
        }
    }

    public function scopeInOrder($query)
    {
        // Puts the categories in order
        $query->orderBy('position', 'asc');
    }

    /**
     * Returns the number of products in the category
     * @return  integer
     */
    public function getProductCountAttribute()
    {
        return $this->pseudo != 'sale'
            ? count($this->products)
            : Product::isDiscounted()->isVisible()->count();
    }

    /**
     * Returns the category's product arrangement
     * @param   integer     $pageNumber
     * @return  Collection  Bedard\Shop\Models\Product
     */
    public function getArrangedProducts($page = 0)
    {
        // Load all active and visible products
        if ($this->pseudo == 'all')
            $products = Product::isVisible();

        // Select discounts by category
        else {
            $products = $this->pseudo == 'sale'
                ? Product::isDiscounted()
                : Product::inCategory($this->id);

            // Only show active and visible
            $products->isVisible();
        }

        // Standard product arrangements
        if ($this->arrangement_method == 'alpha_asc')
            $products->orderBy('name', 'asc');
        elseif ($this->arrangement_method == 'alpha_desc')
            $products->orderBy('name', 'desc');
        elseif ($this->arrangement_method == 'newest')
            $products->orderBy('created_at', 'desc');
        elseif ($this->arrangement_method == 'oldest')
            $products->orderBy('created_at', 'asc');

        // Custom product arrangement
        elseif ($this->arrangement_method == 'custom' && !empty($this->arrangement_order)) {
            foreach ($this->arrangement_order as $id)
                $products->orderBy(DB::raw("id <> $id"));
        }

        // If a page value was passed in, query only products on that page
        if ($page > 0) {
            $products->onPage($page, $this->productsPerPage);
        }

        return $products->get();
    }

    /**
     * Return the first discount
     * @return  Bedard\Shop\Models\Discount
     */
    public function getDiscountAttribute()
    {
        if (count($this->discounts) > 0) {
            foreach ($this->discounts as $discount)
                return $discount;
        }
    }

    /**
     * Returns the number of products per page
     * @return  integer
     */
    public function getProductsPerPageAttribute()
    {
        return $this->arrangement_rows * $this->arrangement_columns;
    }

}