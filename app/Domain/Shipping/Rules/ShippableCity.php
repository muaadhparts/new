<?php

namespace App\Domain\Shipping\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Domain\Shipping\Models\City;

/**
 * Shippable City Rule
 *
 * Validates that shipping is available to the city.
 */
class ShippableCity implements ValidationRule
{
    /**
     * Merchant ID to check shipping availability for
     */
    protected ?int $merchantId = null;

    /**
     * Create a new rule instance.
     */
    public function __construct(?int $merchantId = null)
    {
        $this->merchantId = $merchantId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value)) {
            $fail(__('validation.city.must_be_numeric'));
            return;
        }

        $city = City::find($value);

        if (!$city) {
            $fail(__('validation.city.not_found'));
            return;
        }

        if (!$city->status) {
            $fail(__('validation.city.inactive'));
            return;
        }

        // Check if merchant ships to this city
        if ($this->merchantId) {
            $hasShipping = \App\Domain\Shipping\Models\Shipping::query()
                ->forMerchant($this->merchantId)
                ->where(function ($q) use ($city) {
                    $q->where('city_id', $city->id)
                      ->orWhere('city_id', 0); // 0 means all cities
                })
                ->where('status', 1)
                ->exists();

            if (!$hasShipping) {
                $fail(__('validation.city.no_shipping_available'));
            }
        }
    }

    /**
     * Set merchant ID
     */
    public function forMerchant(int $merchantId): self
    {
        $this->merchantId = $merchantId;
        return $this;
    }
}
