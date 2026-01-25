<?php

namespace App\Domain\Commerce\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Domain\Commerce\Models\Coupon;

/**
 * Valid Coupon Rule
 *
 * Validates coupon code for cart.
 */
class ValidCoupon implements ValidationRule
{
    /**
     * Cart total
     */
    protected float $cartTotal;

    /**
     * User ID
     */
    protected ?int $userId;

    /**
     * Create a new rule instance.
     */
    public function __construct(float $cartTotal, ?int $userId = null)
    {
        $this->cartTotal = $cartTotal;
        $this->userId = $userId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || empty($value)) {
            $fail(__('validation.coupon.must_be_string'));
            return;
        }

        $code = strtoupper(trim($value));
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            $fail(__('validation.coupon.not_found'));
            return;
        }

        if (!$coupon->status) {
            $fail(__('validation.coupon.inactive'));
            return;
        }

        // Check expiry
        if ($coupon->end_date && $coupon->end_date < now()) {
            $fail(__('validation.coupon.expired'));
            return;
        }

        // Check start date
        if ($coupon->start_date && $coupon->start_date > now()) {
            $fail(__('validation.coupon.not_started'));
            return;
        }

        // Check minimum order
        if ($coupon->min_order && $this->cartTotal < $coupon->min_order) {
            $fail(__('validation.coupon.min_order_required', [
                'min' => $coupon->min_order,
            ]));
            return;
        }

        // Check usage limit
        if ($coupon->max_uses && $coupon->used_count >= $coupon->max_uses) {
            $fail(__('validation.coupon.limit_reached'));
        }
    }
}
