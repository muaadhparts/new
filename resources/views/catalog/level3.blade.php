@extends('layouts.front')

@section('title', $key2 . ' - ' . __('Parts'))

@section('content')
{{-- Breadcrumb Banner --}}
<section class="gs-breadcrumb-section bg-class mb-4"
    data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
    <div class="container">
        <div class="row justify-content-center content-wrapper">
            <div class="col-12">
                <h2 class="breadcrumb-title text-uppercase">{{ $key2 }}</h2>
                <ul class="bread-menu">
                    <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                    <li><a href="{{ route('catlogs.index', $brand->name) }}">{{ $brand->name }}</a></li>
                    <li><a href="{{ route('tree.level1', ['brand' => $brand->name, 'catalog' => $catalog->code, 'vin' => $vin]) }}">{{ $catalog->shortName ?? $catalog->name ?? $catalog->code }}</a></li>
                    <li><a href="{{ route('tree.level2', ['brand' => $brand->name, 'catalog' => $catalog->code, 'key1' => $key1, 'vin' => $vin]) }}" class="text-uppercase">{{ $key1 }}</a></li>
                    <li><a href="javascript:;" class="text-uppercase">{{ $key2 }}</a></li>
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
        @forelse ($nodes as $node)
            <div class="col-6 col-sm-6 col-md-4 col-lg-3">
                <a href="{{ route('illustrations', [
                    'brand' => $brand->name,
                    'catalog' => $catalog->code,
                    'key1' => $node->key1,
                    'key2' => $node->key2,
                    'key3' => $node->key3,
                    'vin' => $vin
                ]) }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 hover-lift transition">
                        {{-- Image Container --}}
                        <div class="position-relative overflow-hidden rounded-top aspect-ratio-3-4">
                            <img class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                                 src="{{ ($node->thumbnail ?? null) ? Storage::url($node->thumbnail) : asset('assets/images/no-image.png') }}"
                                 alt="{{ $node->key3 }}"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='{{ asset('assets/images/no-image.png') }}';">
                        </div>

                        {{-- Card Body --}}
                        <div class="card-body p-2 p-md-3 text-center">
                            <h6 class="product-title text-dark fw-bold text-uppercase mb-1 fs-6 fs-md-5">
                                {{ $node->formatted_code ?? $node->key3 }}
                            </h6>
                            @if(!empty($node->label))
                                <p class="text-muted small mb-0">{{ $node->label }}</p>
                            @endif
                            @if(!empty($node->Applicability))
                                <p class="text-muted small mb-0">{{ $node->Applicability }}</p>
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
