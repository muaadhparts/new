<?php

namespace App\Domain\Shipping\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Shipping\Models\ShipmentTracking;
use App\Domain\Shipping\Services\TryotoService;

/**
 * Create Shipment Job
 *
 * Creates shipment with courier API.
 */
class CreateShipmentJob implements ShouldQueue
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
    public function handle(TryotoService $tryotoService): void
    {
        // Create shipment with courier
        $shipmentData = $tryotoService->createShipment([
            'order_id' => $this->purchase->order_number,
            'customer_name' => $this->purchase->customer_name,
            'customer_phone' => $this->purchase->customer_phone,
            'customer_address' => $this->purchase->customer_address,
            'customer_city' => $this->purchase->customer_city,
            'weight' => $this->calculateWeight(),
        ]);

        // Create tracking record
        ShipmentTracking::create([
            'purchase_id' => $this->purchase->id,
            'tracking_number' => $shipmentData['tracking_number'] ?? null,
            'courier_id' => $shipmentData['courier_id'] ?? null,
            'status' => 'pending',
            'tracking_history' => [[
                'status' => 'created',
                'timestamp' => now()->toISOString(),
                'notes' => 'Shipment created',
            ]],
        ]);

        // Update purchase
        $this->purchase->update([
            'tracking_number' => $shipmentData['tracking_number'] ?? null,
            'status' => 'shipped',
        ]);
    }

    /**
     * Calculate total weight
     */
    protected function calculateWeight(): float
    {
        // Default weight calculation
        $items = $this->purchase->getCartItems();
        $totalQuantity = array_sum(array_column($items, 'quantity'));

        return $totalQuantity * 0.5; // 0.5kg per item average
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('CreateShipmentJob failed', [
            'purchase_id' => $this->purchase->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
