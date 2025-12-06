@extends('layouts.front')

@section('title', ($parentCategory2->slug ?? $parentCategory2->full_code) . ' - ' . __('Parts'))

@section('content')
{{-- Uses catalog-unified.css for breadcrumb styling --}}

<div class="container py-3">
    {{-- Breadcrumb - Uses catalog-unified.css --}}
    <div class="catalog-breadcrumb-wrapper mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb catalog-breadcrumb mb-0">
                {{-- Home --}}
                <li class="breadcrumb-item">
                    <a href="{{ route('front.index') }}">
                        <i class="fas fa-home"></i>
                        <span class="d-none d-sm-inline ms-1">{{ __('Home') }}</span>
                    </a>
                </li>

                {{-- Brand --}}
                <li class="breadcrumb-item">
                    <a href="{{ route('catlogs.index', $brand->name) }}">
                        {{ $brand->name }}
                    </a>
                </li>

                {{-- VIN --}}
                @if($vin)
                    <li class="breadcrumb-item d-none d-sm-block">
                        <a href="{{ route('tree.level1', [
                            'brand' => $brand->name,
                            'catalog' => $catalog->code,
                            'vin' => $vin
                        ]) }}">
                            <i class="fas fa-car me-1"></i>
                            {{ Str::limit($vin, 12) }}
                        </a>
                    </li>
                @endif

                {{-- Catalog --}}
                <li class="breadcrumb-item d-none d-md-block">
                    <a href="{{ route('tree.level1', [
                        'brand' => $brand->name,
                        'catalog' => $catalog->code,
                        'vin' => $vin
                    ]) }}">
                        {{ $catalog->shortName ?? $catalog->name ?? $catalog->code }}
                    </a>
                </li>

                {{-- Level 1 --}}
                @if($parentCategory1)
                <li class="breadcrumb-item d-none d-lg-block text-uppercase">
                    <a href="{{ route('tree.level2', [
                        'brand' => $brand->name,
                        'catalog' => $catalog->code,
                        'key1' => $parentCategory1->full_code,
                        'vin' => $vin
                    ]) }}">
                        {{ str_replace('-', ' ', $parentCategory1->slug ?? $parentCategory1->full_code) }}
                    </a>
                </li>
                @endif

                {{-- Level 2 - Current --}}
                @if($parentCategory2)
                <li class="breadcrumb-item active text-uppercase" aria-current="page">
                    <span>{{ str_replace('-', ' ', $parentCategory2->slug ?? $parentCategory2->full_code) }}</span>
                </li>
                @endif
            </ol>
        </nav>
    </div>

    {{-- Search Box - Full Width on Mobile --}}
    <div class="row mb-4">
        <div class="col-12">
            {{-- Specifications Button --}}
            <div class="mb-3">
                @include('catalog.partials.specs-modal', [
                    'catalog' => $catalog,
                    'filters' => $filters,
                    'selectedFilters' => $selectedFilters,
                    'isVinMode' => $isVinMode
                ])
            </div>

            {{-- Chips Bar --}}
            @include('catalog.partials.chips-bar', ['chips' => $chips])

            {{-- Search --}}
            @include('includes.frontend.vehicle-search-ajax', [
                'catalog' => $catalog,
                'uniqueId' => 'level3',
                'showAttributes' => false
            ])
        </div>
    </div>

    {{-- Categories Grid - Responsive --}}
    @if($categories && $categories->count() > 0)
        <div class="row g-3 g-md-4 mb-5">
            @foreach ($categories as $cat)
                <div class="col-6 col-sm-6 col-md-4 col-lg-3">
                    <a href="{{ route('illustrations', [
                        'brand' => $brand->name,
                        'catalog' => $catalog->code,
                        'key1' => $parentCategory1->full_code,
                        'key2' => $parentCategory2->full_code,
                        'key3' => $cat->full_code,
                        'vin' => $vin
                    ]) }}" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100 hover-lift transition">
                            {{-- Image Container - Maintain Aspect Ratio --}}
                            <div class="position-relative overflow-hidden rounded-top" style="padding-top: 75%;">
                                <img class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                                     src="{{ ($cat->thumbnail ?? null) ? Storage::url($cat->thumbnail) : asset('assets/images/no-image.png') }}"
                                     alt="{{ $cat->full_code }}"
                                     loading="lazy"
                                     onerror="this.onerror=null; this.src='{{ asset('assets/images/no-image.png') }}';">
                            </div>

                            {{-- Card Body - Responsive Text --}}
                            <div class="card-body p-2 p-md-3 text-center">
                                <h6 class="product-title text-dark fw-bold text-uppercase mb-1 fs-6 fs-md-5">
                                    {{ $cat->full_code }}
                                </h6>
                                @if(!empty($cat->Applicability))
                                    <p class="text-muted small mb-0">{{ $cat->Applicability }}</p>
                                @endif
                                @if(!empty($cat->debug_begin) || !empty($cat->debug_end))
                                    <p class="text-secondary small mb-0 mt-1">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        {{ $cat->debug_begin ?? '—' }} - {{ $cat->debug_end ?? '—' }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @else
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    <h5 class="mb-2">{{ __('No categories available') }}</h5>
                    <p class="mb-0">{{ __('There are no categories in this level.') }}</p>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
.hover-lift {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
}
.object-fit-cover {
    object-fit: cover;
}
</style>
@endsection
