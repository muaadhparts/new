@extends('layouts.front')

@php use Illuminate\Support\Str; @endphp

@section('title', ($category->localized_name ?? $category->full_code) . ' - ' . __('Illustrations'))

@section('content')
{{-- Uses catalog-unified.css for breadcrumb styling --}}
<style>
    /* Landmarks Styles */
    #zoom_container .landmarks {
        position: absolute;
        z-index: 10;
        top: 0;
        left: 0;
    }

    #zoom_container .landmarks .item {
        position: absolute;
        text-align: center;
        display: none;
    }

    .hovered {
        border: 2px solid rgb(219, 16, 16) !important;
        background-color: #bce8f1 !important;
    }

    div[id*='zoom_container'] .landmarks .lable div {
        z-index: 19999;
        text-align: center;
        vertical-align: middle;
        border: 2px solid blue;
        background-color: transparent;
        display: table-cell;
        cursor: pointer;
        padding-left: 4px !important;
        padding-right: 4px !important;
        position: absolute;
        border-radius: 999px;
        font: bold 15px tahoma, arial, verdana, sans-serif;
    }

    .callout-label,
    .correct-callout,
    .bbdover {
        cursor: pointer !important;
        -webkit-tap-highlight-color: rgba(0, 123, 255, 0.2);
    }

    .callout-label:hover .bbdover,
    .bbdover:hover {
        background-color: rgba(0, 123, 255, 0.3) !important;
        opacity: 1 !important;
    }

    .inner-card { height: 20px; background-color: #eee; }
    .card-1 { height: 200px; background-color: #eee; }
    .card-2 { height: 130px; }
    .h-screen { height: 100vh; }

    .animate-pulse { animation: pulse 2s cubic-bezier(.4, 0, .6, 1) infinite; }
    @keyframes pulse { 50% { opacity: .2; } }

    @media (max-width: 768px) {
        .smoothZoom_controls {
            transform: scale(0.6) !important;
            transform-origin: top right !important;
        }

        .smoothZoom_controls a {
            width: 24px !important;
            height: 24px !important;
            font-size: 16px !important;
            line-height: 24px !important;
        }

        .vehicle-search-wrapper,
        .container,
        main,
        body {
            transform: none !important;
            zoom: 1 !important;
        }

        #zoom_container {
            margin: 0 auto !important;
            padding: 0 !important;
            border: 0 !important;
            transform: none !important;
        }

        #zoom_container img#image {
            display: block;
            margin: 0;
        }

        body { overscroll-behavior-y: contain; }

        .callout-label {
            cursor: pointer !important;
            -webkit-tap-highlight-color: rgba(0, 123, 255, 0.3);
        }

        .bbdover {
            cursor: pointer !important;
            min-width: 40px !important;
            min-height: 40px !important;
        }

        .card-body .products-view,
        .card-body .view-options__body {
            padding: 0 !important;
        }

        .products-view__options {
            margin: 0 !important;
        }
    }
</style>

{{-- Callout Modal Component (Pure HTML/JS - No Livewire) --}}
@include('catalog.partials.callout-modal')

<div class="container py-2">
    <div class="row">
        <div class="col-12">
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
                        @if($brand)
                            <li class="breadcrumb-item">
                                <a href="{{ route('catlogs.index', ['brand' => $brand->name]) }}">
                                    {{ Str::limit($brand->name, 15) }}
                                </a>
                            </li>
                        @endif

                        {{-- VIN --}}
                        @if($vin)
                            <li class="breadcrumb-item d-none d-sm-block">
                                <a href="{{ route('tree.level1', [
                                    'brand'   => $brand->name,
                                    'catalog' => $catalog->code,
                                    'vin'  => $vin
                                ]) }}">
                                    <i class="fas fa-car me-1"></i>
                                    {{ Str::limit($vin, 12) }}
                                </a>
                            </li>
                        @endif

                        {{-- Catalog --}}
                        @if($catalog)
                            <li class="breadcrumb-item d-none d-md-block">
                                <a href="{{ route('tree.level1', [
                                    'brand'   => $brand->name,
                                    'catalog' => $catalog->code
                                ]) }}">
                                    {{ Str::limit($catalog->shortName ?? $catalog->name ?? $catalog->code, 20) }}
                                </a>
                            </li>
                        @endif

                        {{-- Level 1 --}}
                        @if($parentCategory1)
                            <li class="breadcrumb-item d-none d-lg-block text-uppercase">
                                <a href="{{ route('tree.level2', [
                                    'brand'   => $brand->name,
                                    'catalog' => $catalog->code,
                                    'key1' => $parentCategory1->full_code
                                ]) }}">
                                    {{ Str::limit(str_replace('-', ' ', $parentCategory1->slug ?? $parentCategory1->full_code), 25) }}
                                </a>
                            </li>
                        @endif

                        {{-- Level 2 --}}
                        @if($parentCategory2 && $parentCategory1)
                            <li class="breadcrumb-item d-none d-xl-block text-uppercase">
                                <a href="{{ route('tree.level3', [
                                    'brand'   => $brand->name,
                                    'catalog' => $catalog->code,
                                    'key1' => $parentCategory1->full_code,
                                    'key2' => $parentCategory2->full_code
                                ]) }}">
                                    {{ Str::limit(str_replace('-', ' ', $parentCategory2->slug ?? $parentCategory2->full_code), 25) }}
                                </a>
                            </li>
                        @endif

                        {{-- Level 3 (current) --}}
                        @if($category)
                            <li class="breadcrumb-item active text-uppercase" aria-current="page">
                                <span>{{ Str::limit($category->Applicability ?? $category->full_code, 30) }}</span>
                            </li>
                        @endif
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

{{-- Search box (تقييد السيكشن الحالي) - AJAX Based --}}
<div class="container mb-3">
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
        'uniqueId' => 'illustrations',
        'showAttributes' => false
    ])
</div>

{{-- Illustration Image Container - Responsive --}}
<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 text-center text-md-start">
                        <i class="fas fa-image me-2 d-none d-md-inline"></i>
                        {{ $category->localized_name ?? $category->full_code }}
                    </h5>
                </div>
                <div class="card-body p-2 p-md-3">
                    <div class="products-view">
                        <div class="products-view__options view-options">
                            <div class="view-options__body">
                                <div id="zoom_container">
                                    <img id="image"
                                         src="{{ Storage::url($category->images) }}"
                                         alt="{{ $category->localized_name ?? $category->full_code }}" />
                                    <div class="landmarks" data-show-at-zoom="0" data-allow-drag="false"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- ✅ تمرير IDs والسياق --}}
<script>
    window.catalogContext = {
        sectionId:   {{ $section->id ?? 'null' }},
        categoryId:  {{ $category->id ?? 'null' }},
        catalogCode: '{{ $catalog->code ?? '' }}',
        brandName:   '{{ optional($brand)->name ?? '' }}'
    };
    let csrf = "{{ csrf_token() }}";
</script>

{{-- سكربتات التكبير ثم سكربتنا --}}
<script src="{{ asset('assets/front/js/jq-zoom.js') }}"></script>
<script src="{{ asset('assets/front/js/preview.js') }}"></script>
<script src="{{ asset('assets/front/js/ill/illustrated.js') }}?v={{ time() }}"></script>
@endpush
