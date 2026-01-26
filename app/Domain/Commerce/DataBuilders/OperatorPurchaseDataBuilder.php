<?php

namespace App\Domain\Commerce\DataBuilders;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Commerce\Models\MerchantPurchase;
use App\Domain\Identity\Models\User;
use App\Domain\Catalog\Models\QualityBrand;
use App\Domain\Shipping\Models\Shipping;
use App\Domain\Commerce\Services\InvoiceSellerService;
use App\Domain\Shipping\Services\TrackingViewService;

/**
 * OperatorPurchaseDataBuilder
 *
 * Builds all data needed for operator purchase views (details, invoice, print).
 * No queries in Blade - all data pre-computed here.
 */
class OperatorPurchaseDataBuilder
{
    private Purchase $purchase;
    private array $cart;
    private ?int $affiliateUserId;

    // Lookups (loaded once, used many times)
    private array $merchantsLookup = [];
    private array $qualityBrandsLookup = [];
    private array $merchantPurchasesLookup = [];
    private array $branchesLookup = [];
    private array $shippingsLookup = [];

    public function __construct(Purchase $purchase)
    {
        $this->purchase = $purchase;
        $this->cart = $purchase->cart ?? ['items' => []];
        $this->affiliateUserId = $purchase->affilate_user;

        $this->loadAllLookups();
    }

    /**
     * Load all lookups in batch (single query per table)
     */
    private function loadAllLookups(): void
    {
        $cartItems = $this->cart['items'] ?? [];

        // Collect all IDs needed
        $merchantIds = [];
        $qualityBrandIds = [];

        foreach ($cartItems as $item) {
            $userId = $item['item']['user_id'] ?? $item['user_id'] ?? 0;
            if ($userId > 0) {
                $merchantIds[] = $userId;
            }

            $qbId = $item['quality_brand_id'] ?? null;
            if ($qbId) {
                $qualityBrandIds[] = $qbId;
            }
        }

        // Add affiliate user
        if ($this->affiliateUserId) {
            $merchantIds[] = $this->affiliateUserId;
        }

        $merchantIds = array_unique($merchantIds);
        $qualityBrandIds = array_unique($qualityBrandIds);

        // Load merchants
        if (!empty($merchantIds)) {
            $this->merchantsLookup = User::whereIn('id', $merchantIds)
                ->get(['id', 'name', 'shop_name', 'photo'])
                ->keyBy('id')
                ->toArray();
        }

        // Load quality brands
        if (!empty($qualityBrandIds)) {
            $this->qualityBrandsLookup = QualityBrand::whereIn('id', $qualityBrandIds)
                ->get(['id', 'name', 'name_ar'])
                ->keyBy('id')
                ->toArray();
        }

        // Load merchant purchases with branches
        $this->merchantPurchasesLookup = $this->purchase->merchantPurchases()
            ->with(['user', 'merchantBranch'])
            ->get()
            ->keyBy('user_id')
            ->toArray();

        // Build branches lookup
        foreach ($this->purchase->merchantPurchases()->with('merchantBranch')->get() as $mp) {
            if ($mp->merchantBranch) {
                $this->branchesLookup[$mp->user_id] = [
                    'id' => $mp->merchantBranch->id,
                    'name' => $mp->merchantBranch->name,
                    'city' => $mp->merchantBranch->city ?? '',
                    'address' => $mp->merchantBranch->address ?? '',
                ];
            }
        }

        // Load shippings for merchant_shipping_id
        $merchantShippingIds = json_decode($this->purchase->merchant_shipping_id, true) ?? [];
        $shippingIds = array_values(array_filter($merchantShippingIds));
        if (!empty($shippingIds)) {
            $this->shippingsLookup = Shipping::whereIn('id', $shippingIds)
                ->get(['id', 'name', 'price'])
                ->keyBy('id')
                ->toArray();
        }
    }

    /**
     * Build data for details view
     */
    public function forDetails(): array
    {
        $trackingData = app(TrackingViewService::class)->forPurchase($this->purchase);

        // Pre-render countries dropdown HTML for billing/shipping edit modals
        $selectedCountry = $this->purchase->customer_country;
        $countriesHtml = \App\Helpers\LocationHelper::getCountriesOptionsHtml($selectedCountry);

        return [
            'purchase' => $this->purchase,
            'cart' => $this->cart,
            'trackingData' => $trackingData,
            'affiliateUserName' => $this->getAffiliateUserName(),
            'groupedItems' => $this->getGroupedItemsWithData(),
            'branchesLookup' => $this->branchesLookup,
            'merchantPurchasesLookup' => $this->merchantPurchasesLookup,
            'merchantsLookup' => $this->merchantsLookup,
            'qualityBrandsLookup' => $this->qualityBrandsLookup,
            'shippingsLookup' => $this->shippingsLookup,
            'platformName' => platformSettings()->get('site_name', __('Platform')),
            'countriesHtml' => $countriesHtml,
        ];
    }

    /**
     * Build data for invoice view
     */
    public function forInvoice(): array
    {
        $trackingData = app(TrackingViewService::class)->forPurchase($this->purchase);
        $merchantPurchases = $this->purchase->merchantPurchases()->with(['user', 'merchantBranch'])->get();
        $sellersInfoLookup = app(InvoiceSellerService::class)->getSellerInfoBatch($merchantPurchases);

        return [
            'purchase' => $this->purchase,
            'cart' => $this->cart,
            'trackingData' => $trackingData,
            'cartItemsWithData' => $this->getCartItemsWithData(),
            'branchesLookup' => $this->branchesLookup,
            'merchantsLookup' => $this->merchantsLookup,
            'qualityBrandsLookup' => $this->qualityBrandsLookup,
            'sellersInfoLookup' => $sellersInfoLookup,
            'shippingMethodName' => $this->getShippingMethodNameByPrice(),
        ];
    }

    /**
     * Build data for print view
     */
    public function forPrint(): array
    {
        $trackingData = app(TrackingViewService::class)->forPurchase($this->purchase);
        $merchantPurchases = $this->purchase->merchantPurchases()->with(['user', 'merchantBranch'])->get();
        $sellersInfoLookup = app(InvoiceSellerService::class)->getSellerInfoBatch($merchantPurchases);

        return [
            'purchase' => $this->purchase,
            'cart' => $this->cart,
            'trackingData' => $trackingData,
            'cartItemsWithData' => $this->getCartItemsWithData(),
            'branchesLookup' => $this->branchesLookup,
            'merchantsLookup' => $this->merchantsLookup,
            'qualityBrandsLookup' => $this->qualityBrandsLookup,
            'sellersInfoLookup' => $sellersInfoLookup,
            'shippingMethodName' => $this->getShippingMethodNameByPrice(),
        ];
    }

    /**
     * Get affiliate user name
     */
    private function getAffiliateUserName(): ?string
    {
        if (!$this->affiliateUserId) {
            return null;
        }

        $user = $this->merchantsLookup[$this->affiliateUserId] ?? null;
        return $user ? ($user['name'] ?? __('Deleted')) : __('Deleted');
    }

    /**
     * Get cart items grouped by merchant with all related data
     */
    private function getGroupedItemsWithData(): array
    {
        $grouped = [];
        $cartItems = $this->cart['items'] ?? [];

        foreach ($cartItems as $key => $item) {
            $userId = $item['item']['user_id'] ?? $item['user_id'] ?? 0;

            if (!isset($grouped[$userId])) {
                $grouped[$userId] = [
                    'merchant' => $this->getMerchantData($userId),
                    'merchantPurchase' => $this->getMerchantPurchaseData($userId),
                    'branch' => $this->branchesLookup[$userId] ?? null,
                    'shipping' => $this->getMerchantShipping($userId),
                    'items' => [],
                    'total' => 0,
                ];
            }

            $itemWithData = $this->enrichCartItem($item, $key);
            $grouped[$userId]['items'][$key] = $itemWithData;
            $grouped[$userId]['total'] += (float) ($item['price'] ?? 0);
        }

        return $grouped;
    }

    /**
     * Get cart items with pre-computed data (for invoice/print)
     */
    private function getCartItemsWithData(): array
    {
        $result = [];
        $cartItems = $this->cart['items'] ?? [];

        foreach ($cartItems as $key => $item) {
            $result[$key] = $this->enrichCartItem($item, $key);
        }

        return $result;
    }

    /**
     * Enrich a cart item with all related data
     */
    private function enrichCartItem(array $item, $key): array
    {
        $userId = $item['item']['user_id'] ?? $item['user_id'] ?? 0;
        $qbId = $item['quality_brand_id'] ?? null;

        // Merchant data
        $merchant = $this->merchantsLookup[$userId] ?? null;
        $item['_merchant'] = $merchant;
        $item['_merchantName'] = $merchant
            ? ($merchant['shop_name'] ?? $merchant['name'] ?? __('Unknown'))
            : ($userId == 0 ? platformSettings()->get('site_name', __('Platform')) : __('Merchant Removed'));

        // Quality brand data
        $qualityBrand = $qbId ? ($this->qualityBrandsLookup[$qbId] ?? null) : null;
        $item['_qualityBrand'] = $qualityBrand;
        $item['_qualityBrandName'] = $qualityBrand
            ? $this->getLocalizedQualityName($qualityBrand)
            : null;

        // Merchant purchase data
        $merchantPurchase = $this->merchantPurchasesLookup[$userId] ?? null;
        $item['_merchantPurchase'] = $merchantPurchase;

        // Branch data
        $item['_branch'] = $this->branchesLookup[$userId] ?? null;

        // Item condition
        $item['_condition'] = isset($item['item']['item_condition']) && $item['item']['item_condition'] == 1
            ? __('Used')
            : __('New');

        // Computed prices
        $qty = (int) ($item['qty'] ?? 1);
        $totalPrice = (float) ($item['price'] ?? 0);
        $item['_unitPrice'] = $qty > 0 ? $totalPrice / $qty : $totalPrice;
        $item['_totalPrice'] = $totalPrice;
        $item['_qty'] = $qty;

        // Product URL
        $item['_productUrl'] = !empty($item['item']['part_number'])
            ? route('front.part-result', $item['item']['part_number'])
            : '#';

        return $item;
    }

    /**
     * Get merchant data
     */
    private function getMerchantData(int $userId): array
    {
        if ($userId == 0) {
            return [
                'id' => 0,
                'name' => platformSettings()->get('site_name', __('Platform')),
                'shop_name' => platformSettings()->get('site_name', __('Platform')),
                'is_platform' => true,
            ];
        }

        $merchant = $this->merchantsLookup[$userId] ?? null;
        if (!$merchant) {
            return [
                'id' => $userId,
                'name' => __('Merchant Removed'),
                'shop_name' => __('Merchant Removed'),
                'is_platform' => false,
            ];
        }

        return [
            'id' => $merchant['id'],
            'name' => $merchant['name'],
            'shop_name' => $merchant['shop_name'] ?? $merchant['name'],
            'is_platform' => false,
        ];
    }

    /**
     * Get merchant purchase data
     */
    private function getMerchantPurchaseData(int $userId): ?array
    {
        $mp = $this->merchantPurchasesLookup[$userId] ?? null;
        if (!$mp) {
            return null;
        }

        return [
            'id' => $mp['id'],
            'status' => $mp['status'],
            'payment_owner_id' => $mp['payment_owner_id'] ?? 0,
            'shipping_owner_id' => $mp['shipping_owner_id'] ?? 0,
            'shipping_type' => $mp['shipping_type'] ?? 'shipping',
        ];
    }

    /**
     * Get merchant shipping info
     */
    private function getMerchantShipping(int $userId): ?array
    {
        $merchantShippingIds = json_decode($this->purchase->merchant_shipping_id, true) ?? [];
        $shippingId = $merchantShippingIds[$userId] ?? null;

        if (!$shippingId) {
            return null;
        }

        $shipping = $this->shippingsLookup[$shippingId] ?? null;
        if (!$shipping) {
            return null;
        }

        return [
            'id' => $shipping['id'],
            'name' => $shipping['name'],
            'price' => $shipping['price'],
        ];
    }

    /**
     * Get shipping method name by price (for invoice footer)
     */
    private function getShippingMethodNameByPrice(): ?string
    {
        if ($this->purchase->shipping_cost == 0) {
            return null;
        }

        $price = round($this->purchase->shipping_cost / $this->purchase->currency_value, 2);

        // Search in loaded shippings first
        foreach ($this->shippingsLookup as $shipping) {
            if (abs((float) $shipping['price'] - $price) < 0.01) {
                return $shipping['name'];
            }
        }

        // Fallback: query by price
        $shipping = Shipping::where('price', $price)->first(['name']);
        return $shipping ? $shipping->name : null;
    }

    /**
     * Get localized quality brand name
     */
    private function getLocalizedQualityName(array $qualityBrand): string
    {
        $locale = app()->getLocale();
        if ($locale === 'ar' && !empty($qualityBrand['name_ar'])) {
            return $qualityBrand['name_ar'];
        }
        return $qualityBrand['name'] ?? '';
    }
}
