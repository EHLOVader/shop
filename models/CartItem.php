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
        'cart' => ['Bedard\Shop\Models\Cart', 'table' => 'bedard_shop_carts'],
        'inventory' => ['Bedard\Shop\Models\Inventory', 'table' => 'bedard_shop_inventories']
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

    /**
     * Returns the full price of the item
     * @return  float
     */
    public function getFullPriceAttribute()
    {
        return !is_null($this->backup_full_price)
            ? $this->backup_full_price
            : $this->inventory->fullPrice;
    }

    /**
     * Returns the name of the inventory, or false if there is none
     * @return  string / false
     */
    public function getInventoryNameAttribute()
    {
        return !empty($this->inventory->name)
            ? $this->inventory->name
            : FALSE;
    }

    /**
     * Prevents invalid quantity values
     * @param   integer
     */
    public function setQuantityAttribute($quantity)
    {
        if ($quantity < 0)
            $quantity = 0;
        if ($quantity > $this->inventory->quantity)
            $quantity = $this->inventory->quantity;
        $this->attributes['quantity'] = $quantity;
    }

    /**
     * Returns true if the product is discounted
     * @return  boolean
     */
    public function getIsDiscountedAttribute()
    {
        return $this->inventory->product->isDiscounted;
    }

    /**
     * Returns the price of the item
     * @return  float
     */
    public function getPriceAttribute()
    {
        return !is_null($this->backup_price)
            ? $this->backup_price
            : $this->inventory->price;
    }

    /**
     * Returns the product name
     * @return  string
     */
    public function getProductNameAttribute()
    {
        return $this->inventory->product->name;
    }

    /**
     * Returns the available inventory stock
     * @return  integer
     */
    public function getStockAttribute()
    {
        return $this->inventory->quantity;
    }


}