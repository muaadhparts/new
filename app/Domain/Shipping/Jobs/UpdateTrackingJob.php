<?php

namespace App\Domain\Shipping\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Shipping\Models\ShipmentTracking;
use App\Domain\Shipping\Services\TryotoService;
use App\Domain\Shipping\Events\ShipmentStatusChangedEvent;

/**
 * Update Tracking Job
 *
 * Updates shipment tracking from courier API.
 */
class UpdateTrackingJob implements ShouldQueue
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
    public function handle(TryotoService $tryotoService): void
    {
        if (!$this->tracking->tracking_number) {
            return;
        }

        // Get tracking info from courier
        $trackingInfo = $tryotoService->getTracking($this->tracking->tracking_number);

        if (!$trackingInfo) {
            return;
        }

        $oldStatus = $this->tracking->status;
        $newStatus = $trackingInfo['status'] ?? $oldStatus;

        // Update tracking history
        $history = $this->tracking->tracking_history ?? [];
        if (isset($trackingInfo['events'])) {
            foreach ($trackingInfo['events'] as $event) {
                $history[] = [
                    'status' => $event['status'],
                    'location' => $event['location'] ?? null,
                    'timestamp' => $event['timestamp'],
                    'notes' => $event['description'] ?? null,
                ];
            }
        }

        $this->tracking->update([
            'status' => $newStatus,
            'tracking_history' => $history,
            'last_checked' => now(),
            'delivered_at' => $newStatus === 'delivered' ? now() : $this->tracking->delivered_at,
        ]);

        // Fire event if status changed
        if ($oldStatus !== $newStatus) {
            event(new ShipmentStatusChangedEvent($this->tracking, $oldStatus, $newStatus));
        }
    }
}
