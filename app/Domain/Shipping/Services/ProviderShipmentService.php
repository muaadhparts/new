<?php

namespace App\Domain\Shipping\Services;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Shipping\Models\Shipping;
use App\Domain\Shipping\Models\ShipmentTracking;
use App\Domain\Shipping\Services\ShipmentTrackingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * ProviderShipmentService
 * 
 * Handles manual/provider shipping management
 * 
 * Responsibilities:
 * - Get available shipping providers
 * - Get shipping options for a provider
 * - Assign provider shipping to purchase
 * - Create manual shipments
 */
class ProviderShipmentService
{
    protected ShipmentTrackingService $trackingService;

    public function __construct(ShipmentTrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Get available shipping providers for merchant
     * 
     * Logic:
     * | user_id | operator    | Meaning                                    |
     * |---------|-------------|-------------------------------------------|
     * | 0       | 0           | Disabled - don't show                      |
     * | 0       | merchant_id | Platform shipping enabled for this merchant|
     * | merchant_id | 0       | Merchant's own shipping                    |
     * 
     * @param int $merchantId
     * @return array
     */
    public function getAvailableProviders(int $merchantId): array
    {
        $providers = Shipping::where('status', 1)
            ->where(function ($q) use ($merchantId) {
                $q->where('user_id', $merchantId)
                  ->orWhere(function ($q2) use ($merchantId) {
                      $q2->where('user_id', 0)
                         ->where('operator', $merchantId);
                  });
            })
            ->whereNotNull('provider')
            ->where('provider', '!=', '')
            ->select('provider')
            ->distinct()
            ->pluck('provider')
            ->toArray();

        // Provider display names
        $providerLabels = [
            'tryoto' => __('Smart Shipping (Tryoto)'),
            'manual' => __('Manual Shipping'),
            'Saudi' => __('Saudi Post'),
            'debts' => __('Debts Shipping'),
        ];

        // Provider icons
        $providerIcons = [
            'tryoto' => 'fas fa-shipping-fast',
            'manual' => 'fas fa-truck',
            'Saudi' => 'fas fa-mail-bulk',
            'debts' => 'fas fa-file-invoice-dollar',
        ];

        $result = [];
        foreach ($providers as $provider) {
            $result[] = [
                'key' => $provider,
                'label' => $providerLabels[$provider] ?? ucfirst($provider),
                'icon' => $providerIcons[$provider] ?? 'fas fa-box',
                'has_api' => ($provider === 'tryoto'),
            ];
        }

        return $result;
    }

    /**
     * Get shipping options for a specific provider
     * 
     * @param string $provider
     * @param int $merchantId
     * @return Collection
     */
    public function getProviderOptions(string $provider, int $merchantId): Collection
    {
        // For tryoto, return empty (options come from API)
        if ($provider === 'tryoto') {
            return collect();
        }

        return Shipping::forMerchant($merchantId)
            ->where('provider', $provider)
            ->orderBy('price', 'asc')
            ->get();
    }

    /**
     * Assign provider shipping to purchase
     * 
     * @param int $purchaseId
     * @param int $shippingId
     * @param string|null $trackingNumber
     * @param int $merchantId
     * @param int $merchantBranchId
     * @return ShipmentTracking
     * @throws \Exception
     */
    public function assignProviderShipping(
        int $purchaseId,
        int $shippingId,
        ?string $trackingNumber,
        int $merchantId,
        int $merchantBranchId
    ): ShipmentTracking {
        $purchase = Purchase::findOrFail($purchaseId);
        $shipping = Shipping::findOrFail($shippingId);

        // Verify purchase belongs to merchant
        $merchantOrder = $purchase->merchantPurchases()
            ->where('user_id', $merchantId)
            ->first();

        if (!$merchantOrder) {
            throw new \Exception(__('This purchase does not belong to you'));
        }

        // Check for existing shipment
        $existingShipment = ShipmentTracking::getLatestForPurchase($purchaseId, $merchantId);

        if ($existingShipment && !$existingShipment->is_final) {
            throw new \Exception(__('A shipment already exists for this purchase. Tracking: ') . $existingShipment->tracking_number);
        }

        // Validate branch
        $branch = \DB::table('merchant_branches')
            ->where('id', $merchantBranchId)
            ->where('user_id', $merchantId)
            ->where('status', 1)
            ->first();

        if (!$branch) {
            throw new \Exception(__('Invalid branch selected or branch is not active.'));
        }

        // Create manual shipment
        $shipment = $this->trackingService->createManualShipment(
            purchaseId: $purchaseId,
            merchantId: $merchantId,
            shippingId: $shippingId,
            trackingNumber: $trackingNumber,
            merchantBranchId: $merchantBranchId
        );

        // Update merchant purchase status
        $merchantOrder->status = 'processing';
        $merchantOrder->save();

        // Add tracking entry
        $purchase->tracks()->create([
            'name' => __('Shipment Created'),
            'text' => __('Shipment created with :provider. Tracking: :tracking', [
                'provider' => $shipping->provider,
                'tracking' => $trackingNumber ?? 'N/A'
            ])
        ]);

        Log::info('Provider shipping assigned', [
            'purchase_id' => $purchaseId,
            'merchant_id' => $merchantId,
            'provider' => $shipping->provider,
            'tracking_number' => $trackingNumber,
            'shipment_id' => $shipment->id
        ]);

        return $shipment;
    }

    /**
     * Check if provider has API integration
     * 
     * @param string $provider
     * @return bool
     */
    public function hasApiIntegration(string $provider): bool
    {
        return $provider === 'tryoto';
    }

    /**
     * Get shipment status for purchase
     * 
     * @param int $purchaseId
     * @param int $merchantId
     * @return array|null
     */
    public function getShipmentStatus(int $purchaseId, int $merchantId): ?array
    {
        $shipment = ShipmentTracking::getLatestForPurchase($purchaseId, $merchantId);

        if (!$shipment) {
            return null;
        }

        return [
            'has_shipment' => true,
            'tracking_number' => $shipment->tracking_number,
            'company' => $shipment->company_name,
            'status' => $shipment->status,
            'status_ar' => $shipment->status_ar,
            'occurred_at' => $shipment->occurred_at?->format('Y-m-d H:i'),
            'message' => $shipment->message_ar ?? $shipment->message,
        ];
    }
}
