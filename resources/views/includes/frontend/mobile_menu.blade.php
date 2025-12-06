{{--
================================================================================
    MUAADH THEME - MOBILE MENU
================================================================================
    CSS GUIDELINES FOR AI AGENTS:
    -----------------------------
    1. The ONLY file for adding/modifying custom CSS is: public/assets/front/css/MUAADH.css
    2. DO NOT add <style> tags in Blade files - move all styles to MUAADH.css
    3. DO NOT create new CSS files - use MUAADH.css sections instead
================================================================================
--}}

{{-- Mobile Menu Sidebar --}}
<div class="muaadh-mobile-menu">
    {{-- Menu Header --}}
    <div class="muaadh-mobile-menu-header">
        <a href="{{ route('front.index') }}" class="muaadh-mobile-logo">
            <img src="{{ asset('assets/images/' . $gs->footer_logo) }}" alt="{{ $gs->title }}">
        </a>
        <button type="button" class="muaadh-mobile-close">
            <i class="fas fa-times"></i>
        </button>
    </div>

    {{-- User Info (if logged in) --}}
    @if (Auth::guard('web')->check())
        <div class="muaadh-mobile-user">
            <div class="muaadh-mobile-user-avatar">
                @if(Auth::guard('web')->user()->photo)
                    <img src="{{ asset('assets/images/users/' . Auth::guard('web')->user()->photo) }}" alt="">
                @else
                    <i class="fas fa-user"></i>
                @endif
            </div>
            <div class="muaadh-mobile-user-info">
                <span class="muaadh-mobile-user-name">{{ Auth::guard('web')->user()->name }}</span>
                <a href="{{ route('user-dashboard') }}" class="muaadh-mobile-user-link">@lang('View Dashboard')</a>
            </div>
        </div>
    @elseif (Auth::guard('rider')->check())
        <div class="muaadh-mobile-user">
            <div class="muaadh-mobile-user-avatar">
                <i class="fas fa-motorcycle"></i>
            </div>
            <div class="muaadh-mobile-user-info">
                <span class="muaadh-mobile-user-name">{{ Auth::guard('rider')->user()->name }}</span>
                <a href="{{ route('rider-dashboard') }}" class="muaadh-mobile-user-link">@lang('Rider Dashboard')</a>
            </div>
        </div>
    @else
        <div class="muaadh-mobile-auth-buttons">
            <a href="{{ route('user.login') }}" class="muaadh-mobile-auth-btn muaadh-btn-primary">
                <i class="fas fa-sign-in-alt"></i>
                <span>@lang('Login')</span>
            </a>
            <a href="{{ route('user.register') }}" class="muaadh-mobile-auth-btn muaadh-btn-outline">
                <i class="fas fa-user-plus"></i>
                <span>@lang('Register')</span>
            </a>
        </div>
    @endif

    {{-- Menu Tabs --}}
    <div class="muaadh-mobile-tabs">
        <button class="muaadh-mobile-tab active" data-target="menu-main">
            <i class="fas fa-bars"></i>
            <span>@lang('Menu')</span>
        </button>
        <button class="muaadh-mobile-tab" data-target="menu-categories">
            <i class="fas fa-th-large"></i>
            <span>@lang('Categories')</span>
        </button>
        <button class="muaadh-mobile-tab" data-target="menu-account">
            <i class="fas fa-user"></i>
            <span>@lang('Account')</span>
        </button>
    </div>

    {{-- Tab Contents --}}
    <div class="muaadh-mobile-tab-content">
        {{-- Main Menu Tab --}}
        <div class="muaadh-mobile-tab-pane active" id="menu-main">
            <nav class="muaadh-mobile-nav">
                <a href="{{ route('front.index') }}" class="muaadh-mobile-nav-item {{ request()->path() == '/' ? 'active' : '' }}">
                    <i class="fas fa-home"></i>
                    <span>@lang('Home')</span>
                </a>
                <a href="{{ route('front.category') }}" class="muaadh-mobile-nav-item {{ request()->is('category*') ? 'active' : '' }}">
                    <i class="fas fa-box-open"></i>
                    <span>@lang('Products')</span>
                </a>
                @if ($pages->where('header', '=', 1)->count() > 0)
                    <div class="muaadh-mobile-nav-accordion">
                        <button class="muaadh-mobile-nav-item muaadh-accordion-toggle">
                            <i class="fas fa-file-alt"></i>
                            <span>@lang('Pages')</span>
                            <i class="fas fa-chevron-down muaadh-accordion-icon"></i>
                        </button>
                        <div class="muaadh-accordion-content">
                            @foreach ($pages->where('header', '=', 1) as $page)
                                <a href="{{ route('front.vendor', $page->slug) }}" class="muaadh-mobile-nav-subitem">
                                    {{ $page->title }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if ($ps->blog == 1)
                    <a href="{{ route('front.blog') }}" class="muaadh-mobile-nav-item {{ request()->path() == 'blog' ? 'active' : '' }}">
                        <i class="fas fa-newspaper"></i>
                        <span>@lang('Blog')</span>
                    </a>
                @endif
                <a href="{{ route('front.faq') }}" class="muaadh-mobile-nav-item {{ request()->path() == 'faq' ? 'active' : '' }}">
                    <i class="fas fa-question-circle"></i>
                    <span>@lang('FAQ')</span>
                </a>
                <a href="{{ route('front.contact') }}" class="muaadh-mobile-nav-item {{ request()->path() == 'contact' ? 'active' : '' }}">
                    <i class="fas fa-envelope"></i>
                    <span>@lang('Contact Us')</span>
                </a>
            </nav>

            {{-- Quick Links --}}
            <div class="muaadh-mobile-quick-links">
                <h6 class="muaadh-mobile-section-title">@lang('Quick Access')</h6>
                <div class="muaadh-mobile-quick-grid">
                    <a href="{{ route('front.cart') }}" class="muaadh-mobile-quick-item">
                        <i class="fas fa-shopping-cart"></i>
                        <span>@lang('Cart')</span>
                        @php $cart = Session::has('cart') ? Session::get('cart')->items : []; @endphp
                        @if(count($cart) > 0)
                            <span class="muaadh-mobile-quick-badge">{{ count($cart) }}</span>
                        @endif
                    </a>
                    <a href="{{ auth()->check() ? route('user-wishlists') : route('user.login') }}" class="muaadh-mobile-quick-item">
                        <i class="fas fa-heart"></i>
                        <span>@lang('Wishlist')</span>
                    </a>
                    <a href="{{ route('product.compare') }}" class="muaadh-mobile-quick-item">
                        <i class="fas fa-exchange-alt"></i>
                        <span>@lang('Compare')</span>
                    </a>
                    <a href="{{ route('front.tracking') }}" class="muaadh-mobile-quick-item">
                        <i class="fas fa-truck"></i>
                        <span>@lang('Track')</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Categories Tab --}}
        <div class="muaadh-mobile-tab-pane" id="menu-categories">
            <nav class="muaadh-mobile-categories">
                @foreach ($categories as $category)
                    @if ($category->subs->count() > 0)
                        <div class="muaadh-mobile-nav-accordion">
                            <div class="muaadh-mobile-category-header">
                                <a href="{{ route('front.category', $category->slug) }}" class="muaadh-mobile-category-link {{ Request::segment(2) === $category->slug ? 'active' : '' }}">
                                    @if($category->photo)
                                        <img src="{{ asset('assets/images/categories/' . $category->photo) }}" alt="" class="muaadh-mobile-category-img">
                                    @else
                                        <i class="fas fa-folder"></i>
                                    @endif
                                    <span>{{ $category->name }}</span>
                                </a>
                                <button class="muaadh-accordion-toggle-btn">
                                    <i class="fas fa-plus"></i>
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                            <div class="muaadh-accordion-content">
                                @foreach ($category->subs as $subcategory)
                                    @if ($subcategory->childs && $subcategory->childs->count() > 0)
                                        <div class="muaadh-mobile-nav-accordion muaadh-nested">
                                            <div class="muaadh-mobile-category-header">
                                                <a href="{{ route('front.category', [$category->slug, $subcategory->slug]) }}" class="muaadh-mobile-nav-subitem">
                                                    {{ $subcategory->name }}
                                                </a>
                                                <button class="muaadh-accordion-toggle-btn">
                                                    <i class="fas fa-plus"></i>
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </div>
                                            <div class="muaadh-accordion-content">
                                                @foreach ($subcategory->childs as $child)
                                                    <a href="{{ route('front.category', [$category->slug, $subcategory->slug, $child->slug]) }}" class="muaadh-mobile-nav-subitem muaadh-child-item">
                                                        {{ $child->name }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <a href="{{ route('front.category', [$category->slug, $subcategory->slug]) }}" class="muaadh-mobile-nav-subitem">
                                            {{ $subcategory->name }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ route('front.category', $category->slug) }}" class="muaadh-mobile-category-link {{ Request::segment(2) === $category->slug ? 'active' : '' }}">
                            @if($category->photo)
                                <img src="{{ asset('assets/images/categories/' . $category->photo) }}" alt="" class="muaadh-mobile-category-img">
                            @else
                                <i class="fas fa-folder"></i>
                            @endif
                            <span>{{ $category->name }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>
        </div>

        {{-- Account Tab --}}
        <div class="muaadh-mobile-tab-pane" id="menu-account">
            @if (Auth::guard('web')->check())
                <nav class="muaadh-mobile-nav">
                    <a href="{{ route('user-dashboard') }}" class="muaadh-mobile-nav-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>@lang('Dashboard')</span>
                    </a>
                    <a href="{{ route('user-orders') }}" class="muaadh-mobile-nav-item">
                        <i class="fas fa-shopping-bag"></i>
                        <span>@lang('My Orders')</span>
                    </a>
                    <a href="{{ route('user-wishlists') }}" class="muaadh-mobile-nav-item">
                        <i class="fas fa-heart"></i>
                        <span>@lang('Wishlist')</span>
                    </a>
                    <a href="{{ route('user-profile') }}" class="muaadh-mobile-nav-item">
                        <i class="fas fa-user-edit"></i>
                        <span>@lang('Edit Profile')</span>
                    </a>
                    <a href="{{ route('user-profile') }}" class="muaadh-mobile-nav-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>@lang('Addresses')</span>
                    </a>
                    <a href="{{ route('user-logout') }}" class="muaadh-mobile-nav-item text-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>@lang('Logout')</span>
                    </a>
                </nav>
            @elseif (Auth::guard('rider')->check())
                <nav class="muaadh-mobile-nav">
                    <a href="{{ route('rider-dashboard') }}" class="muaadh-mobile-nav-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>@lang('Rider Dashboard')</span>
                    </a>
                    <a href="{{ route('rider.logout') }}" class="muaadh-mobile-nav-item text-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>@lang('Logout')</span>
                    </a>
                </nav>
            @else
                <div class="muaadh-mobile-guest-account">
                    <div class="muaadh-mobile-guest-icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h5>@lang('Welcome Guest')</h5>
                    <p>@lang('Login or create an account to access your orders, wishlist and more.')</p>
                    <div class="muaadh-mobile-guest-buttons">
                        <a href="{{ route('user.login') }}" class="muaadh-btn-primary">
                            <i class="fas fa-sign-in-alt"></i>
                            @lang('Login')
                        </a>
                        <a href="{{ route('user.register') }}" class="muaadh-btn-outline">
                            <i class="fas fa-user-plus"></i>
                            @lang('Register')
                        </a>
                    </div>
                </div>

                {{-- Other Login Options --}}
                <div class="muaadh-mobile-other-logins">
                    <h6 class="muaadh-mobile-section-title">@lang('Other Accounts')</h6>
                    <a href="{{ route('vendor.login') }}" class="muaadh-mobile-nav-item">
                        <i class="fas fa-store"></i>
                        <span>@lang('Vendor Login')</span>
                    </a>
                    <a href="{{ route('rider.login') }}" class="muaadh-mobile-nav-item">
                        <i class="fas fa-motorcycle"></i>
                        <span>@lang('Rider Login')</span>
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Menu Footer --}}
    <div class="muaadh-mobile-menu-footer">
        {{-- Language & Currency --}}
        <div class="muaadh-mobile-footer-selects">
            <div class="muaadh-mobile-select">
                <i class="fas fa-globe"></i>
                <select onchange="window.location.href=this.value">
                    @foreach ($languges as $language)
                        <option value="{{ route('front.language', $language->id) }}"
                            {{ Session::has('language') && Session::get('language') == $language->id ? 'selected' : '' }}
                            {{ !Session::has('language') && $language->is_default == 1 ? 'selected' : '' }}>
                            {{ $language->language }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if ($gs->is_currency == 1)
                <div class="muaadh-mobile-select">
                    <i class="fas fa-dollar-sign"></i>
                    <select onchange="window.location.href=this.value">
                        @foreach ($currencies as $currency)
                            <option value="{{ route('front.currency', $currency->id) }}"
                                {{ Session::has('currency') && Session::get('currency') == $currency->id ? 'selected' : '' }}
                                {{ !Session::has('currency') && $currency->is_default == 1 ? 'selected' : '' }}>
                                {{ $currency->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        {{-- Contact Info --}}
        <div class="muaadh-mobile-contact">
            <a href="tel:{{ $ps->phone }}">
                <i class="fas fa-phone-alt"></i>
                <span>{{ $ps->phone }}</span>
            </a>
        </div>

        {{-- Social Links --}}
        @php
            $socialLinks = \App\Models\Socialsetting::first();
        @endphp
        @if($socialLinks && ($socialLinks->facebook || $socialLinks->twitter || $socialLinks->linkedin))
            <div class="muaadh-mobile-social">
                @if($socialLinks->facebook)
                    <a href="{{ $socialLinks->facebook }}" target="_blank"><i class="fab fa-facebook-f"></i></a>
                @endif
                @if($socialLinks->twitter)
                    <a href="{{ $socialLinks->twitter }}" target="_blank"><i class="fab fa-twitter"></i></a>
                @endif
                @if($socialLinks->linkedin)
                    <a href="{{ $socialLinks->linkedin }}" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                @endif
            </div>
        @endif
    </div>
</div>

{{-- Mobile Menu Overlay --}}
<div class="muaadh-mobile-overlay"></div>
