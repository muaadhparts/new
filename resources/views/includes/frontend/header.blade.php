<header class="muaadh-header">
    {{--
    ============================================================================
    TOP BAR - Desktop Only (>= 1200px)
    ============================================================================
    Mobile users access language/currency from mobile_menu.blade.php
    This prevents duplication and saves vertical space on small screens.
    Bootstrap xl breakpoint = 1200px (matches d-xl-none on mobile toggle)
    ============================================================================
    --}}
    @if (!request()->is('api/*'))
        <div class="d-none d-xl-block">
            @include('includes.frontend.topbar')
        </div>
    @endif

    {{-- Main Header --}}
    <div class="muaadh-main-header">
        <div class="container">
            <div class="muaadh-header-inner">
                {{-- Left Side: Toggle + Logo --}}
                <div class="muaadh-header-left">
                    {{-- Mobile Toggle (hidden in merchant/admin where they have their own toggle) --}}
                    @if (!($hideMobileToggle ?? false))
                        @php
                            $currentUrl = url()->current();
                            $urlParts = explode('/', $currentUrl);
                            $isUserDashboard = in_array('user', $urlParts);
                            $isCourierDashboard = in_array('courier', $urlParts);
                            $isDashboardPage = $isUserDashboard || $isCourierDashboard;
                        @endphp

                        @if($isDashboardPage)
                            {{-- Dashboard pages: Show TWO toggle buttons --}}
                            <div class="d-flex align-items-center gap-2 d-xl-none">
                                {{-- Store Menu Toggle --}}
                                <button type="button" class="muaadh-mobile-toggle" aria-label="@lang('Store Menu')" name="@lang('Store Menu')">
                                    <i class="fas fa-store"></i>
                                </button>
                                {{-- Dashboard Menu Toggle --}}
                                <button type="button" class="mobile-menu-toggle" aria-label="@lang('Dashboard Menu')" name="@lang('Dashboard Menu')">
                                    <i class="fas fa-th-list"></i>
                                </button>
                            </div>
                        @else
                            {{-- Regular pages: Show single toggle for store menu --}}
                            <button type="button" class="muaadh-mobile-toggle d-xl-none" aria-label="Toggle Menu">
                                <span></span>
                                <span></span>
                                <span></span>
                            </button>
                        @endif
                    @endif

                    {{-- Logo --}}
                    <a href="{{ route('front.index') }}" class="muaadh-logo">
                        <img src="{{ asset('assets/images/' . $gs->logo) }}" alt="{{ $gs->site_name }}">
                    </a>
                </div>

                {{-- Right Side: Header Actions --}}
                <div class="muaadh-header-actions">
                    {{-- User Account --}}
                    <div class="muaadh-action-dropdown">
                        <button type="button" class="muaadh-action-btn" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i>
                            {{-- Using $authUser/$courierUser from HeaderComposer --}}
                            <span class="muaadh-action-label d-none d-md-block">
                                @if ($authUser)
                                    {{ Str::limit($authUser->name, 10) }}
                                @elseif($courierUser ?? null)
                                    {{ Str::limit($courierUser->name, 10) }}
                                @else
                                    @lang('Account')
                                @endif
                            </span>
                        </button>
                        <div class="muaadh-action-menu">
                            @if ($authUser)
                                <a href="{{ route('user-dashboard') }}" class="muaadh-action-menu-item">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>@lang('Dashboard')</span>
                                </a>
                                <a href="{{ route('user-purchases') }}" class="muaadh-action-menu-item">
                                    <i class="fas fa-shopping-bag"></i>
                                    <span>@lang('My Purchases')</span>
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
                            @elseif($courierUser ?? null)
                                <a href="{{ route('courier-dashboard') }}" class="muaadh-action-menu-item">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>@lang('Dashboard')</span>
                                </a>
                                <a href="{{ route('courier-logout') }}" class="muaadh-action-menu-item text-danger">
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

                    {{-- Favorites --}}
                    <a href="{{ $authUser ? route('user-favorites') : route('user.login') }}" class="muaadh-action-btn">
                        <i class="fas fa-heart"></i>
                        <span class="muaadh-badge" id="favorite-count">{{ $favoriteCount }}</span>
                    </a>

                    {{-- Compare --}}
                    <a href="{{ route('catalog-item.compare') }}" class="muaadh-action-btn d-none d-sm-flex">
                        <i class="fas fa-exchange-alt"></i>
                        <span class="muaadh-badge" id="compare-count">
                            {{ Session::has('compare') ? count(Session::get('compare')->items) : '0' }}
                        </span>
                    </a>

                    {{-- Cart (New System v4) --}}
                    @php
                        // Get cart count from new system, fallback to old systems
                        $cartCount = 0;
                        if (Session::has('merchant_cart')) {
                            $merchantCart = Session::get('merchant_cart');
                            $cartCount = $merchantCart['totals']['qty'] ?? count($merchantCart['items'] ?? []);
                        } elseif (Session::has('cart_v2')) {
                            $cartV2 = Session::get('cart_v2');
                            $cartCount = $cartV2['totalQty'] ?? count($cartV2['items'] ?? []);
                        } elseif (Session::has('cart')) {
                            $oldCart = Session::get('cart');
                            $cartCount = is_object($oldCart) ? count($oldCart->items ?? []) : 0;
                        }
                    @endphp
                    <a href="{{ route('merchant-cart.index') }}" class="muaadh-action-btn muaadh-cart-btn">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="muaadh-badge" id="cart-count">{{ $cartCount }}</span>
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
                {{-- Brands Mega Menu --}}
                <div class="muaadh-categories-dropdown">
                    <button type="button" class="muaadh-categories-toggle">
                        <i class="fas fa-bars"></i>
                        <span>@lang('All Brands')</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="muaadh-categories-menu">
                        @foreach ($brands as $brand)
                            <div class="muaadh-category-item {{ $brand->catalogs && $brand->catalogs->count() > 0 ? 'has-children' : '' }}">
                                <a href="{{ route('front.catalog', [$brand->slug]) }}">
                                    @if($brand->photo)
                                        <img src="{{ asset('assets/images/brand/' . $brand->photo) }}" alt="{{ app()->getLocale() == 'ar' ? ($brand->name_ar ?: $brand->name) : $brand->name }}">
                                    @else
                                        <i class="fas fa-car"></i>
                                    @endif
                                    <span>{{ app()->getLocale() == 'ar' ? ($brand->name_ar ?: $brand->name) : $brand->name }}</span>
                                    @if ($brand->catalogs && $brand->catalogs->count() > 0)
                                        <i class="fas fa-chevron-right muaadh-category-arrow"></i>
                                    @endif
                                </a>
                                @if ($brand->catalogs && $brand->catalogs->count() > 0)
                                    <div class="muaadh-subcategory-panel">
                                        <div class="muaadh-subcategory-grid">
                                            @foreach ($brand->catalogs as $catalog)
                                                <div class="muaadh-subcategory-group">
                                                    <a href="{{ route('front.catalog', [$brand->slug, $catalog->slug]) }}" class="muaadh-subcategory-name">
                                                        {{ app()->getLocale() == 'ar' ? ($catalog->name_ar ?: $catalog->name) : $catalog->name }}
                                                    </a>
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
                    <li class="{{ request()->is('brands*') ? 'active' : '' }}">
                        <a href="{{ route('front.catalog') }}">
                            <i class="fas fa-box-open"></i>
                            <span>@lang('CatalogItems')</span>
                        </a>
                    </li>
                    @if ($static_content->where('header', '=', 1)->count() > 0)
                        <li class="muaadh-nav-dropdown">
                            <a href="javascript:void(0)">
                                <i class="fas fa-file-alt"></i>
                                <span>@lang('Pages')</span>
                                <i class="fas fa-chevron-down"></i>
                            </a>
                            <ul class="muaadh-nav-submenu">
                                @foreach ($static_content->where('header', '=', 1) as $content)
                                    <li>
                                        <a href="{{ route('front.merchant', $content->slug) }}">{{ $content->name }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                    @if ($ps->blog == 1)
                        <li class="{{ request()->path() == 'publications' ? 'active' : '' }}">
                            <a href="{{ route('front.publications') }}">
                                <i class="fas fa-newspaper"></i>
                                <span>@lang('Publications')</span>
                            </a>
                        </li>
                    @endif
                    <li class="{{ request()->path() == 'help-article' ? 'active' : '' }}">
                        <a href="{{ route('front.help-article') }}">
                            <i class="fas fa-question-circle"></i>
                            <span>@lang('Help')</span>
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
