<?php namespace Bedard\Shop\Models;

use Model;

/**
 * Customer Model
 */
class Customer extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'bedard_shop_customers';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['first_name', 'last_name', 'email'];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [
        'transactions' => ['Bedard\Shop\Models\Transaction', 'table' => 'bedard_shop_transactions', 'scope' => 'isComplete']
    ];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

}