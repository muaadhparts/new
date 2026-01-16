{{--
================================================================================
    MUAADH THEME - TOP BAR COMPONENT
================================================================================
    Desktop-only info strip with: Phone, Email, Language, Currency, Quick Links

    Data required (from AppServiceProvider):
    - $ps (PageSettings)
    - $languges (Languages collection)
    - $monetaryUnits (MonetaryUnits collection)
    - $gs (MuaadhSettings)
    - $authUser, $courierUser (from HeaderComposer)

    Usage:
    @include('includes.frontend.topbar')

    Note: This component is included conditionally for desktop only.
    Mobile users access language/currency from mobile_menu.blade.php
================================================================================
--}}

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
                    {{-- Currency Selector (uses $curr from MonetaryUnitService - SINGLE SOURCE OF TRUTH) --}}
                    <div class="muaadh-dropdown">
                        <button class="muaadh-dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <span class="currency-sign">{{ $curr->sign ?? 'ر.س' }}</span>
                            <span>{{ $curr->name ?? 'SAR' }}</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <ul class="muaadh-dropdown-menu">
                            @foreach ($monetaryUnits as $currency)
                                <li>
                                    <a class="muaadh-dropdown-item {{ ($curr->id ?? 0) == $currency->id ? 'active' : '' }}"
                                        href="{{ route('front.monetary-unit', $currency->id) }}">
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
                    @if ($authUser && $authUser->is_merchant == 2)
                        <a href="{{ route('merchant.dashboard') }}" class="muaadh-topbar-link">
                            <i class="fas fa-store"></i>
                            <span>@lang('Merchant Panel')</span>
                        </a>
                    @else
                        <a href="{{ route('merchant.login') }}" class="muaadh-topbar-link">
                            <i class="fas fa-store"></i>
                            <span>@lang('Become Merchant')</span>
                        </a>
                    @endif

                    @if (!($courierUser ?? null))
                        <a href="{{ route('courier.login') }}" class="muaadh-topbar-link">
                            <i class="fas fa-motorcycle"></i>
                            <span>@lang('Courier')</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
