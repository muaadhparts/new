<?php

namespace App\Domain\Commerce\DataBuilders;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Commerce\Models\MerchantPurchase;
use App\Domain\Identity\Models\User;
use App\Domain\Catalog\Models\CatalogItem;
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
    private array $catalogItemExistsLookup = [];

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
        $catalogItemIds = [];

        foreach ($cartItems as $item) {
            $userId = $item['item']['user_id'] ?? $item['user_id'] ?? 0;
            if ($userId > 0) {
                $merchantIds[] = $userId;
            }

            $qbId = $item['quality_brand_id'] ?? null;
            if ($qbId) {
                $qualityBrandIds[] = $qbId;
            }

            // Collect catalog item IDs for existence check
            $catalogItemId = $item['item']['id'] ?? null;
            if ($catalogItemId) {
                $catalogItemIds[] = $catalogItemId;
            }
        }

        // Add affiliate user
        if ($this->affiliateUserId) {
            $merchantIds[] = $this->affiliateUserId;
        }

        $merchantIds = array_unique($merchantIds);
        $qualityBrandIds = array_unique($qualityBrandIds);
        $catalogItemIds = array_unique($catalogItemIds);

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

        // Load catalog item existence (for edit button in view - DATA_FLOW_POLICY)
        if (!empty($catalogItemIds)) {
            $existingIds = CatalogItem::whereIn('id', $catalogItemIds)->pluck('id')->toArray();
            $this->catalogItemExistsLookup = array_flip($existingIds);
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
        // Ensure it's an array before filtering
        if (!is_array($merchantShippingIds)) {
            $merchantShippingIds = [];
        }
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
            // PRE-COMPUTED: All formatted display values (DATA_FLOW_POLICY)
            'purchaseDisplay' => $this->buildPurchaseDisplay(),
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

        // PRE-COMPUTED: Seller display info for header (DATA_FLOW_POLICY - no @php in view)
        $sellerDisplay = $this->getSellerDisplayInfo($sellersInfoLookup);

        // PRE-COMPUTED: Subtotal calculation (DATA_FLOW_POLICY - no @php in view)
        $subtotal = $this->calculateSubtotal();

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
            'showPlatform' => $sellerDisplay['showPlatform'],
            'firstSeller' => $sellerDisplay['firstSeller'],
            'subtotal' => $subtotal,
            // PRE-COMPUTED: All formatted display values (DATA_FLOW_POLICY)
            'purchaseDisplay' => $this->buildPurchaseDisplay(),
            'subtotal_formatted' => \PriceHelper::showCurrencyPrice($subtotal * ($this->purchase->currency_value ?: 1)),
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

        // PRE-COMPUTED: Seller display info for header (DATA_FLOW_POLICY - no @php in view)
        $sellerDisplay = $this->getSellerDisplayInfo($sellersInfoLookup);

        // PRE-COMPUTED: Subtotal calculation (DATA_FLOW_POLICY - no @php in view)
        $subtotal = $this->calculateSubtotal();

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
            'showPlatform' => $sellerDisplay['showPlatform'],
            'firstSeller' => $sellerDisplay['firstSeller'],
            'subtotal' => $subtotal,
            // PRE-COMPUTED: All formatted display values (DATA_FLOW_POLICY)
            'purchaseDisplay' => $this->buildPurchaseDisplay(),
            'subtotal_formatted' => \PriceHelper::showOrderCurrencyPrice(($subtotal * ($this->purchase->currency_value ?: 1)), $this->purchase->currency_sign),
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
        $currencyValue = $this->purchase->currency_value ?: 1;

        foreach ($cartItems as $key => $item) {
            $userId = $item['item']['user_id'] ?? $item['user_id'] ?? 0;

            if (!isset($grouped[$userId])) {
                $merchantData = $this->getMerchantData($userId);
                $shipping = $this->getMerchantShipping($userId);
                $grouped[$userId] = [
                    'merchant' => $merchantData,
                    'merchantPurchase' => $this->getMerchantPurchaseData($userId),
                    'branch' => $this->branchesLookup[$userId] ?? null,
                    'shipping' => $shipping,
                    'items' => [],
                    'total' => 0,
                    // PRE-COMPUTED: Merchant display name (DATA_FLOW_POLICY - no @php in view)
                    '_merchantDisplayName' => $merchantData['shop_name'] ?? $merchantData['name'] ?? __('Unknown Merchant'),
                    // PRE-COMPUTED: Shipping formatted price (DATA_FLOW_POLICY)
                    '_shipping_formatted' => $shipping ? \PriceHelper::showCurrencyPrice($shipping['price'] * $currencyValue) : null,
                ];
            }

            $itemWithData = $this->enrichCartItem($item, $key);
            $grouped[$userId]['items'][$key] = $itemWithData;
            $grouped[$userId]['total'] += (float) ($item['price'] ?? 0);
        }

        // PRE-COMPUTED: Total formatted prices for each group (DATA_FLOW_POLICY)
        foreach ($grouped as $userId => &$groupData) {
            $shippingPrice = $groupData['shipping']['price'] ?? 0;
            $totalWithShipping = $groupData['total'] + $shippingPrice;
            $groupData['_total_formatted'] = \PriceHelper::showCurrencyPrice($totalWithShipping * $currencyValue);
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
        // PRE-COMPUTED: Merchant purchase status (DATA_FLOW_POLICY - no @php in view)
        $item['_mpStatus'] = $merchantPurchase['status'] ?? 'pending';

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

        // PRE-COMPUTED: Formatted prices (DATA_FLOW_POLICY)
        $currencyValue = $this->purchase->currency_value ?: 1;
        $item['_unitPrice_formatted'] = \PriceHelper::showCurrencyPrice($item['_unitPrice'] * $currencyValue);
        $item['_totalPrice_formatted'] = \PriceHelper::showCurrencyPrice($item['_totalPrice'] * $currencyValue);

        // PRE-COMPUTED: Catalog item existence check (DATA_FLOW_POLICY - no query in view)
        $catalogItemId = $item['item']['id'] ?? null;
        $item['_catalogItemExists'] = $catalogItemId ? $this->catalogItemExists($catalogItemId) : false;

        // PRE-COMPUTED: Discount text (DATA_FLOW_POLICY)
        $discount = $item['discount'] ?? 0;
        $item['_discount_text'] = $discount == 0 ? '' : '(' . $discount . '% ' . __('Off') . ')';

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

    /**
     * Get seller display info for invoice/print header
     * PRE-COMPUTED: Determines whether to show platform logo or merchant logo
     */
    private function getSellerDisplayInfo(array $sellersInfoLookup): array
    {
        // Get first seller info for header (or platform if multiple)
        $firstSeller = count($sellersInfoLookup) > 0 ? reset($sellersInfoLookup) : null;

        // Show platform if: no seller found, multiple sellers, or first seller is platform
        $showPlatform = !$firstSeller || count($sellersInfoLookup) > 1 || ($firstSeller['is_platform'] ?? true);

        return [
            'firstSeller' => $firstSeller,
            'showPlatform' => $showPlatform,
        ];
    }

    /**
     * Calculate subtotal for invoice/print
     * PRE-COMPUTED: Sum of all cart item prices
     */
    private function calculateSubtotal(): float
    {
        $subtotal = 0;
        $cartItems = $this->cart['items'] ?? [];

        foreach ($cartItems as $item) {
            $price = (float) ($item['price'] ?? 0);
            // Match the original calculation: round((price / currency_value) * currency_value, 2)
            // This essentially rounds the price to 2 decimal places
            $subtotal += round($price, 2);
        }

        return $subtotal;
    }

    /**
     * Build pre-computed purchase display values
     * PRE-COMPUTED: All formatted values for purchase (DATA_FLOW_POLICY)
     */
    private function buildPurchaseDisplay(): array
    {
        $p = $this->purchase;
        $currencySign = $p->currency_sign;
        $currencyValue = $p->currency_value ?: 1;

        return [
            'shipping_cost_formatted' => \PriceHelper::showOrderCurrencyPrice($p->shipping_cost, $currencySign),
            'tax_formatted' => \PriceHelper::showOrderCurrencyPrice(($p->tax / $currencyValue), $currencySign),
            'wallet_price_formatted' => \PriceHelper::showOrderCurrencyPrice(($p->wallet_price * $currencyValue), $currencySign),
            'pay_amount_formatted' => \PriceHelper::showOrderCurrencyPrice(($p->pay_amount * $currencyValue), $currencySign),
            'total_cost_formatted' => \PriceHelper::showOrderCurrencyPrice((($p->pay_amount + $p->wallet_price) * $currencyValue), $currencySign),
            'affilate_charge_formatted' => \PriceHelper::showOrderCurrencyPrice(($p->affilate_charge * $currencyValue), $currencySign),
            'discount_amount_formatted' => \PriceHelper::showOrderCurrencyPrice($p->discount_amount, $currencySign),
            'created_at_formatted' => $p->created_at ? $p->created_at->format('d-M-Y H:i:s a') : 'N/A',
            'created_at_date_only' => $p->created_at ? $p->created_at->format('d-M-Y') : 'N/A',
        ];
    }

    /**
     * Check if catalog item exists
     */
    private function catalogItemExists(int $id): bool
    {
        return isset($this->catalogItemExistsLookup[$id]);
    }
}
