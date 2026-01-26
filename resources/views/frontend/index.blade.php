{{--
================================================================================
HOME PAGE - DTO-Based Architecture (Data Flow Policy Compliant)
================================================================================
All data comes from HomePageDTO ($page) - pre-computed in HomePageDataBuilder
Views only READ properties - no queries, no logic, no null coalescing
================================================================================
--}}

@extends('layouts.front')

@section('content')

{{-- Main Page Wrapper with unified background --}}
<div class="muaadh-page-wrapper muaadh-section-gray">

    {{-- ===================================================================
         SECTION: Hero Search (if enabled)
         =================================================================== --}}
    @if($page->showHeroSearch)
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
         SECTION: Brands (if enabled and has data)
         =================================================================== --}}
    @if($page->showBrands && count($page->brands) > 0)
    <section class="muaadh-section">
        <div class="container">
            <div class="muaadh-section-header">
                <span class="muaadh-badge-primary">@lang('Genuine Parts Catalogues')</span>
                <h2 class="muaadh-section-name">{{ $page->brandsTitle }}</h2>
            </div>

            <div class="muaadh-brands-grid">
                @foreach ($page->brands as $brand)
                    <a href="{{ $brand->detailsUrl }}" class="muaadh-brand-card">
                        <div class="muaadh-brand-img">
                            @if($brand->photoUrl)
                                <img src="{{ $brand->photoUrl }}" alt="{{ $brand->name }}" loading="lazy">
                            @endif
                        </div>
                        <span class="muaadh-brand-name">{{ $brand->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ===================================================================
         SECTION: Catalogs (if enabled and has data)
         =================================================================== --}}
    @if($page->showCategories && count($page->featuredCatalogs) > 0)
    <section class="muaadh-section">
        <div class="container">
            <div class="muaadh-section-header">
                <span class="muaadh-badge-primary">@lang('Browse Catalogs')</span>
                <h2 class="muaadh-section-name">{{ $page->categoriesTitle }}</h2>
                @if($page->hasMoreCatalogs)
                    <a href="{{ $page->viewAllCatalogsUrl }}" class="muaadh-view-all">
                        @lang('View All') ({{ $page->totalCatalogsCount }})
                        <i class="fas fa-arrow-{{ app()->getLocale() == 'ar' ? 'left' : 'right' }} ms-1"></i>
                    </a>
                @endif
            </div>

            <div class="muaadh-categories-grid">
                @foreach ($page->featuredCatalogs as $catalog)
                    <a href="{{ $catalog->detailsUrl }}" class="muaadh-category-card">
                        <div class="muaadh-category-img">
                            <img src="{{ $catalog->largeImageUrl }}" alt="{{ $catalog->name }}" loading="lazy">
                            <span class="muaadh-category-count">{{ $catalog->itemsCount }}</span>
                        </div>
                        <div class="muaadh-category-info">
                            <h6 class="muaadh-category-name">{{ $catalog->name }}</h6>
                            <span class="muaadh-category-catalogItems">{{ $catalog->itemsCount }} @lang('Items')</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ===================================================================
         SECTION: Blogs (if enabled and has data)
         =================================================================== --}}
    @if($page->showBlogs && count($page->blogs) > 0)
    <section class="muaadh-section">
        <div class="container">
            <div class="muaadh-section-header">
                <span class="muaadh-badge-primary">@lang('Latest News')</span>
                <h2 class="muaadh-section-name">{{ $page->blogsTitle }}</h2>
            </div>

            @include('frontend.sections.blog-grid', ['blogs' => $page->blogs])
        </div>
    </section>
    @endif

    {{-- ===================================================================
         SECTION: Newsletter (if enabled)
         =================================================================== --}}
    @if($page->showNewsletter)
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
