<?php

namespace App\Domain\Shipping\Schedule;

use App\Domain\Shipping\Models\ShipmentTracking;
use Illuminate\Support\Facades\Log;

/**
 * Update Tracking Status Task
 *
 * Updates shipment tracking status from courier APIs.
 */
class UpdateTrackingStatusTask
{
    /**
     * Execute the task.
     */
    public function __invoke(): void
    {
        $activeShipments = ShipmentTracking::whereNotIn('status', ['delivered', 'failed', 'cancelled'])
            ->with('courier')
            ->get();

        $updated = 0;
        $failed = 0;

        foreach ($activeShipments as $shipment) {
            try {
                // In real implementation, call courier API here
                // $status = $this->fetchStatusFromCourier($shipment);

                // Simulated status update for stale shipments
                if ($shipment->updated_at->lt(now()->subHours(24))) {
                    // Mark as stale - needs manual review
                    $shipment->update([
                        'notes' => 'Status update required - no API response',
                    ]);
                }
                $updated++;
            } catch (\Exception $e) {
                Log::error('Failed to update shipment tracking', [
                    'shipment_id' => $shipment->id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        Log::info('Tracking status update completed', [
            'total' => $activeShipments->count(),
            'updated' => $updated,
            'failed' => $failed,
        ]);
    }

    /**
     * Get the schedule frequency.
     */
    public static function frequency(): string
    {
        return 'everyThirtyMinutes';
    }
}
