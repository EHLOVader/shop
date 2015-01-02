<?php namespace Bedard\Shop\Models;

use Model;

/**
 * Cart Model
 */
class Cart extends Model
{

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
    public $belongsTo = [
        'coupon' => ['Bedard\Shop\Models\Coupon', 'table' => 'bedard_shop_coupons']
    ];
    public $hasMany = [
        'items' => ['Bedard\Shop\Models\CartItem', 'table' => 'bedard_shop_cart_items']
    ];

    /**
     * Query Scopes
     */
    public function scopeIsComplete($query)
    {
        $query->where('transaction_id', '<>', NULL);
    }

    /**
     * Checks if the coupon is being applied or not
     * @return  boolean
     */
    public function getCouponIsAppliedAttribute()
    {
        return $this->totalBeforeCoupon > $this->total;
    }

    /**
     * Determine if the cart is at full price or not
     * @return  boolean
     */
    public function getIsDiscountedAttribute()
    {
        return $this->total < $this->fullTotal;
    }

    /**
     * Returns the total value of the cart before discounts or promotions
     * @return  string (numeric)
     */
    public function getFullTotalAttribute()
    {
        $fullTotal = 0;
        foreach ($this->items as $item)
            $fullTotal += $item->quantity * $item->fullPrice;
        return number_format($fullTotal, 2);
    }

    /**
     * Returns the total value of the cart
     * @return  string (numeric)
     */
    public function getTotalAttribute()
    {
        $total = $this->totalBeforeCoupon;
        if (!is_null($this->attributes['coupon_id']) && $this->coupon && $this->coupon->cart_value <= $total) {
            $total -= $this->coupon->is_percentage
                ? $total * ($this->coupon->amount / 100)
                : $this->coupon->amount;
            if ($total < 0) $total = 0;
        }
        return number_format($total, 2);
    }

    /**
     * Returns the total value of the cart before coupons are applied
     * @return  string (numeric)
     */
    public function getTotalBeforeCouponAttribute()
    {
        $totalBeforeCoupon = 0;
        foreach ($this->items as $item)
            $totalBeforeCoupon += $item->quantity * $item->price;
        return number_format($totalBeforeCoupon, 2);
    }

}