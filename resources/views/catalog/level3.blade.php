@extends('layouts.front3')

@section('title', ($level2Category->label ?? $level2Category->full_code) . ' - ' . __('Parts'))

@section('content')
<div class="container py-3">
    {{-- Breadcrumb --}}
    <div class="product-nav-wrapper mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb text-uppercase mb-0 flex-wrap">
                <li class="breadcrumb-item">
                    <a class="text-black text-decoration-none" href="{{ route('front.index') }}">
                        <i class="fas fa-home d-md-none"></i>
                        <span class="d-none d-md-inline">{{ __('Home') }}</span>
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a class="text-black text-decoration-none" href="{{ route('catlogs.index', $brand->name) }}">
                        {{ strtoupper($brand->name) }}
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a class="text-black text-decoration-none" href="{{ route('tree.level1', [
                        'brand' => $brand->name,
                        'catalog' => $catalog->code,
                        'vin' => $vin
                    ]) }}">
                        {{ strtoupper($catalog->shortName ?? $catalog->name ?? $catalog->code) }}
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a class="text-black text-decoration-none" href="{{ route('tree.level2', [
                        'brand' => $brand->name,
                        'catalog' => $catalog->code,
                        'key1' => $key1,
                        'vin' => $vin
                    ]) }}">
                        {{ strtoupper($key1) }}
                    </a>
                </li>
                @if($vin)
                    <li class="breadcrumb-item">
                        <span class="text-muted">
                            <i class="fas fa-car me-1"></i>
                            <span class="d-none d-md-inline">{{ $vin }}</span>
                            <span class="d-md-none">VIN</span>
                        </span>
                    </li>
                @endif
                <li class="breadcrumb-item active text-primary" aria-current="page">
                    <strong>{{ strtoupper($key2) }}</strong>
                </li>
            </ol>
        </nav>
    </div>

    {{-- Search Box & Specs Button --}}
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

            {{-- Search Box --}}
            @include('includes.frontend.vehicle-search-ajax', [
                'catalog' => $catalog,
                'uniqueId' => 'level3',
                'showAttributes' => false
            ])
        </div>
    </div>

    {{-- Parts Grid --}}
    <div class="row g-3 g-md-4 mb-5">
        @forelse ($categories as $part)
            @php
                $isAllowed = empty($allowedCodes) || in_array($part->full_code, $allowedCodes);
            @endphp
            <div class="col-6 col-sm-6 col-md-4 col-lg-3">
                <a href="{{ route('illustrations', [
                    'brand' => $brand->name,
                    'catalog' => $catalog->code,
                    'key1' => $key1,
                    'key2' => $key2,
                    'key3' => $part->full_code,
                    'vin' => $vin
                ]) }}" class="text-decoration-none {{ !$isAllowed ? 'opacity-50' : '' }}">
                    <div class="card border-0 shadow-sm h-100 hover-lift transition {{ !$isAllowed ? 'border-secondary' : '' }}">
                        <div class="position-relative overflow-hidden rounded-top" style="padding-top: 75%;">
                            <img class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                                 src="{{ $part->thumbnail ? Storage::url($part->thumbnail) : asset('assets/images/no-image.png') }}"
                                 alt="{{ $part->full_code }}"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='{{ asset('assets/images/no-image.png') }}';">

                            @if(!$isAllowed)
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-filter"></i>
                                    </span>
                                </div>
                            @endif
                        </div>
                        <div class="card-body p-2 p-md-3 text-center">
                            <h6 class="product-title text-dark fw-bold text-uppercase mb-1 fs-6 fs-md-5">
                                {{ $part->full_code }}
                            </h6>
                            @if($part->label)
                                <p class="text-muted small mb-0 d-none d-md-block">{{ $part->label }}</p>
                            @endif
                        </div>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    {{ __('No parts available') }}
                </div>
            </div>
        @endforelse
    </div>
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
.opacity-50 {
    opacity: 0.5;
}
@media (max-width: 576px) {
    .breadcrumb-item + .breadcrumb-item::before {
        padding: 0 0.25rem;
    }
}
</style>
@endsection
