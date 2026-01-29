<?php

namespace App\Domain\Shipping\Services;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Identity\Models\Courier;
use App\Domain\Shipping\Models\City;
use App\Domain\Shipping\Models\CourierServiceArea;
use App\Domain\Shipping\Models\DeliveryCourier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * CourierAssignmentService
 * 
 * Handles courier finding and assignment logic
 * 
 * Responsibilities:
 * - Find available couriers for a city
 * - Assign courier to purchase
 * - Validate assignments
 * - Confirm handover to courier
 */
class CourierAssignmentService
{
    /**
     * Find available couriers for a city
     * 
     * @param string $cityName City name (English only)
     * @return Collection
     */
    public function findAvailableCouriers(string $cityName): Collection
    {
        // Find city by name or ID
        $city = City::where('id', $cityName)
            ->orWhere('name', $cityName)
            ->first();

        if (!$city) {
            return collect();
        }

        // Get service areas for this city with active couriers
        return CourierServiceArea::where('city_id', $city->id)
            ->whereHas('courier', function($q) {
                $q->where('status', 1);
            })
            ->with('courier')
            ->get();
    }

    /**
     * Assign courier to purchase
     * 
     * @param int $purchaseId
     * @param int $courierId
     * @param int $serviceAreaId
     * @param int $merchantId
     * @param int $merchantBranchId
     * @return DeliveryCourier
     * @throws \Exception
     */
    public function assignCourierToPurchase(
        int $purchaseId,
        int $courierId,
        int $serviceAreaId,
        int $merchantId,
        int $merchantBranchId
    ): DeliveryCourier {
        $purchase = Purchase::findOrFail($purchaseId);
        $courier = Courier::findOrFail($courierId);
        $serviceArea = CourierServiceArea::findOrFail($serviceAreaId);

        // Validate assignment
        if (!$this->validateAssignment($purchase, $courier, $merchantId)) {
            throw new \Exception(__('Invalid courier assignment'));
        }

        // Check if already assigned
        $existing = DeliveryCourier::where('purchase_id', $purchaseId)
            ->where('merchant_id', $merchantId)
            ->first();

        if ($existing) {
            throw new \Exception(__('Courier already assigned to this purchase'));
        }

        // Create delivery courier record
        $delivery = DeliveryCourier::create([
            'purchase_id' => $purchaseId,
            'merchant_id' => $merchantId,
            'courier_id' => $courierId,
            'service_area_id' => $serviceAreaId,
            'merchant_branch_id' => $merchantBranchId,
            'status' => DeliveryCourier::STATUS_PENDING,
        ]);

        // Update merchant purchase status
        $purchase->merchantPurchases()
            ->where('user_id', $merchantId)
            ->update(['status' => 'processing']);

        // Add tracking entry
        $purchase->tracks()->create([
            'name' => __('Courier Assigned'),
            'text' => __('Courier :courier has been assigned for delivery', ['courier' => $courier->name])
        ]);

        Log::info('Courier assigned to purchase', [
            'purchase_id' => $purchaseId,
            'courier_id' => $courierId,
            'merchant_id' => $merchantId,
            'delivery_id' => $delivery->id
        ]);

        return $delivery;
    }

    /**
     * Validate courier assignment
     * 
     * @param Purchase $purchase
     * @param Courier $courier
     * @param int $merchantId
     * @return bool
     */
    public function validateAssignment(Purchase $purchase, Courier $courier, int $merchantId): bool
    {
        // Check if purchase belongs to merchant
        $merchantOrder = $purchase->merchantPurchases()
            ->where('user_id', $merchantId)
            ->first();

        if (!$merchantOrder) {
            Log::warning('Purchase does not belong to merchant', [
                'purchase_id' => $purchase->id,
                'merchant_id' => $merchantId
            ]);
            return false;
        }

        // Check if courier is active
        if ($courier->status != 1) {
            Log::warning('Courier is not active', [
                'courier_id' => $courier->id,
                'status' => $courier->status
            ]);
            return false;
        }

        return true;
    }

    /**
     * Confirm handover to courier
     * 
     * @param int $purchaseId
     * @param int $merchantId
     * @return bool
     * @throws \Exception
     */
    public function confirmHandoverToCourier(int $purchaseId, int $merchantId): bool
    {
        $purchase = Purchase::findOrFail($purchaseId);

        // Verify purchase belongs to merchant
        $merchantOrder = $purchase->merchantPurchases()
            ->where('user_id', $merchantId)
            ->first();

        if (!$merchantOrder) {
            throw new \Exception(__('This purchase does not belong to you'));
        }

        // Get delivery courier
        $deliveryCourier = DeliveryCourier::where('purchase_id', $purchaseId)
            ->where('merchant_id', $merchantId)
            ->first();

        if (!$deliveryCourier) {
            throw new \Exception(__('No courier assigned to this purchase'));
        }

        // Check if order is ready for pickup
        if (!$deliveryCourier->isReadyForPickup()) {
            throw new \Exception(__('Order is not ready for pickup. Current status: ') . $deliveryCourier->status_label);
        }

        // Transition to picked_up
        $deliveryCourier->confirmHandoverToCourier();

        // Update merchant purchase status
        $merchantOrder->status = 'processing';
        $merchantOrder->save();

        Log::info('Merchant confirmed handover to courier', [
            'delivery_courier_id' => $deliveryCourier->id,
            'purchase_id' => $purchase->id,
            'courier_id' => $deliveryCourier->courier_id,
            'courier_name' => $deliveryCourier->courier->name ?? 'N/A',
        ]);

        // Add tracking entry
        $purchase->tracks()->create([
            'name' => __('Picked Up by Courier'),
            'text' => __('Order has been handed over to courier :courier for delivery', [
                'courier' => $deliveryCourier->courier->name ?? 'Courier'
            ])
        ]);

        return true;
    }

    /**
     * Get courier assignment status
     * 
     * @param int $purchaseId
     * @param int $merchantId
     * @return array|null
     */
    public function getAssignmentStatus(int $purchaseId, int $merchantId): ?array
    {
        $delivery = DeliveryCourier::where('purchase_id', $purchaseId)
            ->where('merchant_id', $merchantId)
            ->with('courier', 'servicearea', 'merchantBranch')
            ->first();

        if (!$delivery) {
            return null;
        }

        return [
            'courier_name' => $delivery->courier->name ?? 'N/A',
            'service_area' => $delivery->servicearea->name ?? 'N/A',
            'branch' => $delivery->merchantBranch->location ?? 'N/A',
            'status' => $delivery->status,
            'status_label' => $delivery->status_label ?? $delivery->status,
            'can_handover' => $delivery->isReadyForPickup(),
        ];
    }
}
