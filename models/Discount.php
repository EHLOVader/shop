<?php namespace Bedard\Shop\Models;

use Model;
use Flash;
use ValidationException;

/**
 * Discount Model
 */
class Discount extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'bedard_shop_discounts';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Polymorphic Relationships
     */
    public $morphedByMany = [
        'products' => ['Bedard\Shop\Models\Product', 'table' => 'bedard_shop_discountables',
            'name' => 'discountable', 'foreignKey' => 'discountable_id', 'order' => 'name'],
        'categories' => ['Bedard\Shop\Models\Category', 'table' => 'bedard_shop_discountables',
            'name' => 'discountable', 'foreignKey' => 'discountable_id', 'scope' => 'nonPseudoDefaultOrder']
    ];

    /**
     * Validation
     */
    public $rules = [
        'name'          => 'required',
        'start_date'    => 'before:end_date',
        'amount'        => 'required|numeric|min:0',
        'is_percentage' => 'required|boolean'
    ];

    public $customMessages = [
        
    ];

    /**
     * Apply some extra validation
     */
    public function beforeValidate()
    {
        // Only check the start / end dates when both are provided
        if (!$this->start_date || !$this->end_date)
            unset($this->rules['start_date']);

        // If this is a percentage discount, add integer and max validation
        if ($this->is_percentage) {
            $this->rules['amount'] .= '|integer|max:100';
        }

        // Make sure atleast one category or product was selected
        if (count($this->categories) == 0 && count($this->products) == 0) {
            Flash::error('Please select categories or products for this discount to apply to.');
            throw new ValidationException('Please select categories or products for this discount to apply to.');
        }

        // Allow discounts to apply to either categories or products, but not both.
        if (count($this->products) > 0 && count($this->categories) > 0) {
            Flash::error('A discount may apply to categories or products, but not both.');
            throw new ValidationException('A discount may apply to categories or products, but not both.');
        }

        // Lastly, check for collisions
        $discountableIds = [];
        $discountableType = count($this->products) > 1
            ? 'Bedard\Shop\Models\Product'
            : 'Bedard\Shop\Models\Category';

    }

    /**
     * Query Scopes
     */
    public function scopeIsActive($query)
    {
        $now = date('Y-m-d H:i:s');
        return $query
            ->where(function($query) use ($now) {
                $query->whereNull('start_date')
                      ->orWhere('start_date', '<=', $now);
            })
            ->where(function($query) use ($now) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $now);
            });
    }


    /**
     * Percentages use integers, exact amounts use decimal
     */
    public function setAmountAttribute($amount)
    {
        $this->attributes['amount'] = $this->is_percentage
            ? $this->attributes['amount'] = floor($amount)
            : round($this->attributes['amount'], 2);
    }
    public function getAmountAttribute()
    {
        if (!isset($this->attributes['amount']))
            return;
        
        return $this->is_percentage
            ? floor($this->attributes['amount'])
            : $this->attributes['amount'];
    }
}