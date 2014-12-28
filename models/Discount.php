<?php namespace Bedard\Shop\Models;

use DB;
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
            'name' => 'discountable', 'foreignKey' => 'discountable_id', 'order' => 'name'
        ],
        'categories' => ['Bedard\Shop\Models\Category', 'table' => 'bedard_shop_discountables',
            'name' => 'discountable', 'foreignKey' => 'discountable_id', 'scope' => 'nonPseudoDefaultOrder'
        ]
    ];

    /**
     * Validation
     */
    public $rules = [
        'name'          => 'required',
        'start_date'    => 'date',
        'end_date'      => 'date',
        'amount'        => 'required|numeric|min:0',
        'is_percentage' => 'required|boolean'
    ];

    /**
     * Apply some extra validation
     */
    public function beforeValidate()
    {
        // Adjust our validation rules
        $this->addDynamicValidationRules();

        // Make sure that the category applies to categories or products
        $scope = $this->validateDiscountScope();

        // Lastly, check for collisions
        $this->validateDiscountCollisions($scope);
    }

    /**
     * Dynamic validation rules that are changed based on model values
     */
    private function addDynamicValidationRules()
    {
        // Only check the start / end dates when both are provided
        if ($this->start_date && $this->end_date)
            $this->rules['start_date'] .= '|before:end_date';

        // If this is a percentage discount, add integer and max validation
        if ($this->is_percentage)
            $this->rules['amount'] .= '|integer|max:100';
    }

    /**
     * Makes sure the discount has a either categories or products
     * @return  string  The discount scope
     */
    private function validateDiscountScope()
    {
        // Count the categories and products that are selected
        $checkedCategories = is_array(post('Discount')['categories'])
            ? count(post('Discount')['categories'])
            : 0;
        $checkedProducts = is_array(post('Discount')['products'])
            ? count(post('Discount')['products'])
            : 0;

        // Require a discount or a category
        if (!$checkedCategories && !$checkedProducts) {
            Flash::error('Please select categories or products for this discount to apply to.');
            throw new ValidationException('Please select categories or products for this discount to apply to.');
        }

        // Allow discounts to apply to either categories or products, but not both.
        if ($checkedCategories && $checkedProducts) {
            Flash::error('A discount may apply to categories or products, but not both.');
            throw new ValidationException('A discount may apply to categories or products, but not both.');
        }

        // Return the discount scope
        return $checkedCategories
            ? 'categories'
            : 'products';
    }

    /**
     * Run a query to search for discount collisions
     * @param   string  $scope ('categories' or 'discounts')
     */
    private function validateDiscountCollisions($scope)
    {
        // Skip the collision check if our discount has already ended
        if ($this->end_date && strtotime($this->end_date) < time())
            return;

        // Load our discount_id and discountable_id values
        $discountableIds = post('Discount')[$scope];
        $discountId = $this->id ? $this->id : 0;

        // Query the database and look for collisions
        $collisions = Discount::where('id', '<>', $discountId)
            ->isActiveDuring($this->start_date, $this->end_date)
            ->where(function($query) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', date('Y-m-d H:i:s'));
            })
            ->whereHas($scope, function($query) use ($discountableIds) {
                $query->whereIn('id', $discountableIds);
            })
            ->lists('name');

        // If collisions were found, throw an exception
        if (count($collisions) > 0) {
            $collisionString = count($collisions == 1)
                ? "This discount has $scope that overlap with the discount $collisions[0]."
                : "This discount has $scope that overlap with the following discounts...\n".implode(', ', $collisions);
            Flash::error($collisionString);
            throw new ValidationException($collisionString);
        }
    }

    /**
     * Query Scopes
     */
    
    // Checks if a discount is running during a certain time frame
    public function scopeIsActiveDuring($query, $startDate = NULL, $endDate = NULL)
    {
        // If no start date was provided, assume it starts now
        if (!$startDate)
            $startDate = date('Y-m-d H:i:s');

        // The discount must end after our start date
        $query->where(function($query) use ($startDate) {
            $query->whereNull('end_date')
                  ->orWhere('end_date', '>=', $startDate);
        });

        // And start before our end date
        if ($endDate) {
            $query->where(function($query) use ($endDate) {
                $query->whereNull('start_date')
                      ->orWhere('start_date', '<=', $endDate);
            });
        }

        return $query;
    }

    // Checks if a discount is currently running
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
     * @return  numeric
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
     * Returns the discountable relationship type
     * @return  string
     */
    public function getDiscountableTypeAttribute()
    {
        if (count($this->categories) > 0)
            return 'Bedard\Shop\Models\Category';

        elseif (count($this->products) > 0)
            return 'Bedard\Shop\Models\Product';
    }
}