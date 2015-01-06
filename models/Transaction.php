<?php namespace Bedard\Shop\Models;

use Model;

/**
 * Transaction Model
 */
class Transaction extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'bedard_shop_transactions';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['service', 'payment_code', 'payment_id', 'hash'];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'customer' => ['Bedard\Shop\Models\Customer', 'table' => 'bedard_shop_customers']
    ];
    public $hasOne = [
        'cart' => ['Bedard\Shop\Models\Cart', 'table' => 'bedard_shop_carts']
    ];

    /**
     * Jsonable shipping address
     */
    public $jsonable = ['shipping_address'];

    /**
     * Query Scopes
     */
    public function scopeIsComplete($query)
    {
        // Returns only transactions that have been completed
        $query->where('is_complete', TRUE);
    }

    /**
     * Returns the customer's email address
     * @return  string
     */
    public function getCustomerEmailAttribute()
    {
        return $this->customer->email;
    }

    /**
     * Returns the customer's full name
     * @return  string
     */
    public function getCustomerNameAttribute()
    {
        return $this->customer->first_name.' '.$this->customer->last_name;
    }
}