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
                            {{-- Using $authUser/$riderUser from HeaderComposer --}}
                            <span class="muaadh-action-label d-none d-md-block">
                                @if ($authUser)
                                    {{ Str::limit($authUser->name, 10) }}
                                @elseif($riderUser ?? null)
                                    {{ Str::limit($riderUser->name, 10) }}
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
                            @elseif($riderUser ?? null)
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

                    {{-- Wishlist - Using cached $wishlistCount from HeaderComposer --}}
                    <a href="{{ $authUser ? route('user-wishlists') : route('user.login') }}" class="muaadh-action-btn">
                        <i class="fas fa-heart"></i>
                        <span class="muaadh-badge" id="wishlist-count">{{ $wishlistCount }}</span>
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
