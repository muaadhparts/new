@extends('layouts.front')

@section('content')
<section class="gs-breadcrumb-section">
    <div class="container">
        <div class="row justify-content-center content-wrapper">
            <div class="col-12">
                <h2 class="breadcrumb-name">@lang('Favorites')</h2>
                <ul class="bread-menu">
                    <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                    <li><a href="javascript:;">@lang('Favorites')</a></li>
                </ul>
            </div>
        </div>
    </div>
</section>
<div class="gs-blog-wrapper muaadh-section-gray">
    <div class="container">
        <div class="row flex-column-reverse flex-lg-row">

            <div class="col-12 col-lg-12 col-xl-12 gs-main-blog-wrapper">
                <div class="catalogItem-nav-wrapper">
                    <h5>@lang('Total Items Found:') <span id="favorite-count">{{ $favorites->total() }}</span></h5>
                </div>
                @if($favorites->count() > 0)
                <div class="row gy-4 mt-20">
                    @foreach ($favorites as $favorite)
                    {{-- Uses FavoriteItemDTO from FavoriteController (Clean Architecture) --}}
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="catalogItem-card" id="favorite-card-{{ $favorite->favoriteId }}">
                            {{-- Media Section --}}
                            <div class="catalogItem-card__media">
                                {{-- Remove from Favorites Button --}}
                                <button type="button" class="catalogItem-card__delete removefavorite"
                                        data-href="{{ route('user-favorite-remove', $favorite->favoriteId) }}"
                                        title="@lang('Remove from Favorites')">
                                    <i class="fas fa-times"></i>
                                </button>

                                <a href="{{ $favorite->catalogItemUrl }}" class="catalogItem-card__media-link">
                                    <img src="{{ $favorite->photoUrl }}" alt="{{ $favorite->name }}" class="catalogItem-card__img"
                                         loading="lazy" onerror="this.onerror=null; this.src='{{ asset('assets/images/noimage.png') }}';">
                                </a>
                            </div>

                            {{-- Content Section --}}
                            <div class="catalogItem-card__content">
                                <h6 class="catalogItem-card__name">
                                    <a href="{{ $favorite->catalogItemUrl }}">{{ $favorite->name }}</a>
                                </h6>

                                {{-- Part Number --}}
                                <div class="catalogItem-card__meta">
                                    <span class="catalogItem-card__part-number">{{ $favorite->partNumber }}</span>
                                </div>

                                {{-- Merchant Info --}}
                                @if($favorite->merchantName)
                                    <div class="catalogItem-card__merchant">
                                        @if($favorite->qualityBrandLogo)
                                            <img src="{{ $favorite->qualityBrandLogo }}" alt="{{ $favorite->qualityBrandName }}" class="catalogItem-card__brand-logo">
                                        @endif
                                        <span>{{ $favorite->merchantName }}</span>
                                    </div>
                                @endif

                                {{-- Price --}}
                                <div class="catalogItem-card__price">
                                    <span class="catalogItem-card__price-current">{{ $favorite->priceFormatted }}</span>
                                    @if($favorite->previousPriceFormatted)
                                        <span class="catalogItem-card__price-old">{{ $favorite->previousPriceFormatted }}</span>
                                    @endif
                                </div>

                                {{-- Stock Status --}}
                                <div class="catalogItem-card__stock">
                                    @if($favorite->hasStock)
                                        <span class="stock-availability in-stock">@lang('In Stock')</span>
                                    @else
                                        <span class="stock-availability out-stock">@lang('Out Of Stock')</span>
                                    @endif
                                </div>

                                {{-- Action Button --}}
                                @if($favorite->merchantItemId)
                                    <button type="button" class="catalog-btn catalog-btn-primary m-cart-add" 
                                            data-merchant-item-id="{{ $favorite->merchantItemId }}">
                                        <i class="fas fa-cart-plus"></i> @lang('Add to cart')
                                    </button>
                                @else
                                    <button type="button" class="catalog-btn catalog-btn-outline catalog-offers-btn" 
                                            data-catalog-item-id="{{ $favorite->catalogItemId }}" 
                                            data-part-number="{{ $favorite->partNumber }}">
                                        <i class="fas fa-tags"></i> @lang('View Offers')
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                {{ $favorites->links('includes.frontend.pagination') }}
                
                @else
                <div class="catalogItem-nav-wrapper d-flex justify-content-center mt-4">
                    <h5>@lang('No Items Found in Favorites')</h5>
                </div>
                @endif

            </div>
        </div>
    </div>
</div>
@endsection
