<?php

namespace App\Domain\Accounting\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Commerce\Models\MerchantPurchase;
use App\Domain\Accounting\Models\AccountingLedger;
use App\Domain\Accounting\Models\AccountBalance;

/**
 * Process Commission Job
 *
 * Calculates and records commission for completed orders.
 */
class ProcessCommissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public MerchantPurchase $merchantPurchase
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $merchant = $this->merchantPurchase->merchant;

        if (!$merchant) {
            return;
        }

        // Calculate commission
        $orderTotal = $this->merchantPurchase->total;
        $commissionRate = $this->getCommissionRate($merchant->id);
        $commissionAmount = $orderTotal * ($commissionRate / 100);
        $merchantEarnings = $orderTotal - $commissionAmount;

        // Record ledger entries
        $this->recordLedgerEntry($merchant->id, $merchantEarnings, $commissionAmount);

        // Update merchant balance
        $this->updateMerchantBalance($merchant->id, $merchantEarnings);

        \Log::info('Commission processed', [
            'merchant_purchase_id' => $this->merchantPurchase->id,
            'order_total' => $orderTotal,
            'commission' => $commissionAmount,
            'merchant_earnings' => $merchantEarnings,
        ]);
    }

    /**
     * Get commission rate for merchant
     */
    protected function getCommissionRate(int $merchantId): float
    {
        $commission = \App\Domain\Merchant\Models\MerchantCommission::where('user_id', $merchantId)
            ->first();

        return $commission->rate ?? 10.0; // Default 10%
    }

    /**
     * Record ledger entry
     */
    protected function recordLedgerEntry(int $merchantId, float $earnings, float $commission): void
    {
        AccountingLedger::create([
            'user_id' => $merchantId,
            'type' => 'credit',
            'amount' => $earnings,
            'reference_type' => 'merchant_purchase',
            'reference_id' => $this->merchantPurchase->id,
            'description' => 'Order earnings',
        ]);

        // Platform commission
        AccountingLedger::create([
            'user_id' => 0, // Platform
            'type' => 'credit',
            'amount' => $commission,
            'reference_type' => 'commission',
            'reference_id' => $this->merchantPurchase->id,
            'description' => 'Platform commission',
        ]);
    }

    /**
     * Update merchant balance
     */
    protected function updateMerchantBalance(int $merchantId, float $amount): void
    {
        $balance = AccountBalance::firstOrCreate(
            ['user_id' => $merchantId],
            ['current_balance' => 0, 'pending_balance' => 0, 'total_earned' => 0]
        );

        $balance->increment('pending_balance', $amount);
        $balance->increment('total_earned', $amount);
    }
}
