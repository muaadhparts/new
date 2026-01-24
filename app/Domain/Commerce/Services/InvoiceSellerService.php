<?php

namespace App\Domain\Commerce\Services;

use App\Models\MerchantPurchase;
use App\Models\User;

/**
 * InvoiceSellerService - Determines the seller info for invoices
 *
 * BUSINESS RULE:
 * - payment_owner_id = 0 → Platform is the seller (platform logo + name)
 * - payment_owner_id > 0 → Merchant is the seller (merchant logo + name)
 * - For COD (Cash on Delivery): follows shipping_owner_id logic
 *
 * @see docs/architecture/multi-merchant.md
 */
class InvoiceSellerService
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Get seller information for invoice display
     *
     * @param MerchantPurchase $merchantPurchase
     * @return array{
     *     name: string,
     *     name_ar: string,
     *     logo: string|null,
     *     logo_url: string|null,
     *     address: string,
     *     phone: string,
     *     is_platform: bool
     * }
     */
    public function getSellerInfo(MerchantPurchase $merchantPurchase): array
    {
        // Determine owner based on payment method
        // For COD (Cash on Delivery), check both payment_owner_id and shipping_owner_id
        $isCod = strtolower($merchantPurchase->payment_method ?? '') === 'cash on delivery'
                || $merchantPurchase->payment_type === 'cod';

        // If COD, use shipping_owner_id for determining the seller
        // Otherwise, use payment_owner_id
        if ($isCod) {
            $isPlatformSeller = $merchantPurchase->isPlatformShipping();
        } else {
            $isPlatformSeller = $merchantPurchase->isPlatformPayment();
        }

        if ($isPlatformSeller) {
            return $this->getPlatformInfo();
        }

        return $this->getMerchantInfo($merchantPurchase);
    }

    /**
     * Get platform (operator) information for invoice
     *
     * @return array
     */
    protected function getPlatformInfo(): array
    {
        $ps = platformSettings();

        $invoiceLogo = $ps->get('invoice_logo');
        $logoUrl = null;
        if (!empty($invoiceLogo)) {
            $logoUrl = asset('assets/images/' . $invoiceLogo);
        }

        return [
            'name' => $ps->get('site_name', 'Platform'),
            'name_ar' => $ps->get('site_name_ar') ?? $ps->get('site_name', 'Platform'),
            'logo' => $invoiceLogo,
            'logo_url' => $logoUrl,
            'address' => $ps->get('shop_address', ''),
            'phone' => $ps->get('phone', ''),
            'is_platform' => true,
        ];
    }

    /**
     * Get merchant information for invoice
     *
     * @param MerchantPurchase $merchantPurchase
     * @return array
     */
    protected function getMerchantInfo(MerchantPurchase $merchantPurchase): array
    {
        $merchant = $merchantPurchase->user;

        // If no merchant found, fallback to platform
        if (!$merchant || !$merchant->id) {
            return $this->getPlatformInfo();
        }

        $logoUrl = null;
        if (!empty($merchant->merchant_logo)) {
            $logoUrl = $this->imageService->getMerchantLogoUrl($merchant->merchant_logo);
        }

        return [
            'name' => $merchant->shop_name ?? $merchant->name ?? 'Merchant',
            'name_ar' => $merchant->shop_name_ar ?? $merchant->shop_name ?? $merchant->name ?? 'Merchant',
            'logo' => $merchant->merchant_logo ?? null,
            'logo_url' => $logoUrl,
            'address' => $merchant->shop_address ?? '',
            'phone' => $merchant->phone ?? '',
            'is_platform' => false,
        ];
    }

    /**
     * Get seller info for multiple merchant purchases
     * Useful for operator views that show multiple merchants
     *
     * @param \Illuminate\Support\Collection $merchantPurchases
     * @return array keyed by merchant_purchase_id
     */
    public function getSellerInfoBatch($merchantPurchases): array
    {
        $result = [];

        foreach ($merchantPurchases as $mp) {
            $result[$mp->id] = $this->getSellerInfo($mp);
        }

        return $result;
    }
}
