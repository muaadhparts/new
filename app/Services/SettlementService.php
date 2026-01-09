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
 * ARCHITECTURAL PRINCIPLE (2026-01-09):
 * - user_id = 0 → Platform service → Money goes to platform
 * - user_id ≠ 0 → Merchant service → Money goes directly to merchant
 *
 * Money Flow:
 * - platform_owes_merchant > 0: Platform received payment, owes merchant
 * - merchant_owes_platform > 0: Merchant received payment, owes platform
 *
 * This service is the SINGLE SOURCE OF TRUTH for settlement operations.
 */
class SettlementService
{
    // Settlement types
    const TYPE_PLATFORM_PAYS_MERCHANT = 'platform_pays_merchant';
    const TYPE_MERCHANT_PAYS_PLATFORM = 'merchant_pays_platform';

    // =========================================================================
    // MERCHANT SETTLEMENTS
    // =========================================================================

    /**
     * Get unsettled MerchantPurchases for a merchant
     */
    public function getUnsettledMerchantPurchases(int $merchantId, ?Carbon $fromDate = null, ?Carbon $toDate = null): Collection
    {
        $query = MerchantPurchase::where('user_id', $merchantId)
            ->where(function ($q) {
                $q->whereNull('settlement_status')
                    ->orWhere('settlement_status', 'unsettled');
            })
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
     *
     * ARCHITECTURAL PRINCIPLE:
     * - platform_owes_merchant: Sum of net amounts when platform received payment
     * - merchant_owes_platform: Sum of commissions + platform services when merchant received payment
     */
    public function getMerchantSettlementSummary(int $merchantId, ?Carbon $fromDate = null, ?Carbon $toDate = null): array
    {
        $purchases = $this->getUnsettledMerchantPurchases($merchantId, $fromDate, $toDate);

        // Calculate by payment owner
        $platformPayments = $purchases->where('payment_owner_id', 0);
        $merchantPayments = $purchases->where('payment_owner_id', '!=', 0);

        // Platform owes merchant (platform received the money)
        $platformOwesMerchant = $platformPayments->sum('platform_owes_merchant');

        // Merchant owes platform (merchant received the money directly)
        $merchantOwesPlatform = $merchantPayments->sum('merchant_owes_platform');

        // Net settlement amount
        // Positive = Platform pays merchant
        // Negative = Merchant pays platform
        $netSettlement = $platformOwesMerchant - $merchantOwesPlatform;

        $settlementType = $netSettlement >= 0
            ? self::TYPE_PLATFORM_PAYS_MERCHANT
            : self::TYPE_MERCHANT_PAYS_PLATFORM;

        return [
            'merchant_id' => $merchantId,
            'merchant' => User::find($merchantId),
            'period_start' => $fromDate ?? $purchases->min('created_at')?->toDateString(),
            'period_end' => $toDate ?? $purchases->max('created_at')?->toDateString(),
            'orders_count' => $purchases->count(),
            'items_count' => $purchases->sum('qty'),

            // Gross sales breakdown
            'total_sales' => $purchases->sum('price'),
            'total_commission' => $purchases->sum('commission_amount'),
            'total_tax' => $purchases->sum('tax_amount'),
            'total_shipping' => $purchases->sum('shipping_cost'),
            'total_courier_fee' => $purchases->sum('courier_fee'),
            'total_packing' => $purchases->sum('packing_cost'),
            'net_amount' => $purchases->sum('net_amount'),

            // Platform services used by merchant
            'platform_shipping_fees' => $purchases->sum('platform_shipping_fee'),
            'platform_packing_fees' => $purchases->sum('platform_packing_fee'),

            // Settlement breakdown by payment owner
            'by_payment_owner' => [
                'platform_payments' => [
                    'count' => $platformPayments->count(),
                    'total_sales' => $platformPayments->sum('price'),
                    'platform_owes_merchant' => $platformOwesMerchant,
                ],
                'merchant_payments' => [
                    'count' => $merchantPayments->count(),
                    'total_sales' => $merchantPayments->sum('price'),
                    'merchant_owes_platform' => $merchantOwesPlatform,
                ],
            ],

            // Final settlement
            'platform_owes_merchant' => $platformOwesMerchant,
            'merchant_owes_platform' => $merchantOwesPlatform,
            'net_settlement' => abs($netSettlement),
            'settlement_type' => $settlementType,
            'settlement_direction' => $netSettlement >= 0 ? 'platform_to_merchant' : 'merchant_to_platform',

            // Legacy field for backward compatibility
            'net_payable' => $netSettlement >= 0 ? $netSettlement : 0,

            'purchases' => $purchases,
        ];
    }

    /**
     * Create a merchant settlement
     *
     * ARCHITECTURAL PRINCIPLE:
     * - Settlement type determined by net balance
     * - platform_pays_merchant: Platform owes more than merchant owes
     * - merchant_pays_platform: Merchant owes more than platform owes
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
                'total_shipping' => $summary['total_shipping'] + $summary['total_courier_fee'],
                'total_packing' => $summary['total_packing'],
                'total_deductions' => $summary['merchant_owes_platform'], // What merchant owes
                'net_payable' => $summary['net_settlement'],
                'orders_count' => $summary['orders_count'],
                'items_count' => $summary['items_count'],
                'status' => MerchantSettlement::STATUS_DRAFT,
                'settlement_type' => $summary['settlement_type'],
                'platform_owes_merchant' => $summary['platform_owes_merchant'],
                'merchant_owes_platform' => $summary['merchant_owes_platform'],
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
     *
     * ARCHITECTURAL PRINCIPLE:
     * Shows both directions:
     * - platform_owes_merchant: Platform collected payment, owes merchant
     * - merchant_owes_platform: Merchant collected payment, owes platform
     */
    public function getMerchantsWithUnsettledBalances(): Collection
    {
        return DB::table('merchant_purchases')
            ->select('user_id')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('SUM(price) as total_sales')
            ->selectRaw('SUM(platform_owes_merchant) as platform_owes_merchant')
            ->selectRaw('SUM(merchant_owes_platform) as merchant_owes_platform')
            ->selectRaw('SUM(platform_owes_merchant) - SUM(merchant_owes_platform) as net_balance')
            ->where(function ($q) {
                $q->whereNull('settlement_status')
                    ->orWhere('settlement_status', 'unsettled');
            })
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
                $netBalance = (float) $item->net_balance;

                return [
                    'merchant_id' => $item->user_id,
                    'merchant_name' => $merchant?->shop_name ?? $merchant?->name ?? 'Unknown',
                    'orders_count' => $item->orders_count,
                    'total_sales' => (float) $item->total_sales,
                    'platform_owes_merchant' => (float) $item->platform_owes_merchant,
                    'merchant_owes_platform' => (float) $item->merchant_owes_platform,
                    'net_balance' => abs($netBalance),
                    'settlement_type' => $netBalance >= 0
                        ? self::TYPE_PLATFORM_PAYS_MERCHANT
                        : self::TYPE_MERCHANT_PAYS_PLATFORM,
                    'settlement_direction' => $netBalance >= 0
                        ? 'platform_to_merchant'
                        : 'merchant_to_platform',
                    // Legacy field
                    'net_payable' => $netBalance >= 0 ? $netBalance : 0,
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
     *
     * ARCHITECTURAL PRINCIPLE:
     * Platform revenue comes from:
     * - Commission from all sales
     * - Tax collected
     * - Platform shipping fees (when shipping_owner_id = 0)
     * - Platform packing fees (when packing_owner_id = 0)
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

        // Clone query for different aggregations
        $statsQuery = clone $query;

        // Sales statistics
        $stats = $statsQuery->selectRaw('
            SUM(price) as total_sales,
            SUM(commission_amount) as total_commission,
            SUM(tax_amount) as total_tax,
            SUM(net_amount) as total_net_to_merchants,
            SUM(platform_shipping_fee) as platform_shipping_revenue,
            SUM(platform_packing_fee) as platform_packing_revenue,
            SUM(platform_owes_merchant) as total_platform_owes_merchant,
            SUM(merchant_owes_platform) as total_merchant_owes_platform,
            COUNT(CASE WHEN payment_owner_id = 0 THEN 1 END) as platform_payment_count,
            COUNT(CASE WHEN payment_owner_id != 0 THEN 1 END) as merchant_payment_count
        ')->first();

        // Courier data
        $courierQuery = DeliveryCourier::query();
        if ($fromDate) {
            $courierQuery->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $courierQuery->whereDate('created_at', '<=', $toDate);
        }

        $courierStats = $courierQuery->selectRaw('
            SUM(CASE WHEN payment_method = "cod" THEN cod_amount ELSE 0 END) as total_cod_collected,
            SUM(delivery_fee) as total_fees
        ')->first();

        $totalCodCollected = (float) ($courierStats->total_cod_collected ?? 0);
        $totalCourierFees = (float) ($courierStats->total_fees ?? 0);

        // Settlement status
        $pendingMerchantSettlements = MerchantSettlement::pending()->sum('net_payable');
        $pendingCourierSettlements = CourierSettlement::pending()->sum('amount');

        // Platform revenue calculation
        $commissionRevenue = (float) ($stats->total_commission ?? 0);
        $taxRevenue = (float) ($stats->total_tax ?? 0);
        $shippingRevenue = (float) ($stats->platform_shipping_revenue ?? 0);
        $packingRevenue = (float) ($stats->platform_packing_revenue ?? 0);
        $totalPlatformRevenue = $commissionRevenue + $taxRevenue + $shippingRevenue + $packingRevenue;

        return [
            'period' => [
                'from' => $fromDate?->format('Y-m-d') ?? 'All time',
                'to' => $toDate?->format('Y-m-d') ?? 'All time',
            ],
            'sales' => [
                'total_sales' => (float) ($stats->total_sales ?? 0),
                'total_commission' => $commissionRevenue,
                'total_tax' => $taxRevenue,
                'net_to_merchants' => (float) ($stats->total_net_to_merchants ?? 0),
            ],
            'payment_breakdown' => [
                'platform_payments' => (int) ($stats->platform_payment_count ?? 0),
                'merchant_payments' => (int) ($stats->merchant_payment_count ?? 0),
                'platform_owes_merchants' => (float) ($stats->total_platform_owes_merchant ?? 0),
                'merchants_owe_platform' => (float) ($stats->total_merchant_owes_platform ?? 0),
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
                'commission' => $commissionRevenue,
                'tax' => $taxRevenue,
                'shipping_markup' => $shippingRevenue,
                'packing_markup' => $packingRevenue,
                'total' => $totalPlatformRevenue,
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
