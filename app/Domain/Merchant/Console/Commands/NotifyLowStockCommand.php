<?php

namespace App\Domain\Merchant\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Merchant\Notifications\LowStockNotification;
use App\Domain\Identity\Models\User;

/**
 * Notify Low Stock Command
 *
 * Sends notifications to merchants about low stock items.
 */
class NotifyLowStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'merchant:notify-low-stock
                            {--threshold=5 : Stock threshold for notification}
                            {--dry-run : Show what notifications would be sent}';

    /**
     * The console command description.
     */
    protected $description = 'Notify merchants about low stock items';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $threshold = (int) $this->option('threshold');
        $dryRun = $this->option('dry-run');

        $this->info("Checking for items with stock <= {$threshold}...");

        // Group low stock items by merchant
        $lowStockItems = MerchantItem::where('stock', '>', 0)
            ->where('stock', '<=', $threshold)
            ->where('status', 1)
            ->get()
            ->groupBy('user_id');

        if ($lowStockItems->isEmpty()) {
            $this->info('No low stock items found.');
            return self::SUCCESS;
        }

        $notificationCount = 0;

        foreach ($lowStockItems as $merchantId => $items) {
            $merchant = User::find($merchantId);

            if (!$merchant) {
                continue;
            }

            if ($dryRun) {
                $this->line("Would notify merchant #{$merchantId} about " . $items->count() . " items");
                continue;
            }

            $merchant->notify(new LowStockNotification($items->toArray()));
            $notificationCount++;

            $this->line("Notified merchant #{$merchantId} about " . $items->count() . " items");
        }

        if ($dryRun) {
            $this->warn('Dry run - no notifications were sent.');
        } else {
            $this->info("Sent {$notificationCount} notifications.");
        }

        return self::SUCCESS;
    }
}
