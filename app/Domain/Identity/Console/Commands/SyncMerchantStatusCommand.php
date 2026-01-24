<?php

namespace App\Domain\Identity\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Identity\Models\User;
use App\Domain\Merchant\Models\MerchantSetting;

/**
 * Sync Merchant Status Command
 *
 * Syncs merchant status between users and settings tables.
 */
class SyncMerchantStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'identity:sync-merchant-status
                            {--fix : Fix inconsistencies}';

    /**
     * The console command description.
     */
    protected $description = 'Sync merchant status between tables';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking merchant status consistency...');

        // Find merchants without settings
        $merchantsWithoutSettings = User::where('is_merchant', 1)
            ->whereDoesntHave('merchantSettings')
            ->get();

        // Find settings without valid merchant
        $orphanedSettings = MerchantSetting::whereDoesntHave('user', function ($q) {
            $q->where('is_merchant', 1);
        })->get();

        $this->table(
            ['Issue', 'Count'],
            [
                ['Merchants without settings', $merchantsWithoutSettings->count()],
                ['Orphaned settings', $orphanedSettings->count()],
            ]
        );

        if ($merchantsWithoutSettings->isEmpty() && $orphanedSettings->isEmpty()) {
            $this->info('No inconsistencies found.');
            return self::SUCCESS;
        }

        if (!$this->option('fix')) {
            $this->info('Use --fix to resolve these issues.');
            return self::SUCCESS;
        }

        // Fix merchants without settings
        foreach ($merchantsWithoutSettings as $merchant) {
            MerchantSetting::create([
                'user_id' => $merchant->id,
                'status' => 1,
            ]);
            $this->line("Created settings for merchant #{$merchant->id}");
        }

        // Handle orphaned settings
        foreach ($orphanedSettings as $setting) {
            $user = User::find($setting->user_id);
            if ($user) {
                $user->update(['is_merchant' => 1]);
                $this->line("Marked user #{$user->id} as merchant");
            } else {
                $setting->delete();
                $this->line("Deleted orphaned setting #{$setting->id}");
            }
        }

        $this->info('Sync complete.');

        return self::SUCCESS;
    }
}
