<header class="futuristic-header">
    <!-- Animated Top Banner -->
    <div class="top-banner">
        <div class="container">
            <div class="banner-content">
                <div class="contact-info">
                    <a href="tel:{{ $ps->phone }}" class="info-item">
                        <i class="fas fa-headset"></i>
                        <span>{{ $ps->phone }}</span>
                    </a>
                    <span class="divider">|</span>
                    <a href="mailto:{{ $ps->email }}" class="info-item">
                        <i class="fas fa-envelope-open-text"></i>
                        <span>{{ $ps->email }}</span>
                    </a>
                </div>

                <div class="top-actions">
                    <!-- Language -->
                    <div class="action-dropdown">
                        <button class="action-trigger">
                            <i class="fas fa-language"></i>
                            @php
                                $currentLang = Session::has('language')
                                    ? $languges->where('id', Session::get('language'))->first()
                                    : $languges->where('is_default', 1)->first();
                            @endphp
                            <span>{{ $currentLang ? $currentLang->language : 'EN' }}</span>
                        </button>
                        <div class="action-menu">
                            @foreach ($languges as $language)
                                <a href="{{ route('front.language', $language->id) }}"
                                   class="menu-item {{ Session::has('language') && Session::get('language') == $language->id ? 'active' : '' }}">
                                    {{ $language->language }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Currency -->
                    @if ($gs->is_currency == 1)
                        <div class="action-dropdown">
                            <button class="action-trigger">
                                <i class="fas fa-coins"></i>
                                <span>{{ Session::has('currency') ? $currencies->where('id', Session::get('currency'))->first()->name : DB::table('currencies')->where('is_default', 1)->first()->name }}</span>
                            </button>
                            <div class="action-menu">
                                @foreach ($currencies as $currency)
                                    <a href="{{ route('front.currency', $currency->id) }}"
                                       class="menu-item {{ Session::has('currency') && Session::get('currency') == $currency->id ? 'active' : '' }}">
                                        {{ $currency->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Account -->
                    <div class="action-dropdown">
                        <button class="action-trigger">
                            <i class="fas fa-user-circle"></i>
                            @if (Auth::guard('web')->check())
                                <span>{{ Str::limit(Auth::guard('web')->user()->name, 10) }}</span>
                            @elseif(Auth::guard('rider')->check())
                                <span>{{ Str::limit(Auth::guard('rider')->user()->name, 10) }}</span>
                            @else
                                <span>@lang('Account')</span>
                            @endif
                        </button>
                        <div class="action-menu">
                            @if (Auth::guard('web')->check())
                                <a href="{{ route('user-dashboard') }}" class="menu-item">
                                    <i class="fas fa-tachometer-alt"></i> @lang('Dashboard')
                                </a>
                                <a href="{{ route('user-logout') }}" class="menu-item">
                                    <i class="fas fa-sign-out-alt"></i> @lang('Logout')
                                </a>
                            @elseif(Auth::guard('rider')->check())
                                <a href="{{ route('rider-dashboard') }}" class="menu-item">
                                    <i class="fas fa-tachometer-alt"></i> @lang('Dashboard')
                                </a>
                                <a href="{{ route('rider-logout') }}" class="menu-item">
                                    <i class="fas fa-sign-out-alt"></i> @lang('Logout')
                                </a>
                            @else
                                <a href="{{ route('user.login') }}" class="menu-item">
                                    <i class="fas fa-sign-in-alt"></i> @lang('Login')
                                </a>
                                <a href="{{ route('user.register') }}" class="menu-item">
                                    <i class="fas fa-user-plus"></i> @lang('Register')
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Vendor/Rider Links -->
                    @if (Auth::guard('web')->check() && Auth::guard('web')->user()->is_vendor == 2)
                        <a href="{{ route('vendor.dashboard') }}" class="action-link">
                            <i class="fas fa-store-alt"></i>
                        </a>
                    @else
                        <a href="{{ route('vendor.login') }}" class="action-link" title="@lang('Vendor Login')">
                            <i class="fas fa-store-alt"></i>
                        </a>
                    @endif

                    @if (!Auth::guard('rider')->check())
                        <a href="{{ route('rider.login') }}" class="action-link" title="@lang('Rider Login')">
                            <i class="fas fa-motorcycle"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <div class="main-header-wrapper">
        <div class="container">
            <div class="header-grid">
                <!-- Logo Section -->
                <div class="logo-section">
                    <button class="burger-menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    <a href="{{ route('front.index') }}" class="brand-logo">
                        <img src="{{ asset('assets/images/' . $gs->logo) }}" alt="{{ config('app.name') }}">
                    </a>
                </div>

                <!-- Navigation -->
                <nav class="main-nav">
                    <ul class="nav-list">
                        <li class="nav-item {{ request()->path() == '/' ? 'active' : '' }}">
                            <a href="{{ route('front.index') }}">@lang('Home')</a>
                        </li>

                        <li class="nav-item mega-item {{ request()->path() == 'category' ? 'active' : '' }}">
                            <a href="{{ route('front.category') }}">
                                @lang('Products')
                                <i class="fas fa-angle-down"></i>
                            </a>
                            <div class="mega-dropdown">
                                <div class="mega-grid">
                                    @foreach ($categories as $category)
                                        <div class="mega-col">
                                            <h6 class="mega-heading">
                                                <a href="{{ route('front.category', $category->slug) }}">
                                                    {{ $category->localized_name }}
                                                </a>
                                            </h6>
                                            @if ($category->subs->count() > 0)
                                                <ul class="mega-sublist">
                                                    @foreach ($category->subs->take(5) as $subcategory)
                                                        <li>
                                                            <a href="{{ route('front.category', [$category->slug, $subcategory->slug]) }}">
                                                                {{ $subcategory->localized_name }}
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                    @if ($category->subs->count() > 5)
                                                        <li class="view-all">
                                                            <a href="{{ route('front.category', $category->slug) }}">
                                                                @lang('View All') <i class="fas fa-arrow-right"></i>
                                                            </a>
                                                        </li>
                                                    @endif
                                                </ul>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </li>

                        <li class="nav-item dropdown-item">
                            <a href="javascript:void(0)">
                                @lang('Pages')
                                <i class="fas fa-angle-down"></i>
                            </a>
                            <ul class="sub-dropdown">
                                @foreach ($pages->where('header', 1) as $data)
                                    <li><a href="{{ route('front.vendor', $data->slug) }}">{{ $data->title }}</a></li>
                                @endforeach
                            </ul>
                        </li>

                        @if ($ps->blog == 1)
                            <li class="nav-item {{ request()->path() == 'blog' ? 'active' : '' }}">
                                <a href="{{ route('front.blog') }}">@lang('Blog')</a>
                            </li>
                        @endif

                        <li class="nav-item {{ request()->path() == 'faq' ? 'active' : '' }}">
                            <a href="{{ route('front.faq') }}">@lang('FAQ')</a>
                        </li>

                        <li class="nav-item {{ request()->path() == 'contact' ? 'active' : '' }}">
                            <a href="{{ route('front.contact') }}">@lang('Contact')</a>
                        </li>
                    </ul>
                </nav>

                <!-- Action Icons -->
                <div class="action-icons">
                    <a href="{{ route('product.compare') }}" class="icon-btn" title="@lang('Compare')">
                        <i class="fas fa-random"></i>
                        <span class="icon-badge">{{ Session::has('compare') ? count(Session::get('compare')->items) : '0' }}</span>
                    </a>

                    <a href="{{ auth()->check() ? route('user-wishlists') : route('user.login') }}" class="icon-btn" title="@lang('Wishlist')">
                        <i class="fas fa-heart"></i>
                        <span class="icon-badge">{{ Auth::guard('web')->check() ? Auth::guard('web')->user()->wishlistCount() : '0' }}</span>
                    </a>

                    @php
                        $cart = Session::has('cart') ? Session::get('cart')->items : [];
                    @endphp
                    <a href="{{ route('front.cart') }}" class="icon-btn cart-icon" title="@lang('Cart')">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="icon-badge" id="cart-count" data-cart-count="{{ $cart ? count($cart) : 0 }}">
                            {{ $cart ? count($cart) : 0 }}
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<style>
/* ========================================
   FUTURISTIC HEADER DESIGN
   ======================================== */
:root {
    --primary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --secondary-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --dark-bg: #0a0e27;
    --light-text: #ffffff;
}

.futuristic-header {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: #fff;
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
}

/* ========================================
   TOP BANNER
   ======================================== */
.top-banner {
    background: var(--dark-bg);
    color: var(--light-text);
    padding: 0.75rem 0;
    font-size: 0.875rem;
    position: relative;
    z-index: 50;
}

.banner-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.contact-info {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    transition: all 0.3s ease;
    font-weight: 500;
}

.info-item:hover {
    color: #f5576c;
    transform: translateY(-2px);
}

.info-item i {
    font-size: 1rem;
}

.divider {
    color: rgba(255, 255, 255, 0.3);
}

.top-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

/* Action Dropdowns */
.action-dropdown {
    position: relative;
    z-index: 100;
}

.action-trigger {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 0.5rem 1rem;
    border-radius: 25px;
    color: #fff;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.875rem;
}

.action-trigger:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

.action-menu {
    position: absolute;
    top: calc(100% + 0.25rem);
    right: 0;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    min-width: 160px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    overflow: hidden;
    z-index: 1000;
    padding-top: 0.25rem;
}

.action-menu::before {
    content: '';
    position: absolute;
    top: -0.25rem;
    left: 0;
    right: 0;
    height: 0.25rem;
    background: transparent;
}

.action-dropdown:hover .action-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.25rem;
    color: #333;
    text-decoration: none;
    transition: all 0.2s ease;
    font-weight: 500;
}

.menu-item:hover {
    background: var(--primary-gradient);
    color: #fff;
}

.menu-item.active {
    background: var(--secondary-gradient);
    color: #fff;
}

.action-link {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    color: #fff;
    text-decoration: none;
    transition: all 0.3s ease;
}

.action-link:hover {
    background: var(--primary-gradient);
    transform: scale(1.1);
}

/* ========================================
   MAIN HEADER
   ======================================== */
.main-header-wrapper {
    padding: 1.25rem 0;
    background: #fff;
    position: relative;
    z-index: 40;
}

.header-grid {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 2rem;
}

/* Logo Section */
.logo-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.burger-menu {
    display: none;
    flex-direction: column;
    gap: 0.4rem;
    background: none;
    border: none;
    padding: 0.5rem;
    cursor: pointer;
}

.burger-menu span {
    width: 28px;
    height: 3px;
    background: var(--dark-bg);
    border-radius: 3px;
    transition: all 0.3s ease;
}

.burger-menu:hover span {
    background: var(--primary-gradient);
}

.brand-logo img {
    height: 55px;
    transition: transform 0.3s ease;
}

.brand-logo:hover img {
    transform: scale(1.05);
}

/* Navigation */
.main-nav {
    justify-self: center;
}

.nav-list {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

.nav-item {
    position: relative;
}

.nav-item > a {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    color: #333;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.95rem;
    border-radius: 30px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.nav-item > a::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: var(--primary-gradient);
    transition: left 0.4s ease;
    z-index: -1;
}

.nav-item > a:hover::before,
.nav-item.active > a::before {
    left: 0;
}

.nav-item > a:hover,
.nav-item.active > a {
    color: #fff;
}

/* Mega Dropdown */
.mega-dropdown {
    position: absolute;
    top: calc(100% + 1rem);
    left: 50%;
    transform: translateX(-50%);
    width: 900px;
    max-width: calc(100vw - 2rem);
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    padding: 2rem;
    opacity: 0;
    visibility: hidden;
    transition: all 0.4s ease;
}

.mega-item:hover .mega-dropdown {
    opacity: 1;
    visibility: visible;
    top: 100%;
}

.mega-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 2rem;
}

.mega-heading {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #333;
}

.mega-heading a {
    color: #333;
    text-decoration: none;
    transition: color 0.3s ease;
}

.mega-heading a:hover {
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.mega-sublist {
    list-style: none;
    padding: 0;
    margin: 0;
}

.mega-sublist li {
    margin-bottom: 0.5rem;
}

.mega-sublist a {
    color: #666;
    text-decoration: none;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    display: block;
    padding: 0.5rem 0;
}

.mega-sublist a:hover {
    color: #f5576c;
    padding-left: 0.5rem;
}

.mega-sublist .view-all a {
    color: #f5576c;
    font-weight: 600;
}

/* Sub Dropdown */
.sub-dropdown {
    position: absolute;
    top: calc(100% + 0.5rem);
    left: 0;
    min-width: 200px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
    padding: 0.5rem;
    list-style: none;
    margin: 0;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.dropdown-item:hover .sub-dropdown {
    opacity: 1;
    visibility: visible;
    top: 100%;
}

.sub-dropdown li a {
    display: block;
    padding: 0.75rem 1rem;
    color: #333;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.2s ease;
    font-weight: 500;
}

.sub-dropdown li a:hover {
    background: var(--secondary-gradient);
    color: #fff;
}

/* Action Icons */
.action-icons {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.icon-btn {
    position: relative;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 15px;
    color: #333;
    font-size: 1.25rem;
    text-decoration: none;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.icon-btn:hover {
    background: var(--primary-gradient);
    color: #fff;
    transform: translateY(-5px) rotate(10deg);
    box-shadow: 0 10px 30px rgba(245, 87, 108, 0.3);
}

.icon-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.3rem 0.6rem;
    border-radius: 50px;
    min-width: 22px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(255, 107, 107, 0.5);
}

/* ========================================
   RESPONSIVE
   ======================================== */
@media (max-width: 1199px) {
    .main-nav {
        display: none;
    }

    .burger-menu {
        display: flex;
    }
}

@media (max-width: 991px) {
    .top-banner {
        display: none;
    }

    .header-grid {
        grid-template-columns: 1fr auto;
        gap: 1rem;
    }
}

@media (max-width: 767px) {
    .contact-info span:not(.divider) {
        display: none;
    }

    .action-trigger span {
        display: none;
    }

    .icon-btn {
        width: 45px;
        height: 45px;
        font-size: 1.125rem;
    }
}

@media (max-width: 575px) {
    .brand-logo img {
        height: 45px;
    }

    .action-icons {
        gap: 0.5rem;
    }

    .icon-btn {
        width: 42px;
        height: 42px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const burgerMenu = document.querySelector('.burger-menu');
    const mobileMenu = document.getElementById('mobileMenu');
    const overlay = document.querySelector('.overlay');

    burgerMenu?.addEventListener('click', function() {
        mobileMenu?.classList.add('active');
        overlay?.classList.add('active');
        document.body.style.overflow = 'hidden';
    });
});
</script>
