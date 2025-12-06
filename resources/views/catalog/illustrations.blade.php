@extends('layouts.front')

@php use Illuminate\Support\Str; @endphp

@section('title', ($category->localized_name ?? $category->full_code) . ' - ' . __('Illustrations'))

@section('content')
{{-- Styles moved to MUAADH.css: Landmarks, Zoom Container, Callout Labels, Skeletons --}}

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
