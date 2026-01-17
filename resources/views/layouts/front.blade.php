{{--
================================================================================
    MUAADH THEME - FRONTEND LAYOUT
================================================================================
    CSS GUIDELINES FOR AI AGENTS:
    -----------------------------
    1. The ONLY file for adding/modifying custom CSS is: public/assets/front/css/style.css
    2. DO NOT add <style> tags in Blade files - move all styles to style.css
    3. DO NOT create new CSS files - use style.css sections instead
    4. Use CSS variables from style.css (--theme-* or --muaadh-*)
    5. Add new styles under appropriate section comments in style.css

    FILE STRUCTURE:
    - style.css = MAIN THEME FILE (ALL CUSTOMIZATIONS HERE)
    - theme-colors.css = Generated from Admin Panel (overrides :root variables)
    - External libraries (bootstrap, slick, etc.) = DO NOT MODIFY
================================================================================
--}}
<!DOCTYPE html>
<html lang="{{ $langg->name ?? 'en' }}" dir="{{ $langg && $langg->rtl == 1 ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <name>{{ $gs->site_name }}</name>
    <!--Essential css files-->
    @if($langg && $langg->rtl == 1)
        <link rel="stylesheet" href="{{ asset('assets/front/css/bootstrap.rtl.min.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('assets/front/css/bootstrap.min.css') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('assets/front/css/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/all.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/slick.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/nice-select.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/jquery-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/datatables.min.css') }}">
    {{-- Main Theme File - Contains all base styles (buttons, components, etc.) --}}
    <link rel="stylesheet" href="{{ asset('assets/front/css/style.css') }}?v={{ filemtime(public_path('assets/front/css/style.css')) }}">

    {{-- Design System - New components with m- prefix --}}
    <link rel="stylesheet" href="{{ asset('assets/front/css/muaadh-system.css') }}?v={{ filemtime(public_path('assets/front/css/muaadh-system.css')) }}">

    {{-- Catalog Item Card - Unified styles for all catalog item cards --}}
    <link rel="stylesheet" href="{{ asset('assets/css/catalog-item-card.css') }}?v={{ filemtime(public_path('assets/css/catalog-item-card.css')) }}">

    {{-- Shipping Quote & Customer Location Modal --}}
    <link rel="stylesheet" href="{{ asset('assets/front/css/shipping-quote.css') }}?v={{ filemtime(public_path('assets/front/css/shipping-quote.css')) }}">

    @if($langg && $langg->rtl == 1)
        <link rel="stylesheet" href="{{ asset('assets/front/css/rtl.css') }}">
    @endif

    {{-- Theme Colors - Generated from Admin Panel (MUST load LAST to override :root variables) --}}
    @themeStyles

    {{-- AutoComplete.js for search --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tarekraafat/autocomplete.js@10.2.9/dist/css/autoComplete.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@tarekraafat/autocomplete.js@10.2.9/dist/autoComplete.min.js"></script>
    {{-- Bootstrap Datepicker --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/css/datepicker.css" rel="stylesheet" type="text/css" />
    <link rel="icon" href="{{ asset('assets/images/' . $gs->favicon) }}">
    @livewireStyles
    @include('includes.frontend.extra_head')
    @yield('css')

</head>

<body class="m-theme-scope">
    {{-- Google Tag Manager (noscript) --}}
    @if (!empty($seo->gtm_id))
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $seo->gtm_id }}"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif

    {{-- Header data ($brands, $static_content, $monetaryUnits, $languges) provided by GlobalDataMiddleware with caching --}}
    <!-- header area -->
    @include('includes.frontend.header')

    <!-- Mobile Menus based on page type -->
    @php
        $url = url()->current();
        $explodeUrl = explode('/',$url);
        $isUserPage = in_array('user', $explodeUrl);
        $isCourierPage = in_array('courier', $explodeUrl);
    @endphp

    @if($isUserPage)
        {{-- User pages need BOTH menus: Store menu + Dashboard menu --}}
        {{-- Store Mobile Menu (for shopping) --}}
        @include('includes.frontend.mobile_menu')
        <div class="muaadh-mobile-overlay"></div>

        {{-- User Dashboard Mobile Sidebar --}}
        @include('includes.user.mobile-header')
        <div class="overlay"></div>

    @elseif($isCourierPage)
        {{-- Courier pages need BOTH menus: Store menu + Courier menu --}}
        {{-- Store Mobile Menu (for shopping) --}}
        @include('includes.frontend.mobile_menu')
        <div class="muaadh-mobile-overlay"></div>

        {{-- Courier Dashboard Mobile Sidebar --}}
        @include('includes.courier.mobile-header')
        <div class="overlay"></div>

    @else
        {{-- Regular frontend pages: Store menu only --}}
        @include('includes.frontend.mobile_menu')
        <div class="muaadh-mobile-overlay"></div>
        <div class="overlay"></div>
    @endif

    {{-- Livewire slot support --}}
    @isset($slot)
        {{ $slot }}
    @endisset

    @yield('content')


    <!-- footer section -->
    @include('includes.frontend.footer')
    <!-- footer section -->

    <!--Esential Js Files-->
    <script src="{{ asset('assets/front/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/front/js/slick.js') }}"></script>
    <script src="{{ asset('assets/front/js/jquery-ui.js') }}"></script>
    <script src="{{ asset('assets/front/js/nice-select.js') }}"></script>
    <script src="{{ asset('assets/front/js/wow.js') }}"></script>
    <script src="{{ asset('assets/front/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/front/js/toastr.min.js') }}"></script>
    <script src="{{ asset('assets/front/js/script.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('assets/front/js/myscript.js') }}?v={{ time() }}"></script>
    {{-- Centralized Quantity Control --}}
    <script src="{{ asset('assets/front/js/qty-control.js') }}?v={{ time() }}"></script>
    {{-- Unified Cart System --}}
    <script src="{{ asset('assets/front/js/merchant-cart-unified.js') }}?v={{ time() }}"></script>
    {{-- Customer Location & Shipping Quote --}}
    <script src="{{ asset('assets/front/js/customer-location.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('assets/front/js/shipping-quote.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('assets/front/js/jquery.magnific-popup.js') }}"></script>

    {{-- Magnific Popup Init --}}
    <script>
        $(document).ready(function() {
            $('.test-popup-link').magnificPopup({type:'image'});
        });
    </script>

    <script>
        "use strict";
        var mainurl = "{{ url('/') }}";
        // Using cached $gs from AppServiceProvider instead of direct DB query
        var gs      = {!! json_encode((object)[
            'is_loader' => $gs->is_loader ?? 0,
            'decimal_separator' => $gs->decimal_separator ?? '.',
            'thousand_separator' => $gs->thousand_separator ?? ',',
            'is_cookie' => $gs->is_cookie ?? 0,
            'is_talkto' => $gs->is_talkto ?? 0,
            'talkto' => $gs->talkto ?? ''
        ]) !!};
        var ps_category = {{ $ps->category }};

        // Setup CSRF token for all AJAX requests
        $.ajaxSetup({
            beforeSend: function(xhr) {
                const token = $('meta[name="csrf-token"]').attr('content');
                if (token) {
                    xhr.setRequestHeader('X-CSRF-TOKEN', token);
                }
            }
        });

        var lang = {
            'days': '{{ __('Days') }}',
            'hrs': '{{ __('Hrs') }}',
            'min': '{{ __('Min') }}',
            'sec': '{{ __('Sec') }}',
            'cart_already': '{{ __('Already Added To Card.') }}',
            'cart_out': '{{ __('Out Of Stock') }}',
            'cart_success': '{{ __('Successfully Added To Cart.') }}',
            'cart_empty': '{{ __('Cart is empty.') }}',
            'discount_code_found': '{{ __('Discount Code Found.') }}',
            'no_discount_code': '{{ __('No Discount Code Found.') }}',
            'already_discount_code': '{{ __('Discount Code Already Applied.') }}',
            'enter_discount_code': '{{ __('Enter Discount Code First') }}',
            'minimum_qty_error': '{{ __('Minimum Quantity is:') }}',
            'affiliate_link_copy': '{{ __('Affiliate Link Copied Successfully') }}'
        };

      </script>



    @php
        if (Session::has('success')) {
            echo '<script>
                toastr.success("'.Session::get('success').'")
            </script>';
        }
        if (Session::has('unsuccess')) {
            echo '<script>
                toastr.error("'.Session::get('unsuccess').'")
            </script>';
        }
    @endphp

    {{-- Global Map Picker Modal - Available on all pages --}}
    @include('components.global-map-picker-modal')

    {{-- Cookie Consent Banner & Scripts (GDPR/CCPA Compliance) --}}
    @if (!empty($seo->gtm_id))
    @consentScripts
    @cookieBanner
    @endif

    @stack('scripts')
    @yield('script')
    @livewireScripts
</body>

</html>
