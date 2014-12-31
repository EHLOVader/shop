<?php namespace Bedard\Shop\Models;

use Model;

/**
 * Cart Model
 */
class Cart extends Model
{

    /**
     * Total values for the cart, and if it is discounted or not
     * @var array   [ total, fullTotal, isDiscounted ]
     */
    private $cartTotal;

    /**
     * @var string  The database table used by the model.
     */
    public $table = 'bedard_shop_carts';

    /**
     * @var array   Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array   Fillable fields
     */
    protected $fillable = ['key'];

    /**
     * @var array   Relations
     */
    public $hasMany = [
        'items' => ['Bedard\Shop\Models\CartItem', 'table' => 'bedard_shop_cart_items']
    ];

    /**
     * Calculates the value of the cart, and checks if discounts are in effect
     */
    private function calculateTotals()
    {
        $fullTotal = $total = 0;
        foreach ($this->items as $item) {
            $fullTotal += $item->fullPrice * $item->quantity;
            $total += $item->price * $item->quantity;
        }

        // TO DO: APPLY PROMO CODES TO $TOTAL
        // 

        $this->cartTotal = [
            'total'         => number_format($total, 2),
            'fullTotal'     => number_format($fullTotal, 2),
            'isDiscounted'  => $total < $fullTotal
        ];
    }

    /**
     * Determine if the cart is at full price or not
     * @return  boolean
     */
    public function getIsDiscountedAttribute()
    {
        if (!$this->cartTotal)
            $this->calculateTotals();
        return $this->cartTotal['isDiscounted'];
    }

    /**
     * Calculate the total value of the cart before discounts or promotions
     * @return  float
     */
    public function getFullTotalAttribute()
    {
        if (!$this->cartTotal)
            $this->calculateTotals();
        return $this->cartTotal['fullTotal'];
    }

    /**
     * Calculates the total value of the cart
     * @return  float
     */
    public function getTotalAttribute()
    {
        if (!$this->cartTotal)
            $this->calculateTotals();
        return $this->cartTotal['total'];
    }

}