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

    {{-- User Info (if logged in) - Using $authUser/$riderUser from HeaderComposer --}}
    @if ($authUser)
        <div class="muaadh-mobile-user">
            <div class="muaadh-mobile-user-avatar">
                @if($authUser->photo)
                    <img src="{{ asset('assets/images/users/' . $authUser->photo) }}" alt="">
                @else
                    <i class="fas fa-user"></i>
                @endif
            </div>
            <div class="muaadh-mobile-user-info">
                <span class="muaadh-mobile-user-name">{{ $authUser->name }}</span>
                <a href="{{ route('user-dashboard') }}" class="muaadh-mobile-user-link">@lang('View Dashboard')</a>
            </div>
        </div>
    @elseif ($riderUser ?? null)
        <div class="muaadh-mobile-user">
            <div class="muaadh-mobile-user-avatar">
                <i class="fas fa-motorcycle"></i>
            </div>
            <div class="muaadh-mobile-user-info">
                <span class="muaadh-mobile-user-name">{{ $riderUser->name }}</span>
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
                    <span>@lang('CatalogItems')</span>
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
                                <a href="{{ route('front.merchant', $page->slug) }}" class="muaadh-mobile-nav-subitem">
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
                    <a href="{{ auth()->check() ? route('user-favorites') : route('user.login') }}" class="muaadh-mobile-quick-item">
                        <i class="fas fa-heart"></i>
                        <span>@lang('Favorites')</span>
                    </a>
                    <a href="{{ route('catalog-item.compare') }}" class="muaadh-mobile-quick-item">
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

        {{-- Categories Tab - Multi-Step Selector --}}
        <div class="muaadh-mobile-tab-pane" id="menu-categories">
            @php
                $currentCatSlug = Request::segment(2);
                $currentSubcatSlug = Request::segment(3);
                $currentChildcatSlug = Request::segment(4);

                $selectedCat = $categories->firstWhere('slug', $currentCatSlug);
                $selectedSubcat = $selectedCat && $selectedCat->subs ? $selectedCat->subs->firstWhere('slug', $currentSubcatSlug) : null;
                $selectedChildcat = $selectedSubcat && $selectedSubcat->childs ? $selectedSubcat->childs->firstWhere('slug', $currentChildcatSlug) : null;
            @endphp

            <div class="muaadh-mobile-category-selector">
                {{-- Current Selection Breadcrumb --}}
                @if($selectedCat)
                <div class="muaadh-mobile-selection-breadcrumb">
                    <span class="muaadh-selection-label">@lang('Selected'):</span>
                    <div class="muaadh-selection-tags">
                        <span class="muaadh-selection-tag primary">{{ $selectedCat->localized_name }}</span>
                        @if($selectedSubcat)
                            <i class="fas fa-chevron-{{ app()->getLocale() == 'ar' ? 'left' : 'right' }}"></i>
                            <span class="muaadh-selection-tag secondary">{{ $selectedSubcat->localized_name }}</span>
                        @endif
                        @if($selectedChildcat)
                            <i class="fas fa-chevron-{{ app()->getLocale() == 'ar' ? 'left' : 'right' }}"></i>
                            <span class="muaadh-selection-tag info">{{ $selectedChildcat->localized_name }}</span>
                        @endif
                    </div>
                    <a href="{{ route('front.category') }}" class="muaadh-clear-selection">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
                @endif

                {{-- Step 1: Main Category --}}
                <div class="muaadh-mobile-step">
                    <label class="muaadh-mobile-step-label">
                        <i class="fas fa-car"></i>
                        @lang('Category')
                    </label>
                    <select class="muaadh-mobile-select-input" id="mobile-main-category">
                        <option value="">-- @lang('Select Category') --</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}"
                                data-has-subs="{{ $category->subs && $category->subs->count() > 0 ? '1' : '0' }}"
                                {{ $currentCatSlug === $category->slug ? 'selected' : '' }}>
                                {{ $category->localized_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Step 2: Subcategory --}}
                <div class="muaadh-mobile-step {{ $selectedCat && $selectedCat->subs && $selectedCat->subs->count() > 0 ? '' : 'd-none' }}" id="mobile-subcategory-step">
                    <label class="muaadh-mobile-step-label">
                        <i class="fas fa-cogs"></i>
                        @lang('Model')
                    </label>
                    <select class="muaadh-mobile-select-input" id="mobile-subcategory">
                        <option value="">-- @lang('Select Model') --</option>
                        @if($selectedCat && $selectedCat->subs)
                            @foreach ($selectedCat->subs as $subcategory)
                                <option value="{{ $subcategory->slug }}"
                                    data-has-childs="{{ $subcategory->childs && $subcategory->childs->count() > 0 ? '1' : '0' }}"
                                    {{ $currentSubcatSlug === $subcategory->slug ? 'selected' : '' }}>
                                    {{ $subcategory->localized_name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                {{-- Step 3: Child Category --}}
                <div class="muaadh-mobile-step {{ $selectedSubcat && $selectedSubcat->childs && $selectedSubcat->childs->count() > 0 ? '' : 'd-none' }}" id="mobile-childcategory-step">
                    <label class="muaadh-mobile-step-label">
                        <i class="fas fa-puzzle-piece"></i>
                        @lang('Part Type')
                    </label>
                    <select class="muaadh-mobile-select-input" id="mobile-childcategory">
                        <option value="">-- @lang('Select Part Type') --</option>
                        @if($selectedSubcat && $selectedSubcat->childs)
                            @foreach ($selectedSubcat->childs as $child)
                                <option value="{{ $child->slug }}"
                                    {{ $currentChildcatSlug === $child->slug ? 'selected' : '' }}>
                                    {{ $child->localized_name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                {{-- Go Button --}}
                <button type="button" class="muaadh-mobile-go-btn" id="mobile-category-go-btn">
                    <i class="fas fa-search"></i>
                    @lang('Show CatalogItems')
                </button>
            </div>

            {{-- Hidden JSON Data for JS --}}
            @php
                $mobileCategoriesJson = $categories->map(function($cat) {
                    return [
                        'slug' => $cat->slug,
                        'name' => $cat->localized_name,
                        'subs' => $cat->subs ? $cat->subs->map(function($sub) use ($cat) {
                            return [
                                'slug' => $sub->slug,
                                'name' => $sub->localized_name,
                                'childs' => $sub->childs ? $sub->childs->map(function($child) {
                                    return [
                                        'slug' => $child->slug,
                                        'name' => $child->localized_name,
                                    ];
                                })->values() : []
                            ];
                        })->values() : []
                    ];
                })->values();
            @endphp
            <script type="application/json" id="mobile-categories-data">{!! json_encode($mobileCategoriesJson) !!}</script>
        </div>

        {{-- Account Tab - Using $authUser/$riderUser from HeaderComposer --}}
        <div class="muaadh-mobile-tab-pane" id="menu-account">
            @if ($authUser)
                <nav class="muaadh-mobile-nav">
                    <a href="{{ route('user-dashboard') }}" class="muaadh-mobile-nav-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>@lang('Dashboard')</span>
                    </a>
                    <a href="{{ route('user-purchases') }}" class="muaadh-mobile-nav-item">
                        <i class="fas fa-shopping-bag"></i>
                        <span>@lang('My Purchases')</span>
                    </a>
                    <a href="{{ route('user-favorites') }}" class="muaadh-mobile-nav-item">
                        <i class="fas fa-heart"></i>
                        <span>@lang('Favorites')</span>
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
            @elseif ($riderUser ?? null)
                <nav class="muaadh-mobile-nav">
                    <a href="{{ route('rider-dashboard') }}" class="muaadh-mobile-nav-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>@lang('Rider Dashboard')</span>
                    </a>
                    <a href="{{ route('rider-logout') }}" class="muaadh-mobile-nav-item text-danger">
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
                    <p>@lang('Login or create an account to access your orders, favorites and more.')</p>
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
                    <a href="{{ route('merchant.login') }}" class="muaadh-mobile-nav-item">
                        <i class="fas fa-store"></i>
                        <span>@lang('Merchant Login')</span>
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

        {{-- Social Links - Using cached $socialsetting from AppServiceProvider --}}
        @if($socialsetting && ($socialsetting->facebook || $socialsetting->twitter || $socialsetting->linkedin))
            <div class="muaadh-mobile-social">
                @if($socialsetting->facebook)
                    <a href="{{ $socialsetting->facebook }}" target="_blank"><i class="fab fa-facebook-f"></i></a>
                @endif
                @if($socialsetting->twitter)
                    <a href="{{ $socialsetting->twitter }}" target="_blank"><i class="fab fa-twitter"></i></a>
                @endif
                @if($socialsetting->linkedin)
                    <a href="{{ $socialsetting->linkedin }}" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                @endif
            </div>
        @endif
    </div>
</div>

{{-- Mobile Menu Overlay --}}
<div class="muaadh-mobile-overlay"></div>

{{-- Mobile Category Selector Styles - Using CSS Variables for Theme Support --}}
<style>
    .muaadh-mobile-category-selector {
        padding: 15px;
    }
    .muaadh-mobile-selection-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 12px;
        background: var(--surface-sunken, #f5f5f5);
        border-radius: 8px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }
    .muaadh-selection-label {
        font-size: 11px;
        color: var(--text-muted, #666);
        font-weight: 500;
    }
    .muaadh-selection-tags {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        flex: 1;
    }
    .muaadh-selection-tags i {
        font-size: 10px;
        color: var(--text-muted, #999);
    }
    .muaadh-selection-tag {
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 4px;
        font-weight: 500;
    }
    .muaadh-selection-tag.primary {
        background: var(--action-primary, var(--theme-primary));
        color: var(--text-inverse, #fff);
    }
    .muaadh-selection-tag.secondary {
        background: var(--action-secondary, #6c757d);
        color: var(--text-inverse, #fff);
    }
    .muaadh-selection-tag.info {
        background: var(--action-info, #17a2b8);
        color: var(--text-inverse, #fff);
    }
    .muaadh-clear-selection {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--surface-card, #fff);
        border-radius: 50%;
        color: var(--action-danger, #dc3545);
        font-size: 12px;
        box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.1));
    }
    .muaadh-mobile-step {
        margin-bottom: 12px;
    }
    .muaadh-mobile-step-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-body, #333);
        margin-bottom: 6px;
    }
    .muaadh-mobile-step-label i {
        color: var(--action-primary, var(--theme-primary));
        font-size: 14px;
    }
    .muaadh-mobile-select-input {
        width: 100%;
        padding: 12px 15px;
        font-size: 15px;
        border: 1px solid var(--border-default, #ddd);
        border-radius: var(--radius-md, 8px);
        background-color: var(--surface-card, #fff);
        cursor: pointer;
        transition: all 0.2s ease;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
    }
    [dir="rtl"] .muaadh-mobile-select-input {
        background-position: left 12px center;
    }
    .muaadh-mobile-select-input:focus {
        border-color: var(--border-focus, var(--theme-primary));
        outline: none;
        box-shadow: 0 0 0 3px rgba(var(--theme-primary-rgb), 0.1);
    }
    .muaadh-mobile-go-btn {
        width: 100%;
        padding: 14px 20px;
        font-size: 15px;
        font-weight: 600;
        color: var(--text-inverse, #fff);
        background: var(--action-primary, var(--theme-primary));
        border: none;
        border-radius: var(--radius-md, 8px);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-top: 15px;
        transition: all 0.2s ease;
    }
    .muaadh-mobile-go-btn:hover {
        background: var(--action-primary-hover, var(--theme-primary-hover));
        transform: translateY(-1px);
    }
    .muaadh-mobile-go-btn:disabled {
        background: var(--text-muted, #ccc);
        cursor: not-allowed;
        transform: none;
    }
</style>

{{-- Mobile Category Selector JavaScript --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileBaseUrl = '{{ route("front.category") }}';
    let mobileCategoriesData = [];

    // Try to parse JSON data
    try {
        const jsonEl = document.getElementById('mobile-categories-data');
        if (jsonEl) {
            mobileCategoriesData = JSON.parse(jsonEl.textContent || '[]');
        }
    } catch(e) {
        console.error('Error parsing mobile categories data:', e);
    }

    const mobileMainCat = document.getElementById('mobile-main-category');
    const mobileSubcat = document.getElementById('mobile-subcategory');
    const mobileChildcat = document.getElementById('mobile-childcategory');
    const mobileSubcatStep = document.getElementById('mobile-subcategory-step');
    const mobileChildcatStep = document.getElementById('mobile-childcategory-step');
    const mobileGoBtn = document.getElementById('mobile-category-go-btn');

    if (!mobileMainCat) return;

    // Main Category Change
    mobileMainCat.addEventListener('change', function() {
        const selectedSlug = this.value;

        // Reset subsequent selects
        if (mobileSubcat) {
            mobileSubcat.innerHTML = '<option value="">-- {{ __("Select Model") }} --</option>';
        }
        if (mobileChildcat) {
            mobileChildcat.innerHTML = '<option value="">-- {{ __("Select Part Type") }} --</option>';
        }
        if (mobileSubcatStep) mobileSubcatStep.classList.add('d-none');
        if (mobileChildcatStep) mobileChildcatStep.classList.add('d-none');

        if (!selectedSlug) return;

        // Find selected category
        const selectedCat = mobileCategoriesData.find(cat => cat.slug === selectedSlug);

        if (selectedCat && selectedCat.subs && selectedCat.subs.length > 0) {
            selectedCat.subs.forEach(sub => {
                const option = document.createElement('option');
                option.value = sub.slug;
                option.textContent = sub.name;
                option.dataset.hasChilds = (sub.childs && sub.childs.length > 0) ? '1' : '0';
                mobileSubcat.appendChild(option);
            });
            if (mobileSubcatStep) mobileSubcatStep.classList.remove('d-none');
        }
    });

    // Subcategory Change
    if (mobileSubcat) {
        mobileSubcat.addEventListener('change', function() {
            const catSlug = mobileMainCat.value;
            const selectedSlug = this.value;

            // Reset child select
            if (mobileChildcat) {
                mobileChildcat.innerHTML = '<option value="">-- {{ __("Select Part Type") }} --</option>';
            }
            if (mobileChildcatStep) mobileChildcatStep.classList.add('d-none');

            if (!selectedSlug) return;

            // Find selected category and subcategory
            const selectedCat = mobileCategoriesData.find(cat => cat.slug === catSlug);
            const selectedSub = selectedCat ? selectedCat.subs.find(sub => sub.slug === selectedSlug) : null;

            if (selectedSub && selectedSub.childs && selectedSub.childs.length > 0) {
                selectedSub.childs.forEach(child => {
                    const option = document.createElement('option');
                    option.value = child.slug;
                    option.textContent = child.name;
                    mobileChildcat.appendChild(option);
                });
                if (mobileChildcatStep) mobileChildcatStep.classList.remove('d-none');
            }
        });
    }

    // Go Button Click
    if (mobileGoBtn) {
        mobileGoBtn.addEventListener('click', function() {
            const catSlug = mobileMainCat ? mobileMainCat.value : '';
            const subcatSlug = mobileSubcat ? mobileSubcat.value : '';
            const childcatSlug = mobileChildcat ? mobileChildcat.value : '';

            let url = mobileBaseUrl;
            if (catSlug) {
                url += '/' + catSlug;
                if (subcatSlug) {
                    url += '/' + subcatSlug;
                    if (childcatSlug) {
                        url += '/' + childcatSlug;
                    }
                }
            }
            window.location.href = url;
        });
    }
});
</script>
