{{--
    Product Card (DTO Version)
    Receives: $card (ProductCardDTO)
    Layout: $layout ('list' or 'grid')

    ZERO LOGIC - Only displays pre-computed values from DTO
--}}

@php
    /** @var \App\DataTransferObjects\ProductCardDTO $card */
    $layout = $layout ?? 'list';
@endphp

@if($layout === 'list')
{{-- LIST VIEW --}}
<div class="col-sm-6 col-md-6 col-lg-12">
    <div class="single-product-list-view">
        <div class="img-wrapper">
            @if ($card->offPercentage > 0)
                <span class="product-badge">-{{ $card->offPercentage }}%</span>
            @endif

            @auth
                <a href="javascript:;" class="wishlist" data-href="{{ $card->wishlistUrl }}">
                    <div class="add-to-wishlist-btn {{ $card->isInWishlist ? 'active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M11.9932 5.13581C9.9938 2.7984 6.65975 2.16964 4.15469 4.31001C1.64964 6.45038 1.29697 10.029 3.2642 12.5604C4.89982 14.6651 9.84977 19.1041 11.4721 20.5408C11.6536 20.7016 11.7444 20.7819 11.8502 20.8135C11.9426 20.8411 12.0437 20.8411 12.1361 20.8135C12.2419 20.7819 12.3327 20.7016 12.5142 20.5408C14.1365 19.1041 19.0865 14.6651 20.7221 12.5604C22.6893 10.029 22.3797 6.42787 19.8316 4.31001C17.2835 2.19216 13.9925 2.7984 11.9932 5.13581Z"
                                stroke="#030712" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                </a>
            @else
                <a href="{{ route('user.login') }}">
                    <div class="add-to-wishlist-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M11.9932 5.13581C9.9938 2.7984 6.65975 2.16964 4.15469 4.31001C1.64964 6.45038 1.29697 10.029 3.2642 12.5604C4.89982 14.6651 9.84977 19.1041 11.4721 20.5408C11.6536 20.7016 11.7444 20.7819 11.8502 20.8135C11.9426 20.8411 12.0437 20.8411 12.1361 20.8135C12.2419 20.7819 12.3327 20.7016 12.5142 20.5408C14.1365 19.1041 19.0865 14.6651 20.7221 12.5604C22.6893 10.029 22.3797 6.42787 19.8316 4.31001C17.2835 2.19216 13.9925 2.7984 11.9932 5.13581Z"
                                stroke="#030712" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                </a>
            @endauth

            <img class="product-img" src="{{ $card->photo }}" alt="{{ $card->productName }}">
        </div>

        <div class="details-wrapper">
            <a href="{{ $card->detailsUrl }}" class="product-title">{{ $card->productName }}</a>

            {{-- Product Info --}}
            <div class="product-info-badges my-2">
                @if($card->sku)
                    <span class="badge bg-light text-dark me-1">
                        <i class="fas fa-barcode me-1"></i>{{ $card->sku }}
                    </span>
                @endif

                @if($card->brandName)
                    <span class="badge bg-secondary me-1">{{ $card->brandName }}</span>
                @endif

                @if($card->qualityBrandName)
                    <span class="badge bg-info text-dark me-1">
                        @if($card->qualityBrandLogo)
                            <img src="{{ $card->qualityBrandLogo }}" alt="" style="height: 14px;" class="me-1">
                        @endif
                        {{ $card->qualityBrandName }}
                    </span>
                @endif

                @if($card->vendorName)
                    <span class="badge bg-primary me-1">
                        <i class="fas fa-store me-1"></i>{{ $card->vendorName }}
                    </span>
                @endif

                <span class="badge {{ $card->stockBadgeClass }}">{{ $card->stockText }}</span>
            </div>

            {{-- Rating --}}
            @if($card->ratingsCount > 0)
                <div class="rating-stars mb-2">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="{{ $i <= round($card->ratingsAvg) ? 'fas' : 'far' }} fa-star text-warning"></i>
                    @endfor
                    <span class="ms-1">({{ $card->ratingsCount }})</span>
                </div>
            @endif

            {{-- Price --}}
            <div class="product-price-wrap">
                <span class="current-price">{{ $card->priceFormatted }}</span>
                @if($card->previousPrice > 0 && $card->offPercentage > 0)
                    <span class="old-price text-muted text-decoration-line-through ms-2">
                        {{ $card->previousPriceFormatted }}
                    </span>
                @endif
            </div>

            {{-- Add to Cart --}}
            <div class="mt-3">
                @if($card->inStock)
                    @if($card->type === 'Physical')
                        <button class="template-btn cart-btn add-to-cart-btn"
                            data-product-id="{{ $card->productId }}"
                            data-merchant-product-id="{{ $card->merchantId }}"
                            data-vendor-id="{{ $card->vendorId }}"
                            data-quantity="{{ $card->minQty }}">
                            <i class="fas fa-cart-plus me-1"></i> @lang('Add to Cart')
                        </button>
                    @elseif($card->affiliateLink)
                        <a href="{{ $card->affiliateLink }}" target="_blank" class="template-btn">
                            <i class="fas fa-external-link-alt me-1"></i> @lang('Buy Now')
                        </a>
                    @endif
                @else
                    <button class="template-btn btn-secondary" disabled>
                        <i class="fas fa-times me-1"></i> @lang('Out of Stock')
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

@else
{{-- GRID VIEW --}}
<div class="{{ $class ?? 'col-6 col-md-4 col-lg-3' }}">
    <div class="m-product-card" id="pc_{{ $card->productId }}_{{ $card->merchantId }}">
        {{-- Image Section --}}
        <div class="m-product-card__image">
            {{-- Discount Badge --}}
            @if ($card->offPercentage > 0)
                <span class="m-product-card__badge m-product-card__badge--discount">
                    -{{ $card->offPercentage }}%
                </span>
            @endif

            {{-- Stock Badge --}}
            @if (!$card->inStock)
                <span class="m-product-card__badge m-product-card__badge--stock">
                    @lang('Out of Stock')
                </span>
            @endif

            {{-- Wishlist Button --}}
            @auth
                <button type="button" class="m-product-card__wishlist wishlist" data-href="{{ $card->wishlistUrl }}">
                    <i class="{{ $card->isInWishlist ? 'fas' : 'far' }} fa-heart"></i>
                </button>
            @else
                <a href="{{ route('user.login') }}" class="m-product-card__wishlist">
                    <i class="far fa-heart"></i>
                </a>
            @endauth

            {{-- Product Image --}}
            <a href="{{ $card->detailsUrl }}" class="m-product-card__image-link">
                <img src="{{ $card->photo }}"
                     alt="{{ $card->productName }}"
                     class="m-product-card__img active"
                     loading="lazy"
                     onerror="this.onerror=null; this.src='{{ asset('assets/images/noimage.png') }}';">
            </a>

            {{-- Quick Actions --}}
            @if ($card->type !== 'Listing')
                <div class="m-product-card__actions">
                    {{-- Compare --}}
                    <button type="button" class="m-product-card__action compare_product"
                        data-href="{{ $card->compareUrl }}"
                        title="@lang('Compare')">
                        <i class="fas fa-exchange-alt"></i>
                    </button>

                    {{-- Quick View --}}
                    <a href="{{ $card->detailsUrl }}" class="m-product-card__action" title="@lang('View')">
                        <i class="far fa-eye"></i>
                    </a>
                </div>
            @endif
        </div>

        {{-- Content Section --}}
        <div class="m-product-card__content">
            {{-- Product Name --}}
            <h6 class="m-product-card__title">
                <a href="{{ $card->detailsUrl }}">{{ \Illuminate\Support\Str::limit($card->productName, 50) }}</a>
            </h6>

            {{-- Product Info Badges --}}
            <div class="m-product-card__info">
                @if($card->sku)
                    <span class="m-product-card__sku">{{ $card->sku }}</span>
                @endif
                @if($card->vendorName)
                    <span class="m-product-card__vendor">
                        <i class="fas fa-store"></i> {{ $card->vendorName }}
                    </span>
                @endif
                @if($card->qualityBrandName)
                    <span class="m-product-card__quality">{{ $card->qualityBrandName }}</span>
                @endif
            </div>

            {{-- Price --}}
            <div class="m-product-card__price">
                <span class="m-product-card__price-current">{{ $card->priceFormatted }}</span>
                @if($card->previousPrice > 0 && $card->offPercentage > 0)
                    <span class="m-product-card__price-old">{{ $card->previousPriceFormatted }}</span>
                @endif
            </div>

            {{-- Rating --}}
            <div class="m-product-card__rating">
                <i class="fas fa-star"></i>
                <span>{{ number_format($card->ratingsAvg, 1) }}</span>
                <span class="m-product-card__rating-count">({{ $card->ratingsCount }})</span>
            </div>

            {{-- Add to Cart --}}
            @if ($card->type !== 'Listing' && $card->productType !== 'affiliate')
                @if ($card->inStock && $card->hasVendor && $card->merchantId)
                    <button type="button"
                        class="m-product-card__cart-btn m-cart-add"
                        data-merchant-product-id="{{ $card->merchantId }}"
                        data-vendor-id="{{ $card->vendorId }}"
                        data-product-id="{{ $card->productId }}"
                        data-min-qty="{{ $card->minQty }}"
                        data-stock="{{ $card->stock }}"
                        data-preordered="{{ $card->preordered ? '1' : '0' }}">
                        <i class="fas fa-shopping-cart"></i>
                        <span>@lang('Add to Cart')</span>
                    </button>
                @else
                    <button type="button" class="m-product-card__cart-btn m-product-card__cart-btn--disabled" disabled>
                        <i class="fas fa-ban"></i>
                        <span>@lang('Out of Stock')</span>
                    </button>
                @endif
            @elseif ($card->productType === 'affiliate' && $card->affiliateLink)
                <a href="{{ $card->affiliateLink }}" class="m-product-card__cart-btn" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    <span>@lang('Buy Now')</span>
                </a>
            @endif
        </div>
    </div>
</div>
@endif
