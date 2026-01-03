{{--
    Unified Catalog Item Info Component
    Displays: PART_NUMBER, Brand, Quality Brand, Merchant, Stock

    Usage Examples:
    <x-catalog-item-info :catalog-item="$catalogItem" />
    <x-catalog-item-info :catalog-item="$catalogItem" :mp="$merchantItem" display-mode="badges" />
    <x-catalog-item-info :catalog-item="$catalogItem" display-mode="list" :show-part_number="true" />
--}}

@props([
    'catalogItem' => null,
    'mp' => null,
    'displayMode' => 'inline',
    'showSku' => true,
    'showBrand' => true,
    'showQualityBrand' => true,
    'showMerchant' => true,
    'showStock' => true
])

@php
    // Safety check: ensure catalog item exists
    if (!$catalogItem) {
        return;
    }

    // STRICT: NO FALLBACK - $mp MUST be passed from Controller
    // If on catalog item detail page and $mp is missing, throw error
    if (!$mp && request()->routeIs('front.catalog-item')) {
        throw new \LogicException(
            "CatalogItemInfo component: \$mp (MerchantItem) is REQUIRED on catalog item detail page. " .
            "Pass it explicitly from Controller. CatalogItem ID: {$catalogItem->id}"
        );
    }

    // Extract all display values (using localized names)
    // NO FALLBACK - if $mp is null, merchant-specific fields will be null
    // NOTE: All relationships MUST be eager loaded by Controller before passing to view
    $part_number = $catalogItem->part_number ?? null;
    $brandName = $catalogItem->brand?->localized_name;
    $brandLogo = $catalogItem->brand?->photo_url;
    $qualityBrand = $mp?->qualityBrand;
    $qualityBrandName = $qualityBrand?->localized_name;
    $qualityBrandLogo = $qualityBrand?->logo_url;
    $merchantName = $mp?->user ? getLocalizedShopName($mp->user) : null;
    $stock = $mp?->stock;

    // Format stock display with colors
    if ($stock === null || $stock === '') {
        $stockText = __('Unlimited');
        $stockClass = 'text-success';
        $stockBadgeClass = 'bg-success';
    } elseif ($stock == 0) {
        $stockText = __('Out Of Stock');
        $stockClass = 'text-danger';
        $stockBadgeClass = 'bg-danger';
    } else {
        $stockText = $stock . ' ' . __('Available');
        $stockClass = 'text-primary';
        $stockBadgeClass = 'bg-primary';
    }
@endphp

@if($displayMode === 'badges')
    {{-- Badge Display Mode - Best for catalog item details pages --}}
    <div class="catalog-item-info-badges d-flex flex-wrap gap-2 mb-2">
        @if($showSku && $part_number)
            <span class="badge bg-secondary text-white">
                <i class="fas fa-barcode me-1"></i>{{ $part_number }}
            </span>
        @endif

        @if($showBrand && $brandName)
            <span class="badge bg-light text-dark d-inline-flex align-items-center">
                @if($brandLogo)
                    <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="brand-logo muaadh-quality-logo-sm me-1">
                @else
                    <i class="fas fa-tag me-1"></i>
                @endif
                {{ $brandName }}
            </span>
        @endif

        @if($showQualityBrand && $qualityBrandName)
            <span class="badge bg-light text-dark d-inline-flex align-items-center">
                @if($qualityBrandLogo)
                    <img src="{{ $qualityBrandLogo }}" alt="{{ $qualityBrandName }}" class="quality-brand-logo muaadh-quality-logo-sm me-1">
                @else
                    <i class="fas fa-certificate me-1"></i>
                @endif
                {{ $qualityBrandName }}
            </span>
        @endif

        @if($showMerchant && $merchantName)
            <span class="badge bg-light text-dark">
                <i class="fas fa-store me-1"></i>{{ $merchantName }}
            </span>
        @endif

        @if($showStock)
            <span class="badge {{ $stockBadgeClass }} text-white">
                <i class="fas fa-boxes me-1"></i>{{ $stockText }}
            </span>
        @endif
    </div>

@elseif($displayMode === 'list')
    {{-- List Display Mode - Best for detailed list views --}}
    <ul class="catalog-item-info-list list-unstyled mb-2">
        @if($showSku && $part_number)
            <li class="small text-muted">
                <strong>{{ __('PART_NUMBER') }}:</strong> <span class="font-monospace">{{ $part_number }}</span>
            </li>
        @endif

        @if($showBrand && $brandName)
            <li class="small d-flex align-items-center">
                <strong>{{ __('Brand') }}:</strong>
                @if($brandLogo)
                    <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="brand-logo muaadh-quality-logo-md mx-1">
                @endif
                <span class="ms-1">{{ $brandName }}</span>
            </li>
        @endif

        @if($showQualityBrand && $qualityBrandName)
            <li class="small d-flex align-items-center">
                <strong>{{ __('Quality Brand') }}:</strong>
                @if($qualityBrandLogo)
                    <img src="{{ $qualityBrandLogo }}" alt="{{ $qualityBrandName }}" class="quality-brand-logo muaadh-quality-logo-md mx-1">
                @endif
                <span class="ms-1">{{ $qualityBrandName }}</span>
            </li>
        @endif

        @if($showMerchant && $merchantName)
            <li class="small">
                <strong>{{ __('Merchant') }}:</strong> {{ $merchantName }}
            </li>
        @endif

        @if($showStock)
            <li class="small {{ $stockClass }}">
                <strong>{{ __('Stock') }}:</strong> {{ $stockText }}
            </li>
        @endif
    </ul>

@elseif($displayMode === 'modal')
    {{-- Modal Display Mode - Best for quick view modals --}}
    <div class="catalog-item-info-modal mb-3">
        <table class="table table-sm table-borderless mb-0">
            <tbody>
                @if($showSku && $part_number)
                    <tr>
                        <td class="text-muted" style="width: 100px;"><i class="fas fa-barcode me-1"></i>{{ __('PART_NUMBER') }}</td>
                        <td><code>{{ $part_number }}</code></td>
                    </tr>
                @endif

                @if($showBrand && $brandName)
                    <tr>
                        <td class="text-muted"><i class="fas fa-tag me-1"></i>{{ __('Brand') }}</td>
                        <td class="d-flex align-items-center">
                            @if($brandLogo)
                                <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="brand-logo muaadh-quality-logo-md me-2">
                            @endif
                            {{ $brandName }}
                        </td>
                    </tr>
                @endif

                @if($showQualityBrand && $qualityBrandName)
                    <tr>
                        <td class="text-muted"><i class="fas fa-certificate me-1"></i>{{ __('Quality') }}</td>
                        <td class="d-flex align-items-center">
                            @if($qualityBrandLogo)
                                <img src="{{ $qualityBrandLogo }}" alt="{{ $qualityBrandName }}" class="quality-brand-logo muaadh-quality-logo-md me-2">
                            @endif
                            {{ $qualityBrandName }}
                        </td>
                    </tr>
                @endif

                @if($showMerchant && $merchantName)
                    <tr>
                        <td class="text-muted"><i class="fas fa-store me-1"></i>{{ __('Merchant') }}</td>
                        <td>{{ $merchantName }}</td>
                    </tr>
                @endif

                @if($showStock)
                    <tr>
                        <td class="text-muted"><i class="fas fa-boxes me-1"></i>{{ __('Stock') }}</td>
                        <td class="{{ $stockClass }}">{{ $stockText }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

@elseif($displayMode === 'compact')
    {{-- Compact Display Mode - Best for modern catalog item cards --}}
    <div class="catalog-item-info-compact">
        @if($showSku && $part_number)
            <span class="catalog-item-info-compact__item catalog-item-info-compact__sku">
                {{ $part_number }}
            </span>
        @endif

        @if($showMerchant && $merchantName)
            <span class="catalog-item-info-compact__item catalog-item-info-compact__merchant">
                <i class="fas fa-store"></i> {{ Str::limit($merchantName, 15) }}
            </span>
        @endif

        @if($showQualityBrand && $qualityBrandName)
            <span class="catalog-item-info-compact__item catalog-item-info-compact__quality">
                @if($qualityBrandLogo)
                    <img src="{{ $qualityBrandLogo }}" alt="{{ $qualityBrandName }}" class="catalog-item-info-compact__logo">
                @endif
                {{ $qualityBrandName }}
            </span>
        @endif
    </div>

@else
    {{-- Inline Display Mode (Default) - Best for compact catalog item cards --}}
    <div class="catalog-item-info-inline small text-muted mb-1">
        @if($showSku && $part_number)
            <span class="me-2">
                <i class="fas fa-barcode me-1"></i><span class="font-monospace">{{ $part_number }}</span>
            </span>
        @endif

        @if($showBrand && $brandName)
            <span class="me-2 d-inline-flex align-items-center">
                @if($brandLogo)
                    <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="brand-logo muaadh-quality-logo-xs me-1">
                @else
                    <i class="fas fa-tag me-1"></i>
                @endif
                {{ $brandName }}
            </span>
        @endif

        @if($showQualityBrand && $qualityBrandName)
            <span class="me-2 d-inline-flex align-items-center">
                @if($qualityBrandLogo)
                    <img src="{{ $qualityBrandLogo }}" alt="{{ $qualityBrandName }}" class="quality-brand-logo muaadh-quality-logo-xs me-1">
                @else
                    <i class="fas fa-certificate me-1"></i>
                @endif
                {{ $qualityBrandName }}
            </span>
        @endif

        @if($showMerchant && $merchantName)
            <span class="me-2">
                <i class="fas fa-store me-1"></i>{{ $merchantName }}
            </span>
        @endif

        @if($showStock)
            <span class="{{ $stockClass }}">
                <i class="fas fa-boxes me-1"></i>{{ $stockText }}
            </span>
        @endif
    </div>
@endif
