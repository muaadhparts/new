@extends('layouts.front')

@php use Illuminate\Support\Str; @endphp

@section('name', ($category->localized_name ?? $category->full_code) . ' - ' . __('Illustrations'))

@section('content')
{{-- Breadcrumb --}}
<section class="gs-breadcrumb-section mb-4">
    <div class="container">
        <div class="row justify-content-center content-wrapper">
            <div class="col-12">
                <h2 class="breadcrumb-name">{{ $category->Applicability ?? $category->full_code }}</h2>
                <ul class="bread-menu">
                    {{-- Home --}}
                    <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>

                    {{-- Brand --}}
                    @if($brand)
                    <li><a href="{{ route('catlogs.index', $brand->name) }}">{{ $brand->localized_name }}</a></li>
                    @endif

                    {{-- Catalog --}}
                    <li><a href="{{ route('tree.level1', ['brand' => $brand->name, 'catalog' => $catalog->code, 'vin' => $vin]) }}">{{ $catalog->shortName ?? $catalog->name ?? $catalog->code }}</a></li>

                    {{-- Level 1 (parentCategory1) --}}
                    @if($parentCategory1)
                    <li><a href="{{ route('tree.level2', ['brand' => $brand->name, 'catalog' => $catalog->code, 'key1' => $parentCategory1->full_code, 'vin' => $vin]) }}" class="text-uppercase">{{ str_replace('-', ' ', $parentCategory1->slug ?? $parentCategory1->full_code) }}</a></li>
                    @endif

                    {{-- Level 2 (parentCategory2) --}}
                    @if($parentCategory2 && $parentCategory1)
                    <li><a href="{{ route('tree.level3', ['brand' => $brand->name, 'catalog' => $catalog->code, 'key1' => $parentCategory1->full_code, 'key2' => $parentCategory2->full_code, 'vin' => $vin]) }}" class="text-uppercase">{{ str_replace('-', ' ', $parentCategory2->slug ?? $parentCategory2->full_code) }}</a></li>
                    @endif

                    {{-- Current (category - Level 3) --}}
                    <li><a href="javascript:;">{{ $category->Applicability ?? $category->full_code }}</a></li>
                </ul>
            </div>
        </div>
    </div>
</section>
{{-- Breadcrumb End --}}

{{-- Styles moved to MUAADH.css: Landmarks, Zoom Container, Callout Labels, Skeletons --}}

{{-- Callout Modal Component (Pure HTML/JS - No Livewire) --}}
@include('catalog.partials.callout-modal')

{{-- Single Container for Search + Image (like level3) --}}
<div class="container py-3">
    {{-- Search Box - Full Width on Mobile --}}
    <div class="row mb-4">
        <div class="col-12">
            {{-- Specifications Button - Redirect to level3 after change --}}
            <div class="mb-3">
                @include('catalog.partials.specs-modal', [
                    'catalog' => $catalog,
                    'filters' => $filters,
                    'selectedFilters' => $selectedFilters,
                    'isVinMode' => $isVinMode,
                    'specsRedirectUrl' => route('tree.level3', [
                        'brand' => $brand->name,
                        'catalog' => $catalog->code,
                        'key1' => $parentCategory1->full_code ?? '',
                        'key2' => $parentCategory2->full_code ?? '',
                        'vin' => $vin
                    ])
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
    </div>

    {{-- Illustration Image - Responsive --}}
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <div class="text-center text-md-start">
                        <h5 class="mb-1 illustration-name">
                            <i class="fas fa-image me-2 d-none d-md-inline"></i>
                            <span>{{ $category->full_code }}</span>
                        </h5>
                        @if(!empty($category->slug) || !empty($category->Applicability))
                            <p class="text-muted mb-0 illustration-subname">
                                {{ $category->Applicability ?? str_replace('-', ' ', $category->slug) }}
                            </p>
                        @endif
                    </div>
                </div>
                <div class="card-body p-2 p-md-3">
                    <div class="catalogItems-view">
                        <div class="catalogItems-view__options view-options">
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
