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
            /* Make sidebar appear ABOVE catalogItems on mobile */
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
                    {{-- Dynamic Title based on deepest selection --}}
                    <h2 class="breadcrumb-name" id="breadcrumb-title">
                        @if(!empty($childcat))
                            {{ app()->getLocale() == 'ar' ? ($childcat->label_ar ?: $childcat->label_en) : $childcat->label_en }}
                        @elseif(!empty($subcat))
                            {{ app()->getLocale() == 'ar' ? ($subcat->name_ar ?: $subcat->name) : $subcat->name }}
                        @elseif(!empty($cat))
                            {{ app()->getLocale() == 'ar' ? ($cat->name_ar ?: $cat->name) : $cat->name }}
                        @else
                            @lang('CatalogItem')
                        @endif
                    </h2>

                    {{-- Dynamic Breadcrumb --}}
                    <ul class="bread-menu" id="breadcrumb-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="{{ route('front.catalog') }}">@lang('CatalogItem')</a></li>

                        {{-- Brand Level --}}
                        @if(!empty($cat))
                            <li>
                                <a href="{{ route('front.catalog', ['brand' => $cat->slug]) }}">
                                    {{ app()->getLocale() == 'ar' ? ($cat->name_ar ?: $cat->name) : $cat->name }}
                                </a>
                            </li>
                        @endif

                        {{-- Catalog/Model Level --}}
                        @if(!empty($subcat))
                            <li>
                                <a href="{{ route('front.catalog', ['brand' => $cat->slug ?? '', 'catalog' => $subcat->slug]) }}">
                                    {{ app()->getLocale() == 'ar' ? ($subcat->name_ar ?: $subcat->name) : $subcat->name }}
                                </a>
                            </li>
                        @endif

                        {{-- NewCategory Level 1 --}}
                        @if(!empty($childcat))
                            <li>
                                <a href="{{ route('front.catalog', ['brand' => $cat->slug ?? '', 'catalog' => $subcat->slug ?? '', 'cat1' => $childcat->slug]) }}">
                                    {{ app()->getLocale() == 'ar' ? ($childcat->label_ar ?: $childcat->label_en) : $childcat->label_en }}
                                </a>
                            </li>
                        @endif

                        {{-- NewCategory Level 2 --}}
                        @if(!empty($cat2))
                            <li>
                                <a href="{{ route('front.catalog', ['brand' => $cat->slug ?? '', 'catalog' => $subcat->slug ?? '', 'cat1' => $childcat->slug ?? '', 'cat2' => $cat2->slug]) }}">
                                    {{ app()->getLocale() == 'ar' ? ($cat2->label_ar ?: $cat2->label_en) : $cat2->label_en }}
                                </a>
                            </li>
                        @endif

                        {{-- NewCategory Level 3 --}}
                        @if(!empty($cat3))
                            <li>
                                <a href="javascript:;">
                                    {{ app()->getLocale() == 'ar' ? ($cat3->label_ar ?: $cat3->label_en) : $cat3->label_en }}
                                </a>
                            </li>
                        @endif
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
                        <!-- catalogItem categories wrapper - Multi-Step Selector -->
                        <div class="single-catalogItem-widget">
                            <h5 class="widget-name">@lang('CatalogItem categories')</h5>
                            <div class="catalogItem-cat-widget">

                                {{-- Multi-Step Category Selector (5 Levels - Pre-computed in Controller) --}}
                                <div class="category-step-selector">
                                    @php
                                        // Use pre-computed data from Controller (no DB queries here)
                                        $cs = $categorySelector ?? [];
                                        $currentBrandSlug = $cs['brandSlug'] ?? null;
                                        $currentCatalogSlug = $cs['catalogSlug'] ?? null;
                                        $currentLevel1Slug = $cs['level1Slug'] ?? null;
                                        $currentLevel2Slug = $cs['level2Slug'] ?? null;
                                        $currentLevel3Slug = $cs['level3Slug'] ?? null;
                                        $brandCatalogs = $cs['brandCatalogs'] ?? collect();
                                        $catalogLevel1 = $cs['catalogLevel1'] ?? collect();
                                        $level1Level2 = $cs['level1Level2'] ?? collect();
                                        $level2Level3 = $cs['level2Level3'] ?? collect();
                                    @endphp

                                    {{-- Step 1: Brand --}}
                                    <div class="step-selector-item mb-3">
                                        <label class="step-label">@lang('Brand')</label>
                                        <select class="form-select category-select" id="brand-select">
                                            <option value="">-- @lang('Select Brand') --</option>
                                            @foreach ($brands as $brand)
                                                <option value="{{ $brand->slug }}"
                                                    {{ $currentBrandSlug === $brand->slug ? 'selected' : '' }}>
                                                    {{ app()->getLocale() == 'ar' ? ($brand->name_ar ?: $brand->name) : $brand->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Step 2: Catalog (Model) --}}
                                    <div class="step-selector-item mb-3 {{ $brandCatalogs->count() > 0 ? '' : 'd-none' }}" id="catalog-step">
                                        <label class="step-label">@lang('Model')</label>
                                        <select class="form-select category-select" id="catalog-select">
                                            <option value="">-- @lang('Select Model') --</option>
                                            @foreach ($brandCatalogs as $catalog)
                                                <option value="{{ $catalog->slug }}"
                                                    {{ $currentCatalogSlug === $catalog->slug ? 'selected' : '' }}>
                                                    {{ $catalog->localized_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Step 3: NewCategory Level 1 --}}
                                    <div class="step-selector-item mb-3 {{ $catalogLevel1->count() > 0 ? '' : 'd-none' }}" id="level1-step">
                                        <label class="step-label">@lang('Category')</label>
                                        <select class="form-select category-select" id="level1-select">
                                            <option value="">-- @lang('Select Category') --</option>
                                            @foreach ($catalogLevel1 as $level1)
                                                <option value="{{ $level1->slug }}"
                                                    {{ $currentLevel1Slug === $level1->slug ? 'selected' : '' }}>
                                                    {{ $level1->localized_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Step 4: NewCategory Level 2 --}}
                                    <div class="step-selector-item mb-3 {{ $level1Level2->count() > 0 ? '' : 'd-none' }}" id="level2-step">
                                        <label class="step-label">@lang('Subcategory')</label>
                                        <select class="form-select category-select" id="level2-select">
                                            <option value="">-- @lang('Select Subcategory') --</option>
                                            @foreach ($level1Level2 as $level2)
                                                <option value="{{ $level2->slug }}"
                                                    {{ $currentLevel2Slug === $level2->slug ? 'selected' : '' }}>
                                                    {{ $level2->localized_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Step 5: NewCategory Level 3 --}}
                                    <div class="step-selector-item mb-3 {{ $level2Level3->count() > 0 ? '' : 'd-none' }}" id="level3-step">
                                        <label class="step-label">@lang('Part Type')</label>
                                        <select class="form-select category-select" id="level3-select">
                                            <option value="">-- @lang('Select Part Type') --</option>
                                            @foreach ($level2Level3 as $level3)
                                                <option value="{{ $level3->slug }}"
                                                    {{ $currentLevel3Slug === $level3->slug ? 'selected' : '' }}>
                                                    {{ $level3->localized_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                </div>

                                {{-- API URLs for AJAX loading --}}
                                <script type="application/json" id="category-api-urls">
                                    {!! json_encode([
                                        'catalogs' => route('front.api.catalogs'),
                                        'tree' => route('front.api.tree'),
                                        'category' => route('front.catalog')
                                    ]) !!}
                                </script>

                            </div>
                        </div>




                        @if (
                            (!empty($cat) && !empty($cat->attributes) && !empty(json_decode($cat->attributes, true))) ||
                                (!empty($subcat) && !empty($subcat->attributes) && !empty(json_decode($subcat->attributes, true))) ||
                                (!empty($childcat) && !empty($childcat->attributes) && !empty(json_decode($childcat->attributes, true))))
                            <!-- Warranty Type-->
                            @if (!empty($cat) && !empty($cat->attributes) && !empty(json_decode($cat->attributes, true)))
                                @foreach ($cat->attributes as $key => $attr)
                                    <div class="single-catalogItem-widget">
                                        <h5 class="widget-name">{{ $attr->name }}</h5>
                                        <div class="warranty-type">
                                            @if (!empty($attr->specValues))
                                                <ul>
                                                    @foreach ($attr->specValues as $key => $option)
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


                            @if (!empty($subcat) && !empty($subcat->attributes) && !empty(json_decode($subcat->attributes, true)))
                                @foreach ($subcat->attributes as $key => $attr)
                                    <div class="single-catalogItem-widget">
                                        <h5 class="widget-name">{{ $attr->name }}</h5>
                                        <div class="warranty-type">
                                            @if (!empty($attr->specValues))
                                                <ul>
                                                    @foreach ($attr->specValues as $key => $option)
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


                            @if (!empty($childcat) && !empty($childcat->attributes) && !empty(json_decode($childcat->attributes, true)))
                                @foreach ($childcat->attributes as $key => $attr)
                                    <div class="single-catalogItem-widget">
                                        <h5 class="widget-name">{{ $attr->name }}</h5>
                                        <div class="warranty-type">
                                            @if (!empty($attr->specValues))
                                                <ul>
                                                    @foreach ($attr->specValues as $key => $option)
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

                        {{-- <a href="{{ route('front.catalog') }}" class="template-btn dark-btn">Clear Filter</a> --}}

                        <!-- Merchant Filter -->
                        @if(isset($merchants) && $merchants->count() > 0)
                        <div class="single-catalogItem-widget">
                            <h5 class="widget-name">@lang('Merchant')</h5>
                            <div class="warranty-type m-filter-scroll-box">
                                <ul>
                                    @foreach ($merchants as $merchant)
                                        <li class="gs-checkbox-wrapper">
                                            <input type="checkbox" class="attribute-input merchant-filter"
                                                name="merchant[]"
                                                {{ isset($_GET['merchant']) && in_array($merchant->user_id, (array)$_GET['merchant']) ? 'checked' : '' }}
                                                id="merchant_{{ $merchant->user_id }}"
                                                value="{{ $merchant->user_id }}">
                                            <label class="icon-label"
                                                for="merchant_{{ $merchant->user_id }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12"
                                                    height="12" viewBox="0 0 12 12" fill="none">
                                                    <path d="M10 3L4.5 8.5L2 6" stroke="currentColor"
                                                        stroke-width="1.6666" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                            </label>
                                            <label for="merchant_{{ $merchant->user_id }}">{{ getLocalizedShopName($merchant) }}</label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        @endif

                        <!-- Branch Filter (Loaded via AJAX when ONE merchant is selected) -->
                        <div class="single-catalogItem-widget d-none" id="branch-filter-widget">
                            <h5 class="widget-name">@lang('Branch')</h5>
                            <div class="warranty-type m-filter-scroll-box">
                                <ul id="branch-filter-list">
                                    <!-- Branches loaded via AJAX -->
                                </ul>
                            </div>
                            <p class="text-muted small mt-2 branch-loading-msg d-none">
                                <i class="fas fa-spinner fa-spin"></i> @lang('Loading branches...')
                            </p>
                        </div>

                        <!-- Quality Brand Filter -->
                        @if(isset($quality_brands) && $quality_brands->count() > 0)
                        <div class="single-catalogItem-widget">
                            <h5 class="widget-name">@lang('Quality Brand')</h5>
                            <div class="warranty-type m-filter-scroll-box">
                                <ul>
                                    @foreach ($quality_brands as $quality)
                                        <li class="gs-checkbox-wrapper">
                                            <input type="checkbox" class="attribute-input quality-brand-filter"
                                                name="quality_brand[]"
                                                {{ isset($_GET['quality_brand']) && in_array($quality->id, (array)$_GET['quality_brand']) ? 'checked' : '' }}
                                                id="quality_brand_{{ $quality->id }}"
                                                value="{{ $quality->id }}">
                                            <label class="icon-label"
                                                for="quality_brand_{{ $quality->id }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12"
                                                    height="12" viewBox="0 0 12 12" fill="none">
                                                    <path d="M10 3L4.5 8.5L2 6" stroke="currentColor"
                                                        stroke-width="1.6666" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                            </label>
                                            <label for="quality_brand_{{ $quality->id }}">{{ $quality->localized_name }}</label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        @endif

                    </div>
                </div>
                <div class="col-12 col-lg-8 col-xl-9 gs-main-blog-wrapper catalogItems-column">

                    @php
                        // Default to list-view for first visit
                        if (request()->input('view_check') == null || request()->input('view_check') == 'list-view') {
                            $view = 'list-view';
                        } else {
                            $view = 'grid-view';
                        }
                    @endphp

                    <!-- catalogItem nav wrapper -->
                    <div class=" catalogItem-nav-wrapper">
                        <h5>@lang('Total Items Found:') {{ $prods->total() }}</h5>
                        <div class="filter-wrapper">
                            <div class="sort-wrapper">
                                <h5>@lang('Sort by:')</h5>

                                @php $currentSort = request('sort', 'price_asc'); @endphp
                                <select class="nice-select" id="sortby" name="sort">
                                    <option value="price_asc" {{ $currentSort === 'price_asc' ? 'selected' : '' }}>{{ __('Lowest Price') }}</option>
                                    <option value="price_desc" {{ $currentSort === 'price_desc' ? 'selected' : '' }}>{{ __('Highest Price') }}</option>
                                    <option value="part_number" {{ $currentSort === 'part_number' ? 'selected' : '' }}>{{ __('Part Number') }}</option>
                                    <option value="name_asc" {{ $currentSort === 'name_asc' ? 'selected' : '' }}>{{ __('Name A-Z') }}</option>
                                </select>
                            </div>
                            <!-- list and grid view tab btns  start -->
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
                            <!-- list and grid view tab btns  end -->
                        </div>
                    </div>



                    @if ($prods->total() == 0)
                        {{-- Zero Results Box with Filter Summary --}}
                        <div class="category-catalogItems-box">
                            <div class="category-catalogItems-scroll" id="catalogItems-container">
                                <div class="m-no-results-box">
                                    <div class="m-no-results-box__icon">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <h4 class="m-no-results-box__name">@lang('No matching catalogItems')</h4>
                                    <p class="m-no-results-box__subname">@lang('Try adjusting your filters to find what you are looking for.')</p>

                                    @if(isset($filterSummary) && $filterSummary['hasFilters'])
                                    <div class="m-no-results-box__filters">
                                        <h6 class="m-no-results-box__filters-name">@lang('Applied Filters:')</h6>
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

                                            @if(!empty($filterSummary['merchants']))
                                            <li>
                                                <span class="m-no-results-box__filter-label">@lang('Merchant'):</span>
                                                <span class="m-no-results-box__filter-value">{{ implode(', ', $filterSummary['merchants']) }}</span>
                                            </li>
                                            @endif

                                            @if(!empty($filterSummary['branches']))
                                            <li>
                                                <span class="m-no-results-box__filter-label">@lang('Branch'):</span>
                                                <span class="m-no-results-box__filter-value">{{ implode(', ', $filterSummary['branches']) }}</span>
                                            </li>
                                            @endif

                                            @if(!empty($filterSummary['qualityBrands']))
                                            <li>
                                                <span class="m-no-results-box__filter-label">@lang('Quality Brand'):</span>
                                                <span class="m-no-results-box__filter-value">{{ implode(', ', $filterSummary['qualityBrands']) }}</span>
                                            </li>
                                            @endif
                                        </ul>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            {{-- Empty pagination for consistency --}}
                            <div class="category-catalogItems-pagination">
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
                        <div class="category-catalogItems-box">
                            <div class="category-catalogItems-scroll">
                                <div class="tab-content" id="myTabContent">
                                    <!-- catalogItem list view start  -->
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
                                    <!-- catalogItem grid view end  -->
                                </div>
                            </div>
                            <!-- Pagination outside scroll area -->
                            <div class="category-catalogItems-pagination">
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
    <!-- catalogItem wrapper end -->

@endsection


@section('script')
    <script>
        // Global sort state (accessible from Category Selector script)
        var categoryPageSort = '{{ request('sort', 'price_asc') }}';

        (function($) {
            "use strict";

            // ========================================
            // Category Items AJAX System
            // ========================================
            const $scrollContainer = $('.category-catalogItems-scroll');
            const $paginationContainer = $('.m-pagination-simple');
            const $totalItems = $('.catalogItem-nav-wrapper h5').first();

            let isLoading = false;
            let currentPage = parseInt($paginationContainer.data('current')) || 1;
            let lastPage = parseInt($paginationContainer.data('last')) || 1;

            // Expose pagination state globally for cross-script access
            window.categoryPagination = {
                get currentPage() { return currentPage; },
                set currentPage(val) { currentPage = val; },
                get lastPage() { return lastPage; },
                set lastPage(val) { lastPage = val; },
                updateUI: null // Will be set after updatePaginationUI is defined
            };

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

                // All filter checkboxes (Quality Brand, Merchant, etc.)
                $(".attribute-input:checked").each(function() {
                    params.append($(this).attr('name'), $(this).val());
                });

                // Sort - always use persistent global sort state
                if (categoryPageSort && categoryPageSort !== '') {
                    params.set('sort', categoryPageSort);
                }

                // View mode
                const viewMode = $('.check_view.active').data('shopview');
                if (viewMode) {
                    params.set('view_check', viewMode);
                }

                // Use current pathname as base URL (supports category selector navigation)
                const queryString = params.toString();
                return queryString ? window.location.pathname + '?' + queryString : window.location.pathname;
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
                        const $ajaxContent = $response.find('#ajax-catalogItems-content');
                        const $paginationData = $response.find('#ajax-pagination-data');

                        // Update catalogItems content (handles both catalogItems and no-results box)
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

                                // Update total catalogItems count
                                $totalItems.html('@lang("Total Items Found:") ' + data.total);
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

                        // Reinitialize shipping quote buttons for newly loaded content
                        // Use setTimeout to ensure DOM is fully updated after .html()
                        setTimeout(function() {
                            if (typeof ShippingQuote !== 'undefined' && typeof ShippingQuote.initButtons === 'function') {
                                ShippingQuote.initButtons();
                            }
                        }, 50);
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

            // Expose updatePaginationUI for cross-script access
            window.categoryPagination.updateUI = updatePaginationUI;

            // ========================================
            // Dynamic Branch Filter via AJAX (supports multiple merchants)
            // Branch selections are merchant-context isolated:
            // - Selecting a branch affects ONLY that branch's merchant
            // - When a merchant is unchecked, their branch selections are removed
            // ========================================
            const branchApiUrl = '{{ route("front.api.merchant.branches") }}';
            const $branchWidget = $('#branch-filter-widget');
            const $branchList = $('#branch-filter-list');
            const $branchLoadingMsg = $('.branch-loading-msg');
            let currentLoadedMerchantIds = []; // Track which merchants' branches are loaded
            let branchMerchantMap = {}; // Map branch_id -> merchant_id for context tracking

            // Get selected branches from URL (for page load restoration)
            function getSelectedBranchesFromUrl() {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.getAll('branch[]');
            }

            // Get currently selected branch IDs
            function getSelectedBranchIds() {
                const selected = [];
                $('.branch-filter:checked').each(function() {
                    selected.push($(this).val());
                });
                return selected;
            }

            // Get branches selected for a specific merchant
            function getBranchesForMerchant(merchantId) {
                const selected = [];
                $(`.branch-filter:checked[data-merchant-id="${merchantId}"]`).each(function() {
                    selected.push($(this).val());
                });
                return selected;
            }

            // Remove branch selections for a specific merchant
            function removeBranchesForMerchant(merchantId) {
                $(`.branch-filter[data-merchant-id="${merchantId}"]`).prop('checked', false);
            }

            // Load branches for selected merchants via AJAX
            function loadBranchesForMerchants(merchantIds, removedMerchantId = null) {
                if (!merchantIds || merchantIds.length === 0) {
                    hideBranchFilter();
                    return;
                }

                // Sort for comparison
                const sortedIds = [...merchantIds].sort().join(',');
                const currentIds = [...currentLoadedMerchantIds].sort().join(',');

                // Preserve currently selected branches (except for removed merchant)
                let previouslySelected = getSelectedBranchIds();
                const urlSelected = getSelectedBranchesFromUrl();

                // If a merchant was removed, filter out their branches from selection
                if (removedMerchantId) {
                    previouslySelected = previouslySelected.filter(branchId => {
                        return branchMerchantMap[branchId] != removedMerchantId;
                    });
                }

                const allSelected = [...new Set([...previouslySelected, ...urlSelected])];

                // Skip reload if same merchants and no merchant removed
                if (sortedIds === currentIds && !removedMerchantId) {
                    return;
                }

                currentLoadedMerchantIds = [...merchantIds];
                $branchWidget.removeClass('d-none');
                $branchLoadingMsg.removeClass('d-none');
                $branchList.empty();
                branchMerchantMap = {}; // Reset mapping

                $.ajax({
                    url: branchApiUrl,
                    type: 'GET',
                    data: { 'merchant_ids[]': merchantIds },
                    dataType: 'json',
                    traditional: true,
                    success: function(branches) {
                        $branchLoadingMsg.addClass('d-none');

                        if (branches.length === 0) {
                            $branchWidget.addClass('d-none');
                            currentLoadedMerchantIds = [];
                            return;
                        }

                        // Group branches by merchant if multiple merchants
                        const groupByMerchant = merchantIds.length > 1;
                        let currentMerchantId = null;

                        branches.forEach(function(branch) {
                            // Store branch-merchant mapping
                            branchMerchantMap[branch.id] = branch.merchant_id;

                            // Add merchant header if grouping and merchant changed
                            if (groupByMerchant && branch.merchant_id !== currentMerchantId) {
                                currentMerchantId = branch.merchant_id;
                                const headerHtml = `
                                    <li class="branch-merchant-header" data-merchant-id="${branch.merchant_id}" style="font-weight: bold; padding: 8px 0 4px 0; border-bottom: 1px solid var(--border-light); margin-bottom: 4px; color: var(--text-secondary);">
                                        <small>${branch.merchant_name}</small>
                                    </li>
                                `;
                                $branchList.append(headerHtml);
                            }

                            // Only restore selection if this branch's merchant is still selected
                            // AND the branch was previously selected
                            const merchantStillSelected = merchantIds.includes(branch.merchant_id.toString());
                            const wasSelected = allSelected.includes(branch.id.toString());
                            const isChecked = (merchantStillSelected && wasSelected) ? 'checked' : '';

                            const html = `
                                <li class="gs-checkbox-wrapper" data-merchant-id="${branch.merchant_id}">
                                    <input type="checkbox" class="attribute-input branch-filter"
                                        name="branch[]"
                                        id="branch_${branch.id}"
                                        value="${branch.id}"
                                        data-merchant-id="${branch.merchant_id}" ${isChecked}>
                                    <label class="icon-label" for="branch_${branch.id}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                            <path d="M10 3L4.5 8.5L2 6" stroke="currentColor" stroke-width="1.6666" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </label>
                                    <label for="branch_${branch.id}">${branch.name}</label>
                                </li>
                            `;
                            $branchList.append(html);
                        });

                        // Bind change event to new branch checkboxes
                        $branchList.find('.branch-filter').on('change', function() {
                            currentPage = 1;
                            loadContent(1);
                        });
                    },
                    error: function() {
                        $branchLoadingMsg.addClass('d-none');
                        $branchWidget.addClass('d-none');
                        currentLoadedMerchantIds = [];
                        branchMerchantMap = {};
                    }
                });
            }

            // Hide branch filter and clear selection
            function hideBranchFilter() {
                $branchWidget.addClass('d-none');
                $branchList.empty();
                currentLoadedMerchantIds = [];
                branchMerchantMap = {};
            }

            // Update branch filter based on merchant selection
            // removedMerchantId: if provided, that merchant was just unchecked
            function updateBranchFilter(removedMerchantId = null) {
                const selectedMerchants = [];
                $('.merchant-filter:checked').each(function() {
                    selectedMerchants.push($(this).val());
                });

                if (selectedMerchants.length > 0) {
                    // One or more merchants selected - load their branches
                    loadBranchesForMerchants(selectedMerchants, removedMerchantId);
                } else {
                    // No merchant selected - hide branch filter
                    hideBranchFilter();
                }
            }

            // Initialize branch filter on page load
            updateBranchFilter();

            // ========================================
            // Filter Events (Merchant, Branch, Quality Brand)
            // ========================================
            // Merchant filter change - update branches then load content
            // When a merchant is unchecked, their branch selections are automatically removed
            $(".merchant-filter").on('change', function() {
                const merchantId = $(this).val();
                const isChecked = $(this).is(':checked');

                if (isChecked) {
                    // Merchant added - load branches (preserving existing selections)
                    updateBranchFilter();
                } else {
                    // Merchant removed - clear their branches from selection and reload
                    updateBranchFilter(merchantId);
                }

                currentPage = 1;
                loadContent(1);
            });

            // Other attribute filters (excluding merchant and branch which are handled separately)
            $(".attribute-input").not('.merchant-filter').not('.branch-filter').on('change', function() {
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
                    const sortVal = urlParams.get('sort') || 'price_asc';
                    categoryPageSort = sortVal; // Update global persistent state
                    $('#sortby').val(sortVal);

                    // Update filter checkboxes (except branch which is handled dynamically)
                    $('.attribute-input').not('.branch-filter').prop('checked', false);
                    urlParams.forEach(function(value, key) {
                        if (key.endsWith('[]') && key !== 'branch[]') {
                            $('input[name="' + key + '"][value="' + value + '"]').prop('checked', true);
                        }
                    });

                    // Reset branch state and reload based on merchant selection
                    // The branch selections will be restored from URL when branches load
                    currentLoadedMerchantIds = [];
                    branchMerchantMap = {};
                    updateBranchFilter();

                    // Load content without adding to history
                    currentPage = state.page;
                    loadContent(state.page, false);
                }
            });

            // Set initial state
            history.replaceState({page: currentPage, url: window.location.href}, '', window.location.href);

        })(jQuery);
    </script>

    {{-- Multi-Step Category Selector JavaScript (5 Levels - AJAX) --}}
    <script>
        (function($) {
            "use strict";

            // API URLs
            const apiUrls = JSON.parse($('#category-api-urls').text() || '{}');
            const categoryBaseUrl = apiUrls.category || '{{ route("front.catalog") }}';

            // Breadcrumb update function
            function updateBreadcrumb() {
                const brandSelect = $('#brand-select');
                const catalogSelect = $('#catalog-select');
                const level1Select = $('#level1-select');
                const level2Select = $('#level2-select');
                const level3Select = $('#level3-select');

                const brandSlug = brandSelect.val();
                const brandName = brandSlug ? brandSelect.find('option:selected').text().trim() : '';
                const catalogSlug = catalogSelect.val();
                const catalogName = catalogSlug ? catalogSelect.find('option:selected').text().trim() : '';
                const level1Slug = level1Select.val();
                const level1Name = level1Slug ? level1Select.find('option:selected').text().trim() : '';
                const level2Slug = level2Select.val();
                const level2Name = level2Slug ? level2Select.find('option:selected').text().trim() : '';
                const level3Slug = level3Select.val();
                const level3Name = level3Slug ? level3Select.find('option:selected').text().trim() : '';

                // Build breadcrumb HTML
                let breadcrumbHtml = `<li><a href="{{ route('front.index') }}">@lang('Home')</a></li>`;
                breadcrumbHtml += `<li><a href="${categoryBaseUrl}">@lang('CatalogItem')</a></li>`;

                let title = '@lang("CatalogItem")';

                if (brandSlug) {
                    breadcrumbHtml += `<li><a href="${categoryBaseUrl}/${brandSlug}">${brandName}</a></li>`;
                    title = brandName;
                }
                if (catalogSlug) {
                    breadcrumbHtml += `<li><a href="${categoryBaseUrl}/${brandSlug}/${catalogSlug}">${catalogName}</a></li>`;
                    title = catalogName;
                }
                if (level1Slug) {
                    breadcrumbHtml += `<li><a href="${categoryBaseUrl}/${brandSlug}/${catalogSlug}/${level1Slug}">${level1Name}</a></li>`;
                    title = level1Name;
                }
                if (level2Slug) {
                    breadcrumbHtml += `<li><a href="${categoryBaseUrl}/${brandSlug}/${catalogSlug}/${level1Slug}/${level2Slug}">${level2Name}</a></li>`;
                    title = level2Name;
                }
                if (level3Slug) {
                    breadcrumbHtml += `<li><a href="javascript:;">${level3Name}</a></li>`;
                    title = level3Name;
                }

                // Update DOM
                $('#breadcrumb-menu').html(breadcrumbHtml);
                $('#breadcrumb-title').text(title);
            }

            // Reference to AJAX system
            const $scrollContainer = $('.category-catalogItems-scroll');
            const $paginationContainer = $('.m-pagination-simple');
            const $totalItems = $('.catalogItem-nav-wrapper h5').first();

            // Build URL with current filters preserved
            function buildCategoryUrl(basePath) {
                let params = new URLSearchParams();

                if (categoryPageSort && categoryPageSort !== '') {
                    params.set('sort', categoryPageSort);
                }

                $(".merchant-filter:checked").each(function() {
                    params.append('merchant[]', $(this).val());
                });

                $(".branch-filter:checked").each(function() {
                    params.append('branch[]', $(this).val());
                });

                $(".quality-brand-filter:checked").each(function() {
                    params.append('quality_brand[]', $(this).val());
                });

                $(".attribute-input:checked").each(function() {
                    const name = $(this).attr('name');
                    if (name !== 'merchant[]' && name !== 'branch[]' && name !== 'quality_brand[]') {
                        params.append(name, $(this).val());
                    }
                });

                const viewMode = $('.check_view.active').data('shopview');
                if (viewMode) params.set('view_check', viewMode);

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
                        const $ajaxContent = $response.find('#ajax-catalogItems-content');
                        const $paginationData = $response.find('#ajax-pagination-data');

                        if ($ajaxContent.length) {
                            $scrollContainer.html($ajaxContent.html());
                        }

                        // Update pagination from JSON data
                        if ($paginationData.length) {
                            try {
                                const data = JSON.parse($paginationData.text());
                                $totalItems.html('@lang("Total Items Found:") ' + data.total);

                                // Update global pagination state and refresh UI
                                if (window.categoryPagination) {
                                    window.categoryPagination.currentPage = data.currentPage || 1;
                                    window.categoryPagination.lastPage = data.lastPage || 1;

                                    // Update pagination data attributes
                                    const $pag = $('.m-pagination-simple');
                                    if ($pag.length) {
                                        $pag.data('current', data.currentPage);
                                        $pag.data('last', data.lastPage);
                                        $pag.data('total', data.total);
                                    }

                                    // Refresh pagination UI
                                    if (typeof window.categoryPagination.updateUI === 'function') {
                                        window.categoryPagination.updateUI();
                                    }
                                }
                            } catch(e) {
                                console.error('Pagination parse error:', e);
                            }
                        }

                        $scrollContainer.scrollTop(0);
                        history.pushState({categoryUrl: url}, '', url);
                        $('[data-bs-toggle="tooltip"]').tooltip({});

                        // Reinitialize shipping quote buttons for newly loaded content
                        // Use setTimeout to ensure DOM is fully updated after .html()
                        setTimeout(function() {
                            if (typeof ShippingQuote !== 'undefined' && typeof ShippingQuote.initButtons === 'function') {
                                ShippingQuote.initButtons();
                            }
                        }, 50);
                    },
                    error: function() {
                        window.location.href = url;
                    },
                    complete: function() {
                        $scrollContainer.removeClass('is-loading');
                    }
                });
            }

            // Helper: Reset and hide steps from given level onwards
            function resetStepsFrom(level) {
                const steps = ['catalog', 'level1', 'level2', 'level3'];
                const startIndex = steps.indexOf(level);

                for (let i = startIndex; i < steps.length; i++) {
                    const stepId = steps[i];
                    $(`#${stepId}-step`).addClass('d-none');
                    $(`#${stepId}-select`).html(`<option value="">-- @lang('Select') --</option>`);
                }
            }

            // Helper: Populate select with options from AJAX response
            function populateSelect($select, data, defaultText) {
                $select.html(`<option value="">-- ${defaultText} --</option>`);
                data.forEach(item => {
                    $select.append(`<option value="${item.slug}">${item.name}</option>`);
                });
            }

            // Step 1: Brand Change - fetch catalogs via AJAX
            $('#brand-select').on('change', function() {
                const selectedSlug = $(this).val();
                resetStepsFrom('catalog');

                if (!selectedSlug) {
                    updateBreadcrumb();
                    loadCategoryContent(categoryBaseUrl);
                    return;
                }

                // Fetch catalogs via AJAX
                $.get(apiUrls.catalogs, { brand: selectedSlug }, function(data) {
                    if (data && data.length > 0) {
                        populateSelect($('#catalog-select'), data, '@lang("Select Model")');
                        $('#catalog-step').removeClass('d-none');
                    }
                });

                updateBreadcrumb();
                loadCategoryContent(categoryBaseUrl + '/' + selectedSlug);
            });

            // Step 2: Catalog Change - fetch Level 1 via AJAX
            $('#catalog-select').on('change', function() {
                const brandSlug = $('#brand-select').val();
                const selectedSlug = $(this).val();
                resetStepsFrom('level1');

                if (!selectedSlug) {
                    updateBreadcrumb();
                    loadCategoryContent(categoryBaseUrl + '/' + brandSlug);
                    return;
                }

                // Fetch Level 1 categories via AJAX
                $.get(apiUrls.tree, { catalog: selectedSlug, level: 1 }, function(data) {
                    if (data && data.length > 0) {
                        populateSelect($('#level1-select'), data, '@lang("Select Category")');
                        $('#level1-step').removeClass('d-none');
                    }
                });

                updateBreadcrumb();
                loadCategoryContent(categoryBaseUrl + '/' + brandSlug + '/' + selectedSlug);
            });

            // Step 3: Level 1 Change - fetch Level 2 via AJAX
            $('#level1-select').on('change', function() {
                const brandSlug = $('#brand-select').val();
                const catalogSlug = $('#catalog-select').val();
                const selectedSlug = $(this).val();
                resetStepsFrom('level2');

                if (!selectedSlug) {
                    updateBreadcrumb();
                    loadCategoryContent(categoryBaseUrl + '/' + brandSlug + '/' + catalogSlug);
                    return;
                }

                // Fetch Level 2 categories via AJAX
                $.get(apiUrls.tree, { catalog: catalogSlug, parent: selectedSlug, level: 2 }, function(data) {
                    if (data && data.length > 0) {
                        populateSelect($('#level2-select'), data, '@lang("Select Subcategory")');
                        $('#level2-step').removeClass('d-none');
                    }
                });

                updateBreadcrumb();
                loadCategoryContent(categoryBaseUrl + '/' + brandSlug + '/' + catalogSlug + '/' + selectedSlug);
            });

            // Step 4: Level 2 Change - fetch Level 3 via AJAX
            $('#level2-select').on('change', function() {
                const brandSlug = $('#brand-select').val();
                const catalogSlug = $('#catalog-select').val();
                const level1Slug = $('#level1-select').val();
                const selectedSlug = $(this).val();
                resetStepsFrom('level3');

                if (!selectedSlug) {
                    updateBreadcrumb();
                    loadCategoryContent(categoryBaseUrl + '/' + brandSlug + '/' + catalogSlug + '/' + level1Slug);
                    return;
                }

                // Fetch Level 3 categories via AJAX
                $.get(apiUrls.tree, { catalog: catalogSlug, parent: selectedSlug, level: 3 }, function(data) {
                    if (data && data.length > 0) {
                        populateSelect($('#level3-select'), data, '@lang("Select Part Type")');
                        $('#level3-step').removeClass('d-none');
                    }
                });

                updateBreadcrumb();
                loadCategoryContent(categoryBaseUrl + '/' + brandSlug + '/' + catalogSlug + '/' + level1Slug + '/' + selectedSlug);
            });

            // Step 5: Level 3 Change
            $('#level3-select').on('change', function() {
                const brandSlug = $('#brand-select').val();
                const catalogSlug = $('#catalog-select').val();
                const level1Slug = $('#level1-select').val();
                const level2Slug = $('#level2-select').val();
                const selectedSlug = $(this).val();

                if (!selectedSlug) {
                    updateBreadcrumb();
                    loadCategoryContent(categoryBaseUrl + '/' + brandSlug + '/' + catalogSlug + '/' + level1Slug + '/' + level2Slug);
                    return;
                }

                updateBreadcrumb();
                loadCategoryContent(categoryBaseUrl + '/' + brandSlug + '/' + catalogSlug + '/' + level1Slug + '/' + level2Slug + '/' + selectedSlug);
            });

        })(jQuery);
    </script>
@endsection
