@extends('layouts.front')

@section('content')
    {{-- Breadcrumb --}}
    <section class="gs-breadcrumb-section bg-class"
        data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-name">@lang('Search Results')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li>@lang('Search')</li>
                        @if($query)
                            <li>{{ $query }}</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Main Content --}}
    <div class="gs-page-wrapper muaadh-section-gray">
        <div class="container py-4">

            {{-- Search Info Header --}}
            <div class="m-card mb-4">
                <div class="m-card__body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <h4 class="mb-1">
                                <i class="fas fa-search me-2 text-primary"></i>
                                @lang('Search Results')
                            </h4>
                            @if($query)
                                <p class="text-muted mb-0">
                                    @lang('Showing results for'): <strong>"{{ $query }}"</strong>
                                    <span class="badge bg-primary ms-2">{{ $count }} @lang('items')</span>
                                </p>
                            @endif
                        </div>

                        {{-- Search Box --}}
                        <div class="search-box-mini">
                            <form action="{{ route('front.search-results') }}" method="GET" class="d-flex gap-2">
                                <input type="text" name="q" value="{{ $query }}"
                                       class="form-control"
                                       placeholder="@lang('Enter part number or name')"
                                       style="min-width: 250px;">
                                <button type="submit" class="m-btn m-btn--primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @if($count > 0)
                {{-- Search Results Section --}}
                <div class="m-card mb-4">
                    <div class="m-card__header">
                        <h5 class="m-card__title mb-0">
                            <i class="fas fa-cube me-2"></i>
                            @lang('Results')
                            <span class="badge bg-primary ms-2">{{ $count }}</span>
                        </h5>
                    </div>
                    <div class="m-card__body">
                        <div class="row gy-4">
                            @foreach ($cards as $card)
                                @include('includes.frontend.home_catalog_item', ['card' => $card, 'layout' => 'list'])
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Alternatives Section --}}
                @if($alternativeCards->isNotEmpty())
                    <div class="m-card">
                        <div class="m-card__header">
                            <h5 class="m-card__title mb-0">
                                <i class="fas fa-exchange-alt me-2"></i>
                                @lang('Alternatives')
                                <span class="badge bg-secondary ms-2">{{ $alternativeCards->count() }}</span>
                            </h5>
                        </div>
                        <div class="m-card__body">
                            <div class="row gy-4">
                                @foreach ($alternativeCards as $card)
                                    @include('includes.frontend.home_catalog_item', ['card' => $card, 'layout' => 'list'])
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

            @else
                {{-- No Results --}}
                <div class="m-card">
                    <div class="m-card__body text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        @if($query)
                            <h5>@lang('No results found for') "{{ $query }}"</h5>
                            <p class="text-muted">@lang('Try different keywords or check the spelling')</p>
                        @else
                            <h5>@lang('Enter a search term')</h5>
                            <p class="text-muted">@lang('Search by part number or name')</p>
                        @endif
                        <a href="{{ route('front.index') }}" class="btn btn-primary mt-3">
                            <i class="fas fa-home me-1"></i>@lang('Back to Home')
                        </a>
                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection
