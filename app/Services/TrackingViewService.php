<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\ShipmentTracking;
use App\Models\DeliveryCourier;

/**
 * Service to prepare tracking data for views
 *
 * Returns PURE DTO/ViewModel - no models, no relationships, no logic.
 * Blade files receive ONLY display-ready strings and boolean flags.
 *
 * Architecture:
 * - Controller calls this service
 * - Service queries database and computes ALL display values
 * - Blade receives flat arrays with strings/booleans only
 * - Blade does ZERO logic - only rendering
 */
class TrackingViewService
{
    /**
     * Prepare tracking data for merchant views (single merchant)
     * Returns pure DTO - no models
     */
    public function forMerchant(Purchase $purchase, int $merchantId): array
    {
        $shipment = ShipmentTracking::getLatestForPurchase($purchase->id, $merchantId);
        $delivery = DeliveryCourier::where('purchase_id', $purchase->id)
            ->where('merchant_id', $merchantId)
            ->with(['courier', 'merchantLocation', 'servicearea'])
            ->first();
        $customerChoice = $purchase->getCustomerShippingChoice($merchantId);

        return [
            // Flags for conditional rendering
            'hasTracking' => (bool)($shipment || $delivery || $customerChoice),
            'hasShipment' => (bool)$shipment,
            'hasDelivery' => (bool)$delivery,
            'hasCustomerChoice' => (bool)$customerChoice,

            // Shipment display values (all strings/primitives)
            'trackingNumber' => $shipment?->tracking_number ?? null,
            'companyName' => $shipment?->company_name ?? null,
            'status' => $shipment?->status ?? null,
            'statusAr' => $shipment?->status_ar ?? null,
            'statusLabel' => $shipment?->status_label ?? null,
            'statusDisplay' => $shipment?->status_ar ?? $shipment?->status_label ?? null,
            'statusColor' => $shipment?->status_color ?? 'secondary',
            'statusIcon' => $shipment?->status_icon ?? 'fas fa-box',
            'progressPercent' => $shipment?->progress_percent ?? 0,
            'location' => $shipment?->location ?? null,
            'message' => $shipment?->message ?? null,
            'messageAr' => $shipment?->message_ar ?? null,
            'hasMessage' => !empty($shipment?->message_ar) || !empty($shipment?->message),
            'messageDisplay' => $shipment?->message_ar ?? $shipment?->message ?? null,
            'occurredAt' => $shipment?->occurred_at?->format('Y-m-d H:i') ?? null,
            'occurredAtHuman' => $shipment?->occurred_at?->diffForHumans() ?? null,

            // Delivery display values (all strings/primitives)
            'courierName' => $delivery?->courier?->name ?? null,
            'courierPhone' => $delivery?->courier?->phone ?? null,
            'warehouseLocation' => $delivery?->merchantLocation?->location ?? null,
            'deliveryFee' => (float)($delivery?->delivery_fee ?? 0),
            'deliveryStatus' => $delivery?->status ?? null,
            'deliveryStatusLabel' => $delivery?->status_label ?? null,
            'deliveredAtFormatted' => $delivery?->delivered_at?->format('Y-m-d H:i') ?? null,
            'confirmedAtFormatted' => $delivery?->confirmed_at?->format('Y-m-d H:i') ?? null,

            // Delivery status flags (pre-computed booleans)
            'isDeliveryPending' => $delivery ? $delivery->isPendingApproval() : false,
            'isDeliveryApproved' => $delivery ? $delivery->isApproved() : false,
            'isDeliveryReadyForPickup' => $delivery ? $delivery->isReadyForPickup() : false,
            'isDeliveryPickedUp' => $delivery ? $delivery->isPickedUp() : false,
            'isDeliveryDelivered' => $delivery ? $delivery->isDelivered() : false,
            'isDeliveryConfirmed' => $delivery ? $delivery->isConfirmed() : false,
            'isDeliveryRejected' => $delivery ? $delivery->isRejected() : false,
            'customerConfirmed' => (bool)($delivery?->customer_confirmed ?? false),
            'deliveryStatusBadgeColor' => $this->getDeliveryStatusBadgeColor($delivery),

            // Delivery extras
            'hasCourierPhone' => !empty($delivery?->courier?->phone),
            'hasWarehouseLocation' => !empty($delivery?->merchantLocation?->location),
            'paymentMethod' => $delivery?->payment_method ?? null,
            'isCod' => ($delivery?->payment_method ?? null) === 'cod',
            'purchaseAmount' => (float)($delivery?->purchase_amount ?? 0),

            // Customer choice display values
            'customerChoiceCompany' => $customerChoice['company_name'] ?? null,
            'customerChoicePrice' => isset($customerChoice['price']) ? (float)$customerChoice['price'] : null,
            'customerChoiceProvider' => $customerChoice['provider'] ?? null,
            'customerChoiceName' => $customerChoice['name'] ?? null,
            'customerChoiceIsTryoto' => ($customerChoice['provider'] ?? '') === 'tryoto',
            'customerChoiceNameDisplay' => $customerChoice['name'] ?? $customerChoice['provider'] ?? 'Manual',
            'customerChoicePriceFormatted' => isset($customerChoice['price']) ? number_format((float)$customerChoice['price'], 2) : null,
        ];
    }

    /**
     * Prepare tracking data for customer/operator views (all merchants)
     * Returns pure DTO arrays - no models
     */
    public function forPurchase(Purchase $purchase): array
    {
        // Get latest tracking per merchant
        $trackings = ShipmentTracking::where('purchase_id', $purchase->id)
            ->whereIn('id', function ($sub) use ($purchase) {
                $sub->selectRaw('MAX(id)')
                    ->from('shipment_trackings')
                    ->where('purchase_id', $purchase->id)
                    ->groupBy('merchant_id');
            })
            ->with('merchant:id,name,shop_name')
            ->orderBy('occurred_at', 'desc')
            ->get();

        // Get deliveries with eager loaded relationships
        $deliveries = DeliveryCourier::where('purchase_id', $purchase->id)
            ->with(['courier', 'merchantLocation', 'merchant', 'servicearea'])
            ->get();

        // Build pure DTO arrays for trackings
        $trackingsList = [];
        foreach ($trackings as $tracking) {
            $trackingsList[] = [
                'merchantId' => $tracking->merchant_id,
                'merchantName' => $tracking->merchant->shop_name ?? $tracking->merchant->name ?? 'N/A',
                'trackingNumber' => $tracking->tracking_number,
                'companyName' => $tracking->company_name,
                'integrationType' => $tracking->integration_type,
                'isApiType' => $tracking->integration_type === 'api',
                'status' => $tracking->status,
                'statusAr' => $tracking->status_ar,
                'statusLabel' => $tracking->status_label,
                'statusDisplay' => $tracking->status_ar ?? $tracking->status_label,
                'statusColor' => $tracking->status_color,
                'statusIcon' => $tracking->status_icon,
                'progressPercent' => $tracking->progress_percent ?? 0,
                'location' => $tracking->location,
                'message' => $tracking->message,
                'messageAr' => $tracking->message_ar,
                'hasMessage' => !empty($tracking->message_ar) || !empty($tracking->message),
                'messageDisplay' => $tracking->message_ar ?? $tracking->message,
                'occurredAt' => $tracking->occurred_at?->format('Y-m-d H:i'),
                'occurredAtHuman' => $tracking->occurred_at?->diffForHumans(),
                // Status flags
                'isDelivered' => $tracking->status === ShipmentTracking::STATUS_DELIVERED,
                'isInTransit' => $tracking->status === ShipmentTracking::STATUS_IN_TRANSIT,
                'isOutForDelivery' => $tracking->status === ShipmentTracking::STATUS_OUT_FOR_DELIVERY,
                'isPending' => in_array($tracking->status, [ShipmentTracking::STATUS_CREATED, ShipmentTracking::STATUS_PICKED_UP]),
            ];
        }

        // Build pure DTO arrays for deliveries
        $deliveriesList = [];
        foreach ($deliveries as $delivery) {
            $merchant = $delivery->merchant;
            $nextAction = $delivery->next_action ?? ['actor' => 'none', 'action' => ''];

            $deliveriesList[] = [
                'merchantId' => $delivery->merchant_id,
                'merchantName' => $merchant->shop_name ?? $merchant->name ?? 'N/A',
                'courierName' => $delivery->courier->name ?? 'N/A',
                'courierPhone' => $delivery->courier->phone ?? null,
                'hasCourierPhone' => !empty($delivery->courier->phone),
                'warehouseLocation' => $delivery->merchantLocation->location ?? null,
                'hasWarehouseLocation' => !empty($delivery->merchantLocation->location),
                'deliveryFee' => (float)($delivery->delivery_fee ?? 0),
                'status' => $delivery->status,
                'statusLabel' => $delivery->status_label,
                'serviceArea' => $delivery->servicearea->name ?? '-',
                'updatedAt' => $delivery->updated_at?->format('Y-m-d H:i'),
                'deliveredAt' => $delivery->delivered_at?->format('Y-m-d H:i'),
                'confirmedAt' => $delivery->confirmed_at?->format('Y-m-d H:i'),
                'customerConfirmed' => (bool)($delivery->customer_confirmed ?? false),
                // Status flags (pre-computed booleans)
                'isPending' => $delivery->isPendingApproval(),
                'isApproved' => $delivery->isApproved(),
                'isReadyForPickup' => $delivery->isReadyForPickup(),
                'isPickedUp' => $delivery->isPickedUp(),
                'isDelivered' => $delivery->isDelivered(),
                'isConfirmed' => $delivery->isConfirmed(),
                'isRejected' => $delivery->isRejected(),
                // Combined flags for common conditions
                'isConfirmedOrDelivered' => $delivery->isConfirmed() || $delivery->isDelivered(),
                'showConfirmButton' => $delivery->isDelivered() && !$delivery->customer_confirmed,
                'showConfirmedBadge' => $delivery->isConfirmed() || $delivery->customer_confirmed,

                // Workflow data for progress indicator
                'workflowStep' => $delivery->workflow_step ?? 1,
                'rejectionReason' => $delivery->rejection_reason ?? null,
                'approvedAt' => $delivery->approved_at?->format('d/m H:i'),
                'readyAt' => $delivery->ready_at?->format('d/m H:i'),
                'pickedUpAt' => $delivery->picked_up_at?->format('d/m H:i'),
                'deliveredAtShort' => $delivery->delivered_at?->format('d/m H:i'),
                'confirmedAtShort' => $delivery->confirmed_at?->format('d/m H:i'),
                'isCod' => $delivery->isCod(),
                'codAmount' => (float)($delivery->cod_amount ?? $delivery->purchase_amount ?? 0),

                // Next action info
                'nextActionActor' => $nextAction['actor'] ?? 'none',
                'nextActionText' => $nextAction['action'] ?? '',
                'hasNextAction' => ($nextAction['actor'] ?? 'none') !== 'none',
            ];
        }

        // Get first delivery for simple display (user details page)
        $firstDelivery = $deliveriesList[0] ?? null;

        return [
            // Arrays for iteration
            'trackings' => $trackingsList,
            'deliveries' => $deliveriesList,

            // Counts
            'trackingsCount' => count($trackingsList),
            'deliveriesCount' => count($deliveriesList),

            // Flags
            'hasData' => count($trackingsList) > 0 || count($deliveriesList) > 0,
            'hasTrackings' => count($trackingsList) > 0,
            'hasDeliveries' => count($deliveriesList) > 0,
            'hasLocalCourier' => count($deliveriesList) > 0 && !empty($deliveriesList[0]['courierName']),

            // First delivery for simple display (backward compatible)
            'firstDelivery' => $firstDelivery,
        ];
    }

    /**
     * Prepare tracking summary for a list of purchases (purchase tracking page)
     * Returns minimal DTO for list display - optimized for multiple purchases
     *
     * @param \Illuminate\Database\Eloquent\Collection $purchases
     * @return array Keyed by purchase_id
     */
    public function forPurchasesList($purchases): array
    {
        if ($purchases->isEmpty()) {
            return [];
        }

        $purchaseIds = $purchases->pluck('id')->toArray();

        // Get latest tracking per purchase (one query for all purchases)
        $latestTrackings = ShipmentTracking::whereIn('purchase_id', $purchaseIds)
            ->whereIn('id', function ($sub) use ($purchaseIds) {
                $sub->selectRaw('MAX(id)')
                    ->from('shipment_trackings')
                    ->whereIn('purchase_id', $purchaseIds)
                    ->groupBy('purchase_id');
            })
            ->get()
            ->keyBy('purchase_id');

        // Build DTO for each purchase
        $result = [];
        foreach ($purchases as $purchase) {
            $tracking = $latestTrackings->get($purchase->id);

            $result[$purchase->id] = [
                // Purchase info (pre-formatted strings)
                'purchaseId' => $purchase->id,
                'purchaseNumber' => $purchase->purchase_number,
                'status' => $purchase->status,
                'statusLabel' => ucfirst($purchase->status),
                'statusColor' => $this->getPurchaseStatusColor($purchase->status),
                'createdAtFormatted' => $purchase->created_at->format('d M Y'),
                'createdAtTime' => $purchase->created_at->format('h:i A'),
                'payAmount' => number_format($purchase->pay_amount, 2),
                'currencySign' => $purchase->currency_sign,

                // Tracking info (pre-computed from model)
                'hasTracking' => (bool) $tracking,
                'trackingNumber' => $tracking?->tracking_number,
                'trackingStatus' => $tracking?->status,
                'trackingStatusAr' => $tracking?->status_ar ?? __('Awaiting Shipment'),
                'trackingStatusColor' => $tracking?->status_color ?? 'secondary',
                'trackingStatusIcon' => $tracking?->status_icon ?? 'fas fa-clock',
                'trackingOccurredAtHuman' => $tracking?->occurred_at?->diffForHumans(),
                'companyName' => $tracking?->company_name,

                // Flags for conditional rendering
                'isCompleted' => $purchase->status === 'completed',
                'isPending' => $purchase->status === 'pending',
                'isDelivered' => $tracking?->status === ShipmentTracking::STATUS_DELIVERED,
            ];
        }

        return $result;
    }

    /**
     * Get color for purchase status badge
     */
    private function getPurchaseStatusColor(string $status): string
    {
        return match ($status) {
            'completed' => 'success',
            'pending' => 'warning',
            'processing' => 'info',
            'on delivery' => 'primary',
            'declined' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get badge color for delivery status (pre-computed for Blade)
     */
    private function getDeliveryStatusBadgeColor($delivery): string
    {
        if (!$delivery) {
            return 'secondary';
        }

        if ($delivery->isConfirmed()) {
            return 'success';
        }
        if ($delivery->isPickedUp()) {
            return 'primary';
        }
        if ($delivery->isRejected()) {
            return 'danger';
        }
        if ($delivery->isPendingApproval()) {
            return 'warning';
        }

        return 'info';
    }
}
