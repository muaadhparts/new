<?php

namespace App\Domain\Merchant\Policies;

use App\Domain\Identity\Models\User;
use App\Domain\Merchant\Models\MerchantBranch;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Merchant Branch Policy
 *
 * Determines authorization for merchant branch actions.
 */
class MerchantBranchPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if merchant can view any branches
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'merchant' && $user->status === 1;
    }

    /**
     * Determine if merchant can view the branch
     */
    public function view(User $user, MerchantBranch $branch): bool
    {
        return $user->id === $branch->merchant_id;
    }

    /**
     * Determine if merchant can create branches
     */
    public function create(User $user): bool
    {
        if ($user->role !== 'merchant' || $user->status !== 1) {
            return false;
        }

        // Check branch limit from settings
        $maxBranches = config('merchant.max_branches', 10);
        $currentCount = MerchantBranch::where('merchant_id', $user->id)->count();

        return $currentCount < $maxBranches;
    }

    /**
     * Determine if merchant can update the branch
     */
    public function update(User $user, MerchantBranch $branch): bool
    {
        return $user->id === $branch->merchant_id;
    }

    /**
     * Determine if merchant can delete the branch
     */
    public function delete(User $user, MerchantBranch $branch): bool
    {
        // Must own the branch
        if ($user->id !== $branch->merchant_id) {
            return false;
        }

        // Cannot delete main branch
        if ($branch->is_main) {
            return false;
        }

        // Cannot delete if has pending orders
        // This should be checked at service level
        return true;
    }

    /**
     * Determine if merchant can set as main branch
     */
    public function setAsMain(User $user, MerchantBranch $branch): bool
    {
        return $user->id === $branch->merchant_id;
    }

    /**
     * Determine if merchant can toggle branch status
     */
    public function toggleStatus(User $user, MerchantBranch $branch): bool
    {
        // Cannot disable main branch
        if ($branch->is_main) {
            return false;
        }

        return $user->id === $branch->merchant_id;
    }
}
