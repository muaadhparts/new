<?php

namespace App\Domain\Shipping\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Shipping\Models\ShipmentTracking;
use App\Domain\Shipping\Services\TryotoService;

/**
 * Update Tracking Status Command
 *
 * Updates shipment tracking status from courier APIs.
 */
class UpdateTrackingStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'shipping:update-tracking
                            {--tracking= : Specific tracking number to update}
                            {--status= : Filter by current status}
                            {--limit=100 : Maximum shipments to update}';

    /**
     * The console command description.
     */
    protected $description = 'Update shipment tracking status from courier APIs';

    /**
     * Execute the console command.
     */
    public function handle(TryotoService $tryotoService): int
    {
        $trackingNumber = $this->option('tracking');
        $status = $this->option('status');
        $limit = (int) $this->option('limit');

        if ($trackingNumber) {
            return $this->updateSingle($trackingNumber, $tryotoService);
        }

        return $this->updateMultiple($status, $limit, $tryotoService);
    }

    /**
     * Update single tracking
     */
    protected function updateSingle(string $trackingNumber, TryotoService $service): int
    {
        $tracking = ShipmentTracking::where('tracking_number', $trackingNumber)->first();

        if (!$tracking) {
            $this->error("Tracking #{$trackingNumber} not found.");
            return self::FAILURE;
        }

        $this->updateTracking($tracking, $service);
        $this->info("Updated tracking #{$trackingNumber}");

        return self::SUCCESS;
    }

    /**
     * Update multiple trackings
     */
    protected function updateMultiple(?string $status, int $limit, TryotoService $service): int
    {
        $this->info('Fetching active shipments...');

        $query = ShipmentTracking::whereNotIn('status', ['delivered', 'cancelled', 'returned']);

        if ($status) {
            $query->where('status', $status);
        }

        $trackings = $query->limit($limit)->get();

        if ($trackings->isEmpty()) {
            $this->info('No active shipments to update.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($trackings->count());
        $updated = 0;

        foreach ($trackings as $tracking) {
            try {
                $this->updateTracking($tracking, $service);
                $updated++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->warn("Failed to update {$tracking->tracking_number}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Updated {$updated} of {$trackings->count()} shipments.");

        return self::SUCCESS;
    }

    /**
     * Update single tracking record
     */
    protected function updateTracking(ShipmentTracking $tracking, TryotoService $service): void
    {
        // In real implementation, this would call the courier API
        // For now, just update the last_checked timestamp
        $tracking->update([
            'last_checked' => now(),
        ]);
    }
}
