@extends('layouts.front')

@section('title', ($category->slug ?? $category->full_code) . ' - ' . __('Subcategories'))

@section('content')
{{-- Breadcrumb Banner --}}
<section class="gs-breadcrumb-section bg-class"
    data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
    <div class="container">
        <div class="row justify-content-center content-wrapper">
            <div class="col-12">
                <h2 class="breadcrumb-title text-uppercase">{{ str_replace('-', ' ', $category->slug ?? $category->full_code) }}</h2>
                <ul class="bread-menu">
                    <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                    <li><a href="{{ route('catlogs.index', $brand->name) }}">{{ $brand->name }}</a></li>
                    <li><a href="{{ route('tree.level1', ['brand' => $brand->name, 'catalog' => $catalog->code, 'vin' => $vin]) }}">{{ $catalog->shortName ?? $catalog->name ?? $catalog->code }}</a></li>
                    <li><a href="javascript:;" class="text-uppercase">{{ str_replace('-', ' ', $category->slug ?? $category->full_code) }}</a></li>
                </ul>
            </div>
        </div>
    </div>
</section>
{{-- Breadcrumb End --}}

<div class="container py-3">
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
                'uniqueId' => 'level2',
                'showAttributes' => false
            ])
        </div>
    </div>

    @php
        // Sort categories by numeric part of full_code
        $sortedCategories = collect($categories)->sortBy(function($c) {
            $code = is_array($c) ? ($c['full_code'] ?? '') : ($c->full_code ?? '');
            if (preg_match('/\d+/', $code, $m)) {
                return (int) $m[0];
            }
            return PHP_INT_MAX;
        })->values();
    @endphp

    {{-- Categories Grid - Responsive --}}
    <div class="row g-3 g-md-4 mb-5">
        @forelse ($sortedCategories as $cat)
            <div class="col-6 col-sm-6 col-md-4 col-lg-3">
                <a href="{{ route('tree.level3', [
                    'brand' => $brand->name,
                    'catalog' => $catalog->code,
                    'key1' => $category->full_code,
                    'key2' => $cat->full_code,
                    'vin' => $vin
                ]) }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 hover-lift transition">
                        {{-- Image Container - Maintain Aspect Ratio --}}
                        <div class="position-relative overflow-hidden rounded-top aspect-ratio-3-4">
                            <img class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                                 src="{{ ($cat->thumbnail ?? null) ? Storage::url($cat->thumbnail) : asset('assets/images/no-image.png') }}"
                                 alt="{{ $cat->full_code }}"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='{{ asset('assets/images/no-image.png') }}';">
                        </div>

                        {{-- Card Body - Responsive Text --}}
                        <div class="card-body p-2 p-md-3 text-center">
                            <h6 class="product-title text-dark fw-bold text-uppercase mb-1 fs-6 fs-md-5">
                                {{ $cat->formatted_code ?? $cat->full_code }}
                            </h6>

                            @php($catLabel = app()->getLocale() === 'ar' ? $cat->label_ar : str_replace('-', ' ', $cat->slug ?? ''))
                            @if(!empty($catLabel))
                                <p class="text-muted small mb-0 d-none d-md-block text-uppercase">{{ $catLabel }}</p>
                            @endif
                        </div>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    {{ __('No categories available') }}
                </div>
            </div>
        @endforelse
    </div>
</div>

{{-- Styles moved to MUAADH.css: .hover-lift, .object-fit-cover, .aspect-ratio-3-4 --}}
@endsection
