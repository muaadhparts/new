<?php

namespace App\Domain\Accounting\Services;

use App\Models\Courier;
use App\Models\CourierServiceArea;
use App\Models\DeliveryCourier;
use App\Models\MerchantBranch;

/**
 * CourierAccountingService
 *
 * Domain: Accounting
 *
 * Manages courier financial operations including:
 * - COD collection tracking
 * - Delivery fee calculations
 * - Courier balance management
 * - Settlement calculations
 */
class CourierAccountingService
{
    // Settlement type constants
    const TYPE_PAY_TO_COURIER = 'pay_to_courier';
    const TYPE_RECEIVE_FROM_COURIER = 'receive_from_courier';

    public function getAvailableCouriersForCity(int $cityId): \Illuminate\Database\Eloquent\Collection
    {
        return CourierServiceArea::where('city_id', $cityId)
            ->with('courier')
            ->whereHas('courier', function ($query) {
                $query->where('status', 1);
            })
            ->get();
    }

    public function canDeliverToCity(int $merchantId, int $customerCityId): bool
    {
        $merchantHasLocation = MerchantBranch::where('user_id', $merchantId)
            ->where('city_id', $customerCityId)
            ->where('status', 1)
            ->exists();

        if (!$merchantHasLocation) {
            return false;
        }

        $couriersAvailable = CourierServiceArea::where('city_id', $customerCityId)
            ->whereHas('courier', function ($query) {
                $query->where('status', 1);
            })
            ->exists();

        return $couriersAvailable;
    }

    public function getCouriersWithPricesForCity(int $cityId): array
    {
        $serviceAreas = CourierServiceArea::where('city_id', $cityId)
            ->with('courier')
            ->whereHas('courier', function ($query) {
                $query->where('status', 1);
            })
            ->get();

        $couriers = [];
        foreach ($serviceAreas as $area) {
            $couriers[] = [
                'courier_id' => $area->courier_id,
                'courier_name' => $area->courier->name,
                'delivery_fee' => (float)$area->price,
                'service_area_id' => $area->id,
            ];
        }

        return $couriers;
    }

    /**
     * Record COD collection by courier
     */
    public function recordCodCollection(int $deliveryCourierId, float $orderAmount): void
    {
        $delivery = DeliveryCourier::findOrFail($deliveryCourierId);
        $courier = $delivery->courier;

        $courier->recordCodCollection($orderAmount);
    }

    /**
     * Record delivery fee earned by courier
     */
    public function recordDeliveryFeeEarned(int $deliveryCourierId): void
    {
        $delivery = DeliveryCourier::findOrFail($deliveryCourierId);
        $courier = $delivery->courier;

        $courier->recordDeliveryFeeEarned($delivery->delivery_fee);
    }

    public function getCourierBalance(int $courierId): float
    {
        $courier = Courier::findOrFail($courierId);
        return $courier->getCurrentBalance();
    }

    public function getCourierReport(int $courierId, ?string $startDate = null, ?string $endDate = null): array
    {
        $courier = Courier::findOrFail($courierId);

        $baseQuery = DeliveryCourier::where('courier_id', $courierId);
        if ($startDate) {
            $baseQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $baseQuery->whereDate('created_at', '<=', $endDate);
        }

        $stats = (clone $baseQuery)->selectRaw('
            COUNT(*) as total_count,
            SUM(CASE WHEN status = "delivered" OR status = "confirmed" THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN status = "pending_approval" THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN payment_method = "cod" THEN 1 ELSE 0 END) as cod_count,
            SUM(CASE WHEN payment_method = "online" THEN 1 ELSE 0 END) as online_count,
            SUM(delivery_fee) as total_fees,
            SUM(CASE WHEN payment_method = "cod" THEN purchase_amount ELSE 0 END) as cod_collected,
            SUM(CASE WHEN settlement_status = "pending" THEN 1 ELSE 0 END) as unsettled_count
        ')->first();

        return [
            'courier_id' => $courierId,
            'courier_name' => $courier->name,
            'current_balance' => $courier->balance,
            'is_in_debt' => $courier->isInDebt(),
            'has_credit' => $courier->hasCredit(),
            'total_collected' => $courier->total_collected,
            'total_delivered' => $courier->total_delivered,
            'total_fees_earned' => $courier->total_fees_earned,
            'deliveries_count' => (int)($stats->total_count ?? 0),
            'deliveries_completed' => (int)($stats->completed_count ?? 0),
            'deliveries_pending' => (int)($stats->pending_count ?? 0),
            'cod_deliveries' => (int)($stats->cod_count ?? 0),
            'online_deliveries' => (int)($stats->online_count ?? 0),
            'total_delivery_fees' => (float)($stats->total_fees ?? 0),
            'total_cod_collected' => (float)($stats->cod_collected ?? 0),
            'unsettled_deliveries' => (int)($stats->unsettled_count ?? 0),
        ];
    }

    public function getAdminCouriersReport(?string $startDate = null, ?string $endDate = null): array
    {
        $couriers = Courier::all();

        $reports = [];
        foreach ($couriers as $courier) {
            $reports[] = $this->getCourierReport($courier->id, $startDate, $endDate);
        }

        return [
            'total_couriers' => count($reports),
            'couriers_in_debt' => collect($reports)->where('is_in_debt', true)->count(),
            'couriers_with_credit' => collect($reports)->where('has_credit', true)->count(),
            'total_balance' => collect($reports)->sum('current_balance'),
            'couriers' => $reports,
        ];
    }

    public function markDeliveryAsDelivered(int $deliveryCourierId): void
    {
        $delivery = DeliveryCourier::findOrFail($deliveryCourierId);
        $delivery->markAsDelivered();
    }

    public function getUnsettledDeliveriesForCourier(int $courierId): \Illuminate\Database\Eloquent\Collection
    {
        return DeliveryCourier::where('courier_id', $courierId)
            ->where('status', 'delivered')
            ->where('settlement_status', 'pending')
            ->get();
    }

    public function calculateSettlementAmount(int $courierId): array
    {
        $unsettled = $this->getUnsettledDeliveriesForCourier($courierId);

        $codAmount = $unsettled->where('payment_method', 'cod')->sum('purchase_amount');
        $feesEarned = $unsettled->where('payment_method', 'online')->sum('delivery_fee');
        $codFees = $unsettled->where('payment_method', 'cod')->sum('delivery_fee');

        $courierOwes = $codAmount;
        $platformOwes = $feesEarned + $codFees;

        $netAmount = $platformOwes - $courierOwes;

        return [
            'cod_amount' => $codAmount,
            'fees_earned_online' => $feesEarned,
            'fees_earned_cod' => $codFees,
            'courier_owes' => $courierOwes,
            'platform_owes' => $platformOwes,
            'net_amount' => $netAmount,
            'settlement_type' => $netAmount >= 0
                ? self::TYPE_PAY_TO_COURIER
                : self::TYPE_RECEIVE_FROM_COURIER,
        ];
    }
}
