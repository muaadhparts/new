<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StockReservation;

/**
 * Release Expired Stock Reservations
 * ===================================
 * يحرر الحجوزات المنتهية ويعيد المخزون للمنتجات
 *
 * Usage:
 *   php artisan reservations:release
 *
 * Scheduler (Kernel.php):
 *   $schedule->command('reservations:release')->everyFiveMinutes();
 */
class ReleaseExpiredReservations extends Command
{
    protected $signature = 'reservations:release {--dry-run : Show what would be released without actually releasing}';

    protected $description = 'Release expired stock reservations and return stock to products';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $expired = StockReservation::expired()->get();
        $count = $expired->count();

        if ($count === 0) {
            $this->info('No expired reservations found.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("DRY RUN: Would release {$count} expired reservations:");
            foreach ($expired as $reservation) {
                $this->line("  - MP#{$reservation->merchant_product_id} qty:{$reservation->qty} session:{$reservation->session_id}");
            }
            return self::SUCCESS;
        }

        $this->info("Releasing {$count} expired reservations...");

        $released = StockReservation::releaseExpired();

        $this->info("Successfully released {$released} reservations.");

        return self::SUCCESS;
    }
}
