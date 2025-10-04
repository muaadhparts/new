<!DOCTYPE html>
<html lang="en"  @if(app()->getLocale() ==='ar') dir="rtl" @endif  >

{{--@dd(Session::get('language') ,app()->getLocale())--}}
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $gs->title }}</title>
    @livewireStyles

    <!--Essential css files-->
    <link rel="stylesheet" href="{{ asset('assets/front/css/bootstrap.min.css') }}">

{{--    <link rel="stylesheet" href="{{ asset('assets/front/css/bootstrap.rtl.min.css') }}">--}}

    <link rel="stylesheet" href="{{ asset('assets/front/css/all.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/slick.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/front/css/jquery-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/datatables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/magnific-popup.css') }}">
{{--    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tarekraafat/autocomplete.js@10.2.9/dist/css/autoComplete.min.css">--}}
     <link rel="stylesheet" href="{{ asset('assets/front/css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/nice-select2.css')}}">

{{--    @if(app()->getLocale() ==='ar')--}}

{{--        <link rel="stylesheet" href="{{ asset('assets/front/css/style_ar.css') }}">--}}
{{--    @endif--}}
{{--    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tarekraafat/autocomplete.js@10.2.9/dist/css/autoComplete.01.min.css">--}}
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


{{--    {{$slot}}--}}
    @yield('content')


    <!-- footer section -->
    @include('includes.frontend.footer')
    <!-- footer section -->
    {{-- @livewireScripts --}}
    <!--Esential Js Files-->
    {{-- zepto.min.js --}}

    <script src="{{ asset('assets/front/js/zepto.min.js') }}"  ></script>
    <script src="{{ asset('assets/front/js/jquery.magnific-popup.js') }}"  ></script>

    <script src="{{ asset('assets/front/js/jquery.min.js') }}"  ></script>
    <script src="{{ asset('assets/front/js/slick.js') }}"  ></script>
    <script src="{{ asset('assets/front/js/jquery-ui.js') }}"  ></script>
    <script src="{{ asset('assets/front/js/nice-select2.js') }}"  ></script>
    <script src="{{ asset('assets/front/js/wow.js') }}"  ></script>
    <script src="{{ asset('assets/front/js/bootstrap.bundle.min.js') }}"  ></script>
    <script src="{{ asset('assets/front/js/toastr.min.js') }}"  ></script>

    <script src="{{ asset('assets/front/js/script.js') }}"  ></script>

    <script src="{{ asset('assets/front/js/myscript.js') }}"  ></script>

    <script src="https://cdn.jsdelivr.net/npm/@tarekraafat/autocomplete.js@10.2.9/dist/autoComplete.min.js"></script>

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

    @stack('scripts')
  @yield('script')
    @livewireScripts

    {{-- Global Cart State Manager --}}
    <script>
    (function() {
      'use strict';

      /**
       * Global cart state updater - single source of truth
       * Normalizes cart data and updates all UI elements
       */
      window.applyCartState = function(data) {
        if (!data) return;

        // Normalize input (support both new unified format and legacy array format)
        const count = data.cart_count ?? data.totalQty ?? (Array.isArray(data) ? data[0] : null);
        const total = data.cart_total ?? (Array.isArray(data) && data[1] ? data[1] : null);
        const totalQty = data.totalQty ?? count;
        const totalPrice = data.totalPrice ?? null;

        // Update all cart counter badges simultaneously
        const selectors = [
          '[data-cart-count]',
          '#cart-count',
          '.header-cart-count',
          '.cart-count',
          '.mini-cart-count'
        ];

        selectors.forEach(function(sel) {
          const elements = document.querySelectorAll(sel);
          elements.forEach(function(el) {
            if (el && count != null) {
              el.textContent = count;
              // Also update data attribute if present
              if (el.hasAttribute('data-cart-count')) {
                el.setAttribute('data-cart-count', count);
              }
            }
          });
        });

        // Update mini-cart total if present
        if (total) {
          const totalElements = document.querySelectorAll('.mini-cart-total, [data-cart-total]');
          totalElements.forEach(function(el) {
            if (el) el.textContent = total;
          });
        }

        // Dispatch browser event for vanilla JS/Alpine.js
        window.dispatchEvent(new CustomEvent('cart:updated', {
          detail: {
            ok: data.ok ?? true,
            cart_count: count,
            cart_total: total,
            totalQty: totalQty,
            totalPrice: totalPrice
          }
        }));

        // Dispatch Livewire event if available
        if (window.Livewire && typeof window.Livewire.dispatch === 'function') {
          window.Livewire.dispatch('cartUpdated', {
            ok: data.ok ?? true,
            cart_count: count,
            cart_total: total,
            totalQty: totalQty,
            totalPrice: totalPrice
          });
        }
      };

      /**
       * Fallback: fetch cart summary if needed
       */
      window.refreshCartState = function() {
        return fetch('/cart/summary', {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.ok ? r.json() : null; })
        .then(function(data) {
          if (data && typeof window.applyCartState === 'function') {
            window.applyCartState(data);
          }
          return data;
        })
        .catch(function(err) {
          console.warn('Cart summary fetch failed:', err);
          return null;
        });
      };
    })();
    </script>

    <script src="{{ asset('assets/front/js/ill/illustrated.js') }}"></script>

    @stack('scripts')
    @yield('script')

    {{-- Performance and UX Enhancements --}}
    <script>
    // Smooth scroll to top button
    window.addEventListener('scroll', function() {
        const scrollBtn = document.getElementById('scrollToTop');
        if (scrollBtn) {
            if (window.pageYOffset > 300) {
                scrollBtn.style.display = 'block';
            } else {
                scrollBtn.style.display = 'none';
            }
        }
    });

    // Lazy loading images enhancement
    if ('loading' in HTMLImageElement.prototype) {
        const images = document.querySelectorAll('img[loading="lazy"]');
        images.forEach(img => {
            img.src = img.dataset.src || img.src;
        });
    }

    // RTL Direction Fix for Slick Sliders
    @if(app()->getLocale() === 'ar')
    jQuery(document).ready(function($) {
        $('.slick-slider').not('.slick-initialized').each(function() {
            $(this).slick('setOption', 'rtl', true, true);
        });
    });
    @endif
    </script>

    {{-- Scroll to Top Button --}}
    <button id="scrollToTop" onclick="window.scrollTo({top: 0, behavior: 'smooth'})"
            style="display: none; position: fixed; bottom: 30px; {{ app()->getLocale() === 'ar' ? 'left' : 'right' }}: 30px; z-index: 999; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); transition: all 0.3s ease;">
        <i class="fas fa-arrow-up"></i>
    </button>

    <style>
    #scrollToTop:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.5);
    }
    </style>
</body>

</html>

