<?php

namespace App\Domain\Accounting\Schedule;

use App\Domain\Accounting\Models\AccountBalance;
use App\Domain\Commerce\Models\MerchantPurchase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Process Settlements Task
 *
 * Moves pending balance to available balance after settlement period.
 */
class ProcessSettlementsTask
{
    /**
     * Settlement period in days.
     */
    protected int $settlementDays = 7;

    /**
     * Execute the task.
     */
    public function __invoke(): void
    {
        $cutoffDate = now()->subDays($this->settlementDays);

        // Find delivered orders ready for settlement
        $ordersToSettle = MerchantPurchase::where('status', 'delivered')
            ->where('settled', false)
            ->where('updated_at', '<', $cutoffDate)
            ->get();

        $processed = 0;
        $totalAmount = 0;

        DB::beginTransaction();

        try {
            foreach ($ordersToSettle as $order) {
                $netAmount = $order->total - ($order->commission_amount ?? 0);

                // Move from pending to available
                AccountBalance::where('user_id', $order->user_id)
                    ->update([
                        'pending_balance' => DB::raw("pending_balance - {$netAmount}"),
                        'current_balance' => DB::raw("current_balance + {$netAmount}"),
                    ]);

                $order->update(['settled' => true]);

                $processed++;
                $totalAmount += $netAmount;
            }

            DB::commit();

            Log::info('Settlements processed', [
                'orders_settled' => $processed,
                'total_amount' => $totalAmount,
                'settlement_days' => $this->settlementDays,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Settlement processing failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get the schedule frequency.
     */
    public static function frequency(): string
    {
        return 'dailyAt';
    }

    /**
     * Get the schedule time.
     */
    public static function at(): string
    {
        return '00:00';
    }
}
