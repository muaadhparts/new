<?php

namespace App\Services;

use App\Models\MerchantCommission;
use App\Models\MerchantPayment;
use App\Models\MerchantPurchase;
use App\Models\MerchantTaxSetting;
use App\Models\Purchase;
use App\Models\Shipping;
use App\Models\User;

class MerchantAccountingService
{
    public function calculateMerchantPurchaseDetails(Purchase $purchase, int $merchantId, array $cartItems): array
    {
        $itemsTotal = $this->calculateItemsTotal($cartItems, $merchantId);

        $commission = $this->calculateCommission($merchantId, $itemsTotal);

        $taxSetting = MerchantTaxSetting::where('user_id', $merchantId)->first();
        $taxAmount = $taxSetting ? $taxSetting->calculateTax($itemsTotal) : 0;

        $netAmount = $itemsTotal - $commission;

        return [
            'items_total' => $itemsTotal,
            'commission_amount' => $commission,
            'tax_amount' => $taxAmount,
            'net_amount' => $netAmount,
        ];
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

    public function calculateCommission(int $merchantId, float $amount): float
    {
        $commission = MerchantCommission::where('user_id', $merchantId)->first();
        if (!$commission || !$commission->is_active) {
            return 0;
        }

        return $commission->calculateCommission($amount);
    }

    public function determineMoneyReceiver(int $merchantId, ?int $paymentGatewayId): string
    {
        if (!$paymentGatewayId) {
            return 'platform';
        }

        $gateway = MerchantPayment::find($paymentGatewayId);
        if (!$gateway) {
            return 'platform';
        }

        if (isset($gateway->user_id) && $gateway->user_id > 0 && $gateway->user_id == $merchantId) {
            return 'merchant';
        }

        return 'platform';
    }

    public function determinePaymentType(int $merchantId, ?int $paymentGatewayId): string
    {
        if (!$paymentGatewayId) {
            return 'platform';
        }

        $gateway = MerchantPayment::find($paymentGatewayId);
        if (!$gateway) {
            return 'platform';
        }

        if (isset($gateway->user_id) && $gateway->user_id > 0 && $gateway->user_id == $merchantId) {
            return 'merchant';
        }

        return 'platform';
    }

    public function determineShippingType(int $merchantId, ?int $shippingId, ?int $courierId): string
    {
        if ($courierId) {
            return 'courier';
        }

        if (!$shippingId) {
            return 'platform'; // Default to platform shipping
        }

        $shipping = Shipping::find($shippingId);
        if (!$shipping) {
            return 'platform';
        }

        if ($shipping->user_id > 0 && $shipping->user_id == $merchantId) {
            return 'merchant';
        }

        return 'platform';
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
        return MerchantPayment::where('user_id', $merchantId)->where('status', 1)->exists();
    }

    public function hasMerchantShipping(int $merchantId): bool
    {
        return Shipping::where('user_id', $merchantId)->exists();
    }

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

        return [
            'total_sales' => $purchases->sum('price'),
            'total_commission' => $purchases->sum('commission_amount'),
            'total_tax' => $purchases->sum('tax_amount'),
            'total_shipping' => $purchases->sum('shipping_cost'),
            'total_packing' => $purchases->sum('packing_cost'),
            'total_courier_fees' => $purchases->sum('courier_fee'),
            'total_net' => $purchases->sum('net_amount'),
            'count_orders' => $purchases->count(),
            'merchant_payments' => $purchases->where('payment_type', 'merchant')->sum('price'),
            'platform_payments' => $purchases->where('payment_type', 'platform')->sum('price'),
            'courier_deliveries' => $purchases->where('shipping_type', 'courier')->count(),
            'shipping_deliveries' => $purchases->whereIn('shipping_type', ['platform', 'merchant'])->count(),
        ];
    }

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
            $merchantReports[] = [
                'merchant_id' => $merchantId,
                'merchant_name' => $merchant ? $merchant->shop_name : 'Unknown',
                'total_sales' => $merchantPurchases->sum('price'),
                'total_commission' => $merchantPurchases->sum('commission_amount'),
                'total_tax' => $merchantPurchases->sum('tax_amount'),
                'total_net' => $merchantPurchases->sum('net_amount'),
                'orders_count' => $merchantPurchases->count(),
                'merchant_payments_count' => $merchantPurchases->where('payment_type', 'merchant')->count(),
                'platform_payments_count' => $merchantPurchases->where('payment_type', 'platform')->count(),
            ];
        }

        return [
            'total_sales' => $purchases->sum('price'),
            'total_commissions' => $purchases->sum('commission_amount'),
            'total_taxes' => $purchases->sum('tax_amount'),
            'total_net_to_merchants' => $purchases->sum('net_amount'),
            'merchants' => $merchantReports,
        ];
    }
}
