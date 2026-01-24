<?php

namespace App\Domain\Merchant\Observers;

use App\Domain\Merchant\Models\MerchantBranch;

/**
 * Merchant Branch Observer
 *
 * Handles MerchantBranch model lifecycle events.
 */
class MerchantBranchObserver
{
    /**
     * Handle the MerchantBranch "creating" event.
     */
    public function creating(MerchantBranch $branch): void
    {
        // Set default status
        if (!isset($branch->status)) {
            $branch->status = 1;
        }

        // If this is the first branch, make it main
        $existingBranches = MerchantBranch::where('merchant_id', $branch->merchant_id)->count();
        if ($existingBranches === 0) {
            $branch->is_main = true;
        }
    }

    /**
     * Handle the MerchantBranch "created" event.
     */
    public function created(MerchantBranch $branch): void
    {
        // If marked as main, ensure no other branch is main
        if ($branch->is_main) {
            $this->ensureSingleMainBranch($branch);
        }
    }

    /**
     * Handle the MerchantBranch "updating" event.
     */
    public function updating(MerchantBranch $branch): void
    {
        // If setting as main, will need to unset others
        if ($branch->isDirty('is_main') && $branch->is_main) {
            $this->ensureSingleMainBranch($branch);
        }
    }

    /**
     * Handle the MerchantBranch "deleting" event.
     */
    public function deleting(MerchantBranch $branch): bool
    {
        // Prevent deletion of main branch if other branches exist
        if ($branch->is_main) {
            $otherBranches = MerchantBranch::where('merchant_id', $branch->merchant_id)
                ->where('id', '!=', $branch->id)
                ->count();

            if ($otherBranches > 0) {
                // Transfer main status to another branch
                $newMain = MerchantBranch::where('merchant_id', $branch->merchant_id)
                    ->where('id', '!=', $branch->id)
                    ->first();

                if ($newMain) {
                    $newMain->update(['is_main' => true]);
                }
            }
        }

        return true;
    }

    /**
     * Ensure only one branch is marked as main
     */
    protected function ensureSingleMainBranch(MerchantBranch $branch): void
    {
        MerchantBranch::where('merchant_id', $branch->merchant_id)
            ->where('id', '!=', $branch->id)
            ->where('is_main', true)
            ->update(['is_main' => false]);
    }
}
