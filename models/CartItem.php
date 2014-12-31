<?php namespace Bedard\Shop\Models;

use Model;

/**
 * CartItem Model
 */
class CartItem extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'bedard_shop_cart_items';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['cart_id', 'inventory_id'];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'cart' => ['Bedard\Shop\Models\Cart', 'table' => 'bedard_shop_carts']
    ];

    /**
     * Query Scopes
     */
    public function scopeInCart($query)
    {
        // CartItems are never actually deleted, their quantity gets  set to 
        // zero. Therefor, to be in the cart the item must also have a quantity.
        $query->where('quantity', '>', 0);
    }

}