<!-- mobile menu -->
<div class="mobile-menu">
    <div class="mobile-menu-top">
        <img src="{{ asset('assets/images/' . $gs->footer_logo) }}" alt="">
        <svg class="close" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
            fill="none">
            <path d="M18 6L6 18M6 6L18 18" stroke="white" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" />
        </svg>
    </div>
    <nav>
        <div class="nav justify-content-between pt-24" id="nav-tab" role="tablist">
            <button class="flex-grow-1 state-left-btn active active-tab-btn" id="main-menu-tab" data-bs-toggle="tab"
                data-bs-target="#main-menu" type="button" role="tab" aria-controls="main-menu"
                aria-selected="true">@lang('MAIN MENU')</button>

            <button class="flex-grow-1 state-right-btn active-tab-btn" id="categories-tab" data-bs-toggle="tab"
                data-bs-target="#categories" type="button" role="tab" aria-controls="categories"
                aria-selected="false">@lang('CATEGORIES')</button>
        </div>
    </nav>

    <div class="tab-content " id="nav-tabContent1">
        <div class="tab-pane fade show active table-responsive tb-tb" id="main-menu" role="tabpanel"
            aria-labelledby="main-menu-tab" style="color: white;">

            <div class="mobile-menu-widget">
                <div class="single-product-widget">
                    <!-- <h5 class="widget-title">Product categories</h5> -->
                    <div class="product-cat-widget">
                        <ul class="accordion">
                            <!-- main list -->
                            <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                            <li><a href="{{ route('front.category') }}">@lang('Product')</a></li>
                            <li>
                                <a href="#" data-bs-toggle="collapse" data-bs-target="#child_level_1"
                                    aria-controls="child_level_1" aria-expanded="false" class="collapsed">
                                    @lang('Pages')
                                </a>

                                <ul id="child_level_1" class="accordion-collapse collapse ms-3">
                                    @foreach ($pages->where('header', '=', 1) as $data)
                                        <li>
                                            <a href="{{ route('front.vendor', $data->slug) }}">{{ $data->title }}</a>
                                        </li>
                                    @endforeach

                                </ul>
                            </li>
                            <li><a href="{{ route('front.blog') }}">@lang('BLOG')</a></li>
                            <li><a href="{{ route('front.faq') }}">@lang('FAQ')</a></li>
                            <li><a href="{{ route('front.contact') }}">@lang('CONTACT')</a></li>

                        </ul>

                        <div class="auth-actions-btn gap-4 d-flex flex-column">

                            {{-- Vendor Panel or Vendor Login --}}
                            @if (Auth::guard('web')->check() && Auth::guard('web')->user()->is_vendor == 2)
                                <a class="template-btn" href="{{ route('vendor.dashboard') }}">@lang('Vendor Panel')</a>
                            @elseif (!Auth::guard('web')->check() && !Auth::guard('rider')->check())
                                <a class="template-btn" href="{{ route('vendor.login') }}">@lang('Vendor Login')</a>
                            @endif

                            {{-- Rider Dashboard or Rider Login --}}
                            @if (Auth::guard('rider')->check())
                                <a class="template-btn" href="{{ route('rider-dashboard') }}">@lang('Rider Dashboard')</a>
                            @elseif (!Auth::guard('web')->check() && !Auth::guard('rider')->check())
                                <a class="template-btn" href="{{ route('rider.login') }}">@lang('Rider Login')</a>
                            @endif

                            {{-- User Dashboard or User Login --}}
                            @if (Auth::guard('web')->check() && Auth::guard('web')->user()->is_vendor != 2)
                                <a class="template-btn" href="{{ route('user-dashboard') }}">@lang('User Dashboard')</a>
                            @elseif (!Auth::guard('web')->check() && !Auth::guard('rider')->check())
                                <a class="template-btn" href="{{ route('user.login') }}">@lang('User Login')</a>
                            @endif

                        </div>



                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="tab-content " id="nav-tabContent3">
        <div class="tab-pane fade table-responsive tb-tb" id="categories" role="tabpanel"
            aria-labelledby="categories-tab" style="color: white;">

            <div class="mobile-menu-widget">
                <div class="single-product-widget">
                    <!-- <h5 class="widget-title">Product categories</h5> -->
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
                                                {{ $category->name }}
                                            </a>

                                            <button data-bs-toggle="collapse"
                                                data-bs-target="#{{ $category->slug }}_level_2"
                                                aria-controls="{{ $category->slug }}_level_2"
                                                aria-expanded="{{ $isCategoryActive ? 'true' : 'false' }}"
                                                class="position-relative bottom-12 {{ $isCategoryActive ? '' : 'collapsed' }}">
                                                <i class="fa-solid fa-plus"></i>
                                                <i class="fa-solid fa-minus"></i>
                                            </button>
                                        </div>

                                        @foreach ($category->subs as $subcategory)
                                            @php
                                                $isSubcategoryActive =
                                                    $isCategoryActive && Request::segment(3) === $subcategory->slug;
                                            @endphp
                                            <ul id="{{ $category->slug }}_level_2"
                                                class="accordion-collapse collapse ms-3 {{ $isCategoryActive ? 'show' : '' }}">
                                                <li class="">
                                                    <div class="d-flex justify-content-between align-items-lg-baseline">
                                                        <a href="{{ route('front.category', [$category->slug, $subcategory->slug]) }}"
                                                            class="{{ $isSubcategoryActive ? 'sidebar-active-color' : '' }} "
                                                            @if ($subcategory->childs->count() > 0) data-bs-toggle="collapse"
                                        data-bs-target="#inner{{ $subcategory->slug }}_level_2_1"
                                        aria-controls="inner{{ $subcategory->slug }}_level_2_1"
                                        aria-expanded="{{ $isSubcategoryActive ? 'true' : 'false' }}"
                                        class="{{ $isSubcategoryActive ? '' : 'collapsed' }}" @endif>
                                                            {{ $subcategory->name }}
                                                        </a>

                                                        @if ($subcategory->childs->count() > 0)
                                                            <button data-bs-toggle="collapse"
                                                                data-bs-target="#inner{{ $subcategory->slug }}_level_2_1"
                                                                aria-controls="inner{{ $subcategory->slug }}_level_2_1"
                                                                aria-expanded="{{ $isSubcategoryActive ? 'true' : 'false' }}"
                                                                class="position-relative bottom-12 {{ $isSubcategoryActive ? '' : 'collapsed' }}">
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
                                                                        {{ $child->name }}
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
                                            {{ $category->name }}
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- search bar -->
<div class="search-bar" id="searchBar">
    <div class="container">
        <div class="row">
            <div class="col">
                <form class="search-form"
                    action="{{ route('front.category', [Request::route('category'), Request::route('subcategory'), Request::route('childcategory')]) }}">

                    @if (!empty(request()->input('sort')))
                        <input type="hidden" name="sort" value="{{ request()->input('sort') }}">
                    @endif
                    @if (!empty(request()->input('minprice')))
                        <input type="hidden" name="minprice" value="{{ request()->input('minprice') }}">
                    @endif
                    @if (!empty(request()->input('maxprice')))
                        <input type="hidden" name="maxprice" value="{{ request()->input('maxprice') }}">
                    @endif

                    <div class="input-group input__group">
                        <input type="text" class="form-control form__control" name="search"
                            placeholder="@lang('Search Any Product Here')">
                        <div class="input-group-append">
                            <span class="search-separator"></span>
                            <button class="dropdown-toggle btn btn-secondary search-category-dropdown" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                @lang('All Categories')
                            </button>
                            <ul class="dropdown-menu">
                                @foreach ($categories as $category)
                                    <li>
                                        <a class="dropdown-item dropdown__item"
                                            href="javascript:;">{{ $category->name }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>


                        <div class="input-group-append">
                            <button class="btn btn-primary search-icn" type="submit">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none">
                                    <path
                                        d="M21 21L17.5001 17.5M20 11.5C20 16.1944 16.1944 20 11.5 20C6.80558 20 3 16.1944 3 11.5C3 6.80558 6.80558 3 11.5 3C16.1944 3 20 6.80558 20 11.5Z"
                                        stroke="white" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
