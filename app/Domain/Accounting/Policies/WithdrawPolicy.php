<?php

namespace App\Domain\Accounting\Policies;

use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Withdraw Policy
 *
 * Determines authorization for withdraw request actions.
 */
class WithdrawPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any withdrawals.
     */
    public function viewAny(User $user): bool
    {
        // Merchants can view their withdrawals
        return $user->is_merchant ?? false;
    }

    /**
     * Determine if user can view the withdrawal.
     */
    public function view(User $user, Withdraw $withdraw): bool
    {
        return $user->id === $withdraw->user_id;
    }

    /**
     * Determine if user can create withdrawal request.
     */
    public function create(User $user): bool
    {
        // Must be active merchant
        if (!($user->is_merchant ?? false) || $user->status !== 1) {
            return false;
        }

        // Check minimum balance
        $minBalance = config('accounting.min_withdraw_balance', 100);
        $balance = $user->accountBalance?->current_balance ?? 0;

        return $balance >= $minBalance;
    }

    /**
     * Determine if user can cancel the withdrawal.
     */
    public function cancel(User $user, Withdraw $withdraw): bool
    {
        // Must be owner
        if ($user->id !== $withdraw->user_id) {
            return false;
        }

        // Can only cancel pending requests
        return $withdraw->status === 'pending';
    }
}
