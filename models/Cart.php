<?php namespace Bedard\Shop\Models;

use Bedard\Shop\Models\Customer;
use Bedard\Shop\Models\Transaction;
use DB;
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
        $query->whereNotNull('transaction_id');
    }

    /**
     * Ensures that cart quantities do not exceed their available inventories
     * @return  boolean
     */
    public function fixQuantities()
    {
        // Check if any inventories were invalid
        $fixQuantities = FALSE;
        foreach ($this->items as $item) {
            if ($item->quantity > $item->inventory->quantity) {
                $fixQuantities = TRUE;
            }
        }

        // Run a query to fix invalid quantities
        if ($fixQuantities) {
            $updated = DB::table('bedard_shop_cart_items AS item')
                ->join('bedard_shop_inventories AS inventory', 'item.inventory_id', '=', 'inventory.id')
                ->where('item.quantity', '>', DB::raw('`inventory`.`quantity`'))
                ->where('item.cart_id', '=', $this->id)
                ->update(['item.quantity' => DB::raw('`inventory`.`quantity`')]);

            // If anything was changed, update the relationship
            if ($updated > 0) {
                $this->load(['items' => function($cartItem) {
                    $cartItem->inCart();
                }]);
            }
        }

        return $fixQuantities;
    }

    /**
     * Marks a shopping cart as complete, and updates item inventories
     */
    public function complete(Transaction $transaction, Customer $customer)
    {
        foreach ($this->items as $item) {
            // Update the inventory quantity
            $item->inventory->quantity -= $item->quantity;
            $item->inventory->save();

            // Backup the cart item
            $item->backup_product = $item->inventory->product->toArray();
            $item->backup_inventory = $item->inventory->toArray();
            $item->backup_price = $item->price;
            $item->backup_full_price = $item->fullPrice;
            $item->save();
        }

        // Update the transaction
        $transaction->amount = $this->total;
        $transaction->customer_id = $customer->id;
        $transaction->is_complete = TRUE;
        $transaction->save();

        // Attach the transaction and see if a coupon can be used
        $this->transaction_id = $transaction->id;
        if (!$this->couponIsApplied)
            $this->coupon_id = NULL;
        $this->save();
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
        return $fullTotal;
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
        return round($total, 2);
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
        return $totalBeforeCoupon;
    }

}