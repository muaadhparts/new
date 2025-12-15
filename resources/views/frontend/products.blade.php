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
            color: #333;
            margin-bottom: 6px;
        }
        .category-step-selector .form-select {
            width: 100%;
            padding: 10px 35px 10px 12px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background-color: #fff;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .category-step-selector .form-select:focus {
            border-color: var(--theme-primary);
            box-shadow: 0 0 0 2px rgba(var(--theme-primary-rgb), 0.1);
            outline: none;
        }
        .category-step-selector .form-select:hover {
            border-color: #bbb;
        }
        .category-step-selector .current-selection {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
        }
        .category-step-selector .selection-breadcrumb {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 4px;
        }
        .category-step-selector .selection-breadcrumb .badge {
            font-size: 11px;
            font-weight: 500;
            padding: 4px 8px;
        }
        .category-step-selector .btn-outline-secondary {
            font-size: 13px;
            border-color: #dee2e6;
        }
        .category-step-selector .btn-outline-secondary:hover {
            background-color: #f8f9fa;
            border-color: #adb5bd;
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
    <section class="gs-breadcrumb-section bg-class"
        data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
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
    <div class="gs-blog-wrapper">
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

                                    {{-- Current Selection Display --}}
                                    @if($selectedCat)
                                    <div class="current-selection mt-3 p-2 bg-light rounded">
                                        <small class="text-muted d-block mb-1">@lang('Current Selection'):</small>
                                        <div class="selection-breadcrumb">
                                            <span class="badge bg-primary">{{ $selectedCat->localized_name }}</span>
                                            @if($selectedSubcat)
                                                <i class="fa-solid fa-chevron-{{ app()->getLocale() == 'ar' ? 'left' : 'right' }} mx-1 small"></i>
                                                <span class="badge bg-secondary">{{ $selectedSubcat->localized_name }}</span>
                                            @endif
                                            @if($selectedChildcat)
                                                <i class="fa-solid fa-chevron-{{ app()->getLocale() == 'ar' ? 'left' : 'right' }} mx-1 small"></i>
                                                <span class="badge bg-info">{{ $selectedChildcat->localized_name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    @endif

                                    {{-- Clear Filter Button --}}
                                    @if($currentCatSlug)
                                    <a href="{{ route('front.category') }}" class="btn btn-outline-secondary btn-sm w-100 mt-2">
                                        <i class="fa-solid fa-times me-1"></i> @lang('Clear Selection')
                                    </a>
                                    @endif
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
                                                                    <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243"
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
                                                                    <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243"
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
                                                                    <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243"
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
                            <div class="warranty-type">
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
                                                    <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243"
                                                        stroke-width="1.6666" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                            </label>
                                            <label for="vendor_{{ $vendor->user_id }}">{{ $vendor->shop_name ?? __('Unknown Vendor') }}</label>
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
                            <div class="warranty-type">
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
                                                    <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243"
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

                        <!-- Recent Product-->
                        <div class="single-product-widget">
                            <h5 class="widget-title">@lang('Recent Product')</h5>
                            <div class="gs-recent-post-widget">
                                @foreach ($latest_products as $product)
                                    @php
                                        $recentMerchant = $product->merchantProducts()
                                            ->where('status', 1)
                                            ->whereHas('user', function ($user) {
                                                $user->where('is_vendor', 2);
                                            })
                                            ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
                                            ->orderBy('price')
                                            ->first();

                                        $recentProductUrl = $recentMerchant && $product['slug']
                                            ? route('front.product', ['slug' => $product['slug'], 'vendor_id' => $recentMerchant->user_id, 'merchant_product_id' => $recentMerchant->id])
                                            : ($product['slug'] ? route('front.product.legacy', $product['slug']) : '#');
                                    @endphp
                                    <a href="{{ $recentProductUrl }}">

                                        <div class="gs-single-recent-product-widget">
                                            <div class="img-wrapper">
                                                <img class="thumb"
                                                    src="{{ filter_var($product['photo'] ?? '', FILTER_VALIDATE_URL) ? $product['photo'] : (($product['photo'] ?? null) ? \Illuminate\Support\Facades\Storage::url($product['photo']) : asset('assets/images/noimage.png')) }}"
                                                    alt="product img">
                                            </div>
                                            <div class="content-wrapper">
                                                <h6 class="title">{{ $product->localized_name }}</h6>
                                                <div class="price-wrapper">
                                                    <span
                                                        class="price">{{ PriceHelper::showPrice($product['price']) }}</span>
                                                    <span
                                                        class="price"><del>{{ PriceHelper::showPrice($product['previous_price']) }}</del></span>
                                                </div>
                                                <div class="rating-wrapper">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                        viewBox="0 0 14 14" fill="none">
                                                        <path
                                                            d="M7 0.5L8.5716 5.33688H13.6574L9.5429 8.32624L11.1145 13.1631L7 10.1738L2.8855 13.1631L4.4571 8.32624L0.342604 5.33688H5.4284L7 0.5Z"
                                                            fill="#EEAE0B" />
                                                    </svg>
                                                    <span
                                                        class="rating">{{ number_format($product->ratings_avg_rating ?? 0, 1) }}
                                                        ({{ $product->ratings_count ?? 0 }})
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach

                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-8 col-xl-9 gs-main-blog-wrapper products-column">

                    @php
                        if (request()->input('view_check') == null || request()->input('view_check') == 'grid-view') {
                            $view = 'grid-view';
                        } else {
                            $view = 'list-view';
                        }
                    @endphp

                    <!-- product nav wrapper -->
                    <div class=" product-nav-wrapper">
                        <h5>@lang('Total Products Found:') {{ $prods->count() }}</h5>
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
                            <div class="btn-wrapper nav d-none d-lg-inline-block" role="tablist">
                                <button class="grid-btn check_view {{ $view == 'list-view' ? 'active' : '' }}"
                                    data-shopview="list-view" type="button" data-bs-toggle="tab"
                                    data-bs-target="#layout-list-pane" role="tab" aria-controls="layout-list-pane"
                                    aria-selected="{{ $view == 'list-view' ? 'true' : 'false' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="31" height="24"
                                        viewBox="0 0 31 24" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M1.33331 18.7575H3.90917C4.64356 18.7575 5.24248 19.3564 5.24248 20.0908V22.6666C5.24248 23.401 4.64356 24 3.90917 24H1.33331C0.598918 24 0 23.4011 0 22.6666V20.0908C0 19.3564 0.598918 18.7575 1.33331 18.7575ZM10.7121 0H29.44C30.1744 0 30.7734 0.598986 30.7734 1.33331V3.90917C30.7734 4.64349 30.1744 5.24248 29.44 5.24248C15.6911 5.24248 24.461 5.24248 10.7121 5.24248C9.97775 5.24248 9.37876 4.64356 9.37876 3.90917V1.33331C9.37876 0.598918 9.97775 0 10.7121 0ZM1.33331 0H3.90917C4.64356 0 5.24248 0.598986 5.24248 1.33331V3.90917C5.24248 4.64356 4.64356 5.24248 3.90917 5.24248H1.33331C0.598918 5.24248 0 4.64356 0 3.90917V1.33331C0 0.598918 0.598918 0 1.33331 0ZM10.7121 9.37869H29.44C30.1744 9.37869 30.7734 9.97768 30.7734 10.712V13.2879C30.7734 14.0222 30.1744 14.6212 29.44 14.6212C15.6911 14.6212 24.461 14.6212 10.7121 14.6212C9.97775 14.6212 9.37876 14.0223 9.37876 13.2879V10.712C9.37876 9.97761 9.97775 9.37869 10.7121 9.37869ZM1.33331 9.37869H3.90917C4.64356 9.37869 5.24248 9.97768 5.24248 10.712V13.2879C5.24248 14.0223 4.64356 14.6212 3.90917 14.6212H1.33331C0.598918 14.6212 0 14.0223 0 13.2879V10.712C0 9.97761 0.598918 9.37869 1.33331 9.37869ZM10.7121 18.7575H29.44C30.1744 18.7575 30.7734 19.3564 30.7734 20.0908V22.6666C30.7734 23.4009 30.1744 23.9999 29.44 23.9999C15.6911 23.9999 24.461 23.9999 10.7121 23.9999C9.97775 23.9999 9.37876 23.401 9.37876 22.6666V20.0908C9.37876 19.3564 9.97775 18.7575 10.7121 18.7575Z"
                                            fill="#978D8F" />
                                    </svg>
                                </button>
                                <button class="grid-btn check_view  {{ $view == 'grid-view' ? 'active' : '' }}"
                                    type="button" data-shopview="grid-view" data-bs-toggle="tab"
                                    data-bs-target="#layout-grid-pane" role="tab" aria-controls="layout-grid-pane"
                                    aria-selected="{{ $view == 'grid-view' ? 'true' : 'false' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="24"
                                        viewBox="0 0 25 24" fill="none">
                                        <path
                                            d="M9.5685 0H2.8222C1.69252 0 0.773438 0.919078 0.773438 2.04877V8.79506C0.773438 9.92475 1.69252 10.8438 2.8222 10.8438H9.5685C10.6982 10.8438 11.6173 9.92475 11.6173 8.79506V2.04877C11.6173 0.919078 10.6982 0 9.5685 0Z"
                                            fill="#978D8F" />
                                        <path
                                            d="M22.7248 0H15.9785C14.8488 0 13.9297 0.919078 13.9297 2.04877V8.79506C13.9297 9.92475 14.8488 10.8438 15.9785 10.8438H22.7248C23.8544 10.8438 24.7735 9.92475 24.7735 8.79506V2.04877C24.7735 0.919078 23.8544 0 22.7248 0Z"
                                            fill="#978D8F" />
                                        <path
                                            d="M9.5685 13.1562H2.8222C1.69252 13.1562 0.773438 14.0753 0.773438 15.205V21.9513C0.773438 23.081 1.69252 24.0001 2.8222 24.0001H9.5685C10.6982 24.0001 11.6173 23.081 11.6173 21.9513V15.205C11.6173 14.0753 10.6982 13.1562 9.5685 13.1562Z"
                                            fill="#978D8F" />
                                        <path
                                            d="M22.7248 13.1562H15.9785C14.8488 13.1562 13.9297 14.0753 13.9297 15.205V21.9513C13.9297 23.081 14.8488 24.0001 15.9785 24.0001H22.7248C23.8544 24.0001 24.7735 23.081 24.7735 21.9513V15.205C24.7735 14.0753 23.8544 13.1562 22.7248 13.1562Z"
                                            fill="#978D8F" />
                                    </svg>
                                </button>
                            </div>
                            <!-- list and grid view tab btns  end -->
                        </div>
                    </div>



                    @if ($prods->count() == 0)
                        <!-- product nav wrapper for no data found -->
                        <div class="product-nav-wrapper d-flex justify-content-center ">
                            <h5>@lang('No Product Found')</h5>
                        </div>
                    @else
                        <!-- main content -->
                        <div class="tab-content" id="myTabContent">
                            <!-- product list view start  -->
                            <div class="tab-pane fade {{ $view == 'list-view' ? 'show active' : '' }}"
                                id="layout-list-pane" role="tabpanel" tabindex="0">
                                <div class="row gy-4 gy-lg-5 mt-20 ">
                                    @foreach ($prods as $product)
                                        @include('includes.frontend.list_view_product')
                                    @endforeach
                                </div>
                            </div>

                            <div class="tab-pane fade {{ $view == 'grid-view' ? 'show active' : '' }}  "
                                id="layout-grid-pane" role="tabpanel" tabindex="0">
                                <div class="row gy-4 gy-lg-5 mt-20">
                                    @foreach ($prods as $product)
                                        @include('includes.frontend.home_product', [
                                            'class' => 'col-sm-6 col-md-6 col-xl-4',
                                        ])
                                    @endforeach
                                </div>
                            </div>
                            <!-- product grid view end  -->
                        </div>
                        {{ $prods->links('includes.frontend.pagination') }}
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
        $(document).on("click", "#price_filter", function() {
            let amountString = $("#amount").val();

            amountString = amountString.replace(/\$/g, '');

            // Split the string into two amounts
            let amounts = amountString.split('-');

            // Trim whitespace from each amount
            let amount1 = amounts[0].trim();
            let amount2 = amounts[1].trim();


            $("#update_min_price").val(amount1);
            $("#update_max_price").val(amount2);

            filter();

        });



        // when dynamic attribute changes
        $(".attribute-input, #sortby, #pageby").on('change', function() {
            $(".ajax-loader").show();
            filter();
        });

        function filter() {
            let filterlink =
                '{{ route('front.category', [Request::route('category'), Request::route('subcategory'), Request::route('childcategory')]) }}';

            let params = new URLSearchParams();


            $(".attribute-input").each(function() {
                if ($(this).is(':checked')) {
                    params.append($(this).attr('name'), $(this).val());
                }
            });

            if ($("#sortby").val() != '') {
                params.append($("#sortby").attr('name'), $("#sortby").val());
            }

            if ($("#start_value").val() != '') {
                params.append($("#start_value").attr('name'), $("#start_value").val());
            }

            let check_view = $('.check_view.active').data('shopview');

            if (check_view) {
                params.append('view_check', check_view);
            }

            if ($("#update_min_price").val() != '') {
                params.append('min', $("#update_min_price").val());
            }
            if ($("#update_max_price").val() != '') {
                params.append('max', $("#update_max_price").val());
            }

            filterlink += '?' + params.toString();

            console.log(filterlink);
            location.href = filterlink;
        }

        // append parameters to pagination links
        function addToPagination() {
            $('ul.pagination li a').each(function() {
                let url = $(this).attr('href');
                let queryString = '?' + url.split('?')[1]; // "?page=1234...."
                let urlParams = new URLSearchParams(queryString);
                let page = urlParams.get('page'); // value of 'page' parameter

                let fullUrl =
                    '{{ route('front.category', [Request::route('category'), Request::route('subcategory'), Request::route('childcategory')]) }}';
                let params = new URLSearchParams();

                $(".attribute-input").each(function() {
                    if ($(this).is(':checked')) {
                        params.append($(this).attr('name'), $(this).val());
                    }
                });

                if ($("#sortby").val() != '') {
                    params.append('sort', $("#sortby").val());
                }


                if ($("#pageby").val() != '') {
                    params.append('pageby', $("#pageby").val());
                }

                params.append('page', page);

                $(this).attr('href', fullUrl + '?' + params.toString());
            });
        }
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
            const baseUrl = '{{ route("front.category") }}';

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
                    // Go to base category page
                    window.location.href = baseUrl;
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

                // Navigate to category page
                window.location.href = baseUrl + '/' + selectedSlug;
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
                    // Go back to category page
                    window.location.href = baseUrl + '/' + catSlug;
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

                // Navigate to subcategory page
                window.location.href = baseUrl + '/' + catSlug + '/' + selectedSlug;
            });

            // Child Category Change
            $('#childcategory-select').on('change', function() {
                const catSlug = $('#main-category-select').val();
                const subcatSlug = $('#subcategory-select').val();
                const selectedSlug = $(this).val();

                if (!selectedSlug) {
                    // Go back to subcategory page
                    window.location.href = baseUrl + '/' + catSlug + '/' + subcatSlug;
                    return;
                }

                // Navigate to child category page
                window.location.href = baseUrl + '/' + catSlug + '/' + subcatSlug + '/' + selectedSlug;
            });

        })(jQuery);
    </script>
@endsection
