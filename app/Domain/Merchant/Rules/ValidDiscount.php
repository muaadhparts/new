<?php

namespace App\Domain\Merchant\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valid Discount Rule
 *
 * Validates that the discount is valid based on type.
 */
class ValidDiscount implements ValidationRule
{
    /**
     * Discount type (percent or fixed)
     */
    protected string $type;

    /**
     * Original price for fixed discount validation
     */
    protected ?float $originalPrice;

    /**
     * Create a new rule instance.
     */
    public function __construct(string $type = 'percent', ?float $originalPrice = null)
    {
        $this->type = $type;
        $this->originalPrice = $originalPrice;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value)) {
            $fail(__('validation.discount.must_be_numeric'));
            return;
        }

        $discount = (float) $value;

        if ($discount < 0) {
            $fail(__('validation.discount.cannot_be_negative'));
            return;
        }

        if ($this->type === 'percent') {
            if ($discount > 100) {
                $fail(__('validation.discount.percent_max'));
                return;
            }
        } else {
            // Fixed discount
            if ($this->originalPrice !== null && $discount >= $this->originalPrice) {
                $fail(__('validation.discount.exceeds_price'));
                return;
            }
        }
    }

    /**
     * Set discount type
     */
    public function type(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Set original price
     */
    public function price(float $price): self
    {
        $this->originalPrice = $price;
        return $this;
    }
}
