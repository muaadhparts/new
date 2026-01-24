<?php

namespace App\Domain\Accounting\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Domain\Accounting\Models\AccountBalance;

/**
 * Valid Withdraw Amount Rule
 *
 * Validates withdrawal amount against balance and limits.
 */
class ValidWithdrawAmount implements ValidationRule
{
    /**
     * User ID
     */
    protected int $userId;

    /**
     * Minimum withdrawal amount
     */
    protected float $minAmount;

    /**
     * Maximum withdrawal amount
     */
    protected ?float $maxAmount;

    /**
     * Create a new rule instance.
     */
    public function __construct(int $userId, float $minAmount = 100, ?float $maxAmount = null)
    {
        $this->userId = $userId;
        $this->minAmount = $minAmount;
        $this->maxAmount = $maxAmount;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value)) {
            $fail(__('validation.amount.must_be_numeric'));
            return;
        }

        $amount = (float) $value;

        // Check minimum
        if ($amount < $this->minAmount) {
            $fail(__('validation.withdraw.below_minimum', ['min' => $this->minAmount]));
            return;
        }

        // Check maximum if set
        if ($this->maxAmount !== null && $amount > $this->maxAmount) {
            $fail(__('validation.withdraw.above_maximum', ['max' => $this->maxAmount]));
            return;
        }

        // Check balance
        $balance = AccountBalance::where('user_id', $this->userId)->first();
        $available = $balance ? ($balance->current_balance - $balance->pending_balance) : 0;

        if ($amount > $available) {
            $fail(__('validation.withdraw.insufficient_balance', [
                'available' => number_format($available, 2),
            ]));
        }
    }
}
