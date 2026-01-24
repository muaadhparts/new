<?php

namespace App\Domain\Commerce\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valid Payment Method Rule
 *
 * Validates that the payment method is valid and available.
 */
class ValidPaymentMethod implements ValidationRule
{
    /**
     * Allowed payment methods
     */
    protected array $allowedMethods = ['cod', 'card', 'bank_transfer', 'wallet'];

    /**
     * Merchant IDs to check payment availability
     */
    protected array $merchantIds = [];

    /**
     * Create a new rule instance.
     */
    public function __construct(array $merchantIds = [])
    {
        $this->merchantIds = $merchantIds;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail(__('validation.payment.must_be_string'));
            return;
        }

        if (!in_array($value, $this->allowedMethods)) {
            $fail(__('validation.payment.invalid_method'));
            return;
        }

        // Check if all merchants accept this payment method
        if (!empty($this->merchantIds) && $value !== 'cod') {
            foreach ($this->merchantIds as $merchantId) {
                $setting = \App\Domain\Merchant\Models\MerchantSetting::where('merchant_id', $merchantId)->first();

                if ($setting) {
                    if ($value === 'card' && !$setting->accepts_card) {
                        $fail(__('validation.payment.merchant_not_accept', ['method' => $value]));
                        return;
                    }
                }
            }
        }
    }

    /**
     * Set merchant IDs
     */
    public function forMerchants(array $merchantIds): self
    {
        $this->merchantIds = $merchantIds;
        return $this;
    }
}
