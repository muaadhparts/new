<header class="muaadh-header">
    {{-- Top Bar - Compact info strip --}}
    <div class="muaadh-topbar">
        <div class="container">
            <div class="muaadh-topbar-inner">
                {{-- Left: Contact --}}
                <div class="muaadh-topbar-left">
                    <a href="tel:{{ $ps->phone }}" class="muaadh-topbar-link">
                        <i class="fas fa-phone-alt"></i>
                        <span>{{ $ps->phone }}</span>
                    </a>
                    <span class="muaadh-topbar-divider"></span>
                    <a href="mailto:{{ $ps->email ?? 'support@example.com' }}" class="muaadh-topbar-link d-none d-md-flex">
                        <i class="fas fa-envelope"></i>
                        <span>{{ $ps->email ?? __('Support') }}</span>
                    </a>
                </div>

                {{-- Right: Language, Currency, Quick Links --}}
                <div class="muaadh-topbar-right">
                    {{-- Language Selector --}}
                    <div class="muaadh-dropdown">
                        <button class="muaadh-dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-globe"></i>
                            <span>{{ Session::has('language')
                                ? $languges->where('id', '=', Session::get('language'))->first()->language
                                : $languges->where('is_default', '=', 1)->first()->language }}</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <ul class="muaadh-dropdown-menu">
                            @foreach ($languges as $language)
                                <li>
                                    <a class="muaadh-dropdown-item {{ Session::has('language')
                                        ? (Session::get('language') == $language->id ? 'active' : '')
                                        : ($languges->where('is_default', '=', 1)->first()->id == $language->id ? 'active' : '') }}"
                                        href="{{ route('front.language', $language->id) }}">
                                        {{ $language->language }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    @if ($gs->is_currency == 1)
                        <span class="muaadh-topbar-divider"></span>
                        {{-- Currency Selector --}}
                        @php
                            $selectedCurrency = Session::has('currency')
                                ? $currencies->where('id', '=', Session::get('currency'))->first()
                                : $currencies->where('is_default', '=', 1)->first();
                        @endphp
                        <div class="muaadh-dropdown">
                            <button class="muaadh-dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <span class="currency-sign">{{ $selectedCurrency->sign ?? '$' }}</span>
                                <span>{{ $selectedCurrency->name ?? 'USD' }}</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <ul class="muaadh-dropdown-menu">
                                @foreach ($currencies as $currency)
                                    <li>
                                        <a class="muaadh-dropdown-item {{ Session::has('currency')
                                            ? (Session::get('currency') == $currency->id ? 'active' : '')
                                            : ($currencies->where('is_default', '=', 1)->first()->id == $currency->id ? 'active' : '') }}"
                                            href="{{ route('front.currency', $currency->id) }}">
                                            {{ $currency->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <span class="muaadh-topbar-divider d-none d-lg-block"></span>

                    {{-- Quick Auth Links --}}
                    <div class="muaadh-topbar-auth d-none d-lg-flex">
                        @if (Auth::guard('web')->check() && Auth::guard('web')->user()->is_vendor == 2)
                            <a href="{{ route('vendor.dashboard') }}" class="muaadh-topbar-link">
                                <i class="fas fa-store"></i>
                                <span>@lang('Vendor Panel')</span>
                            </a>
                        @else
                            <a href="{{ route('vendor.login') }}" class="muaadh-topbar-link">
                                <i class="fas fa-store"></i>
                                <span>@lang('Become Vendor')</span>
                            </a>
                        @endif

                        @if (!Auth::guard('rider')->check())
                            <a href="{{ route('rider.login') }}" class="muaadh-topbar-link">
                                <i class="fas fa-motorcycle"></i>
                                <span>@lang('Rider')</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Header --}}
    <div class="muaadh-main-header">
        <div class="container">
            <div class="muaadh-header-inner">
                {{-- Left Side: Toggle + Logo --}}
                <div class="muaadh-header-left">
                    {{-- Mobile Toggle --}}
                    <button type="button" class="muaadh-mobile-toggle d-xl-none" aria-label="Toggle Menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>

                    {{-- Logo --}}
                    <a href="{{ route('front.index') }}" class="muaadh-logo">
                        <img src="{{ asset('assets/images/' . $gs->logo) }}" alt="{{ $gs->title }}">
                    </a>
                </div>

                {{-- Right Side: Header Actions --}}
                <div class="muaadh-header-actions">
                    {{-- User Account --}}
                    <div class="muaadh-action-dropdown">
                        <button type="button" class="muaadh-action-btn" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i>
                            <span class="muaadh-action-label d-none d-md-block">
                                @if (Auth::guard('web')->check())
                                    {{ Str::limit(Auth::guard('web')->user()->name, 10) }}
                                @elseif(Auth::guard('rider')->check())
                                    {{ Str::limit(Auth::guard('rider')->user()->name, 10) }}
                                @else
                                    @lang('Account')
                                @endif
                            </span>
                        </button>
                        <div class="muaadh-action-menu">
                            @if (Auth::guard('web')->check())
                                <a href="{{ route('user-dashboard') }}" class="muaadh-action-menu-item">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>@lang('Dashboard')</span>
                                </a>
                                <a href="{{ route('user-orders') }}" class="muaadh-action-menu-item">
                                    <i class="fas fa-shopping-bag"></i>
                                    <span>@lang('My Orders')</span>
                                </a>
                                <a href="{{ route('user-profile') }}" class="muaadh-action-menu-item">
                                    <i class="fas fa-cog"></i>
                                    <span>@lang('Settings')</span>
                                </a>
                                <div class="muaadh-action-menu-divider"></div>
                                <a href="{{ route('user-logout') }}" class="muaadh-action-menu-item text-danger">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>@lang('Logout')</span>
                                </a>
                            @elseif(Auth::guard('rider')->check())
                                <a href="{{ route('rider-dashboard') }}" class="muaadh-action-menu-item">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>@lang('Dashboard')</span>
                                </a>
                                <a href="{{ route('rider.logout') }}" class="muaadh-action-menu-item text-danger">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>@lang('Logout')</span>
                                </a>
                            @else
                                <a href="{{ route('user.login') }}" class="muaadh-action-menu-item">
                                    <i class="fas fa-sign-in-alt"></i>
                                    <span>@lang('Login')</span>
                                </a>
                                <a href="{{ route('user.register') }}" class="muaadh-action-menu-item">
                                    <i class="fas fa-user-plus"></i>
                                    <span>@lang('Register')</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Wishlist --}}
                    <a href="{{ auth()->check() ? route('user-wishlists') : route('user.login') }}" class="muaadh-action-btn">
                        <i class="fas fa-heart"></i>
                        <span class="muaadh-badge" id="wishlist-count">
                            {{ Auth::guard('web')->check() ? Auth::guard('web')->user()->wishlistCount() : '0' }}
                        </span>
                    </a>

                    {{-- Compare --}}
                    <a href="{{ route('product.compare') }}" class="muaadh-action-btn d-none d-sm-flex">
                        <i class="fas fa-exchange-alt"></i>
                        <span class="muaadh-badge" id="compare-count">
                            {{ Session::has('compare') ? count(Session::get('compare')->items) : '0' }}
                        </span>
                    </a>

                    {{-- Cart --}}
                    @php
                        $cart = Session::has('cart') ? Session::get('cart')->items : [];
                    @endphp
                    <a href="{{ route('front.cart') }}" class="muaadh-action-btn muaadh-cart-btn">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="muaadh-badge" id="cart-count">{{ $cart ? count($cart) : 0 }}</span>
                        <span class="muaadh-action-label d-none d-md-block">@lang('Cart')</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Navigation Bar --}}
    <nav class="muaadh-navbar d-none d-xl-block">
        <div class="container">
            <div class="muaadh-navbar-inner">
                {{-- Categories Mega Menu --}}
                <div class="muaadh-categories-dropdown">
                    <button type="button" class="muaadh-categories-toggle">
                        <i class="fas fa-bars"></i>
                        <span>@lang('All Categories')</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="muaadh-categories-menu">
                        @foreach ($categories as $category)
                            <div class="muaadh-category-item {{ $category->subs->count() > 0 ? 'has-children' : '' }}">
                                <a href="{{ route('front.category', [$category->slug]) }}">
                                    @if($category->photo)
                                        <img src="{{ asset('assets/images/categories/' . $category->photo) }}" alt="{{ $category->name }}">
                                    @else
                                        <i class="fas fa-folder"></i>
                                    @endif
                                    <span>{{ $category->name }}</span>
                                    @if ($category->subs->count() > 0)
                                        <i class="fas fa-chevron-right muaadh-category-arrow"></i>
                                    @endif
                                </a>
                                @if ($category->subs->count() > 0)
                                    <div class="muaadh-subcategory-panel">
                                        <div class="muaadh-subcategory-grid">
                                            @foreach ($category->subs as $subcategory)
                                                <div class="muaadh-subcategory-group">
                                                    <a href="{{ route('front.category', [$category->slug, $subcategory->slug]) }}" class="muaadh-subcategory-title">
                                                        {{ $subcategory->name }}
                                                    </a>
                                                    @if ($subcategory->childs && $subcategory->childs->count() > 0)
                                                        <ul class="muaadh-child-list">
                                                            @foreach ($subcategory->childs->take(5) as $child)
                                                                <li>
                                                                    <a href="{{ route('front.category', [$category->slug, $subcategory->slug, $child->slug]) }}">
                                                                        {{ $child->name }}
                                                                    </a>
                                                                </li>
                                                            @endforeach
                                                            @if ($subcategory->childs->count() > 5)
                                                                <li>
                                                                    <a href="{{ route('front.category', [$category->slug, $subcategory->slug]) }}" class="muaadh-view-all">
                                                                        @lang('View All') →
                                                                    </a>
                                                                </li>
                                                            @endif
                                                        </ul>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Main Navigation --}}
                <ul class="muaadh-nav-menu">
                    <li class="{{ request()->path() == '/' ? 'active' : '' }}">
                        <a href="{{ route('front.index') }}">
                            <i class="fas fa-home"></i>
                            <span>@lang('Home')</span>
                        </a>
                    </li>
                    <li class="{{ request()->is('category*') ? 'active' : '' }}">
                        <a href="{{ route('front.category') }}">
                            <i class="fas fa-box-open"></i>
                            <span>@lang('Products')</span>
                        </a>
                    </li>
                    @if ($pages->where('header', '=', 1)->count() > 0)
                        <li class="muaadh-nav-dropdown">
                            <a href="javascript:void(0)">
                                <i class="fas fa-file-alt"></i>
                                <span>@lang('Pages')</span>
                                <i class="fas fa-chevron-down"></i>
                            </a>
                            <ul class="muaadh-nav-submenu">
                                @foreach ($pages->where('header', '=', 1) as $page)
                                    <li>
                                        <a href="{{ route('front.vendor', $page->slug) }}">{{ $page->title }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                    @if ($ps->blog == 1)
                        <li class="{{ request()->path() == 'blog' ? 'active' : '' }}">
                            <a href="{{ route('front.blog') }}">
                                <i class="fas fa-newspaper"></i>
                                <span>@lang('Blog')</span>
                            </a>
                        </li>
                    @endif
                    <li class="{{ request()->path() == 'faq' ? 'active' : '' }}">
                        <a href="{{ route('front.faq') }}">
                            <i class="fas fa-question-circle"></i>
                            <span>@lang('FAQ')</span>
                        </a>
                    </li>
                    <li class="{{ request()->path() == 'contact' ? 'active' : '' }}">
                        <a href="{{ route('front.contact') }}">
                            <i class="fas fa-envelope"></i>
                            <span>@lang('Contact')</span>
                        </a>
                    </li>
                </ul>

                {{-- Promo Text --}}
                <div class="muaadh-nav-promo">
                    <i class="fas fa-truck"></i>
                    <span>@lang('Free Shipping on Orders Over')</span>
                </div>
            </div>
        </div>
    </nav>

    </header>
