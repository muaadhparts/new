<?php

namespace App\Domain\Shipping\Services;

use App\Domain\Shipping\Models\DeliveryCourier;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * CourierDisplayService - Centralized formatting for courier display
 *
 * API-Ready: All formatting in one place for Web and API consumption.
 * DATA FLOW POLICY: Controller → Service → DTO → View/API
 *
 * @see docs/rules/DATA_FLOW_POLICY.md
 */
class CourierDisplayService
{
    // =========================================================================
    // DELIVERY STATUS FORMATTING
    // =========================================================================

    /**
     * Get delivery status display data
     */
    public function getDeliveryStatusDisplay(DeliveryCourier $delivery): array
    {
        if ($delivery->isPendingApproval()) {
            return [
                'label' => __('Awaiting Approval'),
                'class' => 'bg-warning text-dark',
                'icon' => 'fa-clock',
            ];
        }
        if ($delivery->isApproved()) {
            return [
                'label' => __('Preparing'),
                'class' => 'bg-info',
                'icon' => 'fa-box-open',
            ];
        }
        if ($delivery->isReadyForPickup()) {
            return [
                'label' => __('Ready for Pickup'),
                'class' => 'bg-primary',
                'icon' => 'fa-truck-loading',
            ];
        }
        if ($delivery->isPickedUp()) {
            return [
                'label' => __('Picked Up'),
                'class' => 'bg-info',
                'icon' => 'fa-truck',
            ];
        }
        if ($delivery->isDelivered()) {
            return [
                'label' => __('Delivered'),
                'class' => 'bg-success',
                'icon' => 'fa-check-circle',
            ];
        }
        if ($delivery->isRejected()) {
            return [
                'label' => __('Rejected'),
                'class' => 'bg-danger',
                'icon' => 'fa-times-circle',
            ];
        }

        return [
            'label' => __('Unknown'),
            'class' => 'bg-secondary',
            'icon' => 'fa-question-circle',
        ];
    }

    // =========================================================================
    // DASHBOARD DISPLAY
    // =========================================================================

    /**
     * Format deliveries for dashboard display
     */
    public function formatDeliveriesForDashboard(Collection $deliveries): Collection
    {
        return $deliveries->map(function ($delivery) {
            $purchase = $delivery->purchase;
            $currencySign = $purchase->currency_sign ?? 'SAR';

            return [
                'id' => $delivery->id,
                'purchase_number' => $purchase->purchase_number ?? '-',
                'customer_city' => $purchase->customer_city ?? '-',
                'branch_location' => $delivery->merchantBranch->location ?? '-',
                'total_formatted' => \PriceHelper::showAdminCurrencyPrice(
                    (float)($delivery->purchase_amount ?? 0),
                    $currencySign
                ),
                'is_cod' => $delivery->payment_method === 'cod',
                'status' => $this->getDeliveryStatusDisplay($delivery),
                'details_url' => route('courier-purchase-details', $delivery->id),
            ];
        });
    }

    /**
     * Format courier report for dashboard display
     */
    public function formatReportForDashboard(array $report): array
    {
        return [
            'current_balance_formatted' => monetaryUnit()->format($report['current_balance'] ?? 0),
            'current_balance' => $report['current_balance'] ?? 0,
            'is_in_debt' => ($report['current_balance'] ?? 0) < 0,
            'has_credit' => ($report['current_balance'] ?? 0) > 0,
            'total_collected_formatted' => monetaryUnit()->format($report['total_collected'] ?? 0),
            'total_fees_earned_formatted' => monetaryUnit()->format($report['total_fees_earned'] ?? 0),
            'deliveries_count' => $report['deliveries_count'] ?? 0,
            'deliveries_completed' => $report['deliveries_completed'] ?? 0,
            'cod_deliveries' => $report['cod_deliveries'] ?? 0,
            'online_deliveries' => $report['online_deliveries'] ?? 0,
            'deliveries_pending' => $report['deliveries_pending'] ?? 0,
            'unsettled_deliveries' => $report['unsettled_deliveries'] ?? 0,
        ];
    }

    // =========================================================================
    // ORDERS DISPLAY
    // =========================================================================

    /**
     * Format deliveries for orders list
     */
    public function formatDeliveriesForOrders(LengthAwarePaginator $deliveries): LengthAwarePaginator
    {
        $deliveries->through(function ($delivery) {
            $purchase = $delivery->purchase;
            $merchant = $delivery->merchant;

            return [
                'id' => $delivery->id,
                'purchase_id' => $delivery->purchase_id,
                'status' => $delivery->status,
                'purchase_number' => $purchase->purchase_number ?? 'N/A',
                'created_at_formatted' => $delivery->created_at?->format('Y-m-d H:i') ?? 'N/A',
                'merchant_name' => $merchant->shop_name ?? $merchant->name ?? 'N/A',
                'merchant_phone' => $merchant?->shop_phone,
                'branch_location' => $delivery->merchantBranch?->location
                    ? \Illuminate\Support\Str::limit($delivery->merchantBranch->location, 25)
                    : null,
                'customer_name' => $purchase->customer_name ?? 'N/A',
                'customer_phone' => $purchase->customer_phone ?? 'N/A',
                'customer_city' => $purchase->customer_city ?? 'N/A',
                'delivery_fee_formatted' => \PriceHelper::showAdminCurrencyPrice($delivery->delivery_fee ?? 0),
                'purchase_amount_formatted' => \PriceHelper::showAdminCurrencyPrice($delivery->purchase_amount ?? 0),
                'is_cod' => $delivery->isCod(),
                'delivered_at_formatted' => $delivery->delivered_at?->format('Y-m-d H:i'),
                'status_display' => $this->getDeliveryStatusDisplay($delivery),
            ];
        });

        return $deliveries;
    }

    // =========================================================================
    // ORDER DETAILS DISPLAY
    // =========================================================================

    /**
     * Build delivery DTO for order details/workflow display
     */
    public function buildDeliveryDto(DeliveryCourier $delivery): array
    {
        $nextAction = $delivery->next_action ?? ['actor' => 'none', 'action' => ''];
        $step = $delivery->workflow_step ?? 1;

        return [
            'isRejected' => $delivery->isRejected(),
            'rejectionReason' => $delivery->rejection_reason ?? null,
            'workflowStep' => $step,
            'progressPercent' => $this->calculateWorkflowProgress($step),
            'stepsDisplay' => $this->buildWorkflowStepsDisplay($step),
            'approvedAt' => $delivery->approved_at?->format('d/m H:i'),
            'readyAt' => $delivery->ready_at?->format('d/m H:i'),
            'pickedUpAt' => $delivery->picked_up_at?->format('d/m H:i'),
            'deliveredAtShort' => $delivery->delivered_at?->format('d/m H:i'),
            'confirmedAtShort' => $delivery->confirmed_at?->format('d/m H:i'),
            'isCod' => $delivery->isCod(),
            'codAmount' => (float)($delivery->cod_amount ?? $delivery->purchase_amount ?? 0),
            'hasNextAction' => ($nextAction['actor'] ?? 'none') !== 'none',
            'nextActionActor' => $nextAction['actor'] ?? 'none',
            'nextActionText' => $nextAction['action'] ?? '',
        ];
    }

    /**
     * Format delivery details for display
     */
    public function formatDeliveryDetails(DeliveryCourier $delivery): array
    {
        $purchase = $delivery->purchase;
        $merchant = $delivery->merchant;
        $currencySign = $purchase->currency_sign;

        return [
            'id' => $delivery->id,
            'merchant_name' => $merchant->shop_name ?? $merchant->name ?? 'N/A',
            'merchant_phone' => $merchant?->phone,
            'merchant_address' => $merchant?->address,
            'branch_location' => $delivery->merchantBranch?->location,
            'delivered_at_formatted' => $delivery->delivered_at?->format('Y-m-d H:i'),
            'cod_amount_formatted' => \PriceHelper::showAdminCurrencyPrice($delivery->cod_amount, $currencySign),
            'delivery_fee_formatted' => \PriceHelper::showAdminCurrencyPrice($delivery->delivery_fee, $currencySign),
        ];
    }

    /**
     * Format cart items for delivery display
     */
    public function formatCartItemsForDelivery(array $cartItems, int $merchantId): Collection
    {
        return collect($cartItems)->filter(function ($item) use ($merchantId) {
            return ($item['user_id'] ?? null) == $merchantId;
        })->map(function ($item) {
            return [
                'id' => $item['item']['id'] ?? 'N/A',
                'name' => isset($item['item']) ? getLocalizedCatalogItemName($item['item'], 50) : 'N/A',
                'qty' => $item['qty'] ?? 1,
            ];
        })->values();
    }

    /**
     * Calculate workflow progress percent
     */
    private function calculateWorkflowProgress(int $step): int
    {
        return match (true) {
            $step >= 6 => 100,
            $step >= 5 => 80,
            $step >= 4 => 60,
            $step >= 3 => 40,
            $step >= 2 => 20,
            default => 0,
        };
    }

    /**
     * Build workflow steps display array
     */
    private function buildWorkflowStepsDisplay(int $currentStep): array
    {
        $stepDefinitions = [
            ['key' => 'pending_approval', 'label' => __('Approval'), 'icon' => 'fa-clock', 'description' => __('Courier Approval'), 'step' => 1],
            ['key' => 'approved', 'label' => __('Preparing'), 'icon' => 'fa-box-open', 'description' => __('Merchant Preparing'), 'step' => 2],
            ['key' => 'ready_for_pickup', 'label' => __('Ready'), 'icon' => 'fa-box', 'description' => __('Ready for Pickup'), 'step' => 3],
            ['key' => 'picked_up', 'label' => __('Picked Up'), 'icon' => 'fa-handshake', 'description' => __('Courier Picked Up'), 'step' => 4],
            ['key' => 'delivered', 'label' => __('Delivered'), 'icon' => 'fa-truck', 'description' => __('Delivered to Customer'), 'step' => 5],
            ['key' => 'confirmed', 'label' => __('Confirmed'), 'icon' => 'fa-check-double', 'description' => __('Customer Confirmed'), 'step' => 6],
        ];

        $result = [];
        foreach ($stepDefinitions as $s) {
            $isActive = $currentStep >= $s['step'];
            $isCurrent = $currentStep == $s['step'];

            $result[] = [
                'key' => $s['key'],
                'label' => $s['label'],
                'icon' => $s['icon'],
                'description' => $s['description'],
                'step' => $s['step'],
                'isActive' => $isActive,
                'isCurrent' => $isCurrent,
                'circleBackground' => $isCurrent ? 'var(--action-primary, #3b82f6)' : ($isActive ? 'var(--action-success, #22c55e)' : 'var(--surface-secondary, #f3f4f6)'),
                'circleColor' => $isActive ? '#fff' : 'var(--text-tertiary, #9ca3af)',
                'circleBorder' => $isCurrent ? 'var(--action-primary, #3b82f6)' : ($isActive ? 'var(--action-success, #22c55e)' : 'var(--border-default, #e5e7eb)'),
                'labelColor' => $isActive ? 'var(--text-primary, #111827)' : 'var(--text-tertiary, #9ca3af)',
            ];
        }

        return $result;
    }

    // =========================================================================
    // TRANSACTIONS DISPLAY
    // =========================================================================

    /**
     * Format deliveries for transactions list
     */
    public function formatDeliveriesForTransactions(LengthAwarePaginator $deliveries): LengthAwarePaginator
    {
        $deliveries->through(function ($delivery) {
            return [
                'id' => $delivery->id,
                'status' => $delivery->status,
                'payment_method' => $delivery->payment_method,
                'purchase_number' => $delivery->purchase?->purchase_number ?? '-',
                'purchase_amount_formatted' => monetaryUnit()->format($delivery->purchase_amount ?? 0),
                'delivery_fee_formatted' => monetaryUnit()->format($delivery->delivery_fee ?? 0),
                'created_at_formatted' => $delivery->created_at?->format('d-m-Y H:i') ?? 'N/A',
                'status_display' => $this->getDeliveryStatusDisplay($delivery),
            ];
        });

        return $deliveries;
    }

    /**
     * Format report for transactions view
     */
    public function formatReportForTransactions(array $report): array
    {
        return [
            'deliveries_count' => $report['deliveries_count'] ?? 0,
            'deliveries_completed' => $report['deliveries_completed'] ?? 0,
            'total_cod_collected_formatted' => monetaryUnit()->format($report['total_cod_collected'] ?? 0),
            'total_delivery_fees_formatted' => monetaryUnit()->format($report['total_delivery_fees'] ?? 0),
        ];
    }

    // =========================================================================
    // SETTLEMENTS DISPLAY
    // =========================================================================

    /**
     * Format settlement calculation for display
     */
    public function formatSettlementCalc(array $settlementCalc): array
    {
        $netAmount = $settlementCalc['net_amount'] ?? 0;

        return [
            'cod_amount_formatted' => monetaryUnit()->format($settlementCalc['cod_amount'] ?? 0),
            'fees_earned_online_formatted' => monetaryUnit()->format($settlementCalc['fees_earned_online'] ?? 0),
            'fees_earned_cod_formatted' => monetaryUnit()->format($settlementCalc['fees_earned_cod'] ?? 0),
            'net_amount' => $netAmount,
            'net_amount_formatted' => monetaryUnit()->format(abs($netAmount)),
            'is_positive' => $netAmount >= 0,
        ];
    }

    /**
     * Format report for settlements view
     */
    public function formatReportForSettlements(array $report): array
    {
        return [
            'current_balance' => $report['current_balance'] ?? 0,
            'current_balance_formatted' => monetaryUnit()->format($report['current_balance'] ?? 0),
            'is_in_debt' => ($report['current_balance'] ?? 0) < 0,
            'has_credit' => ($report['current_balance'] ?? 0) > 0,
            'total_cod_collected_formatted' => monetaryUnit()->format($report['total_cod_collected'] ?? 0),
            'total_fees_earned_formatted' => monetaryUnit()->format($report['total_fees_earned'] ?? 0),
        ];
    }

    /**
     * Format unsettled deliveries for display
     */
    public function formatUnsettledDeliveries(Collection $deliveries): Collection
    {
        return $deliveries->map(function ($delivery) {
            return [
                'id' => $delivery->id,
                'purchase_number' => $delivery->purchase?->purchase_number ?? '-',
                'payment_method' => $delivery->payment_method,
                'purchase_amount_formatted' => monetaryUnit()->format($delivery->purchase_amount ?? 0),
                'delivery_fee_formatted' => monetaryUnit()->format($delivery->delivery_fee ?? 0),
                'date_formatted' => $delivery->delivered_at?->format('d-m-Y') ?? $delivery->created_at?->format('d-m-Y') ?? 'N/A',
            ];
        });
    }

    // =========================================================================
    // FINANCIAL REPORT DISPLAY
    // =========================================================================

    /**
     * Format report for financial report view
     */
    public function formatFinancialReport(array $report): array
    {
        return [
            // Monetary values - pre-formatted
            'current_balance' => $report['current_balance'] ?? 0,
            'current_balance_formatted' => monetaryUnit()->format($report['current_balance'] ?? 0),
            'total_collected' => $report['total_collected'] ?? 0,
            'total_collected_formatted' => monetaryUnit()->format($report['total_collected'] ?? 0),
            'total_fees_earned' => $report['total_fees_earned'] ?? 0,
            'total_fees_earned_formatted' => monetaryUnit()->format($report['total_fees_earned'] ?? 0),
            'total_delivery_fees_formatted' => monetaryUnit()->format($report['total_delivery_fees'] ?? 0),
            'total_cod_collected_formatted' => monetaryUnit()->format($report['total_cod_collected'] ?? 0),
            'total_delivered_formatted' => monetaryUnit()->format($report['total_delivered'] ?? 0),

            // Boolean flags
            'is_in_debt' => ($report['current_balance'] ?? 0) < 0,
            'has_credit' => ($report['current_balance'] ?? 0) > 0,

            // Count values with defaults
            'deliveries_count' => $report['deliveries_count'] ?? 0,
            'deliveries_completed' => $report['deliveries_completed'] ?? 0,
            'deliveries_pending' => $report['deliveries_pending'] ?? 0,
            'unsettled_deliveries' => $report['unsettled_deliveries'] ?? 0,
            'cod_deliveries' => $report['cod_deliveries'] ?? 0,
            'online_deliveries' => $report['online_deliveries'] ?? 0,
        ];
    }

    // =========================================================================
    // SERVICE AREA DISPLAY
    // =========================================================================

    /**
     * Format service areas for display
     */
    public function formatServiceAreas(LengthAwarePaginator $serviceAreas): LengthAwarePaginator
    {
        $serviceAreas->through(function ($area) {
            return [
                'id' => $area->id,
                'status' => $area->status,
                'country_name' => $area->city?->country?->country_name ?? '-',
                'name' => $area->city?->name ?? '-',
                'radius_display' => $area->service_radius_km ?? 0,
                'price_formatted' => monetaryUnit()->format($area->price),
                'coordinates_display' => ($area->latitude && $area->longitude)
                    ? number_format($area->latitude, 4) . ', ' . number_format($area->longitude, 4)
                    : null,
            ];
        });

        return $serviceAreas;
    }
}
