<?php

namespace App\Domain\Shipping\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Shipping\Models\ShipmentTracking;
use App\Domain\Shipping\Notifications\DeliveryCompletedNotification;

/**
 * Send Delivery Notification Job
 *
 * Sends delivery confirmation to customer.
 */
class SendDeliveryNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ShipmentTracking $tracking
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $purchase = $this->tracking->purchase;

        if (!$purchase || !$purchase->user) {
            return;
        }

        $purchase->user->notify(new DeliveryCompletedNotification($this->tracking));

        // Update purchase status
        $purchase->update(['status' => 'delivered']);
    }
}
