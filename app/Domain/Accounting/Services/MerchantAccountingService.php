<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Platform\Models\MonetaryUnit;
use App\Domain\Shipping\Models\DeliveryCourier;
use App\Domain\Merchant\Models\MerchantCommission;
use App\Domain\Merchant\Models\MerchantPayment;
use App\Domain\Commerce\Models\MerchantPurchase;
use App\Domain\Merchant\Models\MerchantTaxSetting;
use App\Domain\Shipping\Models\Shipping;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * MerchantAccountingService
 *
 * Domain: Accounting
 *
 * ARCHITECTURAL PRINCIPLE (2026-01-09):
 * - owner_id = 0 → Platform service
 * - owner_id > 0 → Merchant/Owner service
 *
 * Single source of truth: MerchantPurchase table
 */
class MerchantAccountingService
{
    // =========================================================================
    // MERCHANT FINANCIAL REPORTS
    // =========================================================================

    /**
     * Get comprehensive merchant financial report
     */
    public function getMerchantReport(int $merchantId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = MerchantPurchase::where('user_id', $merchantId);

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $purchases = $query->get();

        $platformPayments = $purchases->where('payment_owner_id', 0);
        $merchantPayments = $purchases->filter(fn($p) => $p->payment_owner_id > 0);
        $platformShipping = $purchases->where('shipping_owner_id', 0);
        $merchantShipping = $purchases->filter(fn($p) => $p->shipping_owner_id > 0);
        $courierDeliveries = $purchases->where('shipping_type', 'courier');

        return [
            'total_sales' => $purchases->sum('price'),
            'total_orders' => $purchases->count(),
            'total_qty' => $purchases->sum('qty'),
            'total_commission' => $purchases->sum('commission_amount'),
            'total_tax' => $purchases->sum('tax_amount'),
            'total_platform_shipping_fee' => $purchases->sum('platform_shipping_fee'),
            'total_shipping_cost' => $purchases->sum('shipping_cost'),
            'total_courier_fee' => $purchases->sum('courier_fee'),
            'total_net' => $purchases->sum('net_amount'),
            'platform_owes_merchant' => $purchases->sum('platform_owes_merchant'),
            'merchant_owes_platform' => $purchases->sum('merchant_owes_platform'),
            'net_balance' => $purchases->sum('platform_owes_merchant') - $purchases->sum('merchant_owes_platform'),
            'platform_payments' => [
                'count' => $platformPayments->count(),
                'total' => $platformPayments->sum('price'),
                'platform_owes' => $platformPayments->sum('platform_owes_merchant'),
            ],
            'merchant_payments' => [
                'count' => $merchantPayments->count(),
                'total' => $merchantPayments->sum('price'),
                'merchant_owes' => $merchantPayments->sum('merchant_owes_platform'),
            ],
            'platform_shipping' => [
                'count' => $platformShipping->count(),
                'cost' => $platformShipping->sum('shipping_cost'),
            ],
            'merchant_shipping' => [
                'count' => $merchantShipping->count(),
                'cost' => $merchantShipping->sum('shipping_cost'),
            ],
            'courier_deliveries' => [
                'count' => $courierDeliveries->count(),
                'fee' => $courierDeliveries->sum('courier_fee'),
            ],
            'purchases' => $purchases,
        ];
    }

    /**
     * Get merchant statement (account ledger)
     */
    public function getMerchantStatement(int $merchantId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = MerchantPurchase::where('user_id', $merchantId)
            ->with(['purchase']);

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $purchases = $query->orderBy('created_at', 'desc')->get();

        $statement = [];
        $runningBalance = 0;

        foreach ($purchases->sortBy('created_at') as $purchase) {
            $credit = (float) $purchase->platform_owes_merchant;
            $debit = (float) $purchase->merchant_owes_platform;
            $runningBalance += ($credit - $debit);

            $statement[] = [
                'date' => $purchase->created_at,
                'purchase_number' => $purchase->purchase_number,
                'purchase_id' => $purchase->purchase_id,
                'description' => $this->getTransactionDescription($purchase),
                'gross' => (float) $purchase->price,
                'commission' => (float) $purchase->commission_amount,
                'tax' => (float) $purchase->tax_amount,
                'net' => (float) $purchase->net_amount,
                'credit' => $credit,
                'debit' => $debit,
                'balance' => $runningBalance,
                'payment_owner' => $purchase->payment_owner_id === 0 ? 'platform' : 'merchant',
                'settlement_status' => $purchase->settlement_status,
            ];
        }

        return [
            'statement' => array_reverse($statement),
            'opening_balance' => 0,
            'closing_balance' => $runningBalance,
            'total_credit' => $purchases->sum('platform_owes_merchant'),
            'total_debit' => $purchases->sum('merchant_owes_platform'),
        ];
    }

    /**
     * Get merchant-specific tax report
     */
    public function getMerchantTaxReport(int $merchantId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = MerchantPurchase::where('user_id', $merchantId)
            ->where('tax_amount', '>', 0)
            ->with(['purchase']);

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $purchases = $query->orderBy('created_at', 'desc')->get();

        return [
            'total_tax' => $purchases->sum('tax_amount'),
            'total_sales' => $purchases->sum('price'),
            'total_orders' => $purchases->count(),
            'tax_from_platform_payments' => $purchases->where('payment_owner_id', 0)->sum('tax_amount'),
            'tax_from_merchant_payments' => $purchases->filter(fn($p) => $p->payment_owner_id > 0)->sum('tax_amount'),
            'purchases' => $purchases,
        ];
    }

    // =========================================================================
    // ADMIN FINANCIAL REPORTS
    // =========================================================================

    /**
     * Get comprehensive admin report for all merchants
     */
    public function getAdminMerchantReport(?string $startDate = null, ?string $endDate = null): array
    {
        $query = MerchantPurchase::query();

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $purchases = $query->get();

        $merchantReports = [];
        $groupedByMerchant = $purchases->groupBy('user_id');

        foreach ($groupedByMerchant as $merchantId => $merchantPurchases) {
            $merchant = User::find($merchantId);
            $platformPayments = $merchantPurchases->where('payment_owner_id', 0);
            $merchantPaymentsCollection = $merchantPurchases->filter(fn($p) => $p->payment_owner_id > 0);

            $merchantReports[] = [
                'merchant_id' => $merchantId,
                'merchant_name' => $merchant?->shop_name ?? $merchant?->name ?? __('Unknown'),
                'total_sales' => $merchantPurchases->sum('price'),
                'total_commission' => $merchantPurchases->sum('commission_amount'),
                'total_tax' => $merchantPurchases->sum('tax_amount'),
                'total_net' => $merchantPurchases->sum('net_amount'),
                'orders_count' => $merchantPurchases->count(),
                'platform_payments_count' => $platformPayments->count(),
                'platform_payments_total' => $platformPayments->sum('price'),
                'merchant_payments_count' => $merchantPaymentsCollection->count(),
                'merchant_payments_total' => $merchantPaymentsCollection->sum('price'),
                'platform_owes_merchant' => $merchantPurchases->sum('platform_owes_merchant'),
                'merchant_owes_platform' => $merchantPurchases->sum('merchant_owes_platform'),
                'net_balance' => $merchantPurchases->sum('platform_owes_merchant') - $merchantPurchases->sum('merchant_owes_platform'),
            ];
        }

        $platformPaymentsAll = $purchases->where('payment_owner_id', 0);
        $merchantPaymentsAll = $purchases->filter(fn($p) => $p->payment_owner_id > 0);

        return [
            'total_sales' => $purchases->sum('price'),
            'total_commissions' => $purchases->sum('commission_amount'),
            'total_taxes' => $purchases->sum('tax_amount'),
            'total_net_to_merchants' => $purchases->sum('net_amount'),
            'total_orders' => $purchases->count(),
            'platform_payments' => [
                'count' => $platformPaymentsAll->count(),
                'total' => $platformPaymentsAll->sum('price'),
            ],
            'merchant_payments' => [
                'count' => $merchantPaymentsAll->count(),
                'total' => $merchantPaymentsAll->sum('price'),
            ],
            'platform_owes_merchants' => $purchases->sum('platform_owes_merchant'),
            'merchants_owe_platform' => $purchases->sum('merchant_owes_platform'),
            'net_platform_position' => $purchases->sum('merchant_owes_platform') - $purchases->sum('platform_owes_merchant'),
            'merchants' => $merchantReports,
        ];
    }

    /**
     * Get tax report for admin
     */
    public function getAdminTaxReport(?string $startDate = null, ?string $endDate = null): array
    {
        $query = MerchantPurchase::where('tax_amount', '>', 0);

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $purchases = $query->with(['purchase', 'user'])->get();

        $byMerchant = $purchases->groupBy('user_id')->map(function ($items, $merchantId) {
            $merchant = User::find($merchantId);
            return [
                'merchant_id' => $merchantId,
                'merchant_name' => $merchant?->shop_name ?? $merchant?->name ?? __('Unknown'),
                'total_tax' => $items->sum('tax_amount'),
                'total_sales' => $items->sum('price'),
                'orders_count' => $items->count(),
            ];
        })->values();

        $platformPaymentsTax = $purchases->where('payment_owner_id', 0)->sum('tax_amount');
        $merchantPaymentsTax = $purchases->filter(fn($p) => $p->payment_owner_id > 0)->sum('tax_amount');

        return [
            'total_tax_collected' => $purchases->sum('tax_amount'),
            'total_orders_with_tax' => $purchases->count(),
            'total_sales_with_tax' => $purchases->sum('price'),
            'tax_from_platform_payments' => $platformPaymentsTax,
            'tax_from_merchant_payments' => $merchantPaymentsTax,
            'by_merchant' => $byMerchant,
            'purchases' => $purchases,
        ];
    }

    /**
     * Get commission report for admin
     */
    public function getAdminCommissionReport(?string $startDate = null, ?string $endDate = null): array
    {
        $query = MerchantPurchase::where('commission_amount', '>', 0);

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $purchases = $query->with(['purchase', 'user'])->get();

        $byMerchant = $purchases->groupBy('user_id')->map(function ($items, $merchantId) {
            $merchant = User::find($merchantId);
            return [
                'merchant_id' => $merchantId,
                'merchant_name' => $merchant?->shop_name ?? $merchant?->name ?? __('Unknown'),
                'total_commission' => $items->sum('commission_amount'),
                'total_sales' => $items->sum('price'),
                'orders_count' => $items->count(),
                'avg_commission_rate' => $items->sum('price') > 0
                    ? round(($items->sum('commission_amount') / $items->sum('price')) * 100, 2)
                    : 0,
            ];
        })->values();

        return [
            'total_commission' => $purchases->sum('commission_amount'),
            'total_sales' => $purchases->sum('price'),
            'total_orders' => $purchases->count(),
            'avg_commission_rate' => $purchases->sum('price') > 0
                ? round(($purchases->sum('commission_amount') / $purchases->sum('price')) * 100, 2)
                : 0,
            'by_merchant' => $byMerchant,
            'purchases' => $purchases,
        ];
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    public function calculateCommission(int $merchantId, float $amount): float
    {
        $commission = MerchantCommission::where('user_id', $merchantId)->first();
        if (!$commission || !$commission->is_active) {
            return 0;
        }

        return $commission->calculateCommission($amount);
    }

    public function getMerchantTaxSetting(int $merchantId): ?MerchantTaxSetting
    {
        return MerchantTaxSetting::where('user_id', $merchantId)->first();
    }

    public function getMerchantCommissionSetting(int $merchantId): ?MerchantCommission
    {
        return MerchantCommission::where('user_id', $merchantId)->first();
    }

    public function hasMerchantPaymentGateway(int $merchantId): bool
    {
        return MerchantPayment::where('user_id', $merchantId)->where('checkout', 1)->exists();
    }

    public function hasMerchantShipping(int $merchantId): bool
    {
        return Shipping::where('user_id', $merchantId)->exists();
    }

    private function getTransactionDescription(MerchantPurchase $purchase): string
    {
        $paymentMethod = $purchase->payment_owner_id === 0 ? __('Platform Payment') : __('Merchant Payment');

        if ($purchase->shipping_type === 'courier') {
            $shippingMethod = __('Courier Delivery');
        } elseif ($purchase->shipping_owner_id === 0) {
            $shippingMethod = __('Platform Shipping');
        } else {
            $shippingMethod = __('Merchant Shipping');
        }

        return sprintf('%s - %s', $paymentMethod, $shippingMethod);
    }

    public function calculateItemsTotal(array $cartItems, int $merchantId): float
    {
        $total = 0;
        foreach ($cartItems as $item) {
            $itemMerchantId = $item['user_id'] ?? ($item['item']['user_id'] ?? 0);
            if ((int)$itemMerchantId === $merchantId) {
                $total += (float)($item['price'] ?? 0);
            }
        }
        return round($total, 2);
    }
}
