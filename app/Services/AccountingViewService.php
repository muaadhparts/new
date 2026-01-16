<?php

namespace App\Services;

use App\Models\MerchantPurchase;
use App\Models\Purchase;
use App\Models\DeliveryCourier;
use App\Services\MonetaryUnitService;
use Illuminate\Support\Collection;

/**
 * AccountingViewService
 *
 * Provides pre-computed DTOs for accounting views.
 * NO calculations happen in Blade - all values are ready to display.
 *
 * Architecture:
 * - Controller calls this service
 * - Service computes ALL display values
 * - Blade receives flat arrays with strings/booleans only
 * - Blade does ZERO logic - only rendering
 */
class AccountingViewService
{
    protected PaymentAccountingService $accountingService;

    public function __construct(PaymentAccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Get payment/accounting data for a single MerchantPurchase (for details view)
     */
    public function forMerchantPurchase(MerchantPurchase $mp): array
    {
        $currencySign = $mp->purchase->currency_sign ?? monetaryUnit()->getBaseSign();

        return [
            // === Payment Method Info ===
            // payment_type = 'merchant' or 'platform' (who owns the payment gateway)
            // COD is determined by cod_amount > 0
            'paymentType' => $mp->payment_type ?? 'platform',
            'paymentTypeLabel' => $this->getPaymentTypeLabel($mp->payment_type),
            'isCod' => ($mp->cod_amount ?? 0) > 0,
            'isOnlinePayment' => ($mp->cod_amount ?? 0) == 0,

            // === Payment Status ===
            'paymentStatus' => $mp->purchase->payment_status ?? 'Pending',
            'paymentStatusLabel' => $this->getPaymentStatusLabel($mp->purchase->payment_status),
            'paymentStatusColor' => $this->getPaymentStatusColor($mp->purchase->payment_status),
            'isPaid' => ($mp->purchase->payment_status ?? '') === 'Completed',
            'isPending' => ($mp->purchase->payment_status ?? '') !== 'Completed',

            // === Money Holder Info ===
            'moneyHolder' => $mp->money_holder ?? 'pending',
            'moneyHolderLabel' => $this->getMoneyHolderLabel($mp->money_holder),
            'moneyHolderIcon' => $this->getMoneyHolderIcon($mp->money_holder),

            // === Delivery Method Info ===
            'deliveryMethod' => $mp->delivery_method ?? 'none',
            'deliveryMethodLabel' => $this->getDeliveryMethodLabel($mp->delivery_method),
            'deliveryProvider' => $mp->delivery_provider ?? null,
            'hasLocalCourier' => $mp->delivery_method === MerchantPurchase::DELIVERY_LOCAL_COURIER,
            'hasShippingCompany' => $mp->delivery_method === MerchantPurchase::DELIVERY_SHIPPING_COMPANY,

            // === Collection Status (COD) ===
            'collectionStatus' => $mp->collection_status ?? 'not_applicable',
            'collectionStatusLabel' => $this->getCollectionStatusLabel($mp->collection_status),
            'collectionStatusColor' => $this->getCollectionStatusColor($mp->collection_status),
            'isCollected' => ($mp->collection_status ?? '') === MerchantPurchase::COLLECTION_COLLECTED,
            'isPendingCollection' => ($mp->collection_status ?? '') === MerchantPurchase::COLLECTION_PENDING,
            'collectedAt' => $mp->collected_at?->format('Y-m-d H:i'),
            'collectedAtHuman' => $mp->collected_at?->diffForHumans(),
            'collectedBy' => $mp->collected_by,

            // === Amounts (pre-formatted) ===
            'priceFormatted' => $currencySign . ' ' . number_format($mp->price ?? 0, 2),
            'commissionFormatted' => $currencySign . ' ' . number_format($mp->commission_amount ?? 0, 2),
            'taxFormatted' => $currencySign . ' ' . number_format($mp->tax_amount ?? 0, 2),
            'shippingCostFormatted' => $currencySign . ' ' . number_format($mp->shipping_cost ?? 0, 2),
            'courierFeeFormatted' => $currencySign . ' ' . number_format($mp->courier_fee ?? 0, 2),
            'netAmountFormatted' => $currencySign . ' ' . number_format($mp->net_amount ?? 0, 2),
            'codAmountFormatted' => $currencySign . ' ' . number_format($mp->cod_amount ?? 0, 2),

            // === Debt Ledger (pre-formatted) ===
            'platformOwesMerchantFormatted' => $currencySign . ' ' . number_format($mp->platform_owes_merchant ?? 0, 2),
            'merchantOwesPlatformFormatted' => $currencySign . ' ' . number_format($mp->merchant_owes_platform ?? 0, 2),
            'courierOwesMerchantFormatted' => $currencySign . ' ' . number_format($mp->courier_owes_merchant ?? 0, 2),
            'courierOwesPlatformFormatted' => $currencySign . ' ' . number_format($mp->courier_owes_platform ?? 0, 2),
            'shippingOwesMerchantFormatted' => $currencySign . ' ' . number_format($mp->shipping_company_owes_merchant ?? 0, 2),
            'shippingOwesPlatformFormatted' => $currencySign . ' ' . number_format($mp->shipping_company_owes_platform ?? 0, 2),

            // === Debt Flags ===
            'hasPlatformDebt' => ($mp->platform_owes_merchant ?? 0) > 0,
            'hasMerchantDebt' => ($mp->merchant_owes_platform ?? 0) > 0,
            'hasCourierDebt' => ($mp->courier_owes_merchant ?? 0) > 0 || ($mp->courier_owes_platform ?? 0) > 0,
            'hasShippingDebt' => ($mp->shipping_company_owes_merchant ?? 0) > 0 || ($mp->shipping_company_owes_platform ?? 0) > 0,

            // === Settlement Status ===
            'settlementStatus' => $mp->settlement_status ?? 'pending',
            'settlementStatusLabel' => $this->getSettlementStatusLabel($mp->settlement_status),
            'settlementStatusColor' => $this->getSettlementStatusColor($mp->settlement_status),
            'isSettled' => ($mp->settlement_status ?? '') === 'settled',
            'settledAt' => $mp->settled_at?->format('Y-m-d H:i'),

            // === Raw Values (for calculations in Service, not view) ===
            'price' => (float) ($mp->price ?? 0),
            'commission' => (float) ($mp->commission_amount ?? 0),
            'tax' => (float) ($mp->tax_amount ?? 0),
            'netAmount' => (float) ($mp->net_amount ?? 0),
            'codAmount' => (float) ($mp->cod_amount ?? 0),
        ];
    }

    /**
     * Get merchant debt summary (for dashboard/reports)
     */
    public function forMerchantDashboard(int $merchantId): array
    {
        $summary = $this->accountingService->getMerchantDebtSummary($merchantId);
        $currencySign = monetaryUnit()->getBaseSign(); // TODO: Get from settings

        return [
            // === Who Owes You ===
            'platformOwesYou' => $summary['platform_owes_you'],
            'platformOwesYouFormatted' => $currencySign . ' ' . number_format($summary['platform_owes_you'], 2),
            'couriersOweYou' => $summary['couriers_owe_you'],
            'couriersOweYouFormatted' => $currencySign . ' ' . number_format($summary['couriers_owe_you'], 2),
            'shippingOwesYou' => $summary['shipping_companies_owe_you'],
            'shippingOwesYouFormatted' => $currencySign . ' ' . number_format($summary['shipping_companies_owe_you'], 2),

            // === What You Owe ===
            'youOwePlatform' => $summary['you_owe_platform'],
            'youOwePlatformFormatted' => $currencySign . ' ' . number_format($summary['you_owe_platform'], 2),

            // === Net Balance ===
            'netReceivable' => $summary['net_receivable'],
            'netReceivableFormatted' => $currencySign . ' ' . number_format(abs($summary['net_receivable']), 2),
            'isNetPositive' => $summary['net_receivable'] >= 0,
            'netBalanceLabel' => $summary['net_receivable'] >= 0 ? __('مستحق لك') : __('مستحق عليك'),
            'netBalanceColor' => $summary['net_receivable'] >= 0 ? 'success' : 'danger',

            // === Flags ===
            'hasReceivables' => $summary['platform_owes_you'] > 0
                || $summary['couriers_owe_you'] > 0
                || $summary['shipping_companies_owe_you'] > 0,
            'hasPayables' => $summary['you_owe_platform'] > 0,
        ];
    }

    /**
     * Get platform debt summary (for admin dashboard)
     */
    public function forPlatformDashboard(): array
    {
        $summary = $this->accountingService->getPlatformDebtSummary();
        $currencySign = monetaryUnit()->getBaseSign();

        return [
            // === What Platform Owes ===
            'owesToMerchants' => $summary['owes_to_merchants'],
            'owesToMerchantsFormatted' => $currencySign . ' ' . number_format($summary['owes_to_merchants'], 2),

            // === What Others Owe Platform ===
            'merchantsOwe' => $summary['merchants_owe'],
            'merchantsOweFormatted' => $currencySign . ' ' . number_format($summary['merchants_owe'], 2),
            'couriersOwe' => $summary['couriers_owe'],
            'couriersOweFormatted' => $currencySign . ' ' . number_format($summary['couriers_owe'], 2),
            'shippingCompaniesOwe' => $summary['shipping_companies_owe'],
            'shippingCompaniesOweFormatted' => $currencySign . ' ' . number_format($summary['shipping_companies_owe'], 2),

            // === Net Balance ===
            'netReceivable' => $summary['net_receivable'],
            'netReceivableFormatted' => $currencySign . ' ' . number_format(abs($summary['net_receivable']), 2),
            'isNetPositive' => $summary['net_receivable'] >= 0,
            'netBalanceLabel' => $summary['net_receivable'] >= 0 ? __('صافي مستحق للمنصة') : __('صافي مستحق على المنصة'),
            'netBalanceColor' => $summary['net_receivable'] >= 0 ? 'success' : 'warning',
        ];
    }

    /**
     * Get courier debt summary (for courier dashboard)
     */
    public function forCourierDashboard(int $courierId): array
    {
        $summary = $this->accountingService->getCourierDebtSummary($courierId);
        $currencySign = monetaryUnit()->getBaseSign();

        return [
            'codCollected' => $summary['cod_collected'],
            'codCollectedFormatted' => $currencySign . ' ' . number_format($summary['cod_collected'], 2),
            'feesEarned' => $summary['fees_earned'],
            'feesEarnedFormatted' => $currencySign . ' ' . number_format($summary['fees_earned'], 2),
            'owesToPlatform' => $summary['owes_to_platform'],
            'owesToPlatformFormatted' => $currencySign . ' ' . number_format($summary['owes_to_platform'], 2),
            'hasDebt' => $summary['owes_to_platform'] > 0,
        ];
    }

    // === Label Helpers ===

    protected function getPaymentMethodLabel(?string $method): string
    {
        return match (strtolower($method ?? '')) {
            'cod', 'cash on delivery' => __('الدفع عند الاستلام'),
            'stripe' => __('Stripe'),
            'paypal' => __('PayPal'),
            'myfatoorah' => __('MyFatoorah'),
            default => $method ?? __('غير محدد'),
        };
    }

    /**
     * Get payment type label (who owns the payment gateway)
     */
    protected function getPaymentTypeLabel(?string $type): string
    {
        return match (strtolower($type ?? '')) {
            'merchant' => __('بوابة التاجر'),
            'platform' => __('بوابة المنصة'),
            default => __('غير محدد'),
        };
    }

    protected function getPaymentStatusLabel(?string $status): string
    {
        return match ($status) {
            'Completed' => __('مدفوع'),
            'Pending' => __('قيد الانتظار'),
            'Failed' => __('فشل'),
            'Refunded' => __('مسترد'),
            default => $status ?? __('غير محدد'),
        };
    }

    protected function getPaymentStatusColor(?string $status): string
    {
        return match ($status) {
            'Completed' => 'success',
            'Pending' => 'warning',
            'Failed' => 'danger',
            'Refunded' => 'info',
            default => 'secondary',
        };
    }

    protected function getMoneyHolderLabel(?string $holder): string
    {
        return match ($holder) {
            MerchantPurchase::MONEY_HOLDER_PLATFORM => __('المنصة'),
            MerchantPurchase::MONEY_HOLDER_MERCHANT => __('التاجر'),
            MerchantPurchase::MONEY_HOLDER_COURIER => __('المندوب'),
            MerchantPurchase::MONEY_HOLDER_SHIPPING => __('شركة الشحن'),
            MerchantPurchase::MONEY_HOLDER_PENDING => __('قيد التحصيل'),
            default => __('غير محدد'),
        };
    }

    protected function getMoneyHolderIcon(?string $holder): string
    {
        return match ($holder) {
            MerchantPurchase::MONEY_HOLDER_PLATFORM => 'fas fa-building',
            MerchantPurchase::MONEY_HOLDER_MERCHANT => 'fas fa-store',
            MerchantPurchase::MONEY_HOLDER_COURIER => 'fas fa-motorcycle',
            MerchantPurchase::MONEY_HOLDER_SHIPPING => 'fas fa-truck',
            MerchantPurchase::MONEY_HOLDER_PENDING => 'fas fa-clock',
            default => 'fas fa-question',
        };
    }

    protected function getDeliveryMethodLabel(?string $method): string
    {
        return match ($method) {
            MerchantPurchase::DELIVERY_LOCAL_COURIER => __('مندوب محلي'),
            MerchantPurchase::DELIVERY_SHIPPING_COMPANY => __('شركة شحن'),
            MerchantPurchase::DELIVERY_PICKUP => __('استلام من المتجر'),
            MerchantPurchase::DELIVERY_DIGITAL => __('رقمي'),
            MerchantPurchase::DELIVERY_NONE => __('بدون توصيل'),
            default => __('غير محدد'),
        };
    }

    protected function getCollectionStatusLabel(?string $status): string
    {
        return match ($status) {
            MerchantPurchase::COLLECTION_NOT_APPLICABLE => __('لا ينطبق'),
            MerchantPurchase::COLLECTION_PENDING => __('قيد التحصيل'),
            MerchantPurchase::COLLECTION_COLLECTED => __('تم التحصيل'),
            MerchantPurchase::COLLECTION_FAILED => __('فشل التحصيل'),
            default => __('غير محدد'),
        };
    }

    protected function getCollectionStatusColor(?string $status): string
    {
        return match ($status) {
            MerchantPurchase::COLLECTION_NOT_APPLICABLE => 'secondary',
            MerchantPurchase::COLLECTION_PENDING => 'warning',
            MerchantPurchase::COLLECTION_COLLECTED => 'success',
            MerchantPurchase::COLLECTION_FAILED => 'danger',
            default => 'secondary',
        };
    }

    protected function getSettlementStatusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => __('قيد التسوية'),
            'partial' => __('تسوية جزئية'),
            'settled' => __('تمت التسوية'),
            default => __('غير محدد'),
        };
    }

    protected function getSettlementStatusColor(?string $status): string
    {
        return match ($status) {
            'pending' => 'warning',
            'partial' => 'info',
            'settled' => 'success',
            default => 'secondary',
        };
    }
}
