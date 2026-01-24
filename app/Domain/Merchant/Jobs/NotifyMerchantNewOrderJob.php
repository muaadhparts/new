<?php

namespace App\Domain\Merchant\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Commerce\Models\MerchantPurchase;
use App\Domain\Merchant\Notifications\NewOrderNotification;

/**
 * Notify Merchant New Order Job
 *
 * Sends notification to merchant about new order.
 */
class NotifyMerchantNewOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

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

        $merchant->notify(new NewOrderNotification($this->merchantPurchase));
    }
}
