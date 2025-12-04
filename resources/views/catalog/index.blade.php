@extends('layouts.front3')

@section('title', $brand->name . ' - ' . __('Catalogs'))

@section('content')
<div class="container py-4">
    @if(session()->has('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('front.index') }}" class="text-decoration-none">
                    <i class="fas fa-home"></i> {{ __('Home') }}
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                {{ strtoupper($brand->name) }}
            </li>
        </ol>
    </nav>

    {{-- VIN Search --}}
    <div class="row mb-4">
        <div class="col-12">
            @include('includes.frontend.search-vin-ajax', ['uniqueId' => 'catlogs'])
        </div>
    </div>

    {{-- Filters Row --}}
    <form method="GET" action="{{ route('catalog.index', $brand->name) }}" class="row mb-4 g-3" id="catalogFilters">
        {{-- Search Input --}}
        <div class="col-md-3">
            <input type="text"
                   name="search"
                   class="form-control"
                   placeholder="{{ __('Search') }}"
                   value="{{ $searchName }}">
        </div>

        {{-- Region Filter --}}
        @if($regionOptions && count($regionOptions) > 0)
            <div class="col-md-3">
                <select name="region" class="form-select" onchange="this.form.submit()">
                    @foreach ($regionOptions as $value => $label)
                        <option value="{{ $value }}" {{ $region == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        {{-- Year Filter --}}
        <div class="col-md-3">
            <select name="year" class="form-select" onchange="this.form.submit()">
                <option value="">{{ __('Filter by Year') }}</option>
                @foreach ($years as $year)
                    <option value="{{ $year }}" {{ $searchYear == $year ? 'selected' : '' }}>
                        {{ $year }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Search Button --}}
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-search me-1"></i> {{ __('Search') }}
            </button>
        </div>
    </form>

    {{-- Catalogs Grid --}}
    <div class="row g-4">
        @forelse ($catalogs as $catalog)
            <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                <a href="{{ route('catalog.level1', [
                    'brand' => $brand->name,
                    'catalog' => $catalog->code,
                ]) }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 hover-lift">
                        <div class="position-relative overflow-hidden rounded-top" style="padding-top: 75%;">
                            <img class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                                 src="{{ Storage::url($catalog->largeImagePath) }}"
                                 alt="{{ getLocalizedLabel($catalog) }}"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='{{ asset('assets/images/no-image.png') }}';">
                        </div>
                        <div class="card-body p-3 text-center">
                            <h6 class="card-title text-dark fw-bold mb-2">
                                {{ getLocalizedLabel($catalog) }}
                            </h6>
                            <p class="text-muted small mb-1">{{ $catalog->code }}</p>
                            <p class="text-muted small mb-0">
                                {{ formatYearRange($catalog->beginYear, $catalog->endYear) }}
                            </p>
                        </div>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <h5><i class="fas fa-info-circle me-2"></i>{{ __('No catalogs available') }}</h5>
                    <p class="mb-0">{{ __('No catalogs match the selected search criteria.') }}</p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($catalogs->hasPages())
        <div class="d-flex justify-content-center my-5">
            {{ $catalogs->appends(request()->query())->links('includes.frontend.pagination') }}
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
