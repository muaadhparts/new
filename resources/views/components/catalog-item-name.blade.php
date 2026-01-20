{{-- Catalog Item Name Component with Language Support --}}
@props([
    'catalogItem' => null,
    'item' => null, // for cart items format
    'merchantUserId' => null,
    'merchantItemId' => null,
    'showSku' => false, // PART_NUMBER يعرض في catalog-item-info component
    'target' => '_self',
    'class' => '',
    'nameClass' => '',
    'skuClass' => 'text-muted small',
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
        $part_number = $itemData['item']['part_number'] ?? '';
        $slug = $itemData['item']['slug'] ?? '';
        $userId = $merchantUserId ?? $itemData['item']['user_id'] ?? $itemData['user_id'] ?? 0;
        // For merchant_item_id: prioritize explicit prop, then try to infer
        $mpId = $merchantItemId;
        if (!$mpId) {
            // Try to get MP ID if itemData is actually a MerchantItem
            $mpId = ($itemData instanceof \App\Models\MerchantItem)
                ? $itemData->id
                : ($itemData['item']['id'] ?? $itemData['id'] ?? null);
        }
        // Use centralized helper for localized name
        $displayName = getLocalizedCatalogItemName($itemData['item']);
    } else {
        // Direct catalog item object format: $catalogItem->name
        $part_number = $itemData->part_number ?? $itemData['part_number'] ?? '';
        $slug = $itemData->slug ?? $itemData['slug'] ?? '';
        $userId = $merchantUserId ?? $itemData->user_id ?? $itemData['user_id'] ?? 0;
        // For merchant_item_id: prioritize explicit prop
        $mpId = $merchantItemId;
        if (!$mpId) {
            // Check if this is a MerchantItem or need to fetch one
            if ($itemData instanceof \App\Models\MerchantItem) {
                $mpId = $itemData->id;
            } elseif ($itemData instanceof \App\Models\CatalogItem && $userId) {
                // Fetch first active MI for this catalog item and merchant
                $mp = $itemData->merchantItems()->where('user_id', $userId)->where('status', 1)->first();
                $mpId = $mp->id ?? null;
            }
        }
        // Use centralized helper for localized name
        $displayName = getLocalizedCatalogItemName($itemData);
    }

    // PART_NUMBER display
    $displaySku = !empty($part_number) ? $part_number : '-';

    // Route generation - always use part-result route with part_number
    if (!empty($part_number)) {
        $catalogItemRoute = route('front.part-result', $part_number);
    } else {
        $catalogItemRoute = '#';
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
                    @lang('PART_NUMBER'): {{ $displaySku }}
                </a>
            @else
                @lang('PART_NUMBER'): {{ $displaySku }}
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
