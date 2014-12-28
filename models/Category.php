<?php namespace Bedard\Shop\Models;

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
        'products' => ['Bedard\Shop\Models\Product', 'table' => 'bedard_shop_products_categories', 'scope' => 'isActiveAndVisible']
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
        return $query->whereNull('pseudo');
    }
    public function scopeDefaultOrder($query)
    {
        $query->orderBy('is_active', 'desc')
              ->orderBy('is_visible', 'desc')
              ->orderBy('position', 'asc');
    }
    public function scopeNonPseudoDefaultOrder($query)
    {
        $query->nonPseudo()->defaultOrder();
    }

    /**
     * Returns the number of products in the category
     * @return  integer
     */
    public function getProductCountAttribute()
    {
        return $this->pseudo != 'sale'
            ? count($this->products)
            : Product::isDiscounted()->count();
    }

    /**
     * Returns the category's product arrangement
     * @return  Collection  Bedard\Shop\Models\Product
     */
    public function getArrangedProducts($page = 0)
    {
        // Load all active and visible products
        $products = Product::isActiveAndVisible();

        // Select discounted products
        if ($this->pseudo == 'sale')
            $products->isDiscounted();

        // Select products by category ID
        else
            $products->inCategory($this->id);

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
            $limit = $this->arrangement_columns * $this->arrangement_rows;
            $offset = $limit * ($page - 1);
            $products->take($limit)->skip($offset);
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

}