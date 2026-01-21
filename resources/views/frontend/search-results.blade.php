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

            {{-- Search Box --}}
            <div class="mb-4">
                @include('includes.frontend.search-part-ajax', ['uniqueId' => 'searchResults', 'initialValue' => $query])
            </div>

            {{-- Results Info --}}
            @if($query)
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="text-muted">@lang('Showing results for'):</span>
                    <strong>"{{ $query }}"</strong>
                    <span class="badge bg-primary">{{ $count }} @lang('items')</span>
                </div>
            @endif

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
