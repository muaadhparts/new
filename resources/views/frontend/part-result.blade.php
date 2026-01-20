@extends('layouts.front')

@section('content')
    {{-- Breadcrumb --}}
    <section class="gs-breadcrumb-section bg-class"
        data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-name">{{ $part_number }}</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        @if(count($fitmentBrands) === 1)
                            <li>
                                @if($fitmentBrands[0]['logo'])
                                    <img src="{{ $fitmentBrands[0]['logo'] }}" alt="" class="breadcrumb-brand-logo" style="height: 20px; margin-right: 5px;">
                                @endif
                                {{ $fitmentBrands[0]['name'] }}
                            </li>
                        @elseif(count($fitmentBrands) > 1)
                            <li>
                                <button type="button" class="fitment-brands-btn btn btn-sm btn-outline-light"
                                        data-brands="{{ json_encode($fitmentBrands) }}"
                                        data-part-number="{{ $part_number }}">
                                    <i class="fas fa-car"></i>
                                    @lang('Fits') {{ count($fitmentBrands) }} @lang('brands')
                                </button>
                            </li>
                        @endif
                        <li>{{ $part_number }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Main Content --}}
    <div class="gs-page-wrapper muaadh-section-gray">
        <div class="container py-4">

            {{-- Part Info Header --}}
            <div class="part-result-header m-card mb-4">
                <div class="m-card__body">
                    <div class="row align-items-center">
                        {{-- Image --}}
                        <div class="col-md-3 col-lg-2 text-center mb-3 mb-md-0">
                            @php
                                $photo = $catalogItem->photo
                                    ? (filter_var($catalogItem->photo, FILTER_VALIDATE_URL) ? $catalogItem->photo : Storage::url($catalogItem->photo))
                                    : asset('assets/images/noimage.png');
                            @endphp
                            <img src="{{ $photo }}"
                                 alt="{{ $catalogItem->showName() }}"
                                 class="part-result-image img-fluid rounded"
                                 style="max-height: 150px; object-fit: contain;">
                        </div>

                        {{-- Info --}}
                        <div class="col-md-9 col-lg-10">
                            <h1 class="part-result-title h4 mb-2">{{ $catalogItem->showName() }}</h1>

                            <div class="part-result-meta d-flex flex-wrap gap-2 mb-3">
                                <span class="badge bg-dark">
                                    <i class="fas fa-barcode me-1"></i>{{ $part_number }}
                                </span>

                                @if($offersData['offers_count'] > 0)
                                    <span class="badge bg-success">
                                        <i class="fas fa-tags me-1"></i>{{ $offersData['offers_count'] }} @lang('offers')
                                    </span>
                                    <span class="badge bg-primary">
                                        @lang('From') {{ $offersData['lowest_price_formatted'] }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        @lang('No offers available')
                                    </span>
                                @endif

                                {{-- Reviews --}}
                                @if($catalogItem->catalog_reviews_count > 0)
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-star me-1"></i>
                                        {{ number_format($catalogItem->catalog_reviews_avg_rating ?? 0, 1) }}
                                        ({{ $catalogItem->catalog_reviews_count }})
                                    </span>
                                @endif
                            </div>

                            {{-- Fitment Brands --}}
                            @if(count($fitmentBrands) > 0)
                                <div class="part-result-fitment">
                                    <small class="text-muted">@lang('Fits'):</small>
                                    @foreach($fitmentBrands as $brand)
                                        <span class="badge bg-light text-dark border">
                                            @if($brand['logo'])
                                                <img src="{{ $brand['logo'] }}" alt="" style="height: 14px;" class="me-1">
                                            @endif
                                            {{ $brand['name'] }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Offers Section --}}
            @if($offersData['offers_count'] > 0)
                <div class="part-result-offers">
                    @foreach($offersData['grouped_offers'] as $qualityGroup)
                        {{-- Quality Brand Group --}}
                        <div class="quality-group m-card mb-3">
                            {{-- Quality Header --}}
                            <div class="quality-group-header m-card__header d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    @if($qualityGroup['quality_logo'])
                                        <img src="{{ $qualityGroup['quality_logo'] }}"
                                             alt="{{ $qualityGroup['quality_name'] }}"
                                             class="quality-logo"
                                             style="height: 24px;">
                                    @endif
                                    <span class="quality-name fw-bold">{{ $qualityGroup['quality_name'] }}</span>
                                    <small class="text-muted">({{ $qualityGroup['offers_count'] }} @lang('offers'))</small>
                                </div>
                                <span class="quality-price text-success fw-bold">
                                    @lang('From') {{ $qualityGroup['lowest_price_formatted'] }}
                                </span>
                            </div>

                            <div class="m-card__body">
                                {{-- Merchants within Quality --}}
                                @foreach($qualityGroup['merchants'] as $merchantGroup)
                                    <div class="merchant-group mb-3 {{ !$loop->last ? 'border-bottom pb-3' : '' }}">
                                        {{-- Merchant Header --}}
                                        <div class="merchant-header d-flex align-items-center gap-2 mb-2">
                                            @if($merchantGroup['merchant_photo'])
                                                <img src="{{ $merchantGroup['merchant_photo'] }}"
                                                     alt="{{ $merchantGroup['merchant_name'] }}"
                                                     class="merchant-avatar rounded-circle"
                                                     style="width: 32px; height: 32px; object-fit: cover;">
                                            @else
                                                <div class="merchant-avatar bg-secondary rounded-circle d-flex align-items-center justify-content-center"
                                                     style="width: 32px; height: 32px;">
                                                    <i class="fas fa-store text-white small"></i>
                                                </div>
                                            @endif
                                            <span class="merchant-name fw-semibold">{{ $merchantGroup['merchant_name'] }}</span>
                                            <small class="text-muted">({{ $merchantGroup['offers_count'] }} @lang('branches'))</small>
                                        </div>

                                        {{-- Branches / Offers --}}
                                        <div class="branches-list">
                                            @foreach($merchantGroup['branches'] as $branchData)
                                                @php
                                                    $offer = $branchData['offer'];
                                                    $canBuy = $offer['can_buy'] ?? false;
                                                    $inStock = $offer['in_stock'] ?? false;
                                                    $preordered = $offer['preordered'] ?? false;
                                                    $stock = $offer['stock'] ?? 0;
                                                    $minQty = $offer['minimum_qty'] ?? 1;
                                                    $uniqueId = 'offer_' . ($offer['merchant_item_id'] ?? rand());
                                                @endphp

                                                <div class="branch-offer d-flex flex-wrap align-items-center gap-3 p-2 rounded {{ $canBuy ? 'bg-light' : 'bg-light opacity-75' }} mb-2">
                                                    {{-- Branch Info --}}
                                                    <div class="branch-info flex-grow-1">
                                                        <div class="d-flex align-items-center gap-2 mb-1">
                                                            <i class="fas fa-warehouse text-muted"></i>
                                                            <span class="branch-name">{{ $branchData['branch_name'] }}</span>

                                                            {{-- Stock Badge --}}
                                                            @if($inStock)
                                                                <span class="badge bg-success">{{ $stock }} @lang('Available')</span>
                                                            @elseif($preordered)
                                                                <span class="badge bg-warning text-dark">@lang('Preorder')</span>
                                                            @else
                                                                <span class="badge bg-secondary">@lang('Out of Stock')</span>
                                                            @endif
                                                        </div>

                                                        {{-- Merchant Photos --}}
                                                        @if(!empty($offer['photos']))
                                                            <div class="offer-photos d-flex gap-1 mt-1">
                                                                @foreach(array_slice($offer['photos'], 0, 4) as $photo)
                                                                    <img src="{{ $photo['url'] }}"
                                                                         alt=""
                                                                         class="offer-photo-thumb rounded"
                                                                         style="width: 40px; height: 40px; object-fit: cover; cursor: pointer;"
                                                                         loading="lazy">
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>

                                                    {{-- Price --}}
                                                    <div class="branch-price text-end">
                                                        <span class="price-current fw-bold fs-5 text-primary">
                                                            {{ $offer['final_price_formatted'] }}
                                                        </span>
                                                        @if($offer['previous_price_formatted'] ?? null)
                                                            <br>
                                                            <del class="price-old text-muted small">{{ $offer['previous_price_formatted'] }}</del>
                                                            @if(($offer['discount_percentage'] ?? 0) > 0)
                                                                <span class="badge bg-danger">-{{ number_format($offer['discount_percentage'], 0) }}%</span>
                                                            @endif
                                                        @endif
                                                    </div>

                                                    {{-- Actions --}}
                                                    <div class="branch-actions d-flex align-items-center gap-2">
                                                        @if($canBuy)
                                                            {{-- Quantity Control --}}
                                                            <div class="qty-control d-flex align-items-center border rounded">
                                                                <button type="button" class="btn btn-sm btn-light qty-minus px-2"
                                                                        data-target="{{ $uniqueId }}"
                                                                        data-min="{{ $minQty }}">-</button>
                                                                <input type="text"
                                                                       class="form-control form-control-sm text-center qty-input"
                                                                       id="qty_{{ $uniqueId }}"
                                                                       value="{{ $minQty }}"
                                                                       readonly
                                                                       style="width: 50px; border: none;"
                                                                       data-min="{{ $minQty }}"
                                                                       data-stock="{{ $stock }}"
                                                                       data-preordered="{{ $preordered ? 1 : 0 }}">
                                                                <button type="button" class="btn btn-sm btn-light qty-plus px-2"
                                                                        data-target="{{ $uniqueId }}"
                                                                        data-stock="{{ $stock }}"
                                                                        data-preordered="{{ $preordered ? 1 : 0 }}">+</button>
                                                            </div>

                                                            {{-- Add to Cart --}}
                                                            <button type="button"
                                                                    class="btn btn-success btn-sm m-cart-add"
                                                                    data-merchant-item-id="{{ $offer['merchant_item_id'] }}"
                                                                    data-merchant-user-id="{{ $offer['user_id'] }}"
                                                                    data-catalog-item-id="{{ $catalogItem->id }}"
                                                                    data-min-qty="{{ $minQty }}"
                                                                    data-stock="{{ $stock }}"
                                                                    data-preordered="{{ $preordered ? 1 : 0 }}"
                                                                    data-qty-input="#qty_{{ $uniqueId }}">
                                                                <i class="fas fa-cart-plus"></i>
                                                                <span class="d-none d-md-inline">@lang('Add')</span>
                                                            </button>
                                                        @else
                                                            <span class="text-muted">@lang('Unavailable')</span>
                                                        @endif

                                                        {{-- Shipping Quote --}}
                                                        @if($canBuy && isset($offer['user_id']))
                                                            <x-shipping-quote-button
                                                                :merchant-user-id="$offer['user_id']"
                                                                :catalog-item-name="$catalogItem->showName()"
                                                                class="btn-sm" />
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- No Offers --}}
                <div class="m-card">
                    <div class="m-card__body text-center py-5">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h5>@lang('No offers available for this part')</h5>
                        <p class="text-muted">@lang('Check back later or search for alternative parts')</p>
                        <a href="{{ route('front.index') }}" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>@lang('Search Parts')
                        </a>
                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection

@section('script')
<script>
// Initialize quantity controls
document.querySelectorAll('.qty-plus').forEach(btn => {
    btn.addEventListener('click', function() {
        const targetId = this.dataset.target;
        const input = document.getElementById('qty_' + targetId);
        if (!input) return;

        const stock = parseInt(this.dataset.stock) || 999;
        const preordered = parseInt(this.dataset.preordered) || 0;
        const maxQty = preordered ? 999 : stock;
        let val = parseInt(input.value) || 1;

        if (val < maxQty) {
            input.value = val + 1;
        }
    });
});

document.querySelectorAll('.qty-minus').forEach(btn => {
    btn.addEventListener('click', function() {
        const targetId = this.dataset.target;
        const input = document.getElementById('qty_' + targetId);
        if (!input) return;

        const minQty = parseInt(this.dataset.min) || 1;
        let val = parseInt(input.value) || 1;

        if (val > minQty) {
            input.value = val - 1;
        }
    });
});
</script>
@endsection
