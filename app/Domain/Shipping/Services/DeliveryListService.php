<?php

namespace App\Domain\Shipping\Services;

use App\Domain\Commerce\Models\Purchase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * DeliveryListService
 * 
 * Handles delivery list queries and data preparation
 * 
 * Responsibilities:
 * - Get delivery list with eager loading
 * - Prepare delivery data for views
 * - Get DataTables data
 * - Optimize queries (no N+1)
 */
class DeliveryListService
{
    protected DeliveryDisplayService $displayService;

    public function __construct(DeliveryDisplayService $displayService)
    {
        $this->displayService = $displayService;
    }

    /**
     * Get delivery list for merchant
     * 
     * Uses eager loading to avoid N+1 queries
     * 
     * @param int $merchantId
     * @return Collection
     */
    public function getDeliveryList(int $merchantId): Collection
    {
        $purchases = Purchase::orderBy('id', 'desc')
            ->whereHas('merchantPurchases', function ($query) use ($merchantId) {
                $query->where('user_id', $merchantId);
            })
            ->with([
                // Eager load merchant purchases for this merchant
                'merchantPurchases' => function ($query) use ($merchantId) {
                    $query->where('user_id', $merchantId);
                },
                // Eager load delivery couriers for this merchant
                'deliveryCouriers' => function ($query) use ($merchantId) {
                    $query->where('merchant_id', $merchantId)->with('courier');
                },
                // Eager load shipment trackings for this merchant
                'shipmentTrackings' => function ($query) use ($merchantId) {
                    $query->where('merchant_id', $merchantId)
                          ->orderBy('occurred_at', 'desc');
                },
            ])
            ->get();

        // Log if no purchases found
        if ($purchases->isEmpty()) {
            Log::info('Merchant Delivery: No purchases found for merchant', [
                'merchant_id' => $merchantId,
                'tip' => 'Check if merchant_purchases table has records with this user_id'
            ]);
        } else {
            Log::debug('Merchant Delivery: Found purchases', [
                'merchant_id' => $merchantId,
                'purchase_count' => $purchases->count()
            ]);
        }

        return $purchases;
    }

    /**
     * Prepare delivery data for list view
     * 
     * Pre-computes all display data
     * 
     * @param Collection $purchases
     * @param int $merchantId
     * @return array
     */
    public function prepareDeliveryData(Collection $purchases, int $merchantId): array
    {
        $purchaseData = [];

        foreach ($purchases as $purchase) {
            $purchaseData[$purchase->id] = $this->displayService->formatDeliveryForList($purchase, $merchantId);
        }

        return $purchaseData;
    }

    /**
     * Get DataTables data for merchant
     * 
     * @param int $merchantId
     * @return Collection
     */
    public function getDataTablesData(int $merchantId): Collection
    {
        return Purchase::orderBy('id', 'desc')
            ->whereHas('merchantPurchases', function ($query) use ($merchantId) {
                $query->where('user_id', $merchantId);
            })
            ->with(['merchantPurchases' => function ($query) use ($merchantId) {
                $query->where('user_id', $merchantId);
            }])
            ->get();
    }

    /**
     * Get delivery statistics for merchant
     * 
     * @param int $merchantId
     * @return array
     */
    public function getDeliveryStatistics(int $merchantId): array
    {
        $purchases = Purchase::whereHas('merchantPurchases', function ($query) use ($merchantId) {
            $query->where('user_id', $merchantId);
        })->get();

        $total = $purchases->count();
        $withCourier = $purchases->filter(function ($purchase) use ($merchantId) {
            return $purchase->deliveryCouriers()
                ->where('merchant_id', $merchantId)
                ->exists();
        })->count();

        $withShipment = $purchases->filter(function ($purchase) use ($merchantId) {
            return $purchase->shipmentTrackings()
                ->where('merchant_id', $merchantId)
                ->exists();
        })->count();

        $pending = $total - $withCourier - $withShipment;

        return [
            'total' => $total,
            'with_courier' => $withCourier,
            'with_shipment' => $withShipment,
            'pending' => max(0, $pending),
        ];
    }
}
