{{-- استخدام $brands من GlobalDataMiddleware --}}
<div class="col-xl-3">
    <div id="sidebar" class="widget-name-bordered-full">
        <div class="dashbaord-sidebar-close d-xl-none">
            <i class="fas fa-times"></i>
        </div>
        <form id="catalogForm"
            action="{{ route('front.catalog', [Request::route('brand'), Request::route('catalog'), Request::route('cat1')]) }}"
            method="GET">

            <div id="woocommerce_product_categories-4"
                class="widget woocommerce widget_product_categories widget-toggle">
                <h2 class="widget-name">{{ __('Brands') }}</h2>
                <ul class="catalogItem-categories">
                    @foreach ($brands as $brand)
                        <li class="cat-item cat-parent">
                            <a href="{{route('front.catalog', $brand->slug)}}{{!empty(request()->input('search')) ? '?search=' . request()->input('search') : ''}}"
                                class="category-link" id="cat">{{ app()->getLocale() == 'ar' ? ($brand->name_ar ?: $brand->name) : $brand->name }} <span class="count"></span></a>

                            @if($brand->catalogs && $brand->catalogs->count() > 0)
                                <span class="has-child"></span>
                                <ul class="children">
                                    @foreach ($brand->catalogs as $catalog)
                                        <li class="cat-item cat-parent">
                                            <a href="{{route('front.catalog', [$brand->slug, $catalog->slug])}}{{!empty(request()->input('search')) ? '?search=' . request()->input('search') : ''}}"
                                                class="category-link">{{ app()->getLocale() == 'ar' ? ($catalog->name_ar ?: $catalog->name) : $catalog->name }}
                                                <span class="count"></span></a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

        </form>


        @if ((!empty($cat) && !empty(json_decode($cat->attributes, true))) || (!empty($subcat) && !empty(json_decode($subcat->attributes, true))) || (!empty($childcat) && !empty(json_decode($childcat->attributes, true))))

            <form id="attrForm"
                action="{{ route('front.catalog', [Request::route('category'), Request::route('subcategory'), Request::route('childcategory')]) }}"
                method="post">

                @if (!empty($cat) && !empty(json_decode($cat->attributes, true)))
                    @foreach ($cat->attributes as $key => $attr)

                        <div id="bigbazar-attributes-filter-{{$attr->name}}"
                            class="widget woocommerce bigbazar-attributes-filter widget_layered_nav widget-toggle">
                            <h2 class="widget-name">{{$attr->name}}</h2>
                            <ul class="swatch-filter-pa_color">
                                @if (!empty($attr->specValues))
                                    @foreach ($attr->specValues as $key => $option)
                                        <div class="form-check ml-0 pl-0">
                                            <input name="{{$attr->input_name}}[]" class="form-check-input attribute-input" type="checkbox"
                                                id="{{$attr->input_name}}{{$option->id}}" value="{{$option->name}}">
                                            <label class="form-check-label"
                                                for="{{$attr->input_name}}{{$option->id}}">{{$option->name}}</label>
                                        </div>
                                    @endforeach
                                @endif
                            </ul>
                        </div>
                    @endforeach
                @endif

                @if (!empty($subcat) && !empty(json_decode($subcat->attributes, true)))
                    @foreach ($subcat->attributes as $key => $attr)
                        <div id="bigbazar-attributes-filter-{{$attr->name}}"
                            class="widget woocommerce bigbazar-attributes-filter widget_layered_nav widget-toggle">
                            <h2 class="widget-name">{{$attr->name}}</h2>
                            <ul class="swatch-filter-pa_color">
                                @if (!empty($attr->specValues))
                                    @foreach ($attr->specValues as $key => $option)
                                        <div class="form-check ml-0 pl-0">
                                            <input name="{{$attr->input_name}}[]" class="form-check-input attribute-input" type="checkbox"
                                                id="{{$attr->input_name}}{{$option->id}}" value="{{$option->name}}">
                                            <label class="form-check-label"
                                                for="{{$attr->input_name}}{{$option->id}}">{{$option->name}}</label>
                                        </div>
                                    @endforeach
                                @endif
                            </ul>
                        </div>
                    @endforeach
                @endif

                @if (!empty($childcat) && !empty(json_decode($childcat->attributes, true)))
                    @foreach ($childcat->attributes as $key => $attr)
                        <div id="bigbazar-attributes-filter-{{$attr->name}}"
                            class="widget woocommerce bigbazar-attributes-filter widget_layered_nav widget-toggle px-3">
                            <h2 class="widget-name">{{$attr->name}}</h2>
                            <ul class="swatch-filter-pa_color">
                                @if (!empty($attr->specValues))
                                    @foreach ($attr->specValues as $key => $option)
                                        <div class="form-check ml-0 pl-0">
                                            <input name="{{$attr->input_name}}[]" class="form-check-input attribute-input" type="checkbox"
                                                id="{{$attr->input_name}}{{$option->id}}" value="{{$option->name}}">
                                            <label class="form-check-label"
                                                for="{{$attr->input_name}}{{$option->id}}">{{$option->name}}</label>
                                        </div>
                                    @endforeach
                                @endif
                            </ul>
                        </div>
                    @endforeach
                @endif

            </form>
        @endif
        <div class="row mx-0">
            <div class="col-12">
                <div class="section-head border-bottom d-flex justify-content-between align-items-center">
                    <div class="d-flex section-head-side-name">
                        <h5 class="font-700 text-dark mb-0">{{ __('Recent CatalogItem') }}</h5>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div
                    class="catalogItem-style-2 owl-carousel owl-nav-hover-primary nav-top-right single-carousel dot-disable catalogItem-list e-bg-white">

                    @foreach ($latest_products as $item)

                        <div class="item">
                            <div class="row row-cols-1">

                                @foreach ($item as $cartItem)
                                    @php
                                        // ✅ N+1 FIX: Load catalog item with eager-loaded merchantItems
                                        $catalogProdObj = \App\Models\CatalogItem::with(['merchantItems' => fn($q) => $q->where('status', 1)->with('user')->orderBy('price')])->find($cartItem['id']);

                                        // Use best_merchant_item from eager-loaded data
                                        $catalogMerchant = $catalogProdObj?->best_merchant_item;

                                        $catalogProdUrl = $catalogMerchant && isset($cartItem['slug'])
                                            ? route('front.catalog-item', ['slug' => $cartItem['slug'], 'merchant_id' => $catalogMerchant->user_id, 'merchant_item_id' => $catalogMerchant->id])
                                            : (isset($cartItem['slug']) ? route('front.catalog-item.legacy', $cartItem['slug']) : '#');
                                    @endphp

                                    <div class="col mb-1">
                                        <div class="catalogItem type-catalogItem">
                                            <div class="catalogItem-wrapper">
                                                <div class="catalog-item-image">
                                                    <a href="{{ $catalogProdUrl }}"
                                                        class="woocommerce-LoopProduct-link"><img
                                                            src="{{ filter_var($cartItem['photo'] ?? '', FILTER_VALIDATE_URL) ? $cartItem['photo'] : (($cartItem['photo'] ?? null) ? \Illuminate\Support\Facades\Storage::url($cartItem['photo']) : asset('assets/images/noimage.png')) }}"
                                                            alt="CatalogItem Image"></a>
                                                    <div class="favorite-view">
                                                        <div class="quickview-button">
                                                            <a class="quickview-btn"
                                                                href="{{ $catalogProdUrl }}"
                                                                data-bs-toggle="tooltip" data-bs-placement="top" name=""
                                                                data-bs-original-name="Quick View"
                                                                aria-label="Quick View">{{ __('Quick View') }}</a>
                                                        </div>
                                                        <div class="favorite-button">
                                                            <a class="add_to_favorite" href="#" data-bs-toggle="tooltip"
                                                                data-bs-placement="top" name="{{ __('Favorites') }}"
                                                                data-bs-original-name="Add to Favorites"
                                                                aria-label="Add to Favorites">{{ __('Favorites') }}</a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="catalogItem-info">
                                                    <h3 class="catalogItem-name"><a
                                                            href="{{ $catalogProdUrl }}">{{ $cartItem['name']  }}</a>
                                                    </h3>
                                                    <div class="catalogItem-price">
                                                        <div class="price">
                                                            <ins>{{ PriceHelper::showPrice($cartItem['price'])  }}</ins>
                                                            <del>{{ PriceHelper::showPrice($cartItem['previous_price'])  }}</del>
                                                        </div>
                                                        <div class="on-sale">
                                                            <span>{{ round($cartItem->offPercentage())}}</span><span>% off</span>
                                                        </div>
                                                    </div>
                                                    <div class="shipping-feed-back">
                                                        <div class="star-rating">
                                                            <div class="rating-wrap">
                                                                <p><i class="fas fa-star"></i><span>
                                                                        {{ number_format($cartItem->catalog_reviews_avg_rating, 1) }}</span>
                                                                </p>
                                                            </div>
                                                            <div class="rating-counts-wrap">
                                                                <p>({{ $cartItem->catalog_reviews_count }})</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>