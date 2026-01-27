@extends('layouts.front')

@section('name', $brand->localized_name . ' - ' . __('Catalogs'))

@section('content')
{{-- Breadcrumb --}}
<section class="gs-breadcrumb-section">
    <div class="container">
        <div class="row justify-content-center content-wrapper">
            <div class="col-12">
                <h2 class="breadcrumb-name">{{ $brand->localized_name }}</h2>
                <ul class="bread-menu">
                    <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                    <li><a href="javascript:;">@lang('Catalogs')</a></li>
                    <li><a href="javascript:;">{{ $brand->localized_name }}</a></li>
                </ul>
            </div>
        </div>
    </div>
</section>
{{-- Breadcrumb End --}}

<div class="container">
    @if(session()->has('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="row gy-4 gy-lg-5 mt-4">
        {{-- AJAX-based VIN Search --}}
        @include('includes.frontend.search-vin-ajax', ['uniqueId' => 'catlogs'])

        <div>
            {{-- Search and Filter Row --}}
            <form method="GET" action="{{ route('catlogs.index', $brand->name) }}" class="row mb-4">
                {{-- Search Input --}}
                <div class="col-md-3 mb-3">
                    <input type="text" class="form-control" name="search"
                           placeholder="{{ __('Search') }}" value="{{ $searchName ?? '' }}">
                </div>

                {{-- Region Filter --}}
                @if(!empty($regionOptions) && count($regionOptions) > 0)
                    <div class="col-md-3 mb-3">
                        <select class="form-select" name="region" onchange="this.form.submit()">
                            @foreach ($regionOptions as $value => $label)
                                <option value="{{ $value }}" {{ ($region ?? '') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Year Filter Dropdown --}}
                <div class="col-md-3 mb-3">
                    <select class="form-select" name="year" onchange="this.form.submit()">
                        <option value="">{{ __('Filter by Year') }}</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}" {{ ($searchYear ?? '') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Search Button --}}
                <div class="col-md-3 mb-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> {{ __('Search') }}
                    </button>
                </div>
            </form>
        </div>

        @if($catalogs && $catalogs->count() > 0)
            @foreach ($catalogs->sortby('sort') as $catalog)
                <div class="col-12 col-sm-6 col-md-4 col-xl-3 text-center">
                    {{-- $vin pre-computed in VehicleCatalogController (DATA_FLOW_POLICY) --}}

                    <a href="{{ route('tree.level1', [
                        'brand' => $brand->name,
                        'catalog' => $catalog->code,
                        'vin' => $vin
                    ]) }}">
                        <div class="single-catalogItem card border-0 shadow-sm h-100">
                            <div class="img-wrapper position-relative">
                                <img class="xproduct-img img-fluid rounded"
                                     src="{{ Storage::url($catalog->largeImagePath) }}"
                                     alt="catalogItem img"
                                     onerror="this.onerror=null; this.src='{{ asset('assets/images/no-image.png') }}';">
                            </div>
                            <div class="ccontent-wrapper p-3 text-center">
                                <h6 class="catalogItem-name text-dark fw-bold text-center">
                                    {{ getLocalizedLabel($catalog) }}
                                </h6>
                                <p class="text-muted small">{{ $catalog->code }}</p>
                                <div class="xprice-wrapper mt-2 text-center">
                                    <h6 class="text-muted">
                                        {{ formatYearRange($catalog->beginYear, $catalog->endYear) }}
                                    </h6>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        @else
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <h5>{{ __('No catalogs available') }}</h5>
                    <p>{{ __('No catalogs match the selected search criteria.') }}</p>
                </div>
            </div>
        @endif
    </div>

    @if($catalogs && $catalogs->hasPages())
        <div class="d-flex justify-content-center my-5">
            {!! $catalogs->appends(request()->query())->links('includes.frontend.pagination') !!}
        </div>
    @endif
</div>
@endsection
