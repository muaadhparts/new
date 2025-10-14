<!-- Modern Mobile Menu -->
<div class="modern-mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <img src="{{ asset('assets/images/' . $gs->footer_logo) }}" alt="{{ config('app.name') }}" class="mobile-logo">
        <button class="close-mobile-menu" aria-label="Close Menu">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="mobile-menu-body">
        <nav class="mobile-nav-tabs">
            <button class="mobile-tab-btn active" data-tab="main-menu">
                <i class="fas fa-bars me-2"></i>
                @lang('MAIN MENU')
            </button>
            <button class="mobile-tab-btn" data-tab="categories">
                <i class="fas fa-th-large me-2"></i>
                @lang('CATEGORIES')
            </button>
        </nav>

        <div class="mobile-tab-content">
            <!-- Main Menu Tab -->
            <div class="mobile-tab-pane active" id="main-menu">
                <ul class="mobile-menu-list">
                    <li class="mobile-menu-item">
                        <a href="{{ route('front.index') }}" class="mobile-menu-link">
                            <i class="fas fa-home"></i>
                            <span>@lang('Home')</span>
                        </a>
                    </li>

                    <li class="mobile-menu-item">
                        <a href="{{ route('front.category') }}" class="mobile-menu-link">
                            <i class="fas fa-box"></i>
                            <span>@lang('Product')</span>
                        </a>
                    </li>

                    <li class="mobile-menu-item has-submenu">
                        <a href="#" class="mobile-menu-link" data-bs-toggle="collapse" data-bs-target="#pages-submenu">
                            <i class="fas fa-file-alt"></i>
                            <span>@lang('Pages')</span>
                            <i class="fas fa-chevron-down ms-auto"></i>
                        </a>
                        <ul id="pages-submenu" class="collapse mobile-submenu">
                            @foreach ($pages->where('header', '=', 1) as $data)
                                <li>
                                    <a href="{{ route('front.vendor', $data->slug) }}">{{ $data->title }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </li>

                    <li class="mobile-menu-item">
                        <a href="{{ route('front.blog') }}" class="mobile-menu-link">
                            <i class="fas fa-blog"></i>
                            <span>@lang('BLOG')</span>
                        </a>
                    </li>

                    <li class="mobile-menu-item">
                        <a href="{{ route('front.faq') }}" class="mobile-menu-link">
                            <i class="fas fa-question-circle"></i>
                            <span>@lang('FAQ')</span>
                        </a>
                    </li>

                    <li class="mobile-menu-item">
                        <a href="{{ route('front.contact') }}" class="mobile-menu-link">
                            <i class="fas fa-envelope"></i>
                            <span>@lang('CONTACT')</span>
                        </a>
                    </li>
                </ul>

                <!-- Language & Currency Section -->
                <div class="mobile-settings-section">
                    <h6 class="mobile-settings-title">@lang('Settings')</h6>

                    <!-- Language Selector -->
                    <div class="mobile-setting-item">
                        <label class="setting-label">
                            <i class="fas fa-language"></i>
                            @lang('Language')
                        </label>
                        <div class="setting-options">
                            @php
                                $currentLang = Session::has('language')
                                    ? $languges->where('id', Session::get('language'))->first()
                                    : $languges->where('is_default', 1)->first();
                            @endphp
                            @foreach ($languges as $language)
                                <a href="{{ route('front.language', $language->id) }}"
                                   class="setting-option {{ Session::has('language') && Session::get('language') == $language->id ? 'active' : (!Session::has('language') && $language->is_default == 1 ? 'active' : '') }}">
                                    {{ $language->language }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Currency Selector -->
                    @if ($gs->is_currency == 1)
                        <div class="mobile-setting-item">
                            <label class="setting-label">
                                <i class="fas fa-coins"></i>
                                @lang('Currency')
                            </label>
                            <div class="setting-options">
                                @foreach ($currencies as $currency)
                                    <a href="{{ route('front.currency', $currency->id) }}"
                                       class="setting-option {{ Session::has('currency') && Session::get('currency') == $currency->id ? 'active' : (!Session::has('currency') && $currency->is_default == 1 ? 'active' : '') }}">
                                        {{ $currency->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Auth Actions -->
                <div class="mobile-auth-actions">
                    @if (Auth::guard('web')->check() && Auth::guard('web')->user()->is_vendor == 2)
                        <a href="{{ route('vendor.dashboard') }}" class="mobile-auth-btn">
                            <i class="fas fa-store"></i>
                            @lang('Vendor Panel')
                        </a>
                    @elseif (!Auth::guard('web')->check() && !Auth::guard('rider')->check())
                        <a href="{{ route('vendor.login') }}" class="mobile-auth-btn">
                            <i class="fas fa-store"></i>
                            @lang('Vendor Login')
                        </a>
                    @endif

                    @if (Auth::guard('rider')->check())
                        <a href="{{ route('rider-dashboard') }}" class="mobile-auth-btn">
                            <i class="fas fa-motorcycle"></i>
                            @lang('Rider Dashboard')
                        </a>
                    @elseif (!Auth::guard('web')->check() && !Auth::guard('rider')->check())
                        <a href="{{ route('rider.login') }}" class="mobile-auth-btn">
                            <i class="fas fa-motorcycle"></i>
                            @lang('Rider Login')
                        </a>
                    @endif

                    @if (Auth::guard('web')->check() && Auth::guard('web')->user()->is_vendor != 2)
                        <a href="{{ route('user-dashboard') }}" class="mobile-auth-btn">
                            <i class="fas fa-user"></i>
                            @lang('User Dashboard')
                        </a>
                    @elseif (!Auth::guard('web')->check() && !Auth::guard('rider')->check())
                        <a href="{{ route('user.login') }}" class="mobile-auth-btn">
                            <i class="fas fa-sign-in-alt"></i>
                            @lang('User Login')
                        </a>
                    @endif
                </div>
            </div>

            <!-- Categories Tab -->
            <div class="mobile-tab-pane" id="categories">
                <ul class="mobile-menu-list mobile-categories-list">
                    @foreach ($categories as $category)
                        <li class="mobile-menu-item">
                            @if ($category->subs->count() > 0)
                                @php
                                    $isCategoryActive = Request::segment(2) === $category->slug;
                                @endphp
                                <a href="#" class="mobile-menu-link" data-bs-toggle="collapse" data-bs-target="#category-{{ $category->slug }}">
                                    <span>{{ $category->name }}</span>
                                    <i class="fas fa-chevron-down ms-auto"></i>
                                </a>
                                <ul id="category-{{ $category->slug }}" class="collapse mobile-submenu {{ $isCategoryActive ? 'show' : '' }}">
                                    <li>
                                        <a href="{{ route('front.category', $category->slug) }}" class="view-all-link">
                                            <i class="fas fa-th"></i>
                                            @lang('View All')
                                        </a>
                                    </li>
                                    @foreach ($category->subs as $subcategory)
                                        <li>
                                            @if ($subcategory->childs->count() > 0)
                                                <a href="#" class="submenu-link" data-bs-toggle="collapse" data-bs-target="#subcategory-{{ $subcategory->slug }}">
                                                    {{ $subcategory->name }}
                                                    <i class="fas fa-chevron-down ms-auto"></i>
                                                </a>
                                                <ul id="subcategory-{{ $subcategory->slug }}" class="collapse mobile-submenu-level-2">
                                                    @foreach ($subcategory->childs as $child)
                                                        <li>
                                                            <a href="{{ route('front.category', [$category->slug, $subcategory->slug, $child->slug]) }}">
                                                                {{ $child->name }}
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <a href="{{ route('front.category', [$category->slug, $subcategory->slug]) }}">
                                                    {{ $subcategory->name }}
                                                </a>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <a href="{{ route('front.category', $category->slug) }}" class="mobile-menu-link">
                                    <span>{{ $category->name }}</span>
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
/* ========================================
   MODERN MOBILE MENU
   ======================================== */
.modern-mobile-menu {
    position: fixed;
    top: 0;
    left: -100%;
    width: 320px;
    max-width: 85vw;
    height: 100vh;
    background: #fff;
    z-index: 9999;
    transition: left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
}

.modern-mobile-menu.active {
    left: 0;
}

/* Mobile Menu Header */
.mobile-menu-header {
    padding: 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.mobile-logo {
    max-height: 40px;
}

.close-mobile-menu {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    border-radius: 10px;
    color: #fff;
    font-size: 1.25rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.close-mobile-menu:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

/* Mobile Nav Tabs */
.mobile-nav-tabs {
    display: flex;
    padding: 1rem;
    gap: 0.5rem;
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
}

.mobile-tab-btn {
    flex: 1;
    padding: 0.75rem 1rem;
    background: #fff;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.875rem;
    color: #64748b;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mobile-tab-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border-color: #667eea;
}

/* Mobile Menu Body */
.mobile-menu-body {
    flex: 1;
    overflow-y: auto;
}

.mobile-tab-pane {
    display: none;
    padding: 1rem;
}

.mobile-tab-pane.active {
    display: block;
}

/* Mobile Menu List */
.mobile-menu-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.mobile-menu-item {
    margin-bottom: 0.5rem;
}

.mobile-menu-link {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 12px;
    text-decoration: none;
    color: #1e293b;
    font-weight: 600;
    transition: all 0.3s ease;
    gap: 1rem;
}

.mobile-menu-link:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    color: #667eea;
    transform: translateX(5px);
}

.mobile-menu-link i:first-child {
    width: 24px;
    text-align: center;
    color: #667eea;
}

/* Mobile Submenu */
.mobile-submenu {
    list-style: none;
    padding: 0;
    margin: 0.5rem 0 0 0;
}

.mobile-submenu li {
    margin-bottom: 0.25rem;
}

.mobile-submenu a {
    display: block;
    padding: 0.75rem 1rem 0.75rem 3rem;
    background: rgba(102, 126, 234, 0.05);
    border-radius: 8px;
    color: #64748b;
    text-decoration: none;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.mobile-submenu a:hover {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
    padding-left: 3.25rem;
}

.mobile-submenu-level-2 {
    list-style: none;
    padding: 0;
    margin: 0.25rem 0;
}

.mobile-submenu-level-2 a {
    padding-left: 4rem;
    font-size: 0.875rem;
}

.view-all-link {
    font-weight: 600;
    color: #667eea !important;
    background: rgba(102, 126, 234, 0.1) !important;
}

/* Mobile Settings Section */
.mobile-settings-section {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 2px solid #e2e8f0;
}

.mobile-settings-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 1rem;
    padding: 0 0.5rem;
}

.mobile-setting-item {
    margin-bottom: 1.5rem;
}

.setting-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.75rem;
    padding: 0 0.5rem;
    font-size: 0.95rem;
}

.setting-label i {
    width: 20px;
    text-align: center;
    color: #667eea;
}

.setting-options {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.setting-option {
    padding: 0.65rem 1.25rem;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 25px;
    color: #64748b;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.setting-option:hover {
    background: rgba(102, 126, 234, 0.1);
    border-color: #667eea;
    color: #667eea;
}

.setting-option.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: #667eea;
    color: #fff;
}

/* Mobile Auth Actions */
.mobile-auth-actions {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 2px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.mobile-auth-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.mobile-auth-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

/* Overlay */
.overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    z-index: 9997;
    opacity: 0;
    visibility: hidden;
    transition: all 0.4s ease;
}

.overlay.active {
    opacity: 1;
    visibility: visible;
}

/* Responsive */
@media (max-width: 575px) {
    .modern-mobile-menu {
        width: 100%;
        max-width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile Menu Toggle
    const mobileToggle = document.querySelector('.mobile-toggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const closeMobileMenu = document.querySelector('.close-mobile-menu');
    const overlay = document.querySelector('.overlay');

    function openMobileMenu() {
        mobileMenu?.classList.add('active');
        overlay?.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileMenuFn() {
        mobileMenu?.classList.remove('active');
        overlay?.classList.remove('active');
        document.body.style.overflow = '';
    }

    mobileToggle?.addEventListener('click', openMobileMenu);
    closeMobileMenu?.addEventListener('click', closeMobileMenuFn);
    overlay?.addEventListener('click', closeMobileMenuFn);

    // Mobile Tabs
    const tabBtns = document.querySelectorAll('.mobile-tab-btn');
    const tabPanes = document.querySelectorAll('.mobile-tab-pane');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');

            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));

            this.classList.add('active');
            document.getElementById(targetTab)?.classList.add('active');
        });
    });
});
</script>
