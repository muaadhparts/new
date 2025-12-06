{{--
================================================================================
    MUAADH THEME - HOME PAGE
================================================================================
    CSS GUIDELINES FOR AI AGENTS:
    -----------------------------
    1. The ONLY file for adding/modifying custom CSS is: public/assets/front/css/MUAADH.css
    2. DO NOT add <style> tags in Blade files - move all styles to MUAADH.css
    3. DO NOT create new CSS files - use MUAADH.css sections instead
================================================================================
--}}

@extends('layouts.front')

@section('content')

<!-- Hero Search Section -->
<section class="muaadh-hero">
    <div class="container">
        <div class="muaadh-hero-content">
            <h1 class="muaadh-hero-title">@lang('Search By Part Number Or Name')</h1>

            <!-- Search Box -->
            <div class="muaadh-hero-search">
                @include('includes.frontend.search-part-ajax', ['uniqueId' => 'home'])
            </div>

            <p class="muaadh-hero-subtitle">
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

<!-- Brands Section -->
<section class="muaadh-section muaadh-section-gray">
    <div class="container">
        <div class="muaadh-section-header">
            <span class="muaadh-badge-primary">@lang('Genuine Parts Catalogues')</span>
            <h2 class="muaadh-section-title">@lang('Explore genuine OEM parts catalogues')</h2>
            <p class="muaadh-section-desc">@lang('Select your vehicle brand to find the perfect parts')</p>
        </div>

        <div class="muaadh-brands-grid">
            @foreach (DB::table('brands')->get() as $brand)
                <a href="{{ route('catlogs.index', $brand->name) }}" class="muaadh-brand-card">
                    <div class="muaadh-brand-img">
                        <img src="{{ asset('assets/images/brand/' . $brand->photo) }}" alt="{{ $brand->name }}" loading="lazy">
                    </div>
                    <span class="muaadh-brand-name">{{ $brand->name }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="muaadh-section">
    <div class="container">
        <div class="muaadh-section-header">
            <span class="muaadh-badge-primary">@lang('Browse Categories')</span>
            <h2 class="muaadh-section-title">@lang('Shop by Category')</h2>
            <p class="muaadh-section-desc">@lang('Find exactly what you need from our extensive catalog')</p>
        </div>

        <div class="muaadh-categories-grid">
            @foreach ($featured_categories as $fcategory)
                <a href="{{ route('front.category', $fcategory->slug) }}" class="muaadh-category-card">
                    <div class="muaadh-category-img">
                        <img src="{{ asset('assets/images/categories/' . $fcategory->image) }}" alt="{{ $fcategory->name }}" loading="lazy">
                        <span class="muaadh-category-count">{{ $fcategory->products_count }}</span>
                    </div>
                    <div class="muaadh-category-info">
                        <h6 class="muaadh-category-name">{{ $fcategory->name }}</h6>
                        <span class="muaadh-category-products">{{ $fcategory->products_count }} @lang('Products')</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="muaadh-section muaadh-section-gray">
    <div class="container">
        <div class="muaadh-services-grid">
            @foreach (DB::table('services')->get() as $service)
                <div class="muaadh-service-card">
                    <div class="muaadh-service-icon">
                        <img src="{{ asset('assets/images/services/' . $service->photo) }}" alt="{{ $service->title }}">
                    </div>
                    <div class="muaadh-service-info">
                        <h6 class="muaadh-service-title">{{ $service->title }}</h6>
                        <p class="muaadh-service-desc">{{ $service->details }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- VIN Search Modal -->
<div class="modal fade" id="vinSearchModalHome" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content muaadh-modal">
            <div class="modal-header muaadh-modal-header">
                <h5 class="modal-title">
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
