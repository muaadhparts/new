<?php

namespace App\Domain\Commerce\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Commerce\Enums\PurchaseStatus;
use Carbon\Carbon;

/**
 * Process Pending Orders Command
 *
 * Processes or cancels orders that have been pending too long.
 */
class ProcessPendingOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'commerce:process-pending-orders
                            {--days=7 : Days before pending order is flagged}
                            {--auto-cancel : Automatically cancel old pending orders}';

    /**
     * The console command description.
     */
    protected $description = 'Process orders that have been pending for too long';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $autoCancel = $this->option('auto-cancel');
        $cutoff = Carbon::now()->subDays($days);

        $this->info("Looking for pending orders older than {$days} days...");

        $pendingOrders = Purchase::where('status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->get();

        if ($pendingOrders->isEmpty()) {
            $this->info('No old pending orders found.');
            return self::SUCCESS;
        }

        $this->warn("Found {$pendingOrders->count()} pending orders.");

        $this->table(
            ['Order #', 'User', 'Total', 'Created'],
            $pendingOrders->map(fn($o) => [
                $o->order_number,
                $o->user_id,
                number_format($o->total, 2),
                $o->created_at->format('Y-m-d'),
            ])
        );

        if ($autoCancel) {
            if (!$this->confirm('Cancel these orders?')) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }

            $cancelled = 0;
            foreach ($pendingOrders as $order) {
                $order->update([
                    'status' => 'cancelled',
                    'cancel_reason' => 'Auto-cancelled: Pending too long',
                ]);
                $cancelled++;
            }

            $this->info("Cancelled {$cancelled} orders.");
        } else {
            $this->info('Use --auto-cancel to automatically cancel these orders.');
        }

        return self::SUCCESS;
    }
}
