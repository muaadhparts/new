@extends('layouts.front')

@section('content')
    {{-- Multi-Step Category Selector Styles --}}
    <style>
        .category-step-selector .step-selector-item {
            position: relative;
        }
        .category-step-selector .step-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--theme-text-primary, #1a1510);
            margin-bottom: 6px;
        }
        .category-step-selector .form-select {
            width: 100%;
            padding: 10px 35px 10px 12px;
            font-size: 14px;
            border: 1px solid var(--theme-border, #d4c4a8);
            border-radius: 6px;
            background-color: var(--color-surface, #fff);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .category-step-selector .form-select:focus {
            border-color: var(--theme-primary);
            box-shadow: 0 0 0 2px rgba(var(--theme-primary-rgb), 0.1);
            outline: none;
        }
        .category-step-selector .form-select:hover {
            border-color: var(--theme-border-dark, #b8a88c);
        }
        /* RTL Support */
        [dir="rtl"] .category-step-selector .form-select {
            padding: 10px 12px 10px 35px;
        }
        /* Mobile Responsive */
        @media (max-width: 991px) {
            .category-step-selector .form-select {
                padding: 12px 35px 12px 12px;
                font-size: 16px;
            }
            [dir="rtl"] .category-step-selector .form-select {
                padding: 12px 12px 12px 35px;
            }
            /* Make sidebar appear ABOVE products on mobile */
            .gs-blog-wrapper .row.products-layout {
                display: flex;
                flex-direction: column;
            }
            .gs-blog-wrapper .row.products-layout .sidebar-column {
                order: 1;
                margin-top: 0;
                margin-bottom: 20px;
            }
            .gs-blog-wrapper .row.products-layout .products-column {
                order: 2;
            }
        }
    </style>
    <section class="gs-breadcrumb-section">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">@lang('Product')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="javascript:;">@lang('Product')</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <!-- breadcrumb end -->

    <!-- product wrapper start -->
    <div class="muaadh-section muaadh-section-gray">
        <div class="container">
            <div class="row products-layout">
                <div class="col-12 col-lg-4 col-xl-3 sidebar-column">
                    <div class="gs-product-sidebar-wrapper">
                        <!-- product categories wrapper - Multi-Step Selector -->
                        <div class="single-product-widget">
                            <h5 class="widget-title">@lang('Product categories')</h5>
                            <div class="product-cat-widget">

                                {{-- Multi-Step Category Selector --}}
                                <div class="category-step-selector">
                                    @php
                                        $currentCatSlug = Request::segment(2);
                                        $currentSubcatSlug = Request::segment(3);
                                        $currentChildcatSlug = Request::segment(4);

                                        $selectedCat = $categories->firstWhere('slug', $currentCatSlug);
                                        $selectedSubcat = $selectedCat ? $selectedCat->subs->firstWhere('slug', $currentSubcatSlug) : null;
                                        $selectedChildcat = $selectedSubcat ? $selectedSubcat->childs->firstWhere('slug', $currentChildcatSlug) : null;
                                    @endphp

                                    {{-- Step 1: Main Category --}}
                                    <div class="step-selector-item mb-3">
                                        <label class="step-label">@lang('Category')</label>
                                        <select class="form-select category-select" id="main-category-select">
                                            <option value="">-- @lang('Select Category') --</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->slug }}"
                                                    data-has-subs="{{ $category->subs->count() > 0 ? '1' : '0' }}"
                                                    {{ $currentCatSlug === $category->slug ? 'selected' : '' }}>
                                                    {{ $category->localized_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Step 2: Subcategory (shown when category selected and has subs) --}}
                                    <div class="step-selector-item mb-3 {{ $selectedCat && $selectedCat->subs->count() > 0 ? '' : 'd-none' }}" id="subcategory-step">
                                        <label class="step-label">@lang('Model')</label>
                                        <select class="form-select category-select" id="subcategory-select">
                                            <option value="">-- @lang('Select Model') --</option>
                                            @if($selectedCat)
                                                @foreach ($selectedCat->subs as $subcategory)
                                                    <option value="{{ $subcategory->slug }}"
                                                        data-has-childs="{{ $subcategory->childs->count() > 0 ? '1' : '0' }}"
                                                        {{ $currentSubcatSlug === $subcategory->slug ? 'selected' : '' }}>
                                                        {{ $subcategory->localized_name }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    {{-- Step 3: Child Category (shown when subcategory selected and has childs) --}}
                                    <div class="step-selector-item mb-3 {{ $selectedSubcat && $selectedSubcat->childs->count() > 0 ? '' : 'd-none' }}" id="childcategory-step">
                                        <label class="step-label">@lang('Part Type')</label>
                                        <select class="form-select category-select" id="childcategory-select">
                                            <option value="">-- @lang('Select Part Type') --</option>
                                            @if($selectedSubcat)
                                                @foreach ($selectedSubcat->childs as $child)
                                                    <option value="{{ $child->slug }}"
                                                        {{ $currentChildcatSlug === $child->slug ? 'selected' : '' }}>
                                                        {{ $child->localized_name }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                </div>

                                {{-- Hidden data for JavaScript --}}
                                @php
                                    $categoriesJson = $categories->map(function($cat) {
                                        return [
                                            'slug' => $cat->slug,
                                            'name' => $cat->localized_name,
                                            'subs' => $cat->subs->map(function($sub) use ($cat) {
                                                return [
                                                    'slug' => $sub->slug,
                                                    'name' => $sub->localized_name,
                                                    'cat_slug' => $cat->slug,
                                                    'childs' => $sub->childs->map(function($child) use ($cat, $sub) {
                                                        return [
                                                            'slug' => $child->slug,
                                                            'name' => $child->localized_name,
                                                            'cat_slug' => $cat->slug,
                                                            'sub_slug' => $sub->slug,
                                                        ];
                                                    })->values()
                                                ];
                                            })->values()
                                        ];
                                    })->values();
                                @endphp
                                <script type="application/json" id="categories-data">{!! json_encode($categoriesJson) !!}</script>

                            </div>
                        </div>

                        {{-- Price Range - Hidden --}}
                        <div class="d-none">
                            <input id="start_value" type="number" name="min"
                                value="{{ isset($_GET['min']) ? $_GET['min'] : $gs->min_price }}">
                            <input id="end_value" type="number"
                                value="{{ isset($_GET['max']) ? $_GET['max'] : $gs->max_price }}">
                            <input id="max_value" type="number" name="max" value="{{ $gs->max_price }}">
                        </div>



                        @if (
                            (!empty($cat) && !empty(json_decode($cat->attributes, true))) ||
                                (!empty($subcat) && !empty(json_decode($subcat->attributes, true))) ||
                                (!empty($childcat) && !empty(json_decode($childcat->attributes, true))))
                            <!-- Warranty Type-->
                            @if (!empty($cat) && !empty(json_decode($cat->attributes, true)))
                                @foreach ($cat->attributes as $key => $attr)
                                    <div class="single-product-widget">
                                        <h5 class="widget-title">{{ $attr->name }}</h5>
                                        <div class="warranty-type">
                                            @if (!empty($attr->attribute_options))
                                                <ul>
                                                    @foreach ($attr->attribute_options as $key => $option)
                                                        <li class="gs-checkbox-wrapper">
                                                            <input type="checkbox" class="attribute-input"
                                                                name="{{ $attr->input_name }}[]"
                                                                {{ isset($_GET[$attr->input_name]) && in_array($option->name, $_GET[$attr->input_name]) ? 'checked' : '' }}
                                                                id="{{ $attr->input_name }}{{ $option->id }}"
                                                                value="{{ $option->name }}">
                                                            <label class="icon-label"
                                                                for="{{ $attr->input_name }}{{ $option->id }}">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="12"
                                                                    height="12" viewBox="0 0 12 12" fill="none">
                                                                    <path d="M10 3L4.5 8.5L2 6" stroke="currentColor"
                                                                        stroke-width="1.6666" stroke-linecap="round"
                                                                        stroke-linejoin="round" />
                                                                </svg>
                                                            </label>
                                                            <label
                                                                for="{{ $attr->input_name }}{{ $option->id }}">{{ $option->name }}</label>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endif


                            @if (!empty($subcat) && !empty(json_decode($subcat->attributes, true)))
                                @foreach ($subcat->attributes as $key => $attr)
                                    <div class="single-product-widget">
                                        <h5 class="widget-title">{{ $attr->name }}</h5>
                                        <div class="warranty-type">
                                            @if (!empty($attr->attribute_options))
                                                <ul>
                                                    @foreach ($attr->attribute_options as $key => $option)
                                                        <li class="gs-checkbox-wrapper">
                                                            <input type="checkbox" name="{{ $attr->input_name }}[]"
                                                                id="{{ $attr->input_name }}{{ $option->id }}"
                                                                value="{{ $option->name }}">
                                                            <label class="icon-label"
                                                                for="{{ $attr->input_name }}{{ $option->id }}">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="12"
                                                                    height="12" viewBox="0 0 12 12" fill="none">
                                                                    <path d="M10 3L4.5 8.5L2 6" stroke="currentColor"
                                                                        stroke-width="1.6666" stroke-linecap="round"
                                                                        stroke-linejoin="round" />
                                                                </svg>
                                                            </label>
                                                            <label
                                                                for="{{ $attr->input_name }}{{ $option->id }}">{{ $option->name }}</label>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endif


                            @if (!empty($childcat) && !empty(json_decode($childcat->attributes, true)))
                                @foreach ($childcat->attributes as $key => $attr)
                                    <div class="single-product-widget">
                                        <h5 class="widget-title">{{ $attr->name }}</h5>
                                        <div class="warranty-type">
                                            @if (!empty($attr->attribute_options))
                                                <ul>
                                                    @foreach ($attr->attribute_options as $key => $option)
                                                        <li class="gs-checkbox-wrapper">
                                                            <input type="checkbox" name="{{ $attr->input_name }}[]"
                                                                id="{{ $attr->input_name }}{{ $option->id }}"
                                                                value="{{ $option->name }}">
                                                            <label class="icon-label"
                                                                for="{{ $attr->input_name }}{{ $option->id }}">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="12"
                                                                    height="12" viewBox="0 0 12 12" fill="none">
                                                                    <path d="M10 3L4.5 8.5L2 6" stroke="currentColor"
                                                                        stroke-width="1.6666" stroke-linecap="round"
                                                                        stroke-linejoin="round" />
                                                                </svg>
                                                            </label>
                                                            <label
                                                                for="{{ $attr->input_name }}{{ $option->id }}">{{ $option->name }}</label>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        @endif

                        {{-- <a href="{{ route('front.category') }}" class="template-btn dark-btn">Clear Filter</a> --}}

                        <!-- Vendor Filter -->
                        @if(isset($vendors) && $vendors->count() > 0)
                        <div class="single-product-widget">
                            <h5 class="widget-title">@lang('Vendor')</h5>
                            <div class="warranty-type m-filter-scroll-box">
                                <ul>
                                    @foreach ($vendors as $vendor)
                                        <li class="gs-checkbox-wrapper">
                                            <input type="checkbox" class="attribute-input vendor-filter"
                                                name="vendor[]"
                                                {{ isset($_GET['vendor']) && in_array($vendor->user_id, (array)$_GET['vendor']) ? 'checked' : '' }}
                                                id="vendor_{{ $vendor->user_id }}"
                                                value="{{ $vendor->user_id }}">
                                            <label class="icon-label"
                                                for="vendor_{{ $vendor->user_id }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12"
                                                    height="12" viewBox="0 0 12 12" fill="none">
                                                    <path d="M10 3L4.5 8.5L2 6" stroke="currentColor"
                                                        stroke-width="1.6666" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                            </label>
                                            <label for="vendor_{{ $vendor->user_id }}">{{ getLocalizedShopName($vendor) }}</label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        @endif

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
                        </div>
                        @endif

                    </div>
                </div>
                <div class="col-12 col-lg-8 col-xl-9 gs-main-blog-wrapper products-column">

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
                        <h5>@lang('Total Products Found:') {{ $prods->total() }}</h5>
                        <div class="filter-wrapper">
                            <div class="sort-wrapper">
                                <h5>@lang('Sort by:')</h5>

                                @php $currentSort = request('sort', 'date_desc'); @endphp
                                <select class="nice-select" id="sortby" name="sort">
                                    <option value="date_desc" {{ $currentSort === 'date_desc' ? 'selected' : '' }}>{{ __('Latest Product') }}</option>
                                    <option value="date_asc" {{ $currentSort === 'date_asc' ? 'selected' : '' }}>{{ __('Oldest Product') }}</option>
                                    <option value="price_asc" {{ $currentSort === 'price_asc' ? 'selected' : '' }}>{{ __('Lowest Price') }}</option>
                                    <option value="price_desc" {{ $currentSort === 'price_desc' ? 'selected' : '' }}>{{ __('Highest Price') }}</option>
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



                    @if ($prods->total() == 0)
                        {{-- Zero Results Box with Filter Summary --}}
                        <div class="category-products-box">
                            <div class="category-products-scroll" id="products-container">
                                <div class="m-no-results-box">
                                    <div class="m-no-results-box__icon">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <h4 class="m-no-results-box__title">@lang('No matching products')</h4>
                                    <p class="m-no-results-box__subtitle">@lang('Try adjusting your filters to find what you are looking for.')</p>

                                    @if(isset($filterSummary) && $filterSummary['hasFilters'])
                                    <div class="m-no-results-box__filters">
                                        <h6 class="m-no-results-box__filters-title">@lang('Applied Filters:')</h6>
                                        <ul class="m-no-results-box__filters-list">
                                            @if($filterSummary['category'])
                                            <li>
                                                <span class="m-no-results-box__filter-label">@lang('Category'):</span>
                                                <span class="m-no-results-box__filter-value">
                                                    {{ $filterSummary['category'] }}
                                                    @if($filterSummary['subcategory'])
                                                        <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} mx-1"></i>
                                                        {{ $filterSummary['subcategory'] }}
                                                    @endif
                                                    @if($filterSummary['childcategory'])
                                                        <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} mx-1"></i>
                                                        {{ $filterSummary['childcategory'] }}
                                                    @endif
                                                </span>
                                            </li>
                                            @endif

                                            @if(!empty($filterSummary['vendors']))
                                            <li>
                                                <span class="m-no-results-box__filter-label">@lang('Vendor'):</span>
                                                <span class="m-no-results-box__filter-value">{{ implode(', ', $filterSummary['vendors']) }}</span>
                                            </li>
                                            @endif

                                            @if(!empty($filterSummary['brandQualities']))
                                            <li>
                                                <span class="m-no-results-box__filter-label">@lang('Brand Quality'):</span>
                                                <span class="m-no-results-box__filter-value">{{ implode(', ', $filterSummary['brandQualities']) }}</span>
                                            </li>
                                            @endif
                                        </ul>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            {{-- Empty pagination for consistency --}}
                            <div class="category-products-pagination">
                                <div class="m-pagination-simple" data-current="1" data-last="1" data-total="0">
                                    <button type="button" class="m-pagination-simple__btn m-pagination-simple__prev m-pagination-simple__btn--disabled" disabled>
                                        <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>
                                    </button>
                                    <div class="m-pagination-simple__input-group">
                                        <input type="number" class="m-pagination-simple__input" value="1" min="1" max="1">
                                        <span class="m-pagination-simple__separator">@lang('of')</span>
                                        <span class="m-pagination-simple__total">1</span>
                                    </div>
                                    <button type="button" class="m-pagination-simple__btn m-pagination-simple__next m-pagination-simple__btn--disabled" disabled>
                                        <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- main content inside scrollable box -->
                        <div class="category-products-box">
                            <div class="category-products-scroll">
                                <div class="tab-content" id="myTabContent">
                                    <!-- product list view start  -->
                                    <div class="tab-pane fade {{ $view == 'list-view' ? 'show active' : '' }}"
                                        id="layout-list-pane" role="tabpanel" tabindex="0">
                                        <div class="row gy-4">
                                            @foreach ($cards as $card)
                                                @include('includes.frontend.home_catalog_item', ['card' => $card, 'layout' => 'list'])
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="tab-pane fade {{ $view == 'grid-view' ? 'show active' : '' }}"
                                        id="layout-grid-pane" role="tabpanel" tabindex="0">
                                        <div class="row gy-4">
                                            @foreach ($cards as $card)
                                                @include('includes.frontend.home_catalog_item', ['card' => $card, 'layout' => 'grid', 'class' => 'col-6 col-md-4 col-lg-3'])
                                            @endforeach
                                        </div>
                                    </div>
                                    <!-- product grid view end  -->
                                </div>
                            </div>
                            <!-- Pagination outside scroll area -->
                            <div class="category-products-pagination">
                                <div class="m-pagination-simple"
                                     data-current="{{ $prods->currentPage() }}"
                                     data-last="{{ $prods->lastPage() }}"
                                     data-total="{{ $prods->total() }}">

                                    {{-- Previous Button --}}
                                    <button type="button" class="m-pagination-simple__btn m-pagination-simple__prev {{ $prods->onFirstPage() ? 'm-pagination-simple__btn--disabled' : '' }}"
                                            {{ $prods->onFirstPage() ? 'disabled' : '' }}>
                                        <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>
                                    </button>

                                    {{-- Page Input --}}
                                    <div class="m-pagination-simple__input-group">
                                        <input type="number"
                                               class="m-pagination-simple__input"
                                               value="{{ $prods->currentPage() }}"
                                               min="1"
                                               max="{{ $prods->lastPage() }}">
                                        <span class="m-pagination-simple__separator">@lang('of')</span>
                                        <span class="m-pagination-simple__total">{{ $prods->lastPage() }}</span>
                                    </div>

                                    {{-- Next Button --}}
                                    <button type="button" class="m-pagination-simple__btn m-pagination-simple__next {{ !$prods->hasMorePages() ? 'm-pagination-simple__btn--disabled' : '' }}"
                                            {{ !$prods->hasMorePages() ? 'disabled' : '' }}>
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
    <!-- product wrapper end -->

    <input type="hidden" id="update_min_price" value="">
    <input type="hidden" id="update_max_price" value="">

@endsection


@section('script')
    <script>
        // Global sort state (accessible from Category Selector script)
        var categoryPageSort = '{{ request('sort', 'date_desc') }}';

        (function($) {
            "use strict";

            // ========================================
            // Category Products AJAX System
            // ========================================
            const baseUrl = '{{ route('front.category', [Request::route('category'), Request::route('subcategory'), Request::route('childcategory')]) }}';
            const $scrollContainer = $('.category-products-scroll');
            const $productsContainer = $('.category-products-scroll');
            const $paginationContainer = $('.m-pagination-simple');
            const $totalProducts = $('.product-nav-wrapper h5').first();

            let isLoading = false;
            let currentPage = parseInt($paginationContainer.data('current')) || 1;
            let lastPage = parseInt($paginationContainer.data('last')) || 1;

            // Persistent sort state - initialized from current value or URL
            // Update global variable too for cross-script access
            categoryPageSort = $('#sortby').val() || categoryPageSort;

            // ========================================
            // Build URL with all current filters
            // ========================================
            function buildUrl(page) {
                let params = new URLSearchParams();

                // Page
                if (page && page > 1) {
                    params.set('page', page);
                }

                // All filter checkboxes (Brand Quality, Vendor, etc.)
                $(".attribute-input:checked").each(function() {
                    params.append($(this).attr('name'), $(this).val());
                });

                // Sort - always use persistent global sort state
                if (categoryPageSort && categoryPageSort !== '') {
                    params.set('sort', categoryPageSort);
                }

                // Price filter
                const minPrice = $("#update_min_price").val();
                const maxPrice = $("#update_max_price").val();
                if (minPrice && minPrice !== '') {
                    params.set('min', minPrice);
                }
                if (maxPrice && maxPrice !== '') {
                    params.set('max', maxPrice);
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
                        const $ajaxContent = $response.find('#ajax-products-content');
                        const $paginationData = $response.find('#ajax-pagination-data');

                        // Update products content (handles both products and no-results box)
                        if ($ajaxContent.length) {
                            // Get the inner content from AJAX response
                            const innerContent = $ajaxContent.html();
                            // Replace the entire scroll container content
                            $scrollContainer.html(innerContent);
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

                        // Reinitialize tooltips
                        $('[data-bs-toggle="tooltip"]').tooltip({});
                        $('[rel-toggle="tooltip"]').tooltip();
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
            // Filter Events (Brand Quality, Vendor)
            // ========================================
            $(".attribute-input").on('change', function() {
                // Reset to page 1 when filter changes
                currentPage = 1;
                loadContent(1);
            });

            // ========================================
            // Sort Event - Update persistent state
            // ========================================
            $("#sortby").on('change', function() {
                // Update global persistent sort state
                categoryPageSort = $(this).val();
                // Reset to page 1 when sort changes
                currentPage = 1;
                loadContent(1);
            });

            // ========================================
            // Price Filter
            // ========================================
            $(document).on("click", "#price_filter", function() {
                let amountString = $("#amount").val();
                amountString = amountString.replace(/\$/g, '');

                let amounts = amountString.split('-');
                let amount1 = amounts[0].trim();
                let amount2 = amounts[1].trim();

                $("#update_min_price").val(amount1);
                $("#update_max_price").val(amount2);

                // Reset to page 1 and load
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

                    // Update sort dropdown and persistent global state
                    const sortVal = urlParams.get('sort') || 'date_desc';
                    categoryPageSort = sortVal; // Update global persistent state
                    $('#sortby').val(sortVal);

                    // Update filter checkboxes
                    $('.attribute-input').prop('checked', false);
                    urlParams.forEach(function(value, key) {
                        if (key.endsWith('[]')) {
                            $('input[name="' + key + '"][value="' + value + '"]').prop('checked', true);
                        }
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

    <script type="text/javascript">
        (function($) {
            "use strict";
            $(function() {
                const start_value = $("#start_value").val();
                const end_value = $("#end_value").val();
                const max_value = $("#max_value").val();

                $("#slider-range").slider({
                    range: true,
                    min: 0,
                    max: max_value,
                    values: [start_value, end_value],
                    step: 10,
                    slide: function(event, ui) {
                        $("#amount").val("$" + ui.values[0] + " - $" + ui.values[1]);
                    },
                });
                $("#amount").val(
                    "$" +
                    $("#slider-range").slider("values", 0) +
                    " - $" +
                    $("#slider-range").slider("values", 5000)
                );
            });

        })(jQuery);
    </script>

    {{-- Multi-Step Category Selector JavaScript --}}
    <script>
        (function($) {
            "use strict";

            // Load categories data from JSON
            const categoriesData = JSON.parse($('#categories-data').text() || '[]');
            const categoryBaseUrl = '{{ route("front.category") }}';

            // Reference to AJAX system from main script
            const $scrollContainer = $('.category-products-scroll');
            const $productsContainer = $('.category-products-scroll');
            const $paginationContainer = $('.m-pagination-simple');
            const $totalProducts = $('.product-nav-wrapper h5').first();

            // Build URL with current filters preserved
            function buildCategoryUrl(basePath) {
                let params = new URLSearchParams();

                // Preserve Sort (from global categoryPageSort)
                if (categoryPageSort && categoryPageSort !== '') {
                    params.set('sort', categoryPageSort);
                }

                // Preserve Vendor filters
                $(".vendor-filter:checked").each(function() {
                    params.append('vendor[]', $(this).val());
                });

                // Preserve Brand Quality filters
                $(".brand-quality-filter:checked").each(function() {
                    params.append('brand_quality[]', $(this).val());
                });

                // Preserve other attribute filters
                $(".attribute-input:checked").each(function() {
                    const name = $(this).attr('name');
                    // Skip vendor and brand_quality as they're handled above
                    if (name !== 'vendor[]' && name !== 'brand_quality[]') {
                        params.append(name, $(this).val());
                    }
                });

                // Preserve Price filter
                const minPrice = $("#update_min_price").val();
                const maxPrice = $("#update_max_price").val();
                if (minPrice && minPrice !== '') {
                    params.set('min', minPrice);
                }
                if (maxPrice && maxPrice !== '') {
                    params.set('max', maxPrice);
                }

                // Preserve View mode
                const viewMode = $('.check_view.active').data('shopview');
                if (viewMode) {
                    params.set('view_check', viewMode);
                }

                const queryString = params.toString();
                return queryString ? basePath + '?' + queryString : basePath;
            }

            // Load category content via AJAX
            function loadCategoryContent(basePath) {
                const url = buildCategoryUrl(basePath);
                $scrollContainer.addClass('is-loading');

                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'html',
                    success: function(response) {
                        const $response = $('<div>').html(response);
                        const $ajaxContent = $response.find('#ajax-products-content');
                        const $paginationData = $response.find('#ajax-pagination-data');
                        const $newPagination = $response.find('.m-pagination-simple');

                        // Update products content (handles both products and no-results box)
                        if ($ajaxContent.length) {
                            $scrollContainer.html($ajaxContent.html());
                        }

                        // Update pagination
                        if ($newPagination.length && $paginationContainer.length) {
                            $paginationContainer.replaceWith($newPagination);
                        }

                        // Update total products count
                        if ($paginationData.length) {
                            try {
                                const data = JSON.parse($paginationData.text());
                                $totalProducts.html('@lang("Total Products Found:") ' + data.total);
                            } catch(e) {}
                        }

                        // Scroll to top
                        $scrollContainer.scrollTop(0);

                        // Update URL
                        history.pushState({categoryUrl: url}, '', url);

                        // Reinitialize tooltips
                        $('[data-bs-toggle="tooltip"]').tooltip({});
                    },
                    error: function() {
                        window.location.href = url;
                    },
                    complete: function() {
                        $scrollContainer.removeClass('is-loading');
                    }
                });
            }

            // Main Category Change
            $('#main-category-select').on('change', function() {
                const selectedSlug = $(this).val();
                const $subcatStep = $('#subcategory-step');
                const $childcatStep = $('#childcategory-step');
                const $subcatSelect = $('#subcategory-select');
                const $childcatSelect = $('#childcategory-select');

                // Reset and hide subsequent steps
                $subcatSelect.html('<option value="">-- @lang("Select Model") --</option>');
                $childcatSelect.html('<option value="">-- @lang("Select Part Type") --</option>');
                $subcatStep.addClass('d-none');
                $childcatStep.addClass('d-none');

                if (!selectedSlug) {
                    // Load base category page via AJAX
                    loadCategoryContent(categoryBaseUrl);
                    return;
                }

                // Find selected category
                const selectedCat = categoriesData.find(cat => cat.slug === selectedSlug);

                if (selectedCat && selectedCat.subs && selectedCat.subs.length > 0) {
                    // Populate subcategories
                    selectedCat.subs.forEach(sub => {
                        $subcatSelect.append(
                            `<option value="${sub.slug}" data-has-childs="${sub.childs && sub.childs.length > 0 ? '1' : '0'}">${sub.name}</option>`
                        );
                    });
                    $subcatStep.removeClass('d-none');
                }

                // Load category page via AJAX
                loadCategoryContent(categoryBaseUrl + '/' + selectedSlug);
            });

            // Subcategory Change
            $('#subcategory-select').on('change', function() {
                const catSlug = $('#main-category-select').val();
                const selectedSlug = $(this).val();
                const $childcatStep = $('#childcategory-step');
                const $childcatSelect = $('#childcategory-select');

                // Reset and hide child step
                $childcatSelect.html('<option value="">-- @lang("Select Part Type") --</option>');
                $childcatStep.addClass('d-none');

                if (!selectedSlug) {
                    // Load category page via AJAX
                    loadCategoryContent(categoryBaseUrl + '/' + catSlug);
                    return;
                }

                // Find selected category and subcategory
                const selectedCat = categoriesData.find(cat => cat.slug === catSlug);
                const selectedSub = selectedCat ? selectedCat.subs.find(sub => sub.slug === selectedSlug) : null;

                if (selectedSub && selectedSub.childs && selectedSub.childs.length > 0) {
                    // Populate child categories
                    selectedSub.childs.forEach(child => {
                        $childcatSelect.append(
                            `<option value="${child.slug}">${child.name}</option>`
                        );
                    });
                    $childcatStep.removeClass('d-none');
                }

                // Load subcategory page via AJAX
                loadCategoryContent(categoryBaseUrl + '/' + catSlug + '/' + selectedSlug);
            });

            // Child Category Change
            $('#childcategory-select').on('change', function() {
                const catSlug = $('#main-category-select').val();
                const subcatSlug = $('#subcategory-select').val();
                const selectedSlug = $(this).val();

                if (!selectedSlug) {
                    // Load subcategory page via AJAX
                    loadCategoryContent(categoryBaseUrl + '/' + catSlug + '/' + subcatSlug);
                    return;
                }

                // Load child category page via AJAX
                loadCategoryContent(categoryBaseUrl + '/' + catSlug + '/' + subcatSlug + '/' + selectedSlug);
            });

        })(jQuery);
    </script>
@endsection
