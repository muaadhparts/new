@extends('layouts.front')

@section('content')
    {{-- Category Tree Styles --}}
    <style>
        .category-tree-sidebar .tree-item {
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .category-tree-sidebar .tree-item:hover {
            background-color: var(--color-surface-hover, #f5f5f5);
        }
        .category-tree-sidebar .tree-item.active {
            background-color: var(--theme-primary-light, #e8f4ff);
            color: var(--theme-primary);
            font-weight: 600;
        }
        .category-tree-sidebar .tree-item .tree-toggle {
            display: inline-flex;
            width: 20px;
            justify-content: center;
            margin-right: 4px;
        }
        .category-tree-sidebar .tree-children {
            padding-left: 20px;
            border-left: 2px solid var(--theme-border, #e0e0e0);
            margin-left: 10px;
        }
        .category-tree-sidebar .tree-item .item-count {
            font-size: 12px;
            color: var(--text-secondary);
            margin-left: auto;
        }
        /* RTL Support */
        [dir="rtl"] .category-tree-sidebar .tree-item .tree-toggle {
            margin-right: 0;
            margin-left: 4px;
        }
        [dir="rtl"] .category-tree-sidebar .tree-children {
            padding-left: 0;
            padding-right: 20px;
            border-left: none;
            border-right: 2px solid var(--theme-border, #e0e0e0);
            margin-left: 0;
            margin-right: 10px;
        }
        /* Mobile Responsive */
        @media (max-width: 991px) {
            .gs-blog-wrapper .row.catalogItems-layout {
                display: flex;
                flex-direction: column;
            }
            .gs-blog-wrapper .row.catalogItems-layout .sidebar-column {
                purchase: 1;
                margin-top: 0;
                margin-bottom: 20px;
            }
            .gs-blog-wrapper .row.catalogItems-layout .catalogItems-column {
                purchase: 2;
            }
        }
    </style>

    <section class="gs-breadcrumb-section">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-name">
                        {{ $brand->name ?? __('Catalog') }}
                        @if($catalog)
                            - {{ $catalog->name }}
                        @endif
                    </h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="{{ route('front.catalog.category', ['brand_slug' => $brand_slug, 'catalog_slug' => $catalog_slug]) }}">{{ $brand->name ?? __('Catalog') }}</a></li>
                        @php
                            $breadcrumbCat1 = null;
                            $breadcrumbCat2 = null;
                        @endphp
                        @foreach($breadcrumb as $crumb)
                            @php
                                // Build cumulative breadcrumb URLs
                                $crumbParams = [
                                    'brand_slug' => $brand_slug,
                                    'catalog_slug' => $catalog_slug,
                                ];
                                if ($crumb->level == 1) {
                                    $breadcrumbCat1 = $crumb->slug;
                                    $crumbParams['cat1'] = $crumb->slug;
                                } elseif ($crumb->level == 2) {
                                    $breadcrumbCat2 = $crumb->slug;
                                    $crumbParams['cat1'] = $breadcrumbCat1;
                                    $crumbParams['cat2'] = $crumb->slug;
                                } elseif ($crumb->level == 3) {
                                    $crumbParams['cat1'] = $breadcrumbCat1;
                                    $crumbParams['cat2'] = $breadcrumbCat2;
                                    $crumbParams['cat3'] = $crumb->slug;
                                }
                                $crumbLabel = app()->getLocale() == 'ar' ? ($crumb->label_ar ?: $crumb->label_en) : $crumb->label_en;
                            @endphp
                            <li><a href="{{ route('front.catalog.category', $crumbParams) }}">{{ $crumbLabel }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <!-- breadcrumb end -->

    <!-- catalogItem wrapper start -->
    <div class="muaadh-section muaadh-section-gray">
        <div class="container">
            <div class="row catalogItems-layout">
                <div class="col-12 col-lg-4 col-xl-3 sidebar-column">
                    <div class="gs-catalogItem-sidebar-wrapper">
                        <!-- Category Tree Navigation -->
                        <div class="single-catalogItem-widget category-tree-sidebar">
                            <h5 class="widget-name">@lang('Categories')</h5>
                            <div class="catalogItem-cat-widget">
                                @if($categoryTree->count() > 0)
                                    @include('partials.catalog.category-tree-recursive', [
                                        'categories' => $categoryTree,
                                        'selectedCategory' => $selectedCategory,
                                        'breadcrumb' => $breadcrumb ?? collect(),
                                        'hierarchy' => $hierarchy ?? [],
                                        'brand_slug' => $brand_slug,
                                        'catalog_slug' => $catalog_slug,
                                        'level' => 1,
                                        'parentCat1Slug' => null,
                                        'parentCat2Slug' => null,
                                    ])
                                @else
                                    <p class="text-muted">@lang('No categories available')</p>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>

                <div class="col-12 col-lg-8 col-xl-9 gs-main-blog-wrapper catalogItems-column">

                    @php
                        $view = request()->input('view_check', 'list-view');
                    @endphp

                    <!-- catalogItem nav wrapper -->
                    <div class="catalogItem-nav-wrapper">
                        <h5>@lang('Total Items Found:') {{ $items->total() }}</h5>
                        <div class="filter-wrapper">
                            <div class="sort-wrapper">
                                <h5>@lang('Sort by:')</h5>
                                @php $currentSort = request('sort', 'date_desc'); @endphp
                                <select class="nice-select" id="sortby" name="sort">
                                    <option value="date_desc" {{ $currentSort === 'date_desc' ? 'selected' : '' }}>{{ __('Latest CatalogItem') }}</option>
                                    <option value="date_asc" {{ $currentSort === 'date_asc' ? 'selected' : '' }}>{{ __('Oldest CatalogItem') }}</option>
                                    <option value="price_asc" {{ $currentSort === 'price_asc' ? 'selected' : '' }}>{{ __('Lowest Price') }}</option>
                                    <option value="price_desc" {{ $currentSort === 'price_desc' ? 'selected' : '' }}>{{ __('Highest Price') }}</option>
                                </select>
                            </div>
                            <!-- list and grid view tab btns -->
                            <div class="view-toggle-btns d-flex gap-2" role="tablist">
                                <button class="list-btn check_view {{ $view == 'list-view' ? 'active' : '' }}"
                                    data-shopview="list-view" type="button" data-bs-toggle="tab"
                                    data-bs-target="#layout-list-pane" role="tab" aria-controls="layout-list-pane"
                                    aria-selected="{{ $view == 'list-view' ? 'true' : 'false' }}" name="@lang('List View')">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 31 24" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M1.33331 18.7575H3.90917C4.64356 18.7575 5.24248 19.3564 5.24248 20.0908V22.6666C5.24248 23.401 4.64356 24 3.90917 24H1.33331C0.598918 24 0 23.4011 0 22.6666V20.0908C0 19.3564 0.598918 18.7575 1.33331 18.7575ZM10.7121 0H29.44C30.1744 0 30.7734 0.598986 30.7734 1.33331V3.90917C30.7734 4.64349 30.1744 5.24248 29.44 5.24248C15.6911 5.24248 24.461 5.24248 10.7121 5.24248C9.97775 5.24248 9.37876 4.64356 9.37876 3.90917V1.33331C9.37876 0.598918 9.97775 0 10.7121 0ZM1.33331 0H3.90917C4.64356 0 5.24248 0.598986 5.24248 1.33331V3.90917C5.24248 4.64356 4.64356 5.24248 3.90917 5.24248H1.33331C0.598918 5.24248 0 4.64356 0 3.90917V1.33331C0 0.598918 0.598918 0 1.33331 0ZM10.7121 9.37869H29.44C30.1744 9.37869 30.7734 9.97768 30.7734 10.712V13.2879C30.7734 14.0222 30.1744 14.6212 29.44 14.6212C15.6911 14.6212 24.461 14.6212 10.7121 14.6212C9.97775 14.6212 9.37876 14.0223 9.37876 13.2879V10.712C9.37876 9.97761 9.97775 9.37869 10.7121 9.37869ZM1.33331 9.37869H3.90917C4.64356 9.37869 5.24248 9.97768 5.24248 10.712V13.2879C5.24248 14.0223 4.64356 14.6212 3.90917 14.6212H1.33331C0.598918 14.6212 0 14.0223 0 13.2879V10.712C0 9.97761 0.598918 9.37869 1.33331 9.37869ZM10.7121 18.7575H29.44C30.1744 18.7575 30.7734 19.3564 30.7734 20.0908V22.6666C30.7734 23.4009 30.1744 23.9999 29.44 23.9999C15.6911 23.9999 24.461 23.9999 10.7121 23.9999C9.97775 23.9999 9.37876 23.401 9.37876 22.6666V20.0908C9.37876 19.3564 9.97775 18.7575 10.7121 18.7575Z"
                                            fill="currentColor" />
                                    </svg>
                                </button>
                                <button class="grid-btn check_view {{ $view == 'grid-view' ? 'active' : '' }}"
                                    type="button" data-shopview="grid-view" data-bs-toggle="tab"
                                    data-bs-target="#layout-grid-pane" role="tab" aria-controls="layout-grid-pane"
                                    aria-selected="{{ $view == 'grid-view' ? 'true' : 'false' }}" name="@lang('Grid View')">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 25 24" fill="none">
                                        <path d="M9.5685 0H2.8222C1.69252 0 0.773438 0.919078 0.773438 2.04877V8.79506C0.773438 9.92475 1.69252 10.8438 2.8222 10.8438H9.5685C10.6982 10.8438 11.6173 9.92475 11.6173 8.79506V2.04877C11.6173 0.919078 10.6982 0 9.5685 0Z" fill="currentColor" />
                                        <path d="M22.7248 0H15.9785C14.8488 0 13.9297 0.919078 13.9297 2.04877V8.79506C13.9297 9.92475 14.8488 10.8438 15.9785 10.8438H22.7248C23.8544 10.8438 24.7735 9.92475 24.7735 8.79506V2.04877C24.7735 0.919078 23.8544 0 22.7248 0Z" fill="currentColor" />
                                        <path d="M9.5685 13.1562H2.8222C1.69252 13.1562 0.773438 14.0753 0.773438 15.205V21.9513C0.773438 23.081 1.69252 24.0001 2.8222 24.0001H9.5685C10.6982 24.0001 11.6173 23.081 11.6173 21.9513V15.205C11.6173 14.0753 10.6982 13.1562 9.5685 13.1562Z" fill="currentColor" />
                                        <path d="M22.7248 13.1562H15.9785C14.8488 13.1562 13.9297 14.0753 13.9297 15.205V21.9513C13.9297 23.081 14.8488 24.0001 15.9785 24.0001H22.7248C23.8544 24.0001 24.7735 23.081 24.7735 21.9513V15.205C24.7735 14.0753 23.8544 13.1562 22.7248 13.1562Z" fill="currentColor" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    @if ($items->total() == 0)
                        {{-- Zero Results Box --}}
                        <div class="category-catalogItems-box">
                            <div class="category-catalogItems-scroll" id="catalogItems-container">
                                <div class="m-no-results-box">
                                    <div class="m-no-results-box__icon">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <h4 class="m-no-results-box__name">@lang('No matching catalogItems')</h4>
                                    <p class="m-no-results-box__subname">@lang('Try selecting a different category or browse all categories.')</p>

                                    @if($selectedCategory)
                                    <div class="m-no-results-box__filters">
                                        <h6 class="m-no-results-box__filters-name">@lang('Selected Category:')</h6>
                                        <ul class="m-no-results-box__filters-list">
                                            <li>
                                                <span class="m-no-results-box__filter-value">
                                                    @foreach($breadcrumb as $crumb)
                                                        {{ app()->getLocale() == 'ar' ? ($crumb->label_ar ?: $crumb->label_en) : $crumb->label_en }}
                                                        @if(!$loop->last)
                                                            <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} mx-1"></i>
                                                        @endif
                                                    @endforeach
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- main content inside scrollable box -->
                        <div class="category-catalogItems-box">
                            <div class="category-catalogItems-scroll">
                                <div class="tab-content" id="myTabContent">
                                    <!-- catalogItem list view start -->
                                    <div class="tab-pane fade {{ $view == 'list-view' ? 'show active' : '' }}"
                                        id="layout-list-pane" role="tabpanel" tabindex="0">
                                        <div class="row gy-4">
                                            @foreach ($items as $item)
                                                @include('partials.catalog.merchant-item-card', ['item' => $item, 'layout' => 'list'])
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="tab-pane fade {{ $view == 'grid-view' ? 'show active' : '' }}"
                                        id="layout-grid-pane" role="tabpanel" tabindex="0">
                                        <div class="row gy-4">
                                            @foreach ($items as $item)
                                                @include('partials.catalog.merchant-item-card', ['item' => $item, 'layout' => 'grid', 'class' => 'col-6 col-md-4 col-lg-3'])
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Pagination -->
                            <div class="category-catalogItems-pagination">
                                <div class="m-pagination-simple"
                                     data-current="{{ $items->currentPage() }}"
                                     data-last="{{ $items->lastPage() }}"
                                     data-total="{{ $items->total() }}">

                                    {{-- Previous Button --}}
                                    <button type="button" class="m-pagination-simple__btn m-pagination-simple__prev {{ $items->onFirstPage() ? 'm-pagination-simple__btn--disabled' : '' }}"
                                            {{ $items->onFirstPage() ? 'disabled' : '' }}>
                                        <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>
                                    </button>

                                    {{-- Page Input --}}
                                    <div class="m-pagination-simple__input-group">
                                        <input type="number"
                                               class="m-pagination-simple__input"
                                               value="{{ $items->currentPage() }}"
                                               min="1"
                                               max="{{ $items->lastPage() }}">
                                        <span class="m-pagination-simple__separator">@lang('of')</span>
                                        <span class="m-pagination-simple__total">{{ $items->lastPage() }}</span>
                                    </div>

                                    {{-- Next Button --}}
                                    <button type="button" class="m-pagination-simple__btn m-pagination-simple__next {{ !$items->hasMorePages() ? 'm-pagination-simple__btn--disabled' : '' }}"
                                            {{ !$items->hasMorePages() ? 'disabled' : '' }}>
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
    <!-- catalogItem wrapper end -->

@endsection

@section('script')
    <script>
        (function($) {
            "use strict";

            const baseUrl = '{{ route('front.catalog.category', ['brand_slug' => $brand_slug, 'catalog_slug' => $catalog_slug, 'cat1' => $cat1_slug, 'cat2' => $cat2_slug, 'cat3' => $cat3_slug]) }}';
            const $scrollContainer = $('.category-catalogItems-scroll');
            const $paginationContainer = $('.m-pagination-simple');
            const $totalItems = $('.catalogItem-nav-wrapper h5').first();

            let isLoading = false;
            let currentPage = parseInt($paginationContainer.data('current')) || 1;
            let lastPage = parseInt($paginationContainer.data('last')) || 1;
            let currentSort = $('#sortby').val() || 'date_desc';

            // Build URL with filters
            function buildUrl(page) {
                let params = new URLSearchParams();

                if (page && page > 1) {
                    params.set('page', page);
                }

                if (currentSort && currentSort !== 'date_desc') {
                    params.set('sort', currentSort);
                }

                const viewMode = $('.check_view.active').data('shopview');
                if (viewMode) {
                    params.set('view_check', viewMode);
                }

                const queryString = params.toString();
                return queryString ? baseUrl + '?' + queryString : baseUrl;
            }

            // Load content via AJAX
            function loadContent(page, updateHistory = true) {
                if (isLoading) return;

                page = page || 1;
                isLoading = true;

                $scrollContainer.addClass('is-loading');
                $paginationContainer.find('.m-pagination-simple__btn').prop('disabled', true);

                const url = buildUrl(page);

                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'html',
                    success: function(response) {
                        const $response = $('<div>').html(response);
                        const $ajaxContent = $response.find('#ajax-catalogItems-content');
                        const $paginationData = $response.find('#ajax-pagination-data');

                        if ($ajaxContent.length) {
                            $scrollContainer.html($ajaxContent.html());
                        }

                        if ($paginationData.length) {
                            try {
                                const data = JSON.parse($paginationData.text());
                                currentPage = data.currentPage;
                                lastPage = data.lastPage;
                                $totalItems.html('@lang("Total Items Found:") ' + data.total);
                            } catch(e) {
                                currentPage = page;
                            }
                        } else {
                            currentPage = page;
                        }

                        updatePaginationUI();
                        $scrollContainer.scrollTop(0);

                        if (updateHistory) {
                            history.pushState({page: currentPage, url: url}, '', url);
                        }

                        $('[data-bs-toggle="tooltip"]').tooltip({});
                    },
                    error: function() {
                        window.location.href = url;
                    },
                    complete: function() {
                        isLoading = false;
                        $scrollContainer.removeClass('is-loading');
                        updatePaginationUI();
                    }
                });
            }

            // Update Pagination UI
            function updatePaginationUI() {
                const $input = $paginationContainer.find('.m-pagination-simple__input');
                const $prevBtn = $paginationContainer.find('.m-pagination-simple__prev');
                const $nextBtn = $paginationContainer.find('.m-pagination-simple__next');
                const $total = $paginationContainer.find('.m-pagination-simple__total');

                $input.val(currentPage).attr('max', lastPage);
                $total.text(lastPage);

                if (currentPage <= 1) {
                    $prevBtn.addClass('m-pagination-simple__btn--disabled').prop('disabled', true);
                } else {
                    $prevBtn.removeClass('m-pagination-simple__btn--disabled').prop('disabled', false);
                }

                if (currentPage >= lastPage) {
                    $nextBtn.addClass('m-pagination-simple__btn--disabled').prop('disabled', true);
                } else {
                    $nextBtn.removeClass('m-pagination-simple__btn--disabled').prop('disabled', false);
                }
            }

            // Sort Event
            $("#sortby").on('change', function() {
                currentSort = $(this).val();
                currentPage = 1;
                loadContent(1);
            });

            // Pagination Events
            $paginationContainer.on('click', '.m-pagination-simple__prev', function(e) {
                e.preventDefault();
                if (!$(this).prop('disabled') && !isLoading && currentPage > 1) {
                    loadContent(currentPage - 1);
                }
                return false;
            });

            $paginationContainer.on('click', '.m-pagination-simple__next', function(e) {
                e.preventDefault();
                if (!$(this).prop('disabled') && !isLoading && currentPage < lastPage) {
                    loadContent(currentPage + 1);
                }
                return false;
            });

            $paginationContainer.on('keydown', '.m-pagination-simple__input', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    let page = parseInt($(this).val()) || 1;
                    page = Math.max(1, Math.min(page, lastPage));
                    if (page !== currentPage) {
                        loadContent(page);
                    }
                    $(this).blur();
                    return false;
                }
            });

            $paginationContainer.on('blur', '.m-pagination-simple__input', function() {
                let page = parseInt($(this).val()) || currentPage;
                page = Math.max(1, Math.min(page, lastPage));
                if (page !== currentPage) {
                    loadContent(page);
                } else {
                    $(this).val(currentPage);
                }
            });

            // Browser History
            $(window).on('popstate', function(e) {
                const state = e.originalEvent.state;
                if (state && state.page) {
                    const urlParams = new URLSearchParams(window.location.search);
                    const sortVal = urlParams.get('sort') || 'date_desc';
                    currentSort = sortVal;
                    $('#sortby').val(sortVal);

                    currentPage = state.page;
                    loadContent(state.page, false);
                }
            });

            history.replaceState({page: currentPage, url: window.location.href}, '', window.location.href);

        })(jQuery);
    </script>
@endsection
