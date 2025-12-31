{{-- Catalog Item Name Component with Language Support --}}
@props([
    'catalogItem' => null,
    'item' => null, // for cart items format
    'vendorId' => null,
    'merchantItemId' => null,
    'showSku' => false, // SKU يعرض في catalog-item-info component
    'target' => '_self',
    'class' => '',
    'nameClass' => '',
    'skuClass' => 'text-muted small',
    'useSearchRoute' => null // null = auto detect, true = force search route, false = force catalog-item route
])

@php
    // Handle different data formats (direct catalog item object vs cart item format)
    $itemData = $catalogItem ?? $item;

    if (!$itemData) {
        return;
    }

    // Extract data based on format
    if (isset($itemData['item'])) {
        // Cart item format: $item['item']['name']
        $sku = $itemData['item']['sku'] ?? '';
        $slug = $itemData['item']['slug'] ?? '';
        $userId = $vendorId ?? $itemData['item']['user_id'] ?? $itemData['user_id'] ?? 0;
        // For merchant_item_id: prioritize explicit prop, then try to infer
        $mpId = $merchantItemId;
        if (!$mpId) {
            // Try to get MP ID if itemData is actually a MerchantItem
            $mpId = ($itemData instanceof \App\Models\MerchantItem)
                ? $itemData->id
                : ($itemData['item']['id'] ?? $itemData['id'] ?? null);
        }
        // Use centralized helper for localized name
        $displayName = getLocalizedProductName($itemData['item']);
    } else {
        // Direct catalog item object format: $catalogItem->name
        $sku = $itemData->sku ?? $itemData['sku'] ?? '';
        $slug = $itemData->slug ?? $itemData['slug'] ?? '';
        $userId = $vendorId ?? $itemData->user_id ?? $itemData['user_id'] ?? 0;
        // For merchant_item_id: prioritize explicit prop
        $mpId = $merchantItemId;
        if (!$mpId) {
            // Check if this is a MerchantItem or need to fetch one
            if ($itemData instanceof \App\Models\MerchantItem) {
                $mpId = $itemData->id;
            } elseif ($itemData instanceof \App\Models\CatalogItem && $userId) {
                // Fetch first active MI for this catalog item and vendor
                $mp = $itemData->merchantItems()->where('user_id', $userId)->where('status', 1)->first();
                $mpId = $mp->id ?? null;
            }
        }
        // Use centralized helper for localized name
        $displayName = getLocalizedProductName($itemData);
    }

    // SKU display
    $displaySku = !empty($sku) ? $sku : '-';

    // Determine which route to use
    $shouldUseSearchRoute = $useSearchRoute;
    if ($shouldUseSearchRoute === null) {
        // Auto-detect: use search route unless we're in specific contexts
        $currentRouteName = request()->route() ? request()->route()->getName() : '';
        $currentPath = request()->path();

        // Use search route (result/{sku}) for admin pages EXCEPT purchases/invoices
        $isAdminPage = str_starts_with($currentPath, 'admin/');
        $isAdminPurchaseOrInvoice = $isAdminPage && (
            str_contains($currentPath, '/purchase') ||
            str_contains($currentPath, '/invoice')
        );

        // Keep catalog-item route for:
        // 1. search-results-page and category pages (front.category)
        // 2. vendor dashboard (all vendor pages)
        // 3. admin purchases and invoices (merchant-specific pages)
        // 4. cart, checkout, and purchase pages (user)
        $keepCatalogItemRoute =
            in_array($currentRouteName, ['search.result', 'front.category']) ||
            str_starts_with($currentPath, 'vendor/') ||
            $isAdminPurchaseOrInvoice ||
            str_starts_with($currentPath, 'user/purchase') ||
            str_contains($currentPath, '/cart') ||
            str_contains($currentPath, '/checkout');

        $shouldUseSearchRoute = !$keepCatalogItemRoute;
    }

    // Route generation
    if ($shouldUseSearchRoute && !empty($sku)) {
        // Use search route: result/{sku}
        $catalogItemRoute = route('search.result', $sku);
    } else {
        // Use catalog item details route
        $catalogItemRoute = !empty($slug) && $userId && $mpId
            ? route('front.catalog-item', ['slug' => $slug, 'merchant_id' => $userId, 'merchant_item_id' => $mpId])
            : '#';
    }
@endphp

<div class="{{ $class }}">
    @if(!empty($slug))
        <a href="{{ $catalogItemRoute }}" target="{{ $target }}" class="{{ $nameClass }}">
            {{ $displayName }}
        </a>
    @else
        <span class="{{ $nameClass }}">{{ $displayName }}</span>
    @endif

    @if($showSku)
        <br>
        <small class="{{ $skuClass }}">
            @if(!empty($slug))
                <a href="{{ $catalogItemRoute }}" target="{{ $target }}">
                    @lang('SKU'): {{ $displaySku }}
                </a>
            @else
                @lang('SKU'): {{ $displaySku }}
            @endif
        </small>
    @endif
</div>

<style>
/* Catalog Item Name Component Styles */
.catalog-item-name-component a {
    color: var(--dark-color);
    text-decoration: none;
    transition: color var(--transition-fast);
    font-weight: 600;
}

.catalog-item-name-component a:hover {
    color: var(--theme-primary);
}

.catalog-item-name-component small a {
    color: var(--theme-text-muted, #6c757d);
    font-weight: 500;
}

.catalog-item-name-component small a:hover {
    color: var(--theme-primary);
    text-decoration: underline;
}

/* RTL Support */
[dir="rtl"] .catalog-item-name-component {
    text-align: right;
}
</style>
