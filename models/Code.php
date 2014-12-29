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

    /**
     * Add the dynamic validation rules
     */
    public function beforeValidate()
    {
        // Only check the start / end dates when both are provided
        if ($this->start_date && $this->end_date)
            $this->rules['end_date'] .= '|after:start_date';

        // If this is a percentage discount, add integer and max validation
        if ($this->is_percentage)
            $this->rules['amount'] .= '|integer|max:100';
    }

    /**
     * Status Attributes
     */
    public function getIsUpcomingAttribute()
    {
        // Returns true for upcoming codes
        return $this->start_date > date('Y-m-d H:i:s');
    }
    
    public function getIsRunningAttribute()
    {
        // Returns true for running codes
        $now = date('Y-m-d H:i:s');
        return 
            (!$this->start_date || $this->start_date <= $now) && (!$this->end_date || $this->end_date >= $now) &&
            ($this->limit == 0 || $this->uses < $this->limit);
    }

    public function getIsCompleteAttribute()
    {
        // Returns true for completed codes
        return 
            ($this->end_date && $this->end_date < date('Y-m-d H:i:s')) ||
            ($this->limit > 0 && $this->uses >= $this->limit);
    }

    /**
     * Floor percentage discounts, and round exact amount discounts
     * @param   string  $amount
     */
    public function setAmountAttribute($amount)
    {
        if (!isset($this->attributes['is_percentage']))
            $this->attributes['amount'] = $amount;

        else
            $this->attributes['amount'] = $this->attributes['is_percentage']
                ? $this->attributes['amount'] = floor($amount)
                : round($this->attributes['amount'], 2);
    }

    /**
     * Floor percentage discounts
     * @return  string (numeric)
     */
    public function getAmountAttribute()
    {
        if (!isset($this->attributes['amount']))
            return;

        return $this->is_percentage
            ? floor($this->attributes['amount'])
            : $this->attributes['amount'];
    }

    /**
     * Returns the number of times the promo code has been used
     * @return  integer
     */
    public function getUsesAttribute()
    {
        return 0;
    }

}