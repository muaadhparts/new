<?php

namespace App\Domain\Commerce\Schedule;

use App\Domain\Commerce\Models\Purchase;
use Illuminate\Support\Facades\Log;

/**
 * Process Pending Orders Task
 *
 * Handles orders stuck in pending status for too long.
 */
class ProcessPendingOrdersTask
{
    /**
     * Hours before considering an order stuck.
     */
    protected int $hoursThreshold = 48;

    /**
     * Execute the task.
     */
    public function __invoke(): void
    {
        $cutoffDate = now()->subHours($this->hoursThreshold);

        $stuckOrders = Purchase::where('status', 'pending')
            ->where('created_at', '<', $cutoffDate)
            ->get();

        $processed = 0;
        foreach ($stuckOrders as $order) {
            // Mark as failed if unpaid after threshold
            if ($order->payment_status === 'pending') {
                $order->update([
                    'status' => 'failed',
                    'notes' => 'Auto-cancelled: No payment received within ' . $this->hoursThreshold . ' hours',
                ]);
                $processed++;
            }
        }

        Log::info('Pending orders processed', [
            'found' => $stuckOrders->count(),
            'processed' => $processed,
            'threshold_hours' => $this->hoursThreshold,
        ]);
    }

    /**
     * Get the schedule frequency.
     */
    public static function frequency(): string
    {
        return 'hourly';
    }
}
