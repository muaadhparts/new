<?php

namespace App\Domain\Shipping\Services;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Shipping\Models\DeliveryCourier;
use App\Domain\Shipping\Models\ShipmentTracking;
use App\Helpers\PriceHelper;

/**
 * DeliveryDisplayService
 * 
 * Handles all delivery display data formatting
 * 
 * Responsibilities:
 * - Format delivery data for list view
 * - Format delivery data for DataTables
 * - Format shipment status
 * - Format courier info
 * - Pre-compute display data (no @php in views)
 */
class DeliveryDisplayService
{
    /**
     * Format delivery data for list view
     * 
     * Pre-computes all display data to avoid @php in views
     * 
     * @param Purchase $purchase
     * @param int $merchantId
     * @return array
     */
    public function formatDeliveryForList(Purchase $purchase, int $merchantId): array
    {
        // Get delivery courier for this merchant (should be eager loaded)
        $delivery = $purchase->deliveryCouriers->first();

        // Get latest shipment tracking for this merchant (should be eager loaded)
        $shipment = $purchase->shipmentTrackings->first();

        // Get customer's shipping choice from model accessor
        $customerChoice = $purchase->getCustomerShippingChoice($merchantId);

        // Calculate price from eager-loaded merchantPurchases
        $price = $purchase->merchantPurchases->sum('price');

        // PRE-COMPUTED: Display data for customerChoice (no @php in view)
        $customerChoiceDisplay = null;
        if ($customerChoice && !$shipment && !$delivery) {
            $customerChoiceDisplay = [
                'isFreeShipping' => $customerChoice['is_free_shipping'] ?? false,
                'originalPrice' => $customerChoice['original_price'] ?? $customerChoice['price'] ?? 0,
                'actualPrice' => $customerChoice['price'] ?? 0,
                'shippingName' => $customerChoice['company_name']
                    ?? $customerChoice['name']
                    ?? $customerChoice['courier_name']
                    ?? __('N/A'),
            ];
        }

        return [
            'delivery' => $delivery,
            'shipment' => $shipment,
            'customerChoice' => $customerChoice,
            'customerChoiceDisplay' => $customerChoiceDisplay,
            'price' => $price,
            'price_formatted' => PriceHelper::showOrderCurrencyPrice($price, $purchase->currency_sign),
            'date_formatted' => $purchase->created_at?->format('Y-m-d') ?? 'N/A',
        ];
    }

    /**
     * Format delivery data for DataTables
     * 
     * @param Purchase $purchase
     * @param int $merchantId
     * @return array
     */
    public function formatDeliveryForDataTable(Purchase $purchase, int $merchantId): array
    {
        $totalQty = $purchase->merchantPurchases()
            ->where('user_id', $merchantId)
            ->sum('qty');

        $price = $purchase->merchantPurchases()
            ->where('user_id', $merchantId)
            ->sum('price');

        return [
            'id' => $purchase->id,
            'purchase_number' => $purchase->purchase_number,
            'totalQty' => $totalQty,
            'customer_info' => $this->formatCustomerInfo($purchase),
            'couriers' => $this->formatCourierInfoForDataTable($purchase, $merchantId),
            'pay_amount' => PriceHelper::showOrderCurrencyPrice($price, $purchase->currency_sign),
            'action' => $this->formatActionButtons($purchase, $merchantId),
        ];
    }

    /**
     * Format customer info for DataTables
     * 
     * @param Purchase $purchase
     * @return string HTML
     */
    private function formatCustomerInfo(Purchase $purchase): string
    {
        return '<strong>' . __('Name') . ':</strong> ' . $purchase->customer_name . '<br>' .
            '<strong>' . __('Email') . ':</strong> ' . $purchase->customer_email . '<br>' .
            '<strong>' . __('Phone') . ':</strong> ' . $purchase->customer_phone . '<br>' .
            '<strong>' . __('Country') . ':</strong> ' . $purchase->customer_country . '<br>' .
            '<strong>' . __('City') . ':</strong> ' . $purchase->customer_city . '<br>' .
            '<strong>' . __('Postal Code') . ':</strong> ' . $purchase->customer_zip . '<br>' .
            '<strong>' . __('Address') . ':</strong> ' . $purchase->customer_address . '<br>' .
            '<strong>' . __('Purchase Date') . ':</strong> ' . $purchase->created_at->diffForHumans() . '<br>';
    }

    /**
     * Format courier info for DataTables
     * 
     * @param Purchase $purchase
     * @param int $merchantId
     * @return string HTML
     */
    private function formatCourierInfoForDataTable(Purchase $purchase, int $merchantId): string
    {
        $delivery = DeliveryCourier::where('purchase_id', $purchase->id)
            ->where('merchant_id', $merchantId)
            ->first();

        if ($delivery) {
            return '<strong class="display-5">Courier : ' . $delivery->courier->name . ' </br>Delivery Cost : ' . 
                PriceHelper::showAdminCurrencyPrice($delivery->servicearea->price) . '</br>
                Warehouse Location : ' . $delivery->merchantBranch->location . '</br>
                Status :
                <span class="badge badge-dark p-1">' . $delivery->status . '</span>
                </strong>';
        }

        return '<span class="badge badge-danger p-1">' . __('Not Assigned') . '</span>';
    }

    /**
     * Format action buttons for DataTables
     * 
     * @param Purchase $purchase
     * @param int $merchantId
     * @return string HTML
     */
    private function formatActionButtons(Purchase $purchase, int $merchantId): string
    {
        $delivery = DeliveryCourier::where('merchant_id', $merchantId)
            ->where('purchase_id', $purchase->id)
            ->first();

        if ($delivery && $delivery->status == DeliveryCourier::STATUS_DELIVERED) {
            return '<div class="action-list">
                <a href="' . route('merchant-purchase-show', $purchase->purchase_number) . '" class="btn btn-outline-primary btn-sm">
                    <i class="fa fa-eye"></i> ' . __('Purchase View') . '
                </a>
                </div>';
        }

        return '<div class="action-list">
            <button data-bs-toggle="modal" data-bs-target="#courierList" 
                customer-city="' . $purchase->customer_city . '" 
                purchase_id="' . $purchase->id . '" 
                class="mybtn1 searchDeliveryCourier">
                <i class="fa fa-user"></i>  ' . __("Assign Courier") . '
            </button>
            </div>';
    }

    /**
     * Format shipment status
     * 
     * @param ShipmentTracking $shipment
     * @return array
     */
    public function formatShipmentStatus(ShipmentTracking $shipment): array
    {
        return [
            'tracking_number' => $shipment->tracking_number,
            'company' => $shipment->company_name,
            'status' => $shipment->status,
            'status_ar' => $shipment->status_ar,
            'occurred_at' => $shipment->occurred_at?->format('Y-m-d H:i'),
            'message' => $shipment->message_ar ?? $shipment->message,
        ];
    }

    /**
     * Format courier info
     * 
     * @param DeliveryCourier $delivery
     * @return array
     */
    public function formatCourierInfo(DeliveryCourier $delivery): array
    {
        return [
            'courier_name' => $delivery->courier->name ?? 'N/A',
            'delivery_cost' => PriceHelper::showAdminCurrencyPrice($delivery->servicearea->price ?? 0),
            'warehouse_location' => $delivery->merchantBranch->location ?? 'N/A',
            'status' => $delivery->status,
            'status_label' => $delivery->status_label ?? $delivery->status,
        ];
    }
}
