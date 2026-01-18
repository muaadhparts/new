<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Phase 9.3: Data Cleanup Migration
 *
 * This migration ensures ALL merchant_items have a valid merchant_branch_id.
 * For items without a branch, it assigns them to the merchant's first active branch.
 * If the merchant has no branch, a default branch is created.
 *
 * IMPORTANT: After this migration runs, Phase 9.4 will add NOT NULL constraint.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all merchants (user_ids) that have items without branch
        $merchantsWithOrphanItems = DB::table('merchant_items')
            ->whereNull('merchant_branch_id')
            ->distinct()
            ->pluck('user_id');

        if ($merchantsWithOrphanItems->isEmpty()) {
            Log::info('Phase 9.3: No orphan items found - all merchant_items already have branch_id');
            return;
        }

        Log::info('Phase 9.3: Found ' . $merchantsWithOrphanItems->count() . ' merchants with orphan items');

        foreach ($merchantsWithOrphanItems as $merchantId) {
            // Get or create branch for this merchant
            $branch = DB::table('merchant_branches')
                ->where('user_id', $merchantId)
                ->where('status', 1)
                ->first();

            if (!$branch) {
                // Get merchant info for default branch name
                $merchant = DB::table('users')->find($merchantId);
                $branchName = ($merchant->shop_name ?? $merchant->name ?? 'Merchant') . ' - Main Branch';

                // Create default branch
                $branchId = DB::table('merchant_branches')->insertGetId([
                    'user_id' => $merchantId,
                    'warehouse_name' => $branchName,
                    'location' => $merchant->address ?? '',
                    'city_id' => $merchant->city_id ?? null,
                    'status' => 1,
                    'is_default' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info("Phase 9.3: Created default branch for merchant {$merchantId}: branch_id={$branchId}");
            } else {
                $branchId = $branch->id;
            }

            // Assign all orphan items to this branch
            $updatedCount = DB::table('merchant_items')
                ->where('user_id', $merchantId)
                ->whereNull('merchant_branch_id')
                ->update(['merchant_branch_id' => $branchId]);

            Log::info("Phase 9.3: Assigned {$updatedCount} items to branch {$branchId} for merchant {$merchantId}");
        }

        // Verify no orphans remain
        $remainingOrphans = DB::table('merchant_items')
            ->whereNull('merchant_branch_id')
            ->count();

        if ($remainingOrphans > 0) {
            Log::error("Phase 9.3: WARNING - {$remainingOrphans} orphan items still exist!");
            throw new \Exception("Data cleanup failed: {$remainingOrphans} items still have NULL merchant_branch_id");
        }

        Log::info('Phase 9.3: Data cleanup completed successfully - all items now have branch_id');
    }

    /**
     * Reverse the migrations.
     *
     * Note: We don't reverse the branch assignments because:
     * 1. The data was already broken (NULL branch_id is invalid)
     * 2. We can't know which items were previously NULL
     * 3. The default branches created are still valid
     */
    public function down(): void
    {
        Log::warning('Phase 9.3 rollback: Branch assignments are not reversed to prevent data corruption');
    }
};
