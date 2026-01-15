{{--
================================================================================
HOME PAGE - Section-Based Architecture (Theme System)
================================================================================
Each section is controlled by the active HomePageTheme ($theme->*)
All catalogItem data is merchant-only (is_merchant = 2)
Sections are rendered based on theme settings and purchase
================================================================================
--}}

@extends('layouts.front')

@section('content')

{{-- Main Page Wrapper with unified background --}}
<div class="muaadh-page-wrapper muaadh-section-gray">

    {{-- ===================================================================
         SECTION: Hero Search (if enabled: $theme->show_hero_search)
         =================================================================== --}}
    @if($theme->show_hero_search ?? true)
    <section class="muaadh-hero">
        <div class="container">
            <div class="muaadh-hero-content">
                <h1 class="muaadh-hero-name">@lang('Search By Part Number Or Name')</h1>

                <!-- Search Box -->
                <div class="muaadh-hero-search">
                    @include('includes.frontend.search-part-ajax', ['uniqueId' => 'home'])
                </div>

                <p class="muaadh-hero-subname">
                    @lang("If you don't know the correct part number for your vehicle, search below using your VIN, the category tree, or the catalogues.")
                </p>

                <!-- VIN Search Button -->
                <div class="muaadh-hero-actions">
                    <button type="button" class="muaadh-btn-vin" data-bs-toggle="modal" data-bs-target="#vinSearchModalHome">
                        <i class="fas fa-car"></i>
                        @lang('Search by VIN')
                    </button>
                    <p class="muaadh-hero-hint">
                        @lang('Search for spare parts inside the vehicle by VIN number')
                    </p>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- ===================================================================
         SECTION: Brands (if enabled: $theme->show_brands)
         =================================================================== --}}
    @if(($theme->show_brands ?? false) && isset($brands) && count($brands) > 0)
    <section class="muaadh-section">
        <div class="container">
            <div class="muaadh-section-header">
                <span class="muaadh-badge-primary">@lang('Genuine Parts Catalogues')</span>
                <h2 class="muaadh-section-name">{{ $theme->name_brands ?? __('Explore genuine OEM parts catalogues') }}</h2>
            </div>

            <div class="muaadh-brands-grid">
                @foreach ($brands as $brand)
                    <a href="{{ route('catlogs.index', $brand->name) }}" class="muaadh-brand-card">
                        <div class="muaadh-brand-img">
                            @if($brand->photo_url)
                                <img src="{{ $brand->photo_url }}" alt="{{ $brand->localized_name }}" loading="lazy">
                            @endif
                        </div>
                        <span class="muaadh-brand-name">{{ $brand->localized_name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ===================================================================
         SECTION: Categories (if enabled: $theme->show_categories)
         =================================================================== --}}
    @if(($theme->show_categories ?? false) && isset($featured_categories) && count($featured_categories) > 0)
    <section class="muaadh-section">
        <div class="container">
            <div class="muaadh-section-header">
                <span class="muaadh-badge-primary">@lang('Browse Categories')</span>
                <h2 class="muaadh-section-name">{{ $theme->name_categories ?? __('Shop by Category') }}</h2>
            </div>

            <div class="muaadh-categories-grid">
                @foreach ($featured_categories as $fcategory)
                    <a href="{{ route('front.catalog', $fcategory->slug) }}" class="muaadh-category-card">
                        <div class="muaadh-category-img">
                            <img src="{{ asset('assets/images/categories/' . $fcategory->image) }}" alt="{{ $fcategory->name }}" loading="lazy">
                            <span class="muaadh-category-count">{{ $fcategory->products_count }}</span>
                        </div>
                        <div class="muaadh-category-info">
                            <h6 class="muaadh-category-name">{{ $fcategory->name }}</h6>
                            <span class="muaadh-category-catalogItems">{{ $fcategory->products_count }} @lang('Items')</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ===================================================================
         SECTION: Featured Items (if enabled: $theme->show_featured_items)
         Uses carousel like Related Items - each card is a MerchantItem
         =================================================================== --}}
    @if(($theme->show_featured_items ?? false) && isset($featured_merchants) && count($featured_merchants) > 0)
    <section class="muaadh-section">
        <div class="container">
            <h2 class="name text-center">{{ $theme->name_featured_items ?? __('Featured Items') }}</h2>
            @include('frontend.sections.catalog-item-carousel', ['merchantItems' => $featured_merchants])
        </div>
    </section>
    @endif

    {{-- ===================================================================
         SECTION: Deal of the Day (if enabled: $theme->show_deal_of_day)
         =================================================================== --}}
    @if(($theme->show_deal_of_day ?? false) && isset($flash_merchant) && $flash_merchant)
    <section class="muaadh-section muaadh-deal-section">
        <div class="container">
            <h2 class="name text-center">{{ $theme->name_deal_of_day ?? __('Deal of the Day') }}</h2>
            @include('frontend.sections.deal-of-day', ['merchantItem' => $flash_merchant])
        </div>
    </section>
    @endif

    {{-- ===================================================================
         SECTION: Top Rated Items (if enabled: $theme->show_top_rated)
         Uses carousel like Related Items
         =================================================================== --}}
    @if(($theme->show_top_rated ?? false) && isset($top_merchants) && count($top_merchants) > 0)
    <section class="muaadh-section">
        <div class="container">
            <h2 class="name text-center">{{ $theme->name_top_rated ?? __('Top Rated Items') }}</h2>
            @include('frontend.sections.catalog-item-carousel', ['merchantItems' => $top_merchants])
        </div>
    </section>
    @endif

    {{-- ===================================================================
         SECTION: Big Save Items (if enabled: $theme->show_big_save)
         Uses carousel like Related Items
         =================================================================== --}}
    @if(($theme->show_big_save ?? false) && isset($big_merchants) && count($big_merchants) > 0)
    <section class="muaadh-section">
        <div class="container">
            <h2 class="name text-center">{{ $theme->name_big_save ?? __('Big Save Items') }}</h2>
            @include('frontend.sections.catalog-item-carousel', ['merchantItems' => $big_merchants])
        </div>
    </section>
    @endif

    {{-- ===================================================================
         SECTION: Trending Items (if enabled: $theme->show_trending)
         Uses carousel like Related Items
         =================================================================== --}}
    @if(($theme->show_trending ?? false) && isset($trending_merchants) && count($trending_merchants) > 0)
    <section class="muaadh-section">
        <div class="container">
            <h2 class="name text-center">{{ $theme->name_trending ?? __('Trending Items') }}</h2>
            @include('frontend.sections.catalog-item-carousel', ['merchantItems' => $trending_merchants])
        </div>
    </section>
    @endif

    {{-- ===================================================================
         SECTION: Best Selling Items (if enabled: $theme->show_best_sellers)
         Uses carousel like Related Items
         =================================================================== --}}
    @if(($theme->show_best_sellers ?? false) && isset($best_merchants) && count($best_merchants) > 0)
    <section class="muaadh-section">
        <div class="container">
            <h2 class="name text-center">{{ $theme->name_best_sellers ?? __('Best Selling Items') }}</h2>
            @include('frontend.sections.catalog-item-carousel', ['merchantItems' => $best_merchants])
        </div>
    </section>
    @endif

    {{-- ===================================================================
         SECTION: Blogs (if enabled: $theme->show_blogs)
         =================================================================== --}}
    @if(($theme->show_blogs ?? false) && isset($blogs) && count($blogs) > 0)
    <section class="muaadh-section">
        <div class="container">
            <div class="muaadh-section-header">
                <span class="muaadh-badge-primary">@lang('Latest News')</span>
                <h2 class="muaadh-section-name">{{ $theme->name_blogs ?? __('From Our Blog') }}</h2>
            </div>

            @include('frontend.sections.blog-grid', ['blogs' => $blogs])
        </div>
    </section>
    @endif

    {{-- ===================================================================
         SECTION: Newsletter (if enabled: $theme->show_newsletter)
         =================================================================== --}}
    @if($theme->show_newsletter ?? false)
    <section class="muaadh-section muaadh-newsletter-section">
        <div class="container">
            @include('frontend.sections.newsletter')
        </div>
    </section>
    @endif

</div>
{{-- End Main Page Wrapper --}}

<!-- VIN Search Modal -->
<div class="modal fade" id="vinSearchModalHome" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content muaadh-modal">
            <div class="modal-header muaadh-modal-header">
                <h5 class="modal-name">
                    <i class="fas fa-car me-2"></i>
                    @lang('Search by VIN')
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                @include('includes.frontend.search-vin-ajax', ['uniqueId' => 'homeModal'])
            </div>
        </div>
    </div>
</div>

@endsection
