<?php

/**
 * PurchaseHelper
 *
 * DEPRECATED: This helper is being phased out.
 * Use Domain Services instead:
 * - CheckoutUserService for auth_check()
 * - AffiliateCommissionService for item_affilate_check()
 * - StockUpdateService for stock_check()
 * - MerchantPurchaseService for merchant_purchase_check()
 * - AcceptedCurrencies for currency code lists
 *
 * @deprecated Use Domain Services instead
 */

namespace App\Helpers;

use App\Domain\Identity\Services\CheckoutUserService;
use App\Domain\Accounting\Services\AffiliateCommissionService;
use App\Domain\Commerce\Services\StockUpdateService;
use App\Domain\Commerce\Services\MerchantPurchaseService;
use App\Domain\Payment\Constants\AcceptedCurrencies;
use Illuminate\Support\Facades\Log;

class PurchaseHelper
{
    /**
     * Register guest user during checkout
     *
     * @deprecated Use CheckoutUserService::registerGuestUser() instead
     */
    public static function auth_check($data)
    {
        try {
            $service = app(CheckoutUserService::class);
            $result = $service->registerGuestUser($data);

            // Convert to legacy format
            return [
                'auth_success' => $result['success'],
                'error_message' => $result['error'],
            ];
        } catch (\Exception $e) {
            Log::error('auth_check error: ' . $e->getMessage());
            return [
                'auth_success' => false,
                'error_message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check affiliate users for cart items
     *
     * @deprecated Use AffiliateCommissionService::calculateCommissions() instead
     */
    public static function item_affilate_check(array $cartItems): ?array
    {
        $service = app(AffiliateCommissionService::class);
        return $service->calculateCommissions($cartItems);
    }

    /**
     * Get affiliate user
     *
     * @deprecated Use AffiliateCommissionService::getAffiliateUser() instead
     */
    public static function affilate_check($id, $sub, $dp = 0)
    {
        $service = app(AffiliateCommissionService::class);
        return $service->getAffiliateUser($id);
    }

    /**
     * Update stock after purchase
     *
     * @deprecated Use StockUpdateService::updateStockFromCart() instead
     */
    public static function stock_check($cart)
    {
        try {
            $service = app(StockUpdateService::class);
            $service->updateStockFromCart($cart);
        } catch (\Exception $e) {
            Log::error('stock_check error: ' . $e->getMessage());
        }
    }

    /**
     * Create MerchantPurchase records
     *
     * @deprecated Use MerchantPurchaseService::createFromCart() instead
     */
    public static function merchant_purchase_check($cart, $purchase, $checkoutData = [])
    {
        try {
            $service = app(MerchantPurchaseService::class);
            $service->createFromCart($cart, $purchase, $checkoutData);
        } catch (\Exception $e) {
            Log::error('merchant_purchase_check error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Mollie accepted currency codes
     *
     * @deprecated Use AcceptedCurrencies::MOLLIE instead
     */
    public static function mollieAcceptedCodes(): array
    {
        return AcceptedCurrencies::MOLLIE;
    }

    /**
     * Flutterwave accepted currency codes
     *
     * @deprecated Use AcceptedCurrencies::FLUTTERWAVE instead
     */
    public static function flutterwaveAcceptedCodes(): array
    {
        return AcceptedCurrencies::FLUTTERWAVE;
    }

    /**
     * Mercadopago accepted currency codes
     *
     * @deprecated Use AcceptedCurrencies::MERCADOPAGO instead
     */
    public static function mercadopagoAcceptedCodes(): array
    {
        return AcceptedCurrencies::MERCADOPAGO;
    }
}
