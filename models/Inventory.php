<?php namespace Bedard\Shop\Models;

use Model;

/**
 * Inventory Model
 */
class Inventory extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'bedard_shop_inventories';

    /**
     * @var array Relations
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
        'position'  => 'integer|min:0',
        'is_active' => 'boolean'
    ];

    public $customMessages = [
        'quantity.integer'  => 'Inventory quantities must be integers.',
        'quantity.min'      => 'Inventory quantities cannot be negative.',
        'modifier.regex'    => 'Inventory price modifier does not appear to be a valid monetary value.'
    ];

    /**
     * Query Scopes
     */
    public function scopeIsActive($query)
    {
        $query->where('is_active', true);
    }
}