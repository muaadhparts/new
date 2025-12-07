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
    <title>{{ $gs->title }}</title>
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
    {{-- Main Theme File - Contains all styles --}}
    <link rel="stylesheet" href="{{ asset('assets/front/css/style.css') }}?v={{ time() }}">
    @if($langg && $langg->rtl == 1)
        <link rel="stylesheet" href="{{ asset('assets/front/css/rtl.css') }}">
    @endif
    {{-- Theme Colors - Generated from Admin Panel (overrides :root variables) --}}
    <link rel="stylesheet" href="{{ asset('assets/front/css/theme-colors.css') }}?v={{ filemtime(public_path('assets/front/css/theme-colors.css')) }}">
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

<body>

    @php
        $categories = App\Models\Category::with('subs')->where('status', 1)->get();
        $pages = App\Models\Page::get();
        $currencies = App\Models\Currency::all();
        $languges = App\Models\Language::all();
    @endphp
    <!-- header area -->
    @include('includes.frontend.header')

    <!-- if route is user panel then show vendor.mobile-header else show frontend.mobile_menu -->

    @php
        $url = url()->current();
        $explodeUrl = explode('/',$url);

    @endphp

    @if(in_array('user',$explodeUrl))
    <!-- frontend mobile menu -->
    @include('includes.user.mobile-header')
    @elseif(in_array("rider",$explodeUrl))
    @include('includes.rider.mobile-header')
    @else
    @include('includes.frontend.mobile_menu')
        <!-- user panel mobile sidebar -->

    @endif

    <div class="overlay"></div>

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
        var gs      = {!! json_encode(DB::table('generalsettings')->where('id','=',1)->first(['is_loader','decimal_separator','thousand_separator','is_cookie','is_talkto','talkto'])) !!};
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
            'coupon_found': '{{ __('Coupon Found.') }}',
            'no_coupon': '{{ __('No Coupon Found.') }}',
            'already_coupon': '{{ __('Coupon Already Applied.') }}',
            'enter_coupon': '{{ __('Enter Coupon First') }}',
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

    @stack('scripts')
    @yield('script')
    @livewireScripts
</body>

</html>
