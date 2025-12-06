@extends('layouts.front')

@section('content')
<section class="muaadh-section py-4">
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

                {{-- Results Header --}}
                <div class="muaadh-search-results-header">
                    <h4 class="muaadh-search-results-title">
                        <i class="fas fa-box-open"></i>
                        @lang('Total Listings Found:')
                        <span class="muaadh-search-results-count">{{ $filteredMerchants->count() }}</span>
                    </h4>
                    <p class="muaadh-search-results-query">
                        <i class="fas fa-search me-1"></i>
                        @lang('Query') : <strong>{{ $sku }}</strong>
                    </p>
                </div>

                @if ($filteredMerchants->isEmpty())
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

                                {{-- عرض كل المنتجات المفلترة --}}
                                @php
                                    $mainProducts = $filteredMerchants->where('is_alternative', false);
                                    $alternativeProducts = $filteredMerchants->where('is_alternative', true);
                                @endphp

                                {{-- Main Products --}}
                                @foreach ($mainProducts as $item)
                                    @include('includes.frontend.list_view_product', [
                                        'product'  => $item['product'],
                                        'vendorId' => $item['merchant']->user_id,
                                        'mp'       => $item['merchant'],
                                    ])
                                @endforeach

                                {{-- Alternatives Section --}}
                                @if($alternativeProducts->count() > 0)
                                    <div class="col-12">
                                        <div class="alternatives-section mt-4">
                                            <div class="section-header mb-4">
                                                <h3 class="text-primary">
                                                    <i class="fas fa-exchange-alt me-2"></i>
                                                    {{ trans('Substitutions') }}
                                                </h3>
                                                <hr class="border-primary">
                                            </div>
                                        </div>
                                    </div>

                                    @foreach ($alternativeProducts as $item)
                                        @include('includes.frontend.list_view_product', [
                                            'product'  => $item['product'],
                                            'vendorId' => $item['merchant']->user_id,
                                            'mp'       => $item['merchant'],
                                        ])
                                    @endforeach
                                @endif

                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
</section>

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
