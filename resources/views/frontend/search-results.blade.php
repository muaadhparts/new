@extends('layouts.front')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/front/css/shipping-quote.css') }}">
@endsection

@push('scripts')
<script src="{{ asset('assets/front/js/customer-location.js') }}"></script>
<script src="{{ asset('assets/front/js/shipping-quote.js') }}"></script>
@endpush

@section('content')
{{-- Breadcrumb --}}
<section class="gs-breadcrumb-section">
    <div class="container">
        <div class="row justify-content-center content-wrapper">
            <div class="col-12">
                <h2 class="breadcrumb-title">@lang('Search Results')</h2>
                <ul class="bread-menu">
                    <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                    <li><a href="javascript:;">@lang('Search')</a></li>
                    <li><a href="javascript:;">{{ $sku }}</a></li>
                </ul>
            </div>
        </div>
    </div>
</section>
{{-- Breadcrumb End --}}

<div class="muaadh-section muaadh-section-gray">
    <div class="container">
        {{-- Search Box --}}
        <div class="mb-4">
            @include('includes.frontend.search-part-ajax', ['uniqueId' => 'searchResults'])
            <div class="text-center mt-3">
                <button type="button" class="muaadh-btn-vin" data-bs-toggle="modal" data-bs-target="#vinSearchModalResults">
                    <i class="fas fa-car"></i>
                    @lang('Search by VIN')
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">

                {{-- Results Header with View Toggle --}}
                <div class="muaadh-search-results-header d-flex flex-wrap justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="muaadh-search-results-title mb-1">
                            <i class="fas fa-box-open"></i>
                            @lang('Total Listings Found:')
                            <span class="muaadh-search-results-count">{{ $cards->total() + $alternativeCards->count() }}</span>
                        </h4>
                        <p class="muaadh-search-results-query mb-0">
                            <i class="fas fa-search me-1"></i>
                            @lang('Query') : <strong>{{ $sku }}</strong>
                            @if($cards->total() > 12)
                                <span class="text-muted ms-2">
                                    (@lang('Page') {{ $cards->currentPage() }} @lang('of') {{ $cards->lastPage() }})
                                </span>
                            @endif
                        </p>
                    </div>
                    {{-- View Toggle Buttons --}}
                    <div class="view-toggle-btns d-flex gap-2" role="tablist">
                        <button class="list-btn check_view active" type="button" data-shopview="list-view"
                            data-bs-toggle="tab" data-bs-target="#layout-list-pane" role="tab"
                            aria-controls="layout-list-pane" aria-selected="true" title="@lang('List View')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 31 24" fill="none">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M1.33331 18.7575H3.90917C4.64356 18.7575 5.24248 19.3564 5.24248 20.0908V22.6666C5.24248 23.401 4.64356 24 3.90917 24H1.33331C0.598918 24 0 23.4011 0 22.6666V20.0908C0 19.3564 0.598918 18.7575 1.33331 18.7575ZM10.7121 0H29.44C30.1744 0 30.7734 0.598986 30.7734 1.33331V3.90917C30.7734 4.64349 30.1744 5.24248 29.44 5.24248C15.6911 5.24248 24.461 5.24248 10.7121 5.24248C9.97775 5.24248 9.37876 4.64356 9.37876 3.90917V1.33331C9.37876 0.598918 9.97775 0 10.7121 0ZM1.33331 0H3.90917C4.64356 0 5.24248 0.598986 5.24248 1.33331V3.90917C5.24248 4.64356 4.64356 5.24248 3.90917 5.24248H1.33331C0.598918 5.24248 0 4.64356 0 3.90917V1.33331C0 0.598918 0.598918 0 1.33331 0ZM10.7121 9.37869H29.44C30.1744 9.37869 30.7734 9.97768 30.7734 10.712V13.2879C30.7734 14.0222 30.1744 14.6212 29.44 14.6212C15.6911 14.6212 24.461 14.6212 10.7121 14.6212C9.97775 14.6212 9.37876 14.0223 9.37876 13.2879V10.712C9.37876 9.97761 9.97775 9.37869 10.7121 9.37869ZM1.33331 9.37869H3.90917C4.64356 9.37869 5.24248 9.97768 5.24248 10.712V13.2879C5.24248 14.0223 4.64356 14.6212 3.90917 14.6212H1.33331C0.598918 14.6212 0 14.0223 0 13.2879V10.712C0 9.97761 0.598918 9.37869 1.33331 9.37869ZM10.7121 18.7575H29.44C30.1744 18.7575 30.7734 19.3564 30.7734 20.0908V22.6666C30.7734 23.4009 30.1744 23.9999 29.44 23.9999C15.6911 23.9999 24.461 23.9999 10.7121 23.9999C9.97775 23.9999 9.37876 23.401 9.37876 22.6666V20.0908C9.37876 19.3564 9.97775 18.7575 10.7121 18.7575Z"
                                    fill="currentColor" />
                            </svg>
                        </button>
                        <button class="grid-btn check_view" type="button" data-shopview="grid-view"
                            data-bs-toggle="tab" data-bs-target="#layout-grid-pane" role="tab"
                            aria-controls="layout-grid-pane" aria-selected="false" title="@lang('Grid View')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 25 24" fill="none">
                                <path d="M9.5685 0H2.8222C1.69252 0 0.773438 0.919078 0.773438 2.04877V8.79506C0.773438 9.92475 1.69252 10.8438 2.8222 10.8438H9.5685C10.6982 10.8438 11.6173 9.92475 11.6173 8.79506V2.04877C11.6173 0.919078 10.6982 0 9.5685 0Z" fill="currentColor" />
                                <path d="M22.7248 0H15.9785C14.8488 0 13.9297 0.919078 13.9297 2.04877V8.79506C13.9297 9.92475 14.8488 10.8438 15.9785 10.8438H22.7248C23.8544 10.8438 24.7735 9.92475 24.7735 8.79506V2.04877C24.7735 0.919078 23.8544 0 22.7248 0Z" fill="currentColor" />
                                <path d="M9.5685 13.1562H2.8222C1.69252 13.1562 0.773438 14.0753 0.773438 15.205V21.9513C0.773438 23.081 1.69252 24.0001 2.8222 24.0001H9.5685C10.6982 24.0001 11.6173 23.081 11.6173 21.9513V15.205C11.6173 14.0753 10.6982 13.1562 9.5685 13.1562Z" fill="currentColor" />
                                <path d="M22.7248 13.1562H15.9785C14.8488 13.1562 13.9297 14.0753 13.9297 15.205V21.9513C13.9297 23.081 14.8488 24.0001 15.9785 24.0001H22.7248C23.8544 24.0001 24.7735 23.081 24.7735 21.9513V15.205C24.7735 14.0753 23.8544 13.1562 22.7248 13.1562Z" fill="currentColor" />
                            </svg>
                        </button>
                    </div>
                </div>

                @if ($cards->total() === 0 && $alternativeCards->isEmpty())
                    {{-- No Results Found --}}
                    <div class="no-results-wrapper text-center py-5">
                        <div class="card shadow-sm">
                            <div class="card-body py-5">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">@lang('No Product Found')</h4>
                                <p class="text-muted mb-0">@lang('Try searching with a different SKU or keyword')</p>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- main content -->
                    <div class="tab-content" id="myTabContent">
                        <!-- product list view start  -->
                        <div class="tab-pane fade show active" id="layout-list-pane" role="tabpanel" tabindex="0">
                            <div class="row gy-4 gy-lg-5">
                                {{-- Main Products --}}
                                @foreach ($cards as $card)
                                    @include('includes.frontend.home_product', ['card' => $card, 'layout' => 'list'])
                                @endforeach
                            </div>

                            {{-- Pagination Links --}}
                            @if($cards->hasPages())
                                <div class="d-flex justify-content-center mt-4">
                                    {{ $cards->links('pagination::bootstrap-5') }}
                                </div>
                            @endif

                            {{-- Alternatives Section (shown only on first page) --}}
                            @if($alternativeCards->isNotEmpty() && $cards->currentPage() === 1)
                                <div class="alternatives-section mt-5">
                                    <div class="section-header mb-4">
                                        <h3 class="text-primary">
                                            <i class="fas fa-exchange-alt me-2"></i>
                                            @lang('Substitutions')
                                        </h3>
                                        <hr class="border-primary">
                                    </div>
                                    <div class="row gy-4 gy-lg-5">
                                        @foreach ($alternativeCards as $card)
                                            @include('includes.frontend.home_product', ['card' => $card, 'layout' => 'list'])
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                        <!-- product list view end  -->

                        <!-- product grid view start  -->
                        <div class="tab-pane fade" id="layout-grid-pane" role="tabpanel" tabindex="0">
                            <div class="row gy-4">
                                {{-- Main Products --}}
                                @foreach ($cards as $card)
                                    @include('includes.frontend.home_product', ['card' => $card, 'layout' => 'grid', 'class' => 'col-6 col-md-4 col-lg-3'])
                                @endforeach
                            </div>

                            {{-- Pagination Links --}}
                            @if($cards->hasPages())
                                <div class="d-flex justify-content-center mt-4">
                                    {{ $cards->links('pagination::bootstrap-5') }}
                                </div>
                            @endif

                            {{-- Alternatives Section (shown only on first page) --}}
                            @if($alternativeCards->isNotEmpty() && $cards->currentPage() === 1)
                                <div class="alternatives-section mt-5">
                                    <div class="section-header mb-4">
                                        <h3 class="text-primary">
                                            <i class="fas fa-exchange-alt me-2"></i>
                                            @lang('Substitutions')
                                        </h3>
                                        <hr class="border-primary">
                                    </div>
                                    <div class="row gy-4">
                                        @foreach ($alternativeCards as $card)
                                            @include('includes.frontend.home_product', ['card' => $card, 'layout' => 'grid', 'class' => 'col-6 col-md-4 col-lg-3'])
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                        <!-- product grid view end  -->
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>

{{-- VIN Search Modal --}}
<div class="modal fade" id="vinSearchModalResults" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content muaadh-modal">
            <div class="modal-header muaadh-modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-car me-2"></i>
                    @lang('Search by VIN')
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                @include('includes.frontend.search-vin-ajax', ['uniqueId' => 'searchResultsModal'])
            </div>
        </div>
    </div>
</div>
@endsection
