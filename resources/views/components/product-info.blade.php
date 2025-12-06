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
                    <img src="{{ $qualityBrandLogo }}" alt="{{ $qualityBrandName }}" class="quality-brand-logo muaadh-quality-logo-sm me-1">
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
                    <img src="{{ $qualityBrandLogo }}" alt="{{ $qualityBrandName }}" class="quality-brand-logo muaadh-quality-logo-md mx-1">
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

@elseif($displayMode === 'modal')
    {{-- Modal Display Mode - Best for quick view modals --}}
    <div class="product-info-modal mb-3">
        <table class="table table-sm table-borderless mb-0">
            <tbody>
                @if($showSku && $sku)
                    <tr>
                        <td class="text-muted" style="width: 100px;"><i class="fas fa-barcode me-1"></i>{{ __('SKU') }}</td>
                        <td><code>{{ $sku }}</code></td>
                    </tr>
                @endif

                @if($showBrand && $brandName)
                    <tr>
                        <td class="text-muted"><i class="fas fa-tag me-1"></i>{{ __('Brand') }}</td>
                        <td>{{ $brandName }}</td>
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

                @if($showVendor && $vendorName)
                    <tr>
                        <td class="text-muted"><i class="fas fa-store me-1"></i>{{ __('Vendor') }}</td>
                        <td>{{ $vendorName }}</td>
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
                    <img src="{{ $qualityBrandLogo }}" alt="{{ $qualityBrandName }}" class="quality-brand-logo muaadh-quality-logo-xs me-1">
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
