<?php namespace Bedard\Shop\Models;

use Model;

/**
 * Inventory Model
 */
class Inventory extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string  The database table used by the model.
     */
    public $table = 'bedard_shop_inventories';

    /**
     * @var array   Properties that may be mass-assigned
     */
    public $fillable = ['name', 'product_id'];
    
    /**
     * @var array   Relations
     */
    public $belongsTo = [
        'product' => ['Bedard\Shop\Models\Product', 'table' => 'bedard_shop_products']
    ];

    /**
     * Validation
     */
    public $rules = [
        'quantity'  => 'integer|min:0',
        'modifier'  => 'regex:/^(\-{0,1})[0-9]+(\.[0-9]{0,2})?$/',
        'position'  => 'integer|min:0'
    ];

    public $customMessages = [
        'quantity.integer'  => 'Quantities must be whole numbers.',
        'quantity.min'      => 'Quantities cannot be negative.',
        'modifier.regex'    => 'Price modifier does not appear to be a valid monetary value.'
    ];

    /**
     * Query Scopes
     */
    public function scopeIsActive($query)
    {
        // Selects inventories that are active
        $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        // Selects inventories that are active and in stock
        $query->isActive()
              ->where('quantity', '>', 0);
    }
}