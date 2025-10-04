<!-- mobile menu -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-top">
        <img src="{{ asset('assets/images/' . $gs->footer_logo) }}" alt="{{ $gs->title ?? 'Logo' }}">
        <button class="close-menu-btn" id="closeMobileMenu" aria-label="Close menu">
            <svg class="close" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                fill="none">
                <path d="M18 6L6 18M6 6L18 18" stroke="white" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </button>
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
{{--                            <li><a href="{{ route('front.blog') }}">@lang('BLOG')</a></li>--}}
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
                            @foreach ($categories->load('subs') as $category)
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
                                                class="position-relative bottom-12 {{ $isCategoryActive ? '' : 'collapsed' }}">
                                                <i class="fa-solid fa-plus"></i>
                                                <i class="fa-solid fa-minus"></i>
                                            </button>
                                        </div>

{{--                                        @foreach ($category->subs->take(2) as $subcategory)--}}
{{--                                            @php--}}
{{--                                                $isSubcategoryActive =--}}
{{--                                                    $isCategoryActive && Request::segment(3) === $subcategory->slug;--}}
{{--                                            @endphp--}}
{{--                                            <ul id="{{ $category->slug }}_level_2"--}}
{{--                                                class="accordion-collapse collapse ms-3 {{ $isCategoryActive ? 'show' : '' }}">--}}
{{--                                                <li class="">--}}
{{--                                                    <div class="d-flex justify-content-between align-items-lg-baseline">--}}
{{--                                                        <a href="{{ route('front.category', [$category->slug, $subcategory->slug]) }}"--}}
{{--                                                            class="{{ $isSubcategoryActive ? 'sidebar-active-color' : '' }} "--}}
{{--                                                            @if ($subcategory->childs->count() > 0) data-bs-toggle="collapse"--}}
{{--                                                                data-bs-target="#inner{{ $subcategory->slug }}_level_2_1"--}}
{{--                                                                aria-controls="inner{{ $subcategory->slug }}_level_2_1"--}}
{{--                                                                aria-expanded="{{ $isSubcategoryActive ? 'true' : 'false' }}"--}}
{{--                                                                class="{{ $isSubcategoryActive ? '' : 'collapsed' }}"--}}
{{--                                                                @endif>--}}
{{--                                                            {{ $subcategory->name }} 22--}}
{{--                                                        </a>--}}

{{--                                                        @if ($subcategory->childs->count() > 0)--}}
{{--                                                            <button data-bs-toggle="collapse"--}}
{{--                                                                data-bs-target="#inner{{ $subcategory->slug }}_level_2_1"--}}
{{--                                                                aria-controls="inner{{ $subcategory->slug }}_level_2_1"--}}
{{--                                                                aria-expanded="{{ $isSubcategoryActive ? 'true' : 'false' }}"--}}
{{--                                                                class="position-relative bottom-12 {{ $isSubcategoryActive ? '' : 'collapsed' }}">--}}
{{--                                                                <i class="fa-solid fa-plus"></i>--}}
{{--                                                                <i class="fa-solid fa-minus"></i>--}}
{{--                                                            </button>--}}
{{--                                                        @endif--}}
{{--                                                    </div>--}}

{{--                                                    @if ($subcategory->childs->count() > 0)--}}
{{--                                                        <ul id="inner{{ $subcategory->slug }}_level_2_1"--}}
{{--                                                            class="accordion-collapse collapse ms-3 {{ $isSubcategoryActive ? 'show' : '' }}">--}}
{{--                                                            @foreach ($subcategory->childs as $child)--}}
{{--                                                                @php--}}
{{--                                                                    $isChildActive =--}}
{{--                                                                        $isSubcategoryActive &&--}}
{{--                                                                        Request::segment(4) === $child->slug;--}}
{{--                                                                @endphp--}}
{{--                                                                <li>--}}
{{--                                                                    <a href="{{ route('front.category', [$category->slug, $subcategory->slug, $child->slug]) }}"--}}
{{--                                                                        class="{{ $isChildActive ? 'sidebar-active-color' : '' }}">--}}
{{--                                                                        {{ $child->name }}--}}
{{--                                                                    </a>--}}
{{--                                                                </li>--}}
{{--                                                            @endforeach--}}
{{--                                                        </ul>--}}
{{--                                                    @endif--}}
{{--                                                </li>--}}
{{--                                            </ul>--}}
{{--                                        @endforeach--}}

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
            </div>
        </div>
    </div>
</div>

<style>
/* ========================================
   ENHANCED MOBILE MENU STYLES - COMPLETE UX OVERHAUL
   ======================================== */

/* Mobile Menu Container */
.mobile-menu {
    position: fixed;
    top: 0;
    right: -100%;
    width: 320px;
    height: 100vh;
    background: linear-gradient(to bottom, #1a1a2e 0%, #16213e 100%);
    box-shadow: -4px 0 20px rgba(0,0,0,0.3);
    z-index: 9999;
    overflow-y: auto;
    overflow-x: hidden;
    transition: right 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.mobile-menu.active {
    right: 0;
}

/* Mobile Menu Top */
.mobile-menu-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 1.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    position: sticky;
    top: 0;
    z-index: 10;
}

.mobile-menu-top img {
    max-height: 40px;
    max-width: 150px;
    object-fit: contain;
    transition: transform 0.3s ease;
}

.mobile-menu-top img:hover {
    transform: scale(1.05);
}

.close-menu-btn {
    background: transparent;
    border: none;
    padding: 0.5rem;
    cursor: pointer;
    border-radius: 50%;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close-menu-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: rotate(90deg);
}

.close-menu-btn:active {
    transform: rotate(90deg) scale(0.9);
}

/* Tab Navigation */
.mobile-menu .nav {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 0;
    overflow: hidden;
    position: sticky;
    top: 76px;
    z-index: 9;
    backdrop-filter: blur(10px);
}

.mobile-menu .nav button {
    color: rgba(255, 255, 255, 0.7);
    background: transparent;
    border: none;
    padding: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    border-bottom: 3px solid transparent;
    font-size: 0.875rem;
}

.mobile-menu .nav button.active,
.mobile-menu .nav button:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.1);
    border-bottom-color: #ffc107;
}

.mobile-menu .nav button:active {
    transform: scale(0.98);
}

/* Accordion Menu */
.mobile-menu .accordion {
    list-style: none;
    padding: 0;
    margin: 0;
}

.mobile-menu .accordion > li {
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    animation: fadeInUp 0.4s ease backwards;
}

.mobile-menu .accordion > li:nth-child(1) { animation-delay: 0.1s; }
.mobile-menu .accordion > li:nth-child(2) { animation-delay: 0.15s; }
.mobile-menu .accordion > li:nth-child(3) { animation-delay: 0.2s; }
.mobile-menu .accordion > li:nth-child(4) { animation-delay: 0.25s; }
.mobile-menu .accordion > li:nth-child(5) { animation-delay: 0.3s; }

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.mobile-menu .accordion > li > a,
.mobile-menu .accordion > li > div > a {
    display: block;
    padding: 1rem 1.25rem;
    color: #fff;
    font-weight: 500;
    transition: all 0.3s ease;
    position: relative;
    text-decoration: none;
    font-size: 0.95rem;
}

.mobile-menu .accordion > li > a:hover,
.mobile-menu .accordion > li > a.sidebar-active-color,
.mobile-menu .accordion > li > div > a:hover,
.mobile-menu .accordion > li > div > a.sidebar-active-color {
    background: linear-gradient(to right, rgba(102, 126, 234, 0.25), transparent);
    padding-left: 1.75rem;
    color: #ffc107;
}

.mobile-menu .accordion > li > a::before,
.mobile-menu .accordion > li > div > a::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 0;
    background: linear-gradient(to bottom, #ffc107, #ff9800);
    transition: height 0.3s ease;
}

.mobile-menu .accordion > li > a:hover::before,
.mobile-menu .accordion > li > a.sidebar-active-color::before,
.mobile-menu .accordion > li > div > a:hover::before,
.mobile-menu .accordion > li > div > a.sidebar-active-color::before {
    height: 100%;
}

.mobile-menu .accordion > li > a:active,
.mobile-menu .accordion > li > div > a:active {
    transform: scale(0.98);
}

/* Nested Accordion */
.mobile-menu .accordion-collapse {
    background: rgba(0, 0, 0, 0.25);
    overflow: hidden;
}

.mobile-menu .accordion-collapse.show {
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
    }
    to {
        opacity: 1;
        max-height: 500px;
    }
}

.mobile-menu .accordion-collapse li a {
    display: block;
    padding: 0.75rem 1.25rem 0.75rem 2.5rem;
    color: rgba(255, 255, 255, 0.85);
    transition: all 0.3s ease;
    text-decoration: none;
    font-size: 0.9rem;
    position: relative;
}

.mobile-menu .accordion-collapse li a::before {
    content: '›';
    position: absolute;
    left: 1.5rem;
    color: rgba(255, 255, 255, 0.4);
    transition: all 0.3s ease;
}

.mobile-menu .accordion-collapse li a:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.12);
    padding-left: 2.75rem;
}

.mobile-menu .accordion-collapse li a:hover::before {
    left: 1.75rem;
    color: #ffc107;
}

/* Collapse Toggle Buttons */
.mobile-menu button[data-bs-toggle="collapse"] {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.7);
    padding: 0.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    border-radius: 6px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mobile-menu button[data-bs-toggle="collapse"]:hover {
    color: #ffc107;
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 193, 7, 0.3);
    transform: scale(1.05);
}

.mobile-menu button[data-bs-toggle="collapse"]:active {
    transform: scale(0.95);
}

.mobile-menu button[data-bs-toggle="collapse"] .fa-minus {
    display: none;
}

.mobile-menu button[data-bs-toggle="collapse"]:not(.collapsed) .fa-plus {
    display: none;
}

.mobile-menu button[data-bs-toggle="collapse"]:not(.collapsed) .fa-minus {
    display: inline-block;
}

.mobile-menu button[data-bs-toggle="collapse"]:not(.collapsed) {
    background: rgba(255, 193, 7, 0.15);
    border-color: rgba(255, 193, 7, 0.4);
    color: #ffc107;
    transform: rotate(180deg);
}

/* Auth Action Buttons */
.mobile-menu .auth-actions-btn {
    padding: 1.5rem 1.25rem 2rem;
    margin-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.mobile-menu .auth-actions-btn .template-btn {
    display: block;
    width: 100%;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    text-align: center;
    font-weight: 600;
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    text-decoration: none;
    position: relative;
    overflow: hidden;
    font-size: 0.95rem;
}

.mobile-menu .auth-actions-btn .template-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s ease;
}

.mobile-menu .auth-actions-btn .template-btn:hover::before {
    left: 100%;
}

.mobile-menu .auth-actions-btn .template-btn:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.mobile-menu .auth-actions-btn .template-btn:active {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

/* Mobile Menu Scrollbar */
.mobile-menu::-webkit-scrollbar {
    width: 8px;
}

.mobile-menu::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

.mobile-menu::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, rgba(102, 126, 234, 0.5), rgba(118, 75, 162, 0.5));
    border-radius: 10px;
    border: 2px solid transparent;
    background-clip: padding-box;
}

.mobile-menu::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, rgba(102, 126, 234, 0.7), rgba(118, 75, 162, 0.7));
    background-clip: padding-box;
}

/* RTL Support */
[dir="rtl"] .mobile-menu {
    right: auto;
    left: -100%;
}

[dir="rtl"] .mobile-menu.active {
    left: 0;
    right: auto;
}

[dir="rtl"] .mobile-menu .accordion > li > a::before,
[dir="rtl"] .mobile-menu .accordion > li > div > a::before {
    left: auto;
    right: 0;
}

[dir="rtl"] .mobile-menu .accordion > li > a:hover,
[dir="rtl"] .mobile-menu .accordion > li > div > a:hover {
    padding-left: 1.25rem;
    padding-right: 1.75rem;
}

[dir="rtl"] .mobile-menu .accordion-collapse li a {
    padding-left: 1.25rem;
    padding-right: 2.5rem;
}

[dir="rtl"] .mobile-menu .accordion-collapse li a::before {
    content: '‹';
    left: auto;
    right: 1.5rem;
}

[dir="rtl"] .mobile-menu .accordion-collapse li a:hover {
    padding-left: 1.25rem;
    padding-right: 2.75rem;
}

[dir="rtl"] .mobile-menu .accordion-collapse li a:hover::before {
    left: auto;
    right: 1.75rem;
}

/* Mobile-only display */
@media (min-width: 992px) {
    .mobile-menu {
        display: none !important;
    }
}

/* Responsive adjustments */
@media (max-width: 360px) {
    .mobile-menu {
        width: 100%;
    }

    .mobile-menu-top {
        padding: 1rem;
    }

    .mobile-menu .nav button {
        font-size: 0.8rem;
        padding: 0.875rem;
    }
}
</style>

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
                                            href="javascript:;">{{ $category->localized_name }}</a>
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

<style>
/* ========================================
   ENHANCED SEARCH BAR STYLES
   ======================================== */

/* Search Bar Enhancement */
.search-bar {
    background: #fff;
    box-shadow: var(--box-shadow-lg);
    padding: 1.5rem 0;
}

.search-bar .input__group {
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    border: 2px solid var(--primary-color);
    background: #fff;
    transition: all var(--transition);
}

.search-bar .input__group:focus-within {
    border-color: #0b5ed7;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
}

.search-bar .form__control {
    border: none;
    padding: 1rem 1.25rem;
    font-size: 1rem;
    font-weight: 500;
}

.search-bar .form__control:focus {
    box-shadow: none;
    border: none;
}

.search-bar .search-category-dropdown {
    background: #f8f9fa;
    border: none;
    color: var(--dark-color);
    font-weight: 500;
    padding: 1rem 1.25rem;
    border-radius: 0;
    transition: all var(--transition);
}

.search-bar .search-category-dropdown:hover {
    background: #e9ecef;
}

.search-bar .dropdown-menu {
    border-radius: var(--border-radius-sm);
    box-shadow: var(--box-shadow);
    border: none;
    max-height: 300px;
    overflow-y: auto;
}

.search-bar .dropdown__item {
    padding: 0.75rem 1.25rem;
    transition: all var(--transition-fast);
}

.search-bar .dropdown__item:hover {
    background: linear-gradient(to right, rgba(13, 110, 253, 0.1), transparent);
    color: var(--primary-color);
    padding-left: 1.5rem;
}

.search-bar .search-icn {
    background: var(--primary-color);
    border: none;
    padding: 1rem 1.5rem;
    border-radius: 0;
    transition: all var(--transition);
}

.search-bar .search-icn:hover {
    background: #0b5ed7;
    transform: scale(1.05);
}

.search-separator {
    width: 1px;
    background: var(--border-color);
    display: inline-block;
    height: 100%;
}
</style>
