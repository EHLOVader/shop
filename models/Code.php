<?php namespace Bedard\Shop\Models;

use Model;

/**
 * Code Model
 */
class Code extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'bedard_shop_codes';

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
    public $hasMany = [
        // 'carts' => ['Bedard\Shop\Models\Cart', 'table' => 'bedard_shop_carts']
    ];

    /**
     * Validation
     */
    public $rules = [
        'code'              => 'required|regex:/^[a-zA-Z0-9\-\_\ ]+$/|unique:bedard_shop_codes',
        'message'           => 'max:255',
        'start_date'        => 'date',
        'end_date'          => 'date',
        'amount'            => 'numeric|min:0',
        'limit'             => 'integer|min:0',
        'cart_value'        => 'numeric|min:0',
        'is_percentage'     => 'required|boolean',
        'is_freeshipping'   => 'required|boolean'
    ];
    public $customMessages = [
        'code.regex' => 'Codes may only contain alpha-numeric characters, spaces, hyphens, and underscores.'
    ];
}