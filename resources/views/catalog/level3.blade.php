@extends('layouts.front')

@section('title', ($parentCategory2->slug ?? $parentCategory2->full_code) . ' - ' . __('Parts'))

@section('content')
{{-- Breadcrumb Banner --}}
<section class="gs-breadcrumb-section bg-class mb-4"
    data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
    <div class="container">
        <div class="row justify-content-center content-wrapper">
            <div class="col-12">
                <h2 class="breadcrumb-title text-uppercase">{{ str_replace('-', ' ', $parentCategory2->slug ?? $parentCategory2->full_code) }}</h2>
                <ul class="bread-menu">
                    <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                    <li><a href="{{ route('catlogs.index', $brand->name) }}">{{ $brand->name }}</a></li>
                    <li><a href="{{ route('tree.level1', ['brand' => $brand->name, 'catalog' => $catalog->code, 'vin' => $vin]) }}">{{ $catalog->shortName ?? $catalog->name ?? $catalog->code }}</a></li>
                    @if($parentCategory1)
                    <li><a href="{{ route('tree.level2', ['brand' => $brand->name, 'catalog' => $catalog->code, 'key1' => $parentCategory1->full_code, 'vin' => $vin]) }}" class="text-uppercase">{{ str_replace('-', ' ', $parentCategory1->slug ?? $parentCategory1->full_code) }}</a></li>
                    @endif
                    <li><a href="javascript:;" class="text-uppercase">{{ str_replace('-', ' ', $parentCategory2->slug ?? $parentCategory2->full_code) }}</a></li>
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
                'uniqueId' => 'level3',
                'showAttributes' => false
            ])
        </div>
    </div>

    {{-- Categories Grid - Responsive --}}
    <div class="row g-3 g-md-4 mb-5">
        @forelse ($categories as $cat)
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
                        {{-- Image Container --}}
                        <div class="position-relative overflow-hidden rounded-top aspect-ratio-3-4">
                            <img class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                                 src="{{ ($cat->thumbnail ?? null) ? Storage::url($cat->thumbnail) : asset('assets/images/no-image.png') }}"
                                 alt="{{ $cat->full_code }}"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='{{ asset('assets/images/no-image.png') }}';">
                        </div>

                        {{-- Card Body --}}
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
