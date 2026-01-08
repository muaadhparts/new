<?php

namespace App\Services;

use App\Models\Courier;
use App\Models\CourierServiceArea;
use App\Models\CourierSettlement;
use App\Models\CourierTransaction;
use App\Models\DeliveryCourier;
use App\Models\MerchantLocation;

class CourierAccountingService
{
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
        $merchantHasLocation = MerchantLocation::where('user_id', $merchantId)
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

    public function recordCodCollection(int $deliveryCourierId, float $orderAmount): void
    {
        $delivery = DeliveryCourier::findOrFail($deliveryCourierId);
        $courier = $delivery->courier;

        $balanceBefore = $courier->balance;

        $courier->recordCodCollection($orderAmount);

        CourierTransaction::create([
            'courier_id' => $courier->id,
            'delivery_courier_id' => $deliveryCourierId,
            'type' => CourierTransaction::TYPE_COD_COLLECTED,
            'amount' => $orderAmount,
            'balance_before' => $balanceBefore,
            'balance_after' => $courier->balance,
            'notes' => 'COD collected for delivery #' . $deliveryCourierId,
        ]);
    }

    public function recordDeliveryFeeEarned(int $deliveryCourierId): void
    {
        $delivery = DeliveryCourier::findOrFail($deliveryCourierId);
        $courier = $delivery->courier;

        $balanceBefore = $courier->balance;

        $courier->recordDeliveryFeeEarned($delivery->delivery_fee);

        CourierTransaction::create([
            'courier_id' => $courier->id,
            'delivery_courier_id' => $deliveryCourierId,
            'type' => CourierTransaction::TYPE_FEE_EARNED,
            'amount' => $delivery->delivery_fee,
            'balance_before' => $balanceBefore,
            'balance_after' => $courier->balance,
            'notes' => 'Delivery fee earned for delivery #' . $deliveryCourierId,
        ]);
    }

    public function createSettlement(
        int $courierId,
        float $amount,
        string $type,
        ?string $paymentMethod = null,
        ?string $referenceNumber = null,
        ?string $notes = null
    ): CourierSettlement {
        return CourierSettlement::create([
            'courier_id' => $courierId,
            'amount' => $amount,
            'type' => $type,
            'status' => CourierSettlement::STATUS_PENDING,
            'payment_method' => $paymentMethod,
            'reference_number' => $referenceNumber,
            'notes' => $notes,
        ]);
    }

    public function processSettlement(int $settlementId, int $processedBy): bool
    {
        $settlement = CourierSettlement::findOrFail($settlementId);
        return $settlement->process($processedBy);
    }

    public function getCourierBalance(int $courierId): float
    {
        $courier = Courier::findOrFail($courierId);
        return $courier->getCurrentBalance();
    }

    public function getCourierReport(int $courierId, ?string $startDate = null, ?string $endDate = null): array
    {
        $courier = Courier::findOrFail($courierId);

        $deliveriesQuery = DeliveryCourier::where('courier_id', $courierId);
        if ($startDate) {
            $deliveriesQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $deliveriesQuery->whereDate('created_at', '<=', $endDate);
        }
        $deliveries = $deliveriesQuery->get();

        $transactionsQuery = CourierTransaction::where('courier_id', $courierId);
        if ($startDate) {
            $transactionsQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $transactionsQuery->whereDate('created_at', '<=', $endDate);
        }
        $transactions = $transactionsQuery->get();

        return [
            'courier_id' => $courierId,
            'courier_name' => $courier->name,
            'current_balance' => $courier->balance,
            'is_in_debt' => $courier->isInDebt(),
            'has_credit' => $courier->hasCredit(),
            'total_collected' => $courier->total_collected,
            'total_delivered' => $courier->total_delivered,
            'total_fees_earned' => $courier->total_fees_earned,
            'deliveries_count' => $deliveries->count(),
            'deliveries_completed' => $deliveries->where('status', 'delivered')->count(),
            'deliveries_pending' => $deliveries->where('status', 'pending')->count(),
            'cod_deliveries' => $deliveries->where('payment_method', 'cod')->count(),
            'online_deliveries' => $deliveries->where('payment_method', 'online')->count(),
            'total_delivery_fees' => $deliveries->sum('delivery_fee'),
            'total_cod_collected' => $deliveries->where('payment_method', 'cod')->sum('purchase_amount'),
            'unsettled_deliveries' => $deliveries->where('settlement_status', 'pending')->count(),
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
                ? CourierSettlement::TYPE_PAY_TO_COURIER
                : CourierSettlement::TYPE_RECEIVE_FROM_COURIER,
        ];
    }
}
