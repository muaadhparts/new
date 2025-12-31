@extends('layouts.front')

@section('content')
    <section class="gs-breadcrumb-section">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">@lang('Vednor Shop')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="javascript:;">@lang('Vednor Shop :') </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>


    <div class="gs-blog-wrapper muaadh-section-gray">
        <div class="container">
            <div class="row">
                <div class="col-12 col-lg-4 col-xl-3 mt-40 mt-lg-0">
                    <div class="gs-product-sidebar-wrapper">
                        <div class="single-product-widget contact-vendor-wrapper">
                            <h5 class="widget-title">@lang('Contact Vendor')</h5>
                            <div class="img-wrapper">
                                <img src="{{ asset('assets/images/users/' . $vendor->photo) }}" alt="vendor img">
                            </div>
                            <ul>
                                <li><span><b>@lang('Store Name:') </b>{{ getLocalizedShopName($vendor) }}</span></li>
                                <li><span><b>@lang('Owner Name:') </b>{{ $vendor->owner_name }}</span></li>
                                <li><span><b>@lang('Phone:') </b> {{ $vendor->shop_number }}</span></li>
                                <li><span><b>@lang('Email:') </b>{{ $vendor->email }}</span></li>
                                <li><span><b>@lang('Address:') </b>{{ $vendor->shop_address }}</span></li>
                            </ul>
                            @if (!auth()->id() == $vendor->id)
                            @if (auth()->check())
                            <form action="{{ route('user-contact') }}" method="POST">
                                @csrf 
                                <input type="hidden" name="user_id" value="{{auth()->id()}}">
                                <div class="vendor-input-wrapper">
                                   
                                    <div class="input-wrapper">
                                        <input class="input-cls" id="email" name="email" type="email"
                                            placeholder="@lang('Email')" readonly value="{{$vendor->email}}" />
                                    </div>
                                    <div class="input-wrapper">
                                        <input class="input-cls" id="subject" name="subject" type="tel"
                                            placeholder="@lang('Subject')" />
                                    </div>
                                    <input type="hidden" name="vendor_id" value="{{ auth()->id() }}">
                                    <div class="input-wrapper">
                                        <textarea placeholder="@lang('Type Your Message...')" name="message" class="input-cls txtarea-cls" rows="3"></textarea>
                                    </div>
                                    <div class="input-wrapper">
                                        <button class="template-btn w-100" type="submit">@lang('Send Message')</button>
                                    </div>
                                </div>
                            </form>
                            @else
                            <div class="input-wrapper">
                                <a href="{{route("user.login")}}" class="template-btn w-100" type="button">@lang('Login for Send Message')</a>
                            </div>
                            @endif  
                            @endif
                            
                            
                        </div>
                        <!-- Brand Quality Filter -->
                        @if(isset($brand_qualities) && $brand_qualities->count() > 0)
                        <div class="single-product-widget">
                            <h5 class="widget-title">@lang('Brand Quality')</h5>
                            <div class="warranty-type m-filter-scroll-box">
                                <ul>
                                    @foreach ($brand_qualities as $quality)
                                        <li class="gs-checkbox-wrapper">
                                            <input type="checkbox" class="attribute-input brand-quality-filter"
                                                name="brand_quality[]"
                                                {{ isset($_GET['brand_quality']) && in_array($quality->id, (array)$_GET['brand_quality']) ? 'checked' : '' }}
                                                id="brand_quality_{{ $quality->id }}"
                                                value="{{ $quality->id }}">
                                            <label class="icon-label"
                                                for="brand_quality_{{ $quality->id }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12"
                                                    height="12" viewBox="0 0 12 12" fill="none">
                                                    <path d="M10 3L4.5 8.5L2 6" stroke="currentColor"
                                                        stroke-width="1.6666" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                            </label>
                                            <label for="brand_quality_{{ $quality->id }}">{{ $quality->localized_name }}</label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <a href="{{ route('front.merchant', str_replace(' ', '-', $vendor->shop_name)) }}"
                                class="template-btn dark-btn w-100 mt-3">@lang('Clear Filter')</a>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="col-12 col-lg-8 col-xl-9 gs-main-blog-wrapper">

                    @php
                        // Default to list-view for first visit
                        if (request()->input('view_check') == null || request()->input('view_check') == 'list-view') {
                            $view = 'list-view';
                        } else {
                            $view = 'grid-view';
                        }
                    @endphp

                    <!-- product nav wrapper -->
                    <div class=" product-nav-wrapper">
                        <h5>@lang('Total Products Found:') {{ $vprods->total() }}</h5>
                        <div class="filter-wrapper">
                            <div class="sort-wrapper">
                                <h5>@lang('Sort by:')</h5>

                                <select class="nice-select" id="sortby" name="sort">
                                    <option value="date_desc">{{ __('Latest Product') }}</option>
                                    <option value="date_asc">{{ __('Oldest Product') }}</option>
                                    <option value="price_asc">{{ __('Lowest Price') }}</option>
                                    <option value="price_desc">{{ __('Highest Price') }}</option>
                                </select>
                            </div>
                            <!-- list and grid view tab btns  start -->
                            <div class="view-toggle-btns d-flex gap-2" role="tablist">
                                <button class="list-btn check_view {{ $view == 'list-view' ? 'active' : '' }}"
                                    data-shopview="list-view" type="button" data-bs-toggle="tab"
                                    data-bs-target="#layout-list-pane" role="tab" aria-controls="layout-list-pane"
                                    aria-selected="{{ $view == 'list-view' ? 'true' : 'false' }}" title="@lang('List View')">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 31 24" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M1.33331 18.7575H3.90917C4.64356 18.7575 5.24248 19.3564 5.24248 20.0908V22.6666C5.24248 23.401 4.64356 24 3.90917 24H1.33331C0.598918 24 0 23.4011 0 22.6666V20.0908C0 19.3564 0.598918 18.7575 1.33331 18.7575ZM10.7121 0H29.44C30.1744 0 30.7734 0.598986 30.7734 1.33331V3.90917C30.7734 4.64349 30.1744 5.24248 29.44 5.24248C15.6911 5.24248 24.461 5.24248 10.7121 5.24248C9.97775 5.24248 9.37876 4.64356 9.37876 3.90917V1.33331C9.37876 0.598918 9.97775 0 10.7121 0ZM1.33331 0H3.90917C4.64356 0 5.24248 0.598986 5.24248 1.33331V3.90917C5.24248 4.64356 4.64356 5.24248 3.90917 5.24248H1.33331C0.598918 5.24248 0 4.64356 0 3.90917V1.33331C0 0.598918 0.598918 0 1.33331 0ZM10.7121 9.37869H29.44C30.1744 9.37869 30.7734 9.97768 30.7734 10.712V13.2879C30.7734 14.0222 30.1744 14.6212 29.44 14.6212C15.6911 14.6212 24.461 14.6212 10.7121 14.6212C9.97775 14.6212 9.37876 14.0223 9.37876 13.2879V10.712C9.37876 9.97761 9.97775 9.37869 10.7121 9.37869ZM1.33331 9.37869H3.90917C4.64356 9.37869 5.24248 9.97768 5.24248 10.712V13.2879C5.24248 14.0223 4.64356 14.6212 3.90917 14.6212H1.33331C0.598918 14.6212 0 14.0223 0 13.2879V10.712C0 9.97761 0.598918 9.37869 1.33331 9.37869ZM10.7121 18.7575H29.44C30.1744 18.7575 30.7734 19.3564 30.7734 20.0908V22.6666C30.7734 23.4009 30.1744 23.9999 29.44 23.9999C15.6911 23.9999 24.461 23.9999 10.7121 23.9999C9.97775 23.9999 9.37876 23.401 9.37876 22.6666V20.0908C9.37876 19.3564 9.97775 18.7575 10.7121 18.7575Z"
                                            fill="currentColor" />
                                    </svg>
                                </button>
                                <button class="grid-btn check_view {{ $view == 'grid-view' ? 'active' : '' }}"
                                    type="button" data-shopview="grid-view" data-bs-toggle="tab"
                                    data-bs-target="#layout-grid-pane" role="tab" aria-controls="layout-grid-pane"
                                    aria-selected="{{ $view == 'grid-view' ? 'true' : 'false' }}" title="@lang('Grid View')">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 25 24" fill="none">
                                        <path d="M9.5685 0H2.8222C1.69252 0 0.773438 0.919078 0.773438 2.04877V8.79506C0.773438 9.92475 1.69252 10.8438 2.8222 10.8438H9.5685C10.6982 10.8438 11.6173 9.92475 11.6173 8.79506V2.04877C11.6173 0.919078 10.6982 0 9.5685 0Z" fill="currentColor" />
                                        <path d="M22.7248 0H15.9785C14.8488 0 13.9297 0.919078 13.9297 2.04877V8.79506C13.9297 9.92475 14.8488 10.8438 15.9785 10.8438H22.7248C23.8544 10.8438 24.7735 9.92475 24.7735 8.79506V2.04877C24.7735 0.919078 23.8544 0 22.7248 0Z" fill="currentColor" />
                                        <path d="M9.5685 13.1562H2.8222C1.69252 13.1562 0.773438 14.0753 0.773438 15.205V21.9513C0.773438 23.081 1.69252 24.0001 2.8222 24.0001H9.5685C10.6982 24.0001 11.6173 23.081 11.6173 21.9513V15.205C11.6173 14.0753 10.6982 13.1562 9.5685 13.1562Z" fill="currentColor" />
                                        <path d="M22.7248 13.1562H15.9785C14.8488 13.1562 13.9297 14.0753 13.9297 15.205V21.9513C13.9297 23.081 14.8488 24.0001 15.9785 24.0001H22.7248C23.8544 24.0001 24.7735 23.081 24.7735 21.9513V15.205C24.7735 14.0753 23.8544 13.1562 22.7248 13.1562Z" fill="currentColor" />
                                    </svg>
                                </button>
                            </div>
                            <!-- list and grid view tab btns  end -->
                        </div>
                    </div>



                    @if ($vprods->total() == 0)
                        <!-- product nav wrapper for no data found -->
                        <div class="product-nav-wrapper d-flex justify-content-center ">
                            <h5>@lang('No Product Found')</h5>
                        </div>
                    @else
                        <!-- main content inside scrollable box -->
                        <div class="vendor-products-box">
                            <div class="vendor-products-scroll">
                                <div class="tab-content" id="myTabContent">
                                    <!-- product list view start  -->
                                    <div class="tab-pane fade {{ $view == 'list-view' ? 'show active' : '' }}"
                                        id="layout-list-pane" role="tabpanel" tabindex="0">
                                        <div class="row gy-4">
                                            @foreach ($vprods as $catalogItem)
                                                @include('includes.frontend.home_catalog_item', ['layout' => 'list', 'catalogItem' => $catalogItem, 'mp' => $catalogItem->merchant_merchant_item ?? null])
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="tab-pane fade {{ $view == 'grid-view' ? 'show active' : '' }}  "
                                        id="layout-grid-pane" role="tabpanel" tabindex="0">
                                        <div class="row gy-4">
                                            @foreach ($vprods as $catalogItem)
                                                @include('includes.frontend.home_catalog_item', [
                                                    'class' => 'col-sm-6 col-md-6 col-xl-4',
                                                    'catalogItem' => $catalogItem,
                                                    'mp' => $catalogItem->merchant_merchant_item ?? null,
                                                ])
                                            @endforeach
                                        </div>
                                    </div>
                                    <!-- product grid view end  -->
                                </div>
                            </div>
                            <!-- Pagination outside scroll area -->
                            <div class="vendor-products-pagination">
                                <div class="m-pagination-simple"
                                     data-current="{{ $vprods->currentPage() }}"
                                     data-last="{{ $vprods->lastPage() }}"
                                     data-base-url="{{ route('front.merchant', str_replace(' ', '-', $vendor->shop_name)) }}">

                                    {{-- Previous Button --}}
                                    <button type="button" class="m-pagination-simple__btn m-pagination-simple__prev {{ $vprods->onFirstPage() ? 'm-pagination-simple__btn--disabled' : '' }}"
                                            {{ $vprods->onFirstPage() ? 'disabled' : '' }}>
                                        <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>
                                    </button>

                                    {{-- Page Input --}}
                                    <div class="m-pagination-simple__input-group">
                                        <input type="number"
                                               class="m-pagination-simple__input"
                                               value="{{ $vprods->currentPage() }}"
                                               min="1"
                                               max="{{ $vprods->lastPage() }}">
                                        <span class="m-pagination-simple__separator">@lang('of')</span>
                                        <span class="m-pagination-simple__total">{{ $vprods->lastPage() }}</span>
                                    </div>

                                    {{-- Next Button --}}
                                    <button type="button" class="m-pagination-simple__btn m-pagination-simple__next {{ !$vprods->hasMorePages() ? 'm-pagination-simple__btn--disabled' : '' }}"
                                            {{ !$vprods->hasMorePages() ? 'disabled' : '' }}>
                                        <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

@endsection
@section('script')
    <script>
        (function($) {
            "use strict";

            // ========================================
            // Vendor Products AJAX System
            // ========================================
            const baseUrl = '{{ route('front.merchant', str_replace(' ', '-', $vendor->shop_name)) }}';
            const $scrollContainer = $('.vendor-products-scroll');
            const $productsContainer = $('.vendor-products-scroll');
            const $paginationContainer = $('.m-pagination-simple');
            const $totalProducts = $('.product-nav-wrapper h5').first();

            let isLoading = false;
            let currentPage = parseInt($paginationContainer.data('current')) || 1;
            let lastPage = parseInt($paginationContainer.data('last')) || 1;

            // ========================================
            // Build URL with all current filters
            // ========================================
            function buildUrl(page) {
                let params = new URLSearchParams();

                // Page
                if (page && page > 1) {
                    params.set('page', page);
                }

                // Brand Quality filters
                $(".attribute-input:checked").each(function() {
                    params.append($(this).attr('name'), $(this).val());
                });

                // Sort
                const sortVal = $("#sortby").val();
                if (sortVal && sortVal !== '') {
                    params.set('sort', sortVal);
                }

                // View mode
                const viewMode = $('.check_view.active').data('shopview');
                if (viewMode) {
                    params.set('view_check', viewMode);
                }

                const queryString = params.toString();
                return queryString ? baseUrl + '?' + queryString : baseUrl;
            }

            // ========================================
            // Load content via AJAX
            // ========================================
            function loadContent(page, updateHistory = true) {
                if (isLoading) return;

                page = page || 1;
                isLoading = true;

                // Show loading state
                $scrollContainer.addClass('is-loading');
                $paginationContainer.find('.m-pagination-simple__btn').prop('disabled', true);

                const url = buildUrl(page);

                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'html',
                    success: function(response) {
                        // Parse response
                        const $response = $('<div>').html(response);
                        const $newContent = $response.find('#myTabContent');
                        const $paginationData = $response.find('#ajax-pagination-data');

                        // Update products content
                        if ($newContent.length) {
                            $productsContainer.find('#myTabContent').replaceWith($newContent);
                        }

                        // Update pagination data from JSON
                        if ($paginationData.length) {
                            try {
                                const data = JSON.parse($paginationData.text());
                                currentPage = data.currentPage;
                                lastPage = data.lastPage;

                                // Update total products count
                                $totalProducts.html('@lang("Total Products Found:") ' + data.total);
                            } catch(e) {
                                currentPage = page;
                            }
                        } else {
                            currentPage = page;
                        }

                        // Update pagination UI
                        updatePaginationUI();

                        // Scroll to top of container
                        $scrollContainer.scrollTop(0);

                        // Update URL without reload
                        if (updateHistory) {
                            history.pushState({page: currentPage, url: url}, '', url);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        // Fallback to normal navigation on error
                        window.location.href = url;
                    },
                    complete: function() {
                        isLoading = false;
                        $scrollContainer.removeClass('is-loading');
                        updatePaginationUI();
                    }
                });
            }

            // ========================================
            // Update Pagination UI
            // ========================================
            function updatePaginationUI() {
                const $input = $paginationContainer.find('.m-pagination-simple__input');
                const $prevBtn = $paginationContainer.find('.m-pagination-simple__prev');
                const $nextBtn = $paginationContainer.find('.m-pagination-simple__next');
                const $total = $paginationContainer.find('.m-pagination-simple__total');

                // Update input and total
                $input.val(currentPage).attr('max', lastPage);
                $total.text(lastPage);

                // Update prev button
                if (currentPage <= 1) {
                    $prevBtn.addClass('m-pagination-simple__btn--disabled').prop('disabled', true);
                } else {
                    $prevBtn.removeClass('m-pagination-simple__btn--disabled').prop('disabled', false);
                }

                // Update next button
                if (currentPage >= lastPage) {
                    $nextBtn.addClass('m-pagination-simple__btn--disabled').prop('disabled', true);
                } else {
                    $nextBtn.removeClass('m-pagination-simple__btn--disabled').prop('disabled', false);
                }
            }

            // ========================================
            // Filter Events (Brand Quality & Sort)
            // ========================================
            $(".attribute-input, #sortby").on('change', function() {
                // Reset to page 1 when filter changes
                currentPage = 1;
                loadContent(1);
            });

            // ========================================
            // Pagination Events
            // ========================================
            // Previous button
            $paginationContainer.on('click', '.m-pagination-simple__prev', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (!$(this).prop('disabled') && !isLoading && currentPage > 1) {
                    loadContent(currentPage - 1);
                }
                return false;
            });

            // Next button
            $paginationContainer.on('click', '.m-pagination-simple__next', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (!$(this).prop('disabled') && !isLoading && currentPage < lastPage) {
                    loadContent(currentPage + 1);
                }
                return false;
            });

            // Input - Enter key
            $paginationContainer.on('keydown', '.m-pagination-simple__input', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    e.stopPropagation();
                    let page = parseInt($(this).val()) || 1;
                    page = Math.max(1, Math.min(page, lastPage));
                    if (page !== currentPage) {
                        loadContent(page);
                    }
                    $(this).blur();
                    return false;
                }
            });

            // Input - Blur
            $paginationContainer.on('blur', '.m-pagination-simple__input', function() {
                let page = parseInt($(this).val()) || currentPage;
                page = Math.max(1, Math.min(page, lastPage));
                if (page !== currentPage) {
                    loadContent(page);
                } else {
                    $(this).val(currentPage);
                }
            });

            // ========================================
            // Browser History (Back/Forward)
            // ========================================
            $(window).on('popstate', function(e) {
                const state = e.originalEvent.state;
                if (state && state.page) {
                    // Update checkboxes and sort from URL
                    const urlParams = new URLSearchParams(window.location.search);

                    // Update sort dropdown
                    const sortVal = urlParams.get('sort') || 'date_desc';
                    $('#sortby').val(sortVal);

                    // Update Brand Quality checkboxes
                    $('.attribute-input').prop('checked', false);
                    urlParams.getAll('brand_quality[]').forEach(function(val) {
                        $('input[name="brand_quality[]"][value="' + val + '"]').prop('checked', true);
                    });

                    // Load content without adding to history
                    currentPage = state.page;
                    loadContent(state.page, false);
                }
            });

            // Set initial state
            history.replaceState({page: currentPage, url: window.location.href}, '', window.location.href);

        })(jQuery);
    </script>
@endsection
