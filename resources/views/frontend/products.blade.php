

@extends('layouts.front')

@section('styles')
@endsection

@section('content')
    <section class="gs-breadcrumb-section bg-class"
        data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">
                        @if(!empty($childcat))
                            {{ $childcat->localized_name }}
                        @elseif(!empty($subcat))
                            {{ $subcat->localized_name }}
                        @elseif(!empty($cat))
                            {{ $cat->localized_name }}
                        @else
                            @lang('Product')
                        @endif
                    </h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        @if(!empty($cat))
                            <li><a href="{{ route('front.category', $cat->slug) }}">{{ $cat->localized_name }}</a></li>
                        @endif
                        @if(!empty($subcat))
                            <li><a href="{{ route('front.category', [$cat->slug, $subcat->slug]) }}">{{ $subcat->localized_name }}</a></li>
                        @endif
                        @if(!empty($childcat))
                            <li><a href="javascript:;">{{ $childcat->localized_name }}</a></li>
                        @else
                            <li><a href="javascript:;">@lang('Product')</a></li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <!-- breadcrumb end -->

    <!-- product wrapper start -->
    <div class="gs-blog-wrapper">
        <div class="container">
            <div class="row flex-column-reverse flex-lg-row">
                <div class="col-12 col-lg-4 col-xl-3 mt-40 mt-lg-0">
                    <div class="gs-product-sidebar-wrapper">
                        <!-- Price Range -->
                        <div class="single-product-widget">
                            <h5 class="widget-title">@lang('Price Range')</h5>
                            <div class="price-range">
                                <div class="d-none">
                                    <!-- start value -->
                                    <input id="start_value" type="number" name="min"
                                        value="{{ isset($_GET['min']) ? $_GET['min'] : $gs->min_price }}">
                                    <!-- end value -->
                                    <input id="end_value" type="number"
                                        value="{{ isset($_GET['max']) ? $_GET['max'] : $gs->max_price }}">
                                    <!-- max value -->
                                    <input id="max_value" type="number" name="max" value="{{ $gs->max_price }}">
                                </div>
                                <div id="slider-range"></div>

                                <input type="text" id="amount" readonly class="range_output">
                            </div>

                            <button class="template-btn mt-3 w-100" id="price_filter">@lang('Apply Filter')</button>
                            <a href="{{ route('front.category') }}"
                                class="template-btn dark-btn w-100 mt-3">@lang('Clear Filter')</a>
                        </div>

                        <!-- product categories wrapper -->
                        <div class="single-product-widget">
                            <h5 class="widget-title">@lang('Product categories')</h5>
                            <div class="product-cat-widget">

                                <ul class="accordion">
                                    @foreach ($categories as $category)
                                        @if ($category->subs->count() > 0)
                                            <li>
                                                @php
                                                    $isCategoryActive = Request::segment(2) === $category->slug;
                                                @endphp
                                                <div class="d-flex justify-content-between align-items-lg-baseline">
                                                    <a href="{{ route('front.category', $category->slug) }}"
                                                        class="{{ $isCategoryActive ? 'sidebar-active-color' : '' }}">
                                                        {{ $category->localized_name }}
                                                    </a>

                                                    <button data-bs-toggle="collapse"
                                                        data-bs-target="#{{ $category->slug }}_level_2"
                                                        aria-controls="{{ $category->slug }}_level_2"
                                                        aria-expanded="{{ $isCategoryActive ? 'true' : 'false' }}"
                                                        class="{{ $isCategoryActive ? '' : 'collapsed' }}">
                                                        <i class="fa-solid fa-plus"></i>
                                                        <i class="fa-solid fa-minus"></i>
                                                    </button>
                                                </div>

                                                @foreach ($category->subs as $subcategory)
                                                    @php
                                                        $isSubcategoryActive =
                                                            $isCategoryActive &&
                                                            Request::segment(3) === $subcategory->slug;
                                                    @endphp
                                                    <ul id="{{ $category->slug }}_level_2"
                                                        class="accordion-collapse collapse ms-3 {{ $isCategoryActive ? 'show' : '' }}">
                                                        <li class="">
                                                            <div
                                                                class="d-flex justify-content-between align-items-lg-baseline">
                                                                <a href="{{ route('front.category', [$category->slug, $subcategory->slug]) }}"
                                                                    class="{{ $isSubcategoryActive ? 'sidebar-active-color' : '' }} "
                                                                    @if ($subcategory->childs->count() > 0) data-bs-toggle="collapse"
                                                                   data-bs-target="#inner{{ $subcategory->slug }}_level_2_1"
                                                                   aria-controls="inner{{ $subcategory->slug }}_level_2_1"
                                                                   aria-expanded="{{ $isSubcategoryActive ? 'true' : 'false' }}"
                                                                   class="{{ $isSubcategoryActive ? '' : 'collapsed' }}" @endif>
                                                                    {{ $subcategory->localized_name }}
                                                                </a>

                                                                @if ($subcategory->childs->count() > 0)
                                                                    <button data-bs-toggle="collapse"
                                                                        data-bs-target="#inner{{ $subcategory->slug }}_level_2_1"
                                                                        aria-controls="inner{{ $subcategory->slug }}_level_2_1"
                                                                        aria-expanded="{{ $isSubcategoryActive ? 'true' : 'false' }}"
                                                                        class="{{ $isSubcategoryActive ? '' : 'collapsed' }}">
                                                                        <i class="fa-solid fa-plus"></i>
                                                                        <i class="fa-solid fa-minus"></i>
                                                                    </button>
                                                                @endif
                                                            </div>

                                                            @if ($subcategory->childs->count() > 0)
                                                                <ul id="inner{{ $subcategory->slug }}_level_2_1"
                                                                    class="accordion-collapse collapse ms-3 {{ $isSubcategoryActive ? 'show' : '' }}">
                                                                    @foreach ($subcategory->childs as $child)
                                                                        @php
                                                                            $isChildActive =
                                                                                $isSubcategoryActive &&
                                                                                Request::segment(4) === $child->slug;
                                                                        @endphp
                                                                        <li>
                                                                            <a href="{{ route('front.category', [$category->slug, $subcategory->slug, $child->slug]) }}"
                                                                                class="{{ $isChildActive ? 'sidebar-active-color' : '' }}">
                                                                                {{ $child->localized_name }}
                                                                            </a>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            @endif
                                                        </li>
                                                    </ul>
                                                @endforeach

                                            </li>
                                        @else
                                            <li>
                                                <a href="{{ route('front.category', $category->slug) }}"
                                                    class="{{ Request::segment(2) === $category->slug ? 'active' : '' }}">
                                                    {{ $category->localized_name }}
                                                </a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>

                            </div>
                        </div>

                        <!-- Brand Quality Filter - Hidden on desktop (use header dropdown instead) -->
                        <div class="single-product-widget d-lg-none">
                            <h5 class="widget-title">@lang('Brand Quality')</h5>
                            <div class="brand-quality-filter">
                                @php
                                    $qualityBrands = \App\Models\QualityBrand::active()->get();
                                @endphp
                                <ul>
                                    @foreach($qualityBrands as $qualityBrand)
                                        <li class="gs-checkbox-wrapper">
                                            <input type="checkbox" class="brand-quality-input"
                                                name="brand_quality[]"
                                                {{ isset($_GET['brand_quality']) && in_array($qualityBrand->id, $_GET['brand_quality']) ? 'checked' : '' }}
                                                id="brand_quality_{{ $qualityBrand->id }}"
                                                value="{{ $qualityBrand->id }}">
                                            <label class="icon-label" for="brand_quality_{{ $qualityBrand->id }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12"
                                                    height="12" viewBox="0 0 12 12" fill="none">
                                                    <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243"
                                                        stroke-width="1.6666" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                            </label>
                                            <label for="brand_quality_{{ $qualityBrand->id }}">
                                                @if($qualityBrand->logo_url)
                                                    <img src="{{ $qualityBrand->logo_url }}"
                                                         alt="{{ $qualityBrand->display_name }}"
                                                         style="width: 20px; height: 20px; margin-right: 5px; vertical-align: middle;">
                                                @endif
                                                {{ $qualityBrand->display_name }}
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
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

                        <!-- Recent Product -->
                        <div class="single-product-widget">
                            <h5 class="widget-title">@lang('Recent Product')</h5>
                            <div class="gs-recent-post-widget">
                                @foreach ($latest_products as $product)
                                    @php
                                        $recentProduct = is_array($product) ? App\Models\Product::whereId($product['id'])->first() : $product;
                                        $recentMerchant = $recentProduct ? $recentProduct->merchantProducts()->where('status', 1)->orderBy('price')->first() : null;
                                        $recentVendorId = $recentMerchant->user_id ?? 0;
                                        $recentMerchantId = $recentMerchant->id ?? null;
                                    @endphp
                                    @if($recentMerchantId)
                                        <a href="{{ route('front.product', ['slug' => (is_array($product) ? $product['slug'] : $product->slug), 'vendor_id' => $recentVendorId, 'merchant_product_id' => $recentMerchantId]) }}">
                                    @else
                                        <span style="cursor: not-allowed;">
                                    @endif
                                        <div class="gs-single-recent-product-widget">
                                            <div class="img-wrapper">
                                                <img class="thumb"
                                                    src="{{ (is_array($product) ? $product['thumbnail'] : $product->thumbnail) ? asset('assets/images/thumbnails/' . (is_array($product) ? $product['thumbnail'] : $product->thumbnail)) : asset('assets/images/noimage.png') }}"
                                                    alt="product img">
                                            </div>
                                            <div class="content-wrapper">
                                                <h6 class="title"><x-product-name :product="$recentProduct" :vendor-id="$recentVendorId" :merchant-product-id="$recentMerchantId" target="_self" /></h6>
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
                                                        class="rating">{{ number_format($product->ratings_avg_rating, 1) }}
                                                        ({{ $product->ratings_count }})
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @if($recentMerchantId)
                                        </a>
                                    @else
                                        </span>
                                    @endif
                                @endforeach

                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-8 col-xl-9 gs-main-blog-wrapper">

                    @php
                        if (request()->input('view_check') == null || request()->input('view_check') == 'grid-view') {
                            $view = 'grid-view';
                        } else {
                            $view = 'list-view';
                        }
                    @endphp

                    {{-- Products Header & Filters --}}
                    <div class="products-header-section card shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                                {{-- Total Count --}}
                                <div class="products-count">
                                    <h5 class="mb-0">
                                        <i class="fas fa-box-open text-primary me-2"></i>
                                        @lang('Total Offers Found:')
                                        <span class="badge bg-primary ms-2">{{ $prods->total() }}</span>
                                    </h5>
                                </div>

                                {{-- View Toggle & Filters --}}
                                <div class="d-flex align-items-center gap-3 flex-wrap">

                                    {{-- View Toggle Buttons --}}
                                    <div class="view-toggle-buttons d-none d-lg-flex gap-2" role="tablist">
                                        <button class="view-btn check_view {{ $view == 'list-view' ? 'active' : '' }}"
                                            data-shopview="list-view" type="button" data-bs-toggle="tab"
                                            data-bs-target="#layout-list-pane" role="tab"
                                            aria-controls="layout-list-pane"
                                            aria-selected="{{ $view == 'list-view' ? 'true' : 'false' }}"
                                            title="@lang('List View')">
                                            <i class="fas fa-list"></i>
                                        </button>
                                        <button class="view-btn check_view {{ $view == 'grid-view' ? 'active' : '' }}"
                                            type="button" data-shopview="grid-view" data-bs-toggle="tab"
                                            data-bs-target="#layout-grid-pane" role="tab"
                                            aria-controls="layout-grid-pane"
                                            aria-selected="{{ $view == 'grid-view' ? 'true' : 'false' }}"
                                            title="@lang('Grid View')">
                                            <i class="fas fa-th"></i>
                                        </button>
                                    </div>

                                    {{-- Store Filter --}}
                                    <div class="filter-item">
                                        <label class="filter-label mb-1">
                                            <i class="fas fa-store me-1"></i>
                                            @lang('Store by:')
                                        </label>
                                        <select class="form-select form-select-sm" id="vendorby" name="vendor">
                                            <option value="">{{ __('All Stores') }}</option>
                                            @foreach($vendors as $vendor)
                                                <option value="{{ $vendor->user_id }}" {{ request('vendor') == $vendor->user_id ? 'selected' : '' }}>
                                                    {{ $vendor->shop_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Quality Filter --}}
                                    <div class="filter-item">
                                        <label class="filter-label mb-1">
                                            <i class="fas fa-certificate me-1"></i>
                                            @lang('Quality by:')
                                        </label>
                                        <select class="form-select form-select-sm" id="qualityby" name="quality">
                                            <option value="">{{ __('All Quality') }}</option>
                                            @php
                                                $qualityBrands = \App\Models\QualityBrand::where('is_active', 1)->get();
                                            @endphp
                                            @foreach($qualityBrands as $brand)
                                                <option value="{{ $brand->id }}" {{ request('quality') == $brand->id ? 'selected' : '' }}>
                                                    {{ $brand->name_ar ?: $brand->name_en }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Sort Filter --}}
                                    <div class="filter-item">
                                        <label class="filter-label mb-1">
                                            <i class="fas fa-sort me-1"></i>
                                            @lang('Sort by:')
                                        </label>
                                        <select class="form-select form-select-sm" id="sortby" name="sort">
                                            <option value="sku_asc" {{ request('sort') == 'sku_asc' ? 'selected' : '' }}>{{ __('SKU A-Z') }}</option>
                                            <option value="sku_desc" {{ request('sort') == 'sku_desc' ? 'selected' : '' }}>{{ __('SKU Z-A') }}</option>
                                            <option value="date_desc" {{ request('sort') == 'date_desc' ? 'selected' : '' }}>{{ __('Latest Product') }}</option>
                                            <option value="date_asc" {{ request('sort') == 'date_asc' ? 'selected' : '' }}>{{ __('Oldest Product') }}</option>
                                            <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>{{ __('Lowest Price') }}</option>
                                            <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>{{ __('Highest Price') }}</option>
                                        </select>
                                    </div>

                                </div>
                            </div>
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
                                    @foreach ($prods as $mp)
                                        @include('includes.frontend.list_view_product', ['product' => $mp->product, 'mp' => $mp])
                                    @endforeach
                                </div>
                            </div>

                            <div class="tab-pane fade {{ $view == 'grid-view' ? 'show active' : '' }}  "
                                id="layout-grid-pane" role="tabpanel" tabindex="0">
                                <div class="row gy-4 gy-lg-5 mt-20">
                                    @foreach ($prods as $mp)
                                        @include('includes.frontend.home_product', [
                                            'class' => 'col-sm-6 col-md-6 col-xl-4',
                                            'product' => $mp->product,
                                            'mp' => $mp
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



        // Quality dropdown - simple single select like Sort by
        $("#qualityby").on('change', function() {
            $(".ajax-loader").show();
            filter();
        });

        // when dynamic attribute changes
        $(".attribute-input, .brand-quality-input, #sortby, #vendorby, #pageby").on('change', function() {
            $(".ajax-loader").show();
            filter();
        });

        // Update view toggle button functionality
        $(".view-btn").on('click', function() {
            $(".view-btn").removeClass('active');
            $(this).addClass('active');

            // Trigger filter to save the view preference
            setTimeout(function() {
                filter();
            }, 100);
        });

        // Add view_check parameter to all category links and preserve current filters
        function updateCategoryLinks() {
            let currentView = $('.view-btn.active').data('shopview') || $('.check_view.active').data('shopview');

            // Get current URL parameters
            let currentUrl = new URL(window.location.href);
            let currentParams = currentUrl.searchParams;

            // Update all category sidebar links
            $('.product-cat-widget a, .gs-product-sidebar-wrapper a').each(function() {
                let href = $(this).attr('href');
                if (href && (href.includes('category') || href.includes('/category/'))) {
                    try {
                        let url = new URL(href, window.location.origin);

                        // Add view_check if we have it
                        if (currentView) {
                            url.searchParams.set('view_check', currentView);
                        } else if (currentParams.has('view_check')) {
                            url.searchParams.set('view_check', currentParams.get('view_check'));
                        }

                        // Preserve other filters if on same category
                        if (currentParams.has('quality')) {
                            url.searchParams.set('quality', currentParams.get('quality'));
                        }
                        if (currentParams.has('vendor')) {
                            url.searchParams.set('vendor', currentParams.get('vendor'));
                        }
                        if (currentParams.has('sort')) {
                            url.searchParams.set('sort', currentParams.get('sort'));
                        }

                        $(this).attr('href', url.toString());
                    } catch (e) {
                        // Skip invalid URLs
                    }
                }
            });
        }

        // Update links on page load
        $(document).ready(function() {
            updateCategoryLinks();
            addToPagination();
        });

        // Update links when view changes
        $(".view-btn").on('click', function() {
            setTimeout(function() {
                updateCategoryLinks();
            }, 200);
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

            $(".brand-quality-input").each(function() {
                if ($(this).is(':checked')) {
                    params.append($(this).attr('name'), $(this).val());
                }
            });

            if ($("#qualityby").val() != '') {
                params.append('quality', $("#qualityby").val());
            }

            if ($("#sortby").val() != '') {
                params.append($("#sortby").attr('name'), $("#sortby").val());
            }

            if ($("#vendorby").val() != '') {
                params.append($("#vendorby").attr('name'), $("#vendorby").val());
            }

            if ($("#start_value").val() != '') {
                params.append($("#start_value").attr('name'), $("#start_value").val());
            }

            let check_view = $('.view-btn.active').data('shopview') || $('.check_view.active').data('shopview');

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

                $(".brand-quality-input").each(function() {
                    if ($(this).is(':checked')) {
                        params.append($(this).attr('name'), $(this).val());
                    }
                });

                if ($("#qualityby").val() != '') {
                    params.append('quality', $("#qualityby").val());
                }

                if ($("#sortby").val() != '') {
                    params.append('sort', $("#sortby").val());
                }

                if ($("#vendorby").val() != '') {
                    params.append('vendor', $("#vendorby").val());
                }

                if ($("#pageby").length && $("#pageby").val() && $("#pageby").val() !== 'undefined') {
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

    <style>
    /* ========================================
       ENHANCED PRODUCTS LISTING PAGE
       ======================================== */

    /* Products Header Section */
    .products-header-section {
        border: 1.5px solid #dee2e6;
        border-radius: var(--border-radius);
        transition: all 0.3s ease;
        background: #fff;
    }

    .products-header-section:hover {
        border-color: var(--primary-color);
        box-shadow: 0 4px 16px rgba(13, 110, 253, 0.1);
    }

    .products-count h5 {
        color: var(--dark-color);
        font-weight: 700;
        font-size: 1.1rem;
    }

    .products-count .badge {
        font-size: 1rem;
        padding: 0.5rem 0.75rem;
        font-weight: 700;
    }

    /* View Toggle Buttons */
    .view-toggle-buttons {
        background: #f8f9fa;
        padding: 0.25rem;
        border-radius: var(--border-radius-sm);
    }

    .view-btn {
        background: transparent;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: var(--border-radius-sm);
        color: #6c757d;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 1.1rem;
    }

    .view-btn:hover {
        background: rgba(13, 110, 253, 0.1);
        color: var(--primary-color);
    }

    .view-btn.active {
        background: var(--primary-color);
        color: #fff;
        box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
    }

    /* Filter Items */
    .filter-item {
        min-width: 180px;
    }

    .filter-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #495057;
        display: block;
    }

    .filter-item .form-select {
        border: 1.5px solid #dee2e6;
        border-radius: var(--border-radius-sm);
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .filter-item .form-select:hover {
        border-color: var(--primary-color);
    }

    .filter-item .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
    }

    /* Responsive for Filters */
    @media (max-width: 1200px) {
        .filter-item {
            min-width: 150px;
        }
    }

    @media (max-width: 991px) {
        .products-header-section .d-flex {
            flex-direction: column;
            align-items: flex-start !important;
        }

        .filter-item {
            min-width: 100%;
        }

        .products-count {
            width: 100%;
        }
    }

    @media (max-width: 767px) {
        .products-count h5 {
            font-size: 1rem;
        }

        .products-count .badge {
            font-size: 0.9rem;
            padding: 0.4rem 0.6rem;
        }

        .filter-label {
            font-size: 0.8rem;
        }

        .filter-item .form-select {
            font-size: 0.85rem;
        }
    }

    /* Sidebar Enhancement */
    .sidebar {
        background: #fff;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        box-shadow: var(--box-shadow-sm);
        position: sticky;
        top: 100px;
    }

    .sidebar .widget {
        margin-bottom: 2rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid var(--border-color);
    }

    .sidebar .widget:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .sidebar .widget-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--dark-color);
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid var(--primary-color);
    }

    /* Filter Lists */
    .sidebar .filter-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar .filter-list li {
        margin-bottom: 0.75rem;
    }

    .sidebar .filter-list li a,
    .sidebar .filter-list li label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #6c757d;
        text-decoration: none;
        transition: all var(--transition-fast);
        padding: 0.5rem;
        border-radius: var(--border-radius-sm);
    }

    .sidebar .filter-list li a:hover,
    .sidebar .filter-list li label:hover {
        color: var(--primary-color);
        background: rgba(13, 110, 253, 0.05);
        padding-left: 1rem;
    }

    .sidebar .filter-list li a.active {
        color: var(--primary-color);
        background: rgba(13, 110, 253, 0.1);
        font-weight: 600;
    }

    /* Price Slider */
    #slider-range {
        background: #e9ecef;
        border: none;
        border-radius: var(--border-radius-sm);
        height: 8px;
    }

    #slider-range .ui-slider-handle {
        background: var(--primary-color);
        border: none;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: var(--box-shadow-sm);
        top: -6px;
    }

    #slider-range .ui-slider-range {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    #amount {
        border: 1.5px solid var(--border-color);
        border-radius: var(--border-radius-sm);
        padding: 0.75rem 1rem;
        font-weight: 600;
        color: var(--primary-color);
        background: #f8f9fa;
        text-align: center;
    }

    /* Products Grid/List View */
    .products-header {
        background: #fff;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--box-shadow-sm);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .products-header .view-options {
        display: flex;
        gap: 0.5rem;
    }

    .products-header .view-options button {
        background: transparent;
        border: 1.5px solid var(--border-color);
        padding: 0.5rem 1rem;
        border-radius: var(--border-radius-sm);
        transition: all var(--transition-fast);
    }

    .products-header .view-options button.active,
    .products-header .view-options button:hover {
        background: var(--primary-color);
        border-color: var(--primary-color);
        color: #fff;
    }

    .products-header select {
        border: 1.5px solid var(--border-color);
        border-radius: var(--border-radius-sm);
        padding: 0.5rem 1rem;
        font-weight: 500;
        transition: all var(--transition-fast);
    }

    .products-header select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
    }

    /* Category Banner */
    .category-banner {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: var(--border-radius);
        padding: 3rem 2rem;
        margin-bottom: 2rem;
        box-shadow: var(--box-shadow);
        color: #fff;
        text-align: center;
    }

    .category-banner h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        color: #fff;
    }

    .category-banner .breadcrumb {
        background: transparent;
        margin: 0;
        padding: 0;
        justify-content: center;
    }

    .category-banner .breadcrumb-item a {
        color: rgba(255, 255, 255, 0.8);
    }

    .category-banner .breadcrumb-item.active {
        color: #fff;
    }

    /* No Products Found */
    .no-products {
        text-align: center;
        padding: 4rem 2rem;
        background: #f8f9fa;
        border-radius: var(--border-radius);
        margin: 2rem 0;
    }

    .no-products i {
        font-size: 5rem;
        color: #dee2e6;
        margin-bottom: 2rem;
    }

    .no-products h3 {
        color: #6c757d;
        margin-bottom: 1rem;
    }

    .no-products p {
        color: #adb5bd;
        margin-bottom: 2rem;
    }

    /* Active Filters Display */
    .active-filters {
        background: #fff;
        border-radius: var(--border-radius);
        padding: 1rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--box-shadow-sm);
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
    }

    .active-filters .filter-tag {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        border: 1px solid var(--primary-color);
        color: var(--primary-color);
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        font-size: 0.875rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .active-filters .filter-tag button {
        background: none;
        border: none;
        color: var(--primary-color);
        cursor: pointer;
        padding: 0;
        margin: 0;
    }

    .active-filters .clear-all {
        background: var(--danger-color);
        color: #fff;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: var(--border-radius-sm);
        font-weight: 600;
        transition: all var(--transition-fast);
    }

    .active-filters .clear-all:hover {
        background: #c82333;
        transform: translateY(-2px);
    }

    /* Responsive */
    @media (max-width: 991px) {
        .sidebar {
            position: relative;
            top: 0;
            margin-bottom: 2rem;
        }

        .category-banner h1 {
            font-size: 2rem;
        }
    }

    @media (max-width: 767px) {
        .products-header {
            padding: 1rem;
        }

        .category-banner {
            padding: 2rem 1rem;
        }

        .category-banner h1 {
            font-size: 1.5rem;
        }
    }
    </style>
@endsection
