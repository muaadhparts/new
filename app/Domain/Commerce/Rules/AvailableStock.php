<?php

namespace App\Domain\Commerce\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Domain\Merchant\Models\MerchantItem;

/**
 * Available Stock Rule
 *
 * Validates that requested quantity is available in stock.
 */
class AvailableStock implements ValidationRule
{
    /**
     * Merchant item ID
     */
    protected int $merchantItemId;

    /**
     * Create a new rule instance.
     */
    public function __construct(int $merchantItemId)
    {
        $this->merchantItemId = $merchantItemId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value)) {
            $fail(__('validation.quantity.must_be_numeric'));
            return;
        }

        $quantity = (int) $value;

        $merchantItem = MerchantItem::find($this->merchantItemId);

        if (!$merchantItem) {
            $fail(__('validation.item.not_found'));
            return;
        }

        if (!$merchantItem->status) {
            $fail(__('validation.item.unavailable'));
            return;
        }

        if ($merchantItem->stock < $quantity) {
            $fail(__('validation.stock.insufficient', [
                'available' => $merchantItem->stock,
                'requested' => $quantity,
            ]));
        }
    }
}
