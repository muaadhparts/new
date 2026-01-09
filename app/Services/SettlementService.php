<?php

namespace App\Services;

use App\Models\Courier;
use App\Models\CourierSettlement;
use App\Models\CourierTransaction;
use App\Models\DeliveryCourier;
use App\Models\MerchantPurchase;
use App\Models\MerchantSettlement;
use App\Models\MerchantSettlementItem;
use App\Models\PlatformRevenueLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * SettlementService
 *
 * Unified service for managing all settlements:
 * - Merchant settlements (based on MerchantPurchase)
 * - Courier settlements (based on DeliveryCourier)
 *
 * This service is the SINGLE SOURCE OF TRUTH for settlement operations.
 */
class SettlementService
{
    // =========================================================================
    // MERCHANT SETTLEMENTS
    // =========================================================================

    /**
     * Get unsettled MerchantPurchases for a merchant
     */
    public function getUnsettledMerchantPurchases(int $merchantId, ?Carbon $fromDate = null, ?Carbon $toDate = null): Collection
    {
        $query = MerchantPurchase::where('user_id', $merchantId)
            ->where('settlement_status', 'unsettled')
            ->whereHas('purchase', function ($q) {
                $q->whereIn('status', ['completed', 'delivered']);
            });

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        return $query->with('purchase')->orderBy('created_at', 'asc')->get();
    }

    /**
     * Get merchant settlement summary (for preview before creating settlement)
     */
    public function getMerchantSettlementSummary(int $merchantId, ?Carbon $fromDate = null, ?Carbon $toDate = null): array
    {
        $purchases = $this->getUnsettledMerchantPurchases($merchantId, $fromDate, $toDate);

        return [
            'merchant_id' => $merchantId,
            'merchant' => User::find($merchantId),
            'period_start' => $fromDate ?? $purchases->min('created_at')?->toDateString(),
            'period_end' => $toDate ?? $purchases->max('created_at')?->toDateString(),
            'orders_count' => $purchases->count(),
            'items_count' => $purchases->sum('qty'),
            'total_sales' => $purchases->sum('price'),
            'total_commission' => $purchases->sum('commission_amount'),
            'total_tax' => $purchases->sum('tax_amount'),
            'total_shipping' => $purchases->sum('shipping_cost') + $purchases->sum('courier_fee'),
            'total_packing' => $purchases->sum('packing_cost'),
            'net_payable' => $purchases->sum('net_amount'),
            'purchases' => $purchases,
        ];
    }

    /**
     * Create a merchant settlement
     */
    public function createMerchantSettlement(
        int $merchantId,
        ?Carbon $fromDate = null,
        ?Carbon $toDate = null,
        ?int $createdBy = null,
        ?string $notes = null
    ): MerchantSettlement {
        $summary = $this->getMerchantSettlementSummary($merchantId, $fromDate, $toDate);

        if ($summary['orders_count'] === 0) {
            throw new \Exception(__('No unsettled orders found for this period.'));
        }

        return DB::transaction(function () use ($merchantId, $summary, $createdBy, $notes) {
            // Create settlement
            $settlement = MerchantSettlement::create([
                'user_id' => $merchantId,
                'settlement_number' => MerchantSettlement::generateSettlementNumber(),
                'period_start' => $summary['period_start'],
                'period_end' => $summary['period_end'],
                'total_sales' => $summary['total_sales'],
                'total_commission' => $summary['total_commission'],
                'total_tax' => $summary['total_tax'],
                'total_shipping' => $summary['total_shipping'],
                'total_packing' => $summary['total_packing'],
                'total_deductions' => 0,
                'net_payable' => $summary['net_payable'],
                'orders_count' => $summary['orders_count'],
                'items_count' => $summary['items_count'],
                'status' => MerchantSettlement::STATUS_DRAFT,
                'created_by' => $createdBy,
                'notes' => $notes,
            ]);

            // Create settlement items and link purchases
            foreach ($summary['purchases'] as $purchase) {
                MerchantSettlementItem::createFromMerchantPurchase($settlement->id, $purchase);

                $purchase->update([
                    'settlement_status' => 'pending',
                    'merchant_settlement_id' => $settlement->id,
                ]);
            }

            return $settlement;
        });
    }

    /**
     * Get all merchants with unsettled balances
     */
    public function getMerchantsWithUnsettledBalances(): Collection
    {
        return DB::table('merchant_purchases')
            ->select('user_id')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('SUM(price) as total_sales')
            ->selectRaw('SUM(net_amount) as net_payable')
            ->where('settlement_status', 'unsettled')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('purchases')
                    ->whereColumn('purchases.id', 'merchant_purchases.purchase_id')
                    ->whereIn('purchases.status', ['completed', 'delivered']);
            })
            ->groupBy('user_id')
            ->having('orders_count', '>', 0)
            ->get()
            ->map(function ($item) {
                $merchant = User::find($item->user_id);
                return [
                    'merchant_id' => $item->user_id,
                    'merchant_name' => $merchant?->shop_name ?? $merchant?->name ?? 'Unknown',
                    'orders_count' => $item->orders_count,
                    'total_sales' => $item->total_sales,
                    'net_payable' => $item->net_payable,
                ];
            });
    }

    // =========================================================================
    // COURIER SETTLEMENTS
    // =========================================================================

    /**
     * Get unsettled deliveries for a courier
     */
    public function getUnsettledCourierDeliveries(int $courierId): Collection
    {
        return DeliveryCourier::where('courier_id', $courierId)
            ->where('status', 'delivered')
            ->where('settlement_status', 'unsettled')
            ->with('purchase')
            ->orderBy('delivered_at', 'asc')
            ->get();
    }

    /**
     * Get courier settlement summary
     */
    public function getCourierSettlementSummary(int $courierId): array
    {
        $courier = Courier::findOrFail($courierId);
        $deliveries = $this->getUnsettledCourierDeliveries($courierId);

        $codCollected = $deliveries->where('payment_method', 'cod')->sum('cod_amount');
        $feesEarned = $deliveries->sum('delivery_fee');

        // Calculate net balance
        // If COD > fees: Courier owes platform
        // If fees > COD: Platform owes courier
        $netBalance = $codCollected - $feesEarned;
        $settlementType = $netBalance > 0
            ? CourierSettlement::TYPE_RECEIVE_FROM_COURIER
            : CourierSettlement::TYPE_PAY_TO_COURIER;

        return [
            'courier_id' => $courierId,
            'courier' => $courier,
            'current_balance' => $courier->balance,
            'total_deliveries' => $deliveries->count(),
            'cod_collected' => $codCollected,
            'fees_earned' => $feesEarned,
            'net_balance' => abs($netBalance),
            'settlement_type' => $settlementType,
            'deliveries' => $deliveries,
        ];
    }

    /**
     * Create a courier settlement
     */
    public function createCourierSettlement(
        int $courierId,
        float $amount,
        string $type,
        ?string $paymentMethod = null,
        ?string $reference = null,
        ?string $notes = null,
        ?int $createdBy = null
    ): CourierSettlement {
        return DB::transaction(function () use ($courierId, $amount, $type, $paymentMethod, $reference, $notes, $createdBy) {
            $settlement = CourierSettlement::create([
                'courier_id' => $courierId,
                'amount' => $amount,
                'type' => $type,
                'status' => CourierSettlement::STATUS_PENDING,
                'payment_method' => $paymentMethod,
                'reference_number' => $reference,
                'notes' => $notes,
            ]);

            // Mark deliveries as pending settlement
            DeliveryCourier::where('courier_id', $courierId)
                ->where('status', 'delivered')
                ->where('settlement_status', 'unsettled')
                ->update(['settlement_status' => 'pending']);

            return $settlement;
        });
    }

    /**
     * Process courier settlement (execute the payment/receipt)
     */
    public function processCourierSettlement(CourierSettlement $settlement, ?int $processedBy = null): bool
    {
        return DB::transaction(function () use ($settlement, $processedBy) {
            if (!$settlement->process($processedBy)) {
                return false;
            }

            // Mark deliveries as settled
            DeliveryCourier::where('courier_id', $settlement->courier_id)
                ->where('settlement_status', 'pending')
                ->update([
                    'settlement_status' => 'settled',
                    'settled_at' => now(),
                ]);

            return true;
        });
    }

    /**
     * Get all couriers with unsettled balances
     */
    public function getCouriersWithUnsettledBalances(): Collection
    {
        return Courier::whereHas('deliveries', function ($query) {
            $query->where('status', 'delivered')
                  ->where('settlement_status', 'unsettled');
        })
        ->withCount(['deliveries' => function ($query) {
            $query->where('status', 'delivered')
                  ->where('settlement_status', 'unsettled');
        }])
        ->get()
        ->map(function ($courier) {
            $summary = $this->getCourierSettlementSummary($courier->id);
            return [
                'courier_id' => $courier->id,
                'courier_name' => $courier->name,
                'deliveries_count' => $summary['total_deliveries'],
                'cod_collected' => $summary['cod_collected'],
                'fees_earned' => $summary['fees_earned'],
                'net_balance' => $summary['net_balance'],
                'settlement_type' => $summary['settlement_type'],
                'current_balance' => $courier->balance,
            ];
        });
    }

    // =========================================================================
    // PLATFORM REPORTS
    // =========================================================================

    /**
     * Get platform financial summary
     */
    public function getPlatformSummary(?Carbon $fromDate = null, ?Carbon $toDate = null): array
    {
        $query = MerchantPurchase::query();

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $totalSales = $query->sum('price');
        $totalCommission = $query->sum('commission_amount');
        $totalTax = $query->sum('tax_amount');

        // Courier data
        $courierQuery = DeliveryCourier::query();
        if ($fromDate) {
            $courierQuery->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $courierQuery->whereDate('created_at', '<=', $toDate);
        }

        $totalCodCollected = $courierQuery->where('payment_method', 'cod')->sum('cod_amount');
        $totalCourierFees = $courierQuery->sum('delivery_fee');

        // Settlement status
        $pendingMerchantSettlements = MerchantSettlement::pending()->sum('net_payable');
        $pendingCourierSettlements = CourierSettlement::pending()->sum('amount');

        return [
            'period' => [
                'from' => $fromDate?->format('Y-m-d') ?? 'All time',
                'to' => $toDate?->format('Y-m-d') ?? 'All time',
            ],
            'sales' => [
                'total_sales' => $totalSales,
                'total_commission' => $totalCommission,
                'total_tax' => $totalTax,
                'net_to_merchants' => $totalSales - $totalCommission,
            ],
            'courier' => [
                'total_cod_collected' => $totalCodCollected,
                'total_fees' => $totalCourierFees,
                'net_courier_balance' => $totalCodCollected - $totalCourierFees,
            ],
            'pending_settlements' => [
                'merchant_payable' => $pendingMerchantSettlements,
                'courier_payable' => $pendingCourierSettlements,
            ],
            'platform_revenue' => [
                'commission' => $totalCommission,
                'tax' => $totalTax,
                'total' => $totalCommission + $totalTax,
            ],
        ];
    }

    /**
     * Get settlement history
     */
    public function getSettlementHistory(?Carbon $fromDate = null, ?Carbon $toDate = null): array
    {
        $merchantQuery = MerchantSettlement::with('merchant');
        $courierQuery = CourierSettlement::with('courier');

        if ($fromDate) {
            $merchantQuery->whereDate('created_at', '>=', $fromDate);
            $courierQuery->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $merchantQuery->whereDate('created_at', '<=', $toDate);
            $courierQuery->whereDate('created_at', '<=', $toDate);
        }

        return [
            'merchant_settlements' => $merchantQuery->orderBy('created_at', 'desc')->get(),
            'courier_settlements' => $courierQuery->orderBy('created_at', 'desc')->get(),
        ];
    }
}
