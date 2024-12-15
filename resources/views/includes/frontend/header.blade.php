<header class="header-section position-relative z-2 header-stikcy">
    <div class="info-bar d-nonee d-md-blockk">
        <div class="container custom-containerr">
            <div class="info-row d-flex">
                <div class="info-left">
                    <ul class="wows align-items-center">
                        <li><a href="tel:+1(234)567-8901">
                                @lang('Contact & Support'): {{ $ps->phone }}</a>
                        </li>
                    </ul>
                </div>
                <div class="info-right">
                    <ul class="d-flex wows align-items-center">

                        @if (Auth::guard('web')->check() && Auth::guard('web')->user()->is_vendor == 2)
                            <li class="d-none d-lg-block"><a class="border px-3 py-1"
                                    href="{{ route('vendor.dashboard') }}">{{ __('Vendor Panel') }}</a>
                            </li>
                        @else
                            <li class="d-none d-lg-block">
                                <a href="{{ route('vendor.login') }}" class="info-bar-btn">
                                    {{ __('Vendor Login') }}
                                </a>
                            </li>
                        @endif

                        @if (!Auth::guard('rider')->check())
                            <li class="d-none d-lg-block"><a href="{{ route('rider.login') }}" class="info-bar-btn">
                                    @lang('Rider Login')
                                </a>
                            </li>
                        @endif



                        <li class="d-none d-md-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" width="2" height="21" viewBox="0 0 2 21"
                                fill="none">
                                <path d="M1 0.5V20.5" stroke="white" stroke-opacity="0.8" />
                            </svg>
                        </li>

                        <li class="d-flex gap-2 align-items-center">
                            <svg  xmlns="http://www.w3.org/2000/svg" width="20" height="21" viewBox="0 0 20 21"
                                fill="none">
                                <path
                                    d="M9.99935 2.16669C12.4993 3.83335 13.2683 7.41005 13.3327 10.5C13.2683 13.59 12.4994 17.1667 9.99935 18.8334M9.99935 2.16669C7.49935 3.83335 6.73039 7.41005 6.66602 10.5C6.73039 13.59 7.49935 17.1667 9.99935 18.8334M9.99935 2.16669C5.39698 2.16669 1.66602 5.89765 1.66602 10.5M9.99935 2.16669C14.6017 2.16669 18.3327 5.89765 18.3327 10.5M9.99935 18.8334C14.6017 18.8334 18.3327 15.1024 18.3327 10.5M9.99935 18.8334C5.39698 18.8334 1.66602 15.1024 1.66602 10.5M18.3327 10.5C16.666 13 13.0893 13.769 9.99935 13.8334C6.90938 13.769 3.33268 13 1.66602 10.5M18.3327 10.5C16.666 8.00002 13.0893 7.23106 9.99935 7.16669C6.90938 7.23106 3.33268 8.00002 1.66602 10.5"
                                    stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>




                            <div class="dropdown">
                                <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    @lang('English')
                                </button>
                                <ul class="dropdown-menu">
                                    @foreach ($languges as $language)
                                        <li>
                                            <a class="dropdown-item dropdown__item {{ Session::has('language')
                                                ? (Session::get('language') == $language->id
                                                    ? 'active'
                                                    : '')
                                                : ($languges->where('is_default', '=', 1)->first()->id == $language->id
                                                    ? 'active'
                                                    : '') }}"
                                                href="{{ route('front.language', $language->id) }}">{{ $language->language }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </li>


                        <li >
                            <svg xmlns="http://www.w3.org/2000/svg" width="2" height="21" viewBox="0 0 2 21"
                                fill="none">
                                <path d="M1 0.5V20.5" stroke="white" stroke-opacity="0.8" />
                            </svg>
                        </li>

                        @if ($gs->is_currency == 1)


                            <li class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="21"
                                    viewBox="0 0 20 21" fill="none">
                                    <path
                                        d="M7.08268 12.7222C7.08268 13.7961 7.95324 14.6667 9.02713 14.6667H10.8327C11.9833 14.6667 12.916 13.7339 12.916 12.5834C12.916 11.4328 11.9833 10.5 10.8327 10.5H9.16602C8.01542 10.5 7.08268 9.56728 7.08268 8.41669C7.08268 7.26609 8.01542 6.33335 9.16602 6.33335H10.9716C12.0455 6.33335 12.916 7.20391 12.916 8.2778M9.99935 5.08335V6.33335M9.99935 14.6667V15.9167M18.3327 10.5C18.3327 15.1024 14.6017 18.8334 9.99935 18.8334C5.39698 18.8334 1.66602 15.1024 1.66602 10.5C1.66602 5.89765 5.39698 2.16669 9.99935 2.16669C14.6017 2.16669 18.3327 5.89765 18.3327 10.5Z"
                                        stroke="white" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>



                                <div class="dropdown">
                                    <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        {{ Session::has('currency')
                                            ? $currencies->where('id', '=', Session::get('currency'))->first()->name
                                            : DB::table('currencies')->where('is_default', '=', 1)->first()->name }}
                                    </button>
                                    <ul class="dropdown-menu">
                                        @foreach ($currencies as $currency)
                                            <li>

                                                <a class="dropdown-item dropdown__item {{ Session::has('currency')
                                                    ? (Session::get('currency') == $currency->id
                                                        ? 'active'
                                                        : '')
                                                    : ($currencies->where('is_default', '=', 1)->first()->id == $currency->id
                                                        ? 'active'
                                                        : '') }}"
                                                    href="{{ route('front.currency', $currency->id) }}">{{ $currency->name }}</a>
                                            </li>
                                        @endforeach

                                    </ul>
                                </div>
                            </li>
                        @endif
                        <li class="d-none d-md-inline-block">

                            <svg xmlns="http://www.w3.org/2000/svg" width="2" height="21" viewBox="0 0 2 21"
                                fill="none">
                                <path d="M1 0.5V20.5" stroke="white" stroke-opacity="0.8" />
                            </svg>
                        </li>

                        <li class="d-none  d-md-flex gap-2 align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="21" viewBox="0 0 20 21"
                                fill="none">
                                <path
                                    d="M9.99955 13C7.35782 13 5.00855 14.2755 3.51288 16.255C3.19097 16.681 3.03002 16.894 3.03528 17.1819C3.03935 17.4043 3.17902 17.6849 3.35402 17.8222C3.58054 18 3.89444 18 4.52224 18H15.4769C16.1047 18 16.4186 18 16.6451 17.8222C16.8201 17.6849 16.9598 17.4043 16.9638 17.1819C16.9691 16.894 16.8081 16.681 16.4862 16.255C14.9906 14.2755 12.6413 13 9.99955 13Z"
                                    stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path
                                    d="M9.99955 10.5C12.0706 10.5 13.7496 8.82107 13.7496 6.75C13.7496 4.67893 12.0706 3 9.99955 3C7.92848 3 6.24955 4.67893 6.24955 6.75C6.24955 8.82107 7.92848 10.5 9.99955 10.5Z"
                                    stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>

                            @if (Auth::guard('web')->check())
                                <a href="{{ route('user-dashboard') }}">@lang('Dashboard')</a>
                            @elseif(Auth::guard('rider')->check())
                                <a href="{{ route('rider-dashboard') }}">@lang('Dashboard')</a>
                            @else
                                <a href="{{ route('user.login') }}">@lang('My Account')</a>
                            @endif

                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="header-top">
        <div class="container custom-containerr">
            <div class="create-navbar d-flex">

                <div class="nav-left">
                    <button type="button" class="header-toggle mobile-menu-toggle d-flex d-xl-none">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none">
                            <path d="M3 12H21M3 6H21M3 18H15" stroke="#1F0300" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>


                    <a class="header-logo-wrapper" href="{{ route('front.index') }}">
                        <img class="logo" src="{{ asset('assets/images/' . $gs->logo) }}" alt="logo">
                    </a>
                </div>
                <div class="nav-center">
                    <ul class="d-flex align-items-center nav-menus">
                        <li class=""><a href="{{ route('front.index') }}"
                                class="nav-link {{ request()->path() == '/' ? 'active' : '' }}">@lang('Home')</a>
                        </li>
                        <li class="has-megamenu {{ request()->path() == 'category' ? 'active' : '' }}">
                            <a href="{{ route('front.category') }}">
                                @lang('Products')
                            </a>
                            <span class="has-submenu-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M18.7098 8.20986C18.6169 8.11613 18.5063 8.04174 18.3844 7.99097C18.2625 7.9402 18.1318 7.91406 17.9998 7.91406C17.8678 7.91406 17.7371 7.9402 17.6152 7.99097C17.4934 8.04174 17.3828 8.11613 17.2898 8.20986L12.7098 12.7899C12.6169 12.8836 12.5063 12.958 12.3844 13.0088C12.2625 13.0595 12.1318 13.0857 11.9998 13.0857C11.8678 13.0857 11.7371 13.0595 11.6152 13.0088C11.4934 12.958 11.3828 12.8836 11.2898 12.7899L6.70982 8.20986C6.61685 8.11613 6.50625 8.04174 6.38439 7.99097C6.26253 7.9402 6.13183 7.91406 5.99982 7.91406C5.8678 7.91406 5.7371 7.9402 5.61524 7.99097C5.49338 8.04174 5.38278 8.11613 5.28982 8.20986C5.10356 8.39722 4.99902 8.65067 4.99902 8.91486C4.99902 9.17905 5.10356 9.4325 5.28982 9.61986L9.87982 14.2099C10.4423 14.7717 11.2048 15.0872 11.9998 15.0872C12.7948 15.0872 13.5573 14.7717 14.1198 14.2099L18.7098 9.61986C18.8961 9.4325 19.0006 9.17905 19.0006 8.91486C19.0006 8.65067 18.8961 8.39722 18.7098 8.20986Z"
                                        fill="#180207" />
                                </svg>
                            </span>



                            <div class="megamenu cat-megamenu">
                                <div class="row w-100">
                                    @foreach ($categories as $category)
                                        <div class="col-lg-3">
                                            <div class="single-menu mt-30">
                                                <h5><a
                                                        href="{{ route('front.category', [$category->slug]) }}">{{ $category->name }}</a>
                                                </h5>
                                                @if ($category->subs->count() > 0)
                                                    @foreach ($category->subs as $subcategory)
                                                        <ul>
                                                            <li><a
                                                                    href="{{ route('front.category', [$category->slug, $subcategory->slug]) }}{{ !empty(request()->input('search')) ? '?search=' . request()->input('search') : '' }}">{{ $subcategory->name }}</a>
                                                            </li>
                                                        </ul>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </li>


                        <li class="has-submenu">
                            <a href="javascript:void(0)">
                                @lang('Pages')
                            </a>
                            <span class="has-submenu-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M18.7098 8.20986C18.6169 8.11613 18.5063 8.04174 18.3844 7.99097C18.2625 7.9402 18.1318 7.91406 17.9998 7.91406C17.8678 7.91406 17.7371 7.9402 17.6152 7.99097C17.4934 8.04174 17.3828 8.11613 17.2898 8.20986L12.7098 12.7899C12.6169 12.8836 12.5063 12.958 12.3844 13.0088C12.2625 13.0595 12.1318 13.0857 11.9998 13.0857C11.8678 13.0857 11.7371 13.0595 11.6152 13.0088C11.4934 12.958 11.3828 12.8836 11.2898 12.7899L6.70982 8.20986C6.61685 8.11613 6.50625 8.04174 6.38439 7.99097C6.26253 7.9402 6.13183 7.91406 5.99982 7.91406C5.8678 7.91406 5.7371 7.9402 5.61524 7.99097C5.49338 8.04174 5.38278 8.11613 5.28982 8.20986C5.10356 8.39722 4.99902 8.65067 4.99902 8.91486C4.99902 9.17905 5.10356 9.4325 5.28982 9.61986L9.87982 14.2099C10.4423 14.7717 11.2048 15.0872 11.9998 15.0872C12.7948 15.0872 13.5573 14.7717 14.1198 14.2099L18.7098 9.61986C18.8961 9.4325 19.0006 9.17905 19.0006 8.91486C19.0006 8.65067 18.8961 8.39722 18.7098 8.20986Z"
                                        fill="#180207" />
                                </svg>
                            </span>
                            <ul class="dropdown-menu">
                                @foreach ($pages->where('header', '=', 1) as $data)
                                    <li>
                                        <a class="dropdown-item dropdown__item"
                                            href="{{ route('front.vendor', $data->slug) }}">{{ $data->title }}</a>
                                    </li>
                                @endforeach

                            </ul>
                        </li>
                        @if ($ps->blog == 1)
                            <li class="{{ request()->path() == 'blog' ? 'active' : '' }}"><a
                                    href="{{ route('front.blog') }}" class="nav-link">@lang('Blog')</a>
                        @endif
                        </li>
                        <li class="{{ request()->path() == 'faq' ? 'active' : '' }}"><a
                                href="{{ route('front.faq') }}" class="nav-link">@lang('Faq')</a>
                        </li>
                        <li class="{{ request()->path() == 'contact' ? 'active' : '' }}"><a
                                href="{{ route('front.contact') }}" class="nav-link">@lang('Contact Us')</a>
                        </li>
                    </ul>
                </div>

                <div class="nav-right">
                    <div class="icon-circle">
                        <button id="searchIcon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M21 21L17.5001 17.5M20 11.5C20 16.1944 16.1944 20 11.5 20C6.80558 20 3 16.1944 3 11.5C3 6.80558 6.80558 3 11.5 3C16.1944 3 20 6.80558 20 11.5Z"
                                    stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </button>

                    </div>

                    <div class="icon-circle">
                        <a href="{{ route('product.compare') }}">
                            <span class="cart-count" id="compare-count">
                                {{ Session::has('compare') ? count(Session::get('compare')->items) : '0' }}
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M18.1777 8C23.2737 8 23.2737 16 18.1777 16C13.0827 16 11.0447 8 5.43875 8C0.85375 8 0.85375 16 5.43875 16C11.0447 16 13.0828 8 18.1788 8H18.1777Z"
                                    stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </a>
                    </div>

                    <div class="icon-circle">





                        @if (Auth::guard('web')->check())
                            <a href="{{ auth()->check() ? route('user-wishlists') : route('user.login') }}">
                                <span class="cart-count" id="wishlist-count">
                                    {{ Auth::guard('web')->user()->wishlistCount() }}
                                </span>

                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none">
                                    <path
                                        d="M16.1111 3C19.6333 3 22 6.3525 22 9.48C22 15.8138 12.1778 21 12 21C11.8222 21 2 15.8138 2 9.48C2 6.3525 4.36667 3 7.88889 3C9.91111 3 11.2333 4.02375 12 4.92375C12.7667 4.02375 14.0889 3 16.1111 3Z"
                                        stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </a>
                        @else
                            <a href="{{ route('user.login') }}">
                                <span class="cart-count" id="wishlist-count">
                                    0
                                </span>

                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none">
                                    <path
                                        d="M16.1111 3C19.6333 3 22 6.3525 22 9.48C22 15.8138 12.1778 21 12 21C11.8222 21 2 15.8138 2 9.48C2 6.3525 4.36667 3 7.88889 3C9.91111 3 11.2333 4.02375 12 4.92375C12.7667 4.02375 14.0889 3 16.1111 3Z"
                                        stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </a>
                        @endif



                    </div>
                    @php
                        $cart = Session::has('cart') ? Session::get('cart')->items : [];
                    @endphp
                    <div class="icon-circle">
                        <a href="{{ route('front.cart') }}">
                            <span class="cart-count" id="cart-count">
                                {{ $cart ? count($cart) : 0 }}
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M2 2H3.30616C3.55218 2 3.67519 2 3.77418 2.04524C3.86142 2.08511 3.93535 2.14922 3.98715 2.22995C4.04593 2.32154 4.06333 2.44332 4.09812 2.68686L4.57143 6M4.57143 6L5.62332 13.7314C5.75681 14.7125 5.82355 15.2031 6.0581 15.5723C6.26478 15.8977 6.56108 16.1564 6.91135 16.3174C7.30886 16.5 7.80394 16.5 8.79411 16.5H17.352C18.2945 16.5 18.7658 16.5 19.151 16.3304C19.4905 16.1809 19.7818 15.9398 19.9923 15.6342C20.2309 15.2876 20.3191 14.8247 20.4955 13.8988L21.8191 6.94969C21.8812 6.62381 21.9122 6.46087 21.8672 6.3335C21.8278 6.22177 21.7499 6.12768 21.6475 6.06802C21.5308 6 21.365 6 21.0332 6H4.57143ZM10 21C10 21.5523 9.55228 22 9 22C8.44772 22 8 21.5523 8 21C8 20.4477 8.44772 20 9 20C9.55228 20 10 20.4477 10 21ZM18 21C18 21.5523 17.5523 22 17 22C16.4477 22 16 21.5523 16 21C16 20.4477 16.4477 20 17 20C17.5523 20 18 20.4477 18 21Z"
                                    stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </a>
                    </div>


                </div>

            </div>


        </div>

    </div>

</header>
