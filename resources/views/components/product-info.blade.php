{{--
    Unified Product Info Component
    Displays: SKU, Brand, Quality Brand, Vendor, Stock

    Usage Examples:
    <x-product-info :product="$product" />
    <x-product-info :product="$product" :mp="$merchantProduct" display-mode="badges" />
    <x-product-info :product="$product" display-mode="list" :show-sku="true" />
--}}

@props([
    'product' => null,
    'mp' => null,
    'displayMode' => 'inline',
    'showSku' => true,
    'showBrand' => true,
    'showQualityBrand' => true,
    'showVendor' => true,
    'showStock' => true
])

@php
    // Safety check: ensure product exists
    if (!$product) {
        return;
    }

    // Get merchant product if not provided
    if (!$mp) {
        $mp = $product->merchantProducts()
            ->where('status', 1)
            ->whereHas('user', function ($user) {
                $user->where('is_vendor', 2);
            })
            ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
            ->orderBy('price')
            ->first();
    }

    // Extract all display values (using localized names)
    $sku = $product->sku ?? null;
    $brandName = $product->brand ? $product->brand->localized_name : null;
    $qualityBrand = ($mp && $mp->qualityBrand) ? $mp->qualityBrand : null;
    $qualityBrandName = $qualityBrand ? $qualityBrand->localized_name : null;
    $qualityBrandLogo = $qualityBrand ? $qualityBrand->logo_url : null;
    $vendorName = ($mp && $mp->user) ? $mp->user->shop_name : null;
    $stock = $mp ? $mp->stock : null;

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
    {{-- Badge Display Mode - Best for product details pages --}}
    <div class="product-info-badges d-flex flex-wrap gap-2 mb-2">
        @if($showSku && $sku)
            <span class="badge bg-secondary text-white">
                <i class="fas fa-barcode me-1"></i>{{ $sku }}
            </span>
        @endif

        @if($showBrand && $brandName)
            <span class="badge bg-light text-dark">
                <i class="fas fa-tag me-1"></i>{{ $brandName }}
            </span>
        @endif

        @if($showQualityBrand && $qualityBrandName)
            <span class="badge bg-light text-dark d-inline-flex align-items-center">
                @if($qualityBrandLogo)
                    <img src="{{ $qualityBrandLogo }}" alt="{{ $qualityBrandName }}" class="quality-brand-logo me-1" style="max-height: 20px; max-width: 50px; object-fit: contain;">
                @else
                    <i class="fas fa-certificate me-1"></i>
                @endif
                {{ $qualityBrandName }}
            </span>
        @endif

        @if($showVendor && $vendorName)
            <span class="badge bg-light text-dark">
                <i class="fas fa-store me-1"></i>{{ $vendorName }}
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
    <ul class="product-info-list list-unstyled mb-2">
        @if($showSku && $sku)
            <li class="small text-muted">
                <strong>{{ __('SKU') }}:</strong> <span class="font-monospace">{{ $sku }}</span>
            </li>
        @endif

        @if($showBrand && $brandName)
            <li class="small">
                <strong>{{ __('Brand') }}:</strong> {{ $brandName }}
            </li>
        @endif

        @if($showQualityBrand && $qualityBrandName)
            <li class="small d-flex align-items-center">
                <strong>{{ __('Quality Brand') }}:</strong>
                @if($qualityBrandLogo)
                    <img src="{{ $qualityBrandLogo }}" alt="{{ $qualityBrandName }}" class="quality-brand-logo mx-1" style="max-height: 22px; max-width: 60px; object-fit: contain;">
                @endif
                <span class="ms-1">{{ $qualityBrandName }}</span>
            </li>
        @endif

        @if($showVendor && $vendorName)
            <li class="small">
                <strong>{{ __('Vendor') }}:</strong> {{ $vendorName }}
            </li>
        @endif

        @if($showStock)
            <li class="small {{ $stockClass }}">
                <strong>{{ __('Stock') }}:</strong> {{ $stockText }}
            </li>
        @endif
    </ul>

@else
    {{-- Inline Display Mode (Default) - Best for compact product cards --}}
    <div class="product-info-inline small text-muted mb-1">
        @if($showSku && $sku)
            <span class="me-2">
                <i class="fas fa-barcode me-1"></i><span class="font-monospace">{{ $sku }}</span>
            </span>
        @endif

        @if($showBrand && $brandName)
            <span class="me-2">
                <i class="fas fa-tag me-1"></i>{{ $brandName }}
            </span>
        @endif

        @if($showQualityBrand && $qualityBrandName)
            <span class="me-2 d-inline-flex align-items-center">
                @if($qualityBrandLogo)
                    <img src="{{ $qualityBrandLogo }}" alt="{{ $qualityBrandName }}" class="quality-brand-logo me-1" style="max-height: 18px; max-width: 45px; object-fit: contain;">
                @else
                    <i class="fas fa-certificate me-1"></i>
                @endif
                {{ $qualityBrandName }}
            </span>
        @endif

        @if($showVendor && $vendorName)
            <span class="me-2">
                <i class="fas fa-store me-1"></i>{{ $vendorName }}
            </span>
        @endif

        @if($showStock)
            <span class="{{ $stockClass }}">
                <i class="fas fa-boxes me-1"></i>{{ $stockText }}
            </span>
        @endif
    </div>
@endif
