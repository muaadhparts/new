<?php

namespace App\Domain\Commerce\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Commerce\Models\StockReservation;
use Carbon\Carbon;

/**
 * Cleanup Abandoned Carts Command
 *
 * Releases stock reservations from abandoned carts.
 */
class CleanupAbandonedCartsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'commerce:cleanup-abandoned-carts
                            {--hours=24 : Hours before cart is considered abandoned}
                            {--dry-run : Show what would be cleaned without cleaning}';

    /**
     * The console command description.
     */
    protected $description = 'Release stock reservations from abandoned carts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');
        $cutoff = Carbon::now()->subHours($hours);

        $this->info("Looking for abandoned carts older than {$hours} hours...");

        $reservations = StockReservation::where('status', 'reserved')
            ->where('created_at', '<', $cutoff)
            ->get();

        if ($reservations->isEmpty()) {
            $this->info('No abandoned cart reservations found.');
            return self::SUCCESS;
        }

        $this->info("Found {$reservations->count()} abandoned reservations.");

        if ($dryRun) {
            $this->warn('Dry run mode - no changes will be made.');
            $this->table(
                ['ID', 'Item ID', 'Quantity', 'Created At'],
                $reservations->map(fn($r) => [
                    $r->id,
                    $r->merchant_item_id,
                    $r->quantity,
                    $r->created_at->format('Y-m-d H:i'),
                ])
            );
            return self::SUCCESS;
        }

        $released = 0;
        foreach ($reservations as $reservation) {
            // Release stock back to item
            if ($reservation->merchantItem) {
                $reservation->merchantItem->increment('stock', $reservation->quantity);
            }

            $reservation->update(['status' => 'released']);
            $released++;
        }

        $this->info("Released {$released} stock reservations.");

        return self::SUCCESS;
    }
}
