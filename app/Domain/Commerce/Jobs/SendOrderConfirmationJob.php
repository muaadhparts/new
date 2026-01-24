<?php

namespace App\Domain\Commerce\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Commerce\Notifications\OrderPlacedNotification;

/**
 * Send Order Confirmation Job
 *
 * Sends order confirmation email to customer.
 */
class SendOrderConfirmationJob implements ShouldQueue
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
        public Purchase $purchase
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = $this->purchase->user;

        if (!$user) {
            return;
        }

        $user->notify(new OrderPlacedNotification($this->purchase));
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(24);
    }
}
