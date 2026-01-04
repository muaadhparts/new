<div class="col-lg-3">
    <div id="sidebar" class="widget-title-bordered-full">
        <form id="catalogForm"
            action="{{ route('front.category', [Request::route('category'), Request::route('subcategory'), Request::route('childcategory')]) }}"
            method="GET">

            <div id="woocommerce_product_categories-4"
                class="widget woocommerce widget_product_categories widget-toggle">
                <h2 class="widget-title">{{ __('CatalogItem categories') }}</h2>
                <ul class="catalogItem-categories">
                    @foreach (App\Models\Category::where('status', 1)->get() as $category)

                        <li class="cat-item cat-parent">
                            <a href="{{route('front.category', $category->slug)}}{{!empty(request()->input('search')) ? '?search=' . request()->input('search') : ''}}"
                                class="category-link" id="cat">{{ $category->name }} <span class="count"></span></a>

                            @if($category->subs->count() > 0)
                                <span class="has-child"></span>
                                <ul class="children">
                                    @foreach (App\Models\Subcategory::where('category_id', $category->id)->get() as $subcategory)
                                        <li class="cat-item cat-parent">
                                            <a href="{{route('front.category', [$category->slug, $subcategory->slug])}}{{!empty(request()->input('search')) ? '?search=' . request()->input('search') : ''}}"
                                                class="category-link {{ isset($subcat) ? ($subcat->id == $subcategory->id ? 'active' : '') : '' }}">{{$subcategory->name}}
                                                <span class="count"></span></a>


                                            @if($subcategory->childs->count() != 0)
                                                <span class="has-child"></span>
                                                <ul class="children">
                                                    @foreach (DB::table('childcategories')->where('subcategory_id', $subcategory->id)->get() as $key => $childelement)
                                                        <li class="cat-item ">
                                                            <a href="{{route('front.category', [$category->slug, $subcategory->slug, $childelement->slug])}}{{!empty(request()->input('search')) ? '?search=' . request()->input('search') : ''}}"
                                                                class="category-link {{ isset($childcat) ? ($childcat->id == $childelement->id ? 'active' : '') : '' }}">
                                                                {{$childelement->name}} <span class="count"></span></a>
                                                        </li>
                                                    @endforeach
                                                </ul>

                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            <div id="bigbazar-price-filter-list-1"
                class="widget bigbazar_widget_price_filter_list widget_layered_nav widget-toggle">
                <h2 class="widget-title">{{ __('Filter by Price') }}</h2>
                <ul class="price-filter-list">
                    <div class="price-range-block">
                        <div id="slider-range" class="price-filter-range" name="rangeInput"></div>
                        <div class="livecount">
                            <input type="number" name="min" oninput="" id="min_price" class="price-range-field" />
                            <span>
                                {{ __('To') }}
                            </span>
                            <input type="number" name="max" oninput="" id="max_price" class="price-range-field" />
                        </div>
                    </div>

                    <button class="filter-btn btn btn-primary mt-3 mb-4" type="submit">{{ __('Search') }}</button>
                </ul>
            </div>

        </form>


        @if ((!empty($cat) && !empty(json_decode($cat->attributes, true))) || (!empty($subcat) && !empty(json_decode($subcat->attributes, true))) || (!empty($childcat) && !empty(json_decode($childcat->attributes, true))))

            <form id="attrForm"
                action="{{ route('front.category', [Request::route('category'), Request::route('subcategory'), Request::route('childcategory')]) }}"
                method="post">

                @if (!empty($cat) && !empty(json_decode($cat->attributes, true)))
                    @foreach ($cat->attributes as $key => $attr)

                        <div id="bigbazar-attributes-filter-{{$attr->name}}"
                            class="widget woocommerce bigbazar-attributes-filter widget_layered_nav widget-toggle">
                            <h2 class="widget-title">{{$attr->name}}</h2>
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
                            <h2 class="widget-title">{{$attr->name}}</h2>
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
                            class="widget woocommerce bigbazar-attributes-filter widget_layered_nav widget-toggle">
                            <h2 class="widget-title">{{$attr->name}}</h2>
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
        <div class="row">
            <div class="col-12">
                <div class="section-head border-bottom d-flex justify-content-between align-items-center">
                    <div class="d-flex section-head-side-title">
                        <h5 class="font-700 text-dark mb-0">{{ __('Recent CatalogItem') }}</h5>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div
                    class="catalogItem-style-2 owl-carousel owl-nav-hover-primary nav-top-right single-carousel dot-disable catalogItem-list e-bg-white">

                    @foreach (array_chunk($latest_products->toArray(), 3) as $item)

                        <div class="item">
                            <div class="row row-cols-1">
                                @foreach ($item as $cartItem)
                                    @php
                                        // ✅ N+1 FIX: Load catalog item once and reuse
                                        $vCatalogProdObj = \App\Models\CatalogItem::with(['merchantItems' => fn($q) => $q->where('status', 1)->with('user')->orderBy('price')])->find($cartItem['id']);

                                        // Use best_merchant_item from eager-loaded data
                                        $vCatalogMerchant = $vCatalogProdObj?->best_merchant_item;

                                        $vCatalogProdUrl = $vCatalogMerchant && isset($cartItem['slug'])
                                            ? route('front.catalog-item', ['slug' => $cartItem['slug'], 'merchant_id' => $vCatalogMerchant->user_id, 'merchant_item_id' => $vCatalogMerchant->id])
                                            : (isset($cartItem['slug']) ? route('front.catalog-item.legacy', $cartItem['slug']) : '#');
                                    @endphp

                                    <div class="col mb-1">
                                        <div class="catalogItem type-catalogItem">
                                            <div class="catalogItem-wrapper">
                                                <div class="catalog-item-image">
                                                    <a href="{{ $vCatalogProdUrl }}"
                                                        class="woocommerce-LoopProduct-link"><img
                                                            src="{{ filter_var($cartItem['photo'] ?? '', FILTER_VALIDATE_URL) ? $cartItem['photo'] : (($cartItem['photo'] ?? null) ? \Illuminate\Support\Facades\Storage::url($cartItem['photo']) : asset('assets/images/noimage.png')) }}"
                                                            alt="CatalogItem Image"></a>
                                                    <div class="favorite-view">
                                                        <div class="quickview-button">
                                                            <a class="quickview-btn"
                                                                href="{{ $vCatalogProdUrl }}"
                                                                data-bs-toggle="tooltip" data-bs-placement="top" title=""
                                                                data-bs-original-title="Quick View"
                                                                aria-label="Quick View">{{ __('Quick View') }}</a>
                                                        </div>
                                                        <div class="favorite-button">
                                                            <a class="add_to_favorite" href="#" data-bs-toggle="tooltip"
                                                                data-bs-placement="top" title=""
                                                                data-bs-original-title="Add to Favorites"
                                                                aria-label="Add to Favorites">{{ __('Favorites') }}</a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="catalogItem-info">
                                                    {{-- ✅ N+1 FIX: Reuse $vCatalogProdObj instead of querying again --}}
                                                    <h3 class="catalogItem-title"><a
                                                            href="{{ $vCatalogProdUrl }}">{{ $vCatalogProdObj?->showName() ?? '' }}</a>
                                                    </h3>
                                                    <div class="catalogItem-price">
                                                        <div class="price">
                                                            <ins>{{ $vCatalogProdObj?->showPrice() ?? '' }}</ins>
                                                            <del>{{ $vCatalogProdObj?->showPreviousPrice() ?? '' }}</del>
                                                        </div>
                                                        <div class="on-sale">
                                                            <span>{{ $vCatalogProdObj ? round($vCatalogProdObj->offPercentage()) : 0 }}</span><span>%
                                                                off</span></div>
                                                    </div>
                                                    <div class="shipping-feed-back">
                                                        <div class="star-rating">
                                                            <div class="rating-wrap">
                                                                <p><i class="fas fa-star"></i><span>
                                                                        {{ App\Models\CatalogTestimonial::scorePercentage($cartItem['id']) }}</span></p>
                                                            </div>
                                                            <div class="rating-counts-wrap">
                                                                <p>({{ App\Models\CatalogTestimonial::reviewCount($cartItem['id']) }})</p>
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