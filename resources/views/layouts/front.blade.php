<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $gs->title }}</title>
    <!--Essential css files-->
    <link rel="stylesheet" href="{{ asset('assets/front') }}/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('assets/front') }}/css/all.css">
    <link rel="stylesheet" href="{{ asset('assets/front') }}/css/slick.css">
    <link rel="stylesheet" href="{{ asset('assets/front') }}/css/nice-select.css">
    <link rel="stylesheet" href="{{ asset('assets/front') }}/css/jquery-ui.css">
    <link rel="stylesheet" href="{{ asset('assets/front') }}/css/animate.css">
    <link rel="stylesheet" href="{{ asset('assets/front/css/all.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front') }}/css/datatables.min.css">
    <link rel="stylesheet" href="{{ asset('assets/front') }}/css/style.css">
    <link rel="stylesheet" href="{{ asset('assets/front') }}/css/custom.css">
    <link rel="icon" href="{{ asset('assets/images/' . $gs->favicon) }}">
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

    @yield('content')


    <!-- footer section -->
    @include('includes.frontend.footer')
    <!-- footer section -->

    <!--Esential Js Files-->
    <script src="{{ asset('assets/front') }}/js/jquery.min.js"></script>
        <script src="{{ asset('assets/front') }}/js/slick.js"></script>
    <script src="{{ asset('assets/front') }}/js/jquery-ui.js"></script>
    <script src="{{ asset('assets/front') }}/js/nice-select.js"></script>
 
    <script src="{{ asset('assets/front') }}/js/wow.js"></script>
    <script src="{{ asset('assets/front') }}/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/front/js/toastr.min.js') }}"></script>
    
    <script src="{{ asset('assets/front') }}/js/script.js"></script>
    <script src="{{ asset('assets/front/js/myscript.js') }}"></script>


    <script>
        "use strict";
        var mainurl = "{{ url('/') }}";
        var gs      = {!! json_encode(DB::table('generalsettings')->where('id','=',1)->first(['is_loader','decimal_separator','thousand_separator','is_cookie','is_talkto','talkto'])) !!};
        var ps_category = {{ $ps->category }};
    
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

      
  @yield('script')

</body>

</html>
