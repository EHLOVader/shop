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
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [
        'customer' => ['Bedard\Shop\Models\Customer', 'table' => 'bedard_shop_customers']
    ];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    /**
     * Query Scopes
     */
    public function scopeIsComplete($query)
    {
        // Returns only transactions that have been completed
        $query->where('is_complete', TRUE);
    }
}