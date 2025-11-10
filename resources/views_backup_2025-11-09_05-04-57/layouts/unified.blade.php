<!DOCTYPE html>
<html lang="en" @if(app()->getLocale() ==='ar') dir="rtl" @endif>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — @yield('title', $gs->title ?? 'EPC')</title>
    <meta name="application-name" content="{{ config('app.name') }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ config('app.name') }} — @yield('title', 'Auto Parts Catalog')">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @livewireStyles

    <!--Essential css files-->
    <link rel="stylesheet" href="{{ asset('assets/front/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/all.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/slick.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/jquery-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/datatables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/nice-select2.css')}}">

    {{-- دعم CSS إضافي للوحات الإدارة والبائعين --}}
    @if(isset($isDashboard) && $isDashboard)
        <link href="{{ asset('assets/admin/css/plugin.css') }}" rel="stylesheet" />
        <link href="{{ asset('assets/admin/css/jquery.tagit.css') }}" rel="stylesheet" />
        @if(isset($isVendor) && $isVendor)
            <link rel="stylesheet" href="{{ asset('assets/vendor/css/custom.css') }}">
        @endif
    @endif

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

    {{-- Header area - يظهر دائمًا --}}
    @if(!isset($hideHeader) || !$hideHeader)
        @include('includes.frontend.header')
    @endif

    {{-- Mobile menu based on route and authentication --}}
    @php
        $isUserRoute   = request()->is('user/*');
        $isVendorRoute = request()->is('vendor/*');
        $isRiderRoute  = request()->is('rider/*');
        $isAdminRoute  = request()->is('admin/*');
    @endphp

    @if($isRiderRoute && Auth::guard('rider')->check())
        @include('includes.rider.mobile-header')
    @elseif($isVendorRoute && Auth::guard('web')->check() && auth()->user()->is_vendor == 2)
        @include('includes.vendor.vendor-mobile-header')
    @elseif($isUserRoute && Auth::guard('web')->check())
        @include('includes.user.mobile-header')
    @else
        @include('includes.frontend.mobile_menu')
    @endif

    <div class="overlay"></div>

    {{-- المحتوى الرئيسي --}}
    @if(isset($isDashboard) && $isDashboard)
        {{-- تخطيط لوحة التحكم مع sidebar --}}
        <div class="gs-user-panel-review">
            <div class="d-flex">
                {{-- Sidebar للوحات الإدارة --}}
                @if(isset($isAdmin) && $isAdmin)
                    @include('includes.admin.sidebar')
                @elseif(isset($isVendor) && $isVendor)
                    @include('includes.vendor.sidebar')
                @endif

                {{-- المحتوى الأساسي --}}
                <div class="gs-vendor-header-outlet-wrapper" style="flex: 1;">
                    @if(isset($isVendor) && $isVendor)
                        @include('includes.vendor.header')
                    @endif
                    @yield('content')
                </div>
            </div>
        </div>
    @else
        {{-- تخطيط عادي للواجهة الأمامية --}}
        @yield('content')
    @endif

    {{-- Footer section --}}
    @if(!isset($hideFooter) || !$hideFooter)
        @include('includes.frontend.footer')
    @endif

    <!--Essential Js Files-->
    <script src="{{ asset('assets/front/js/zepto.min.js') }}"></script>
    <script src="{{ asset('assets/front/js/jquery.magnific-popup.js') }}"></script>
    <script src="{{ asset('assets/front/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/front/js/slick.js') }}"></script>
    <script src="{{ asset('assets/front/js/jquery-ui.js') }}"></script>
    <script src="{{ asset('assets/front/js/nice-select2.js') }}"></script>
    <script src="{{ asset('assets/front/js/wow.js') }}"></script>
    <script src="{{ asset('assets/front/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/front/js/toastr.min.js') }}"></script>
    <script src="{{ asset('assets/front/js/script.js') }}"></script>
    <script src="{{ asset('assets/front/js/myscript.js') }}?v={{ time() }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tarekraafat/autocomplete.js@10.2.9/dist/autoComplete.min.js"></script>

    {{-- دعم JS إضافي للوحات الإدارة والبائعين --}}
    @if(isset($isDashboard) && $isDashboard)
        <script src="{{ asset('assets/front/js/datatables.min.js') }}"></script>
        <script src="{{ asset('assets/front/js/jquery.waypoints.min.js') }}"></script>
        <script src="{{ asset('assets/front/js/apexcharts.js') }}"></script>
        <script src="{{ asset('assets/admin/js/tag-it.js') }}"></script>
        <script src="{{ asset('assets/front/js/jquery.counterup.js') }}"></script>
        @if(isset($isVendor) && $isVendor)
            <script src="{{ asset('assets/vendor/js/myscript.js') }}"></script>
        @endif
    @endif

    <script>
        "use strict";
        var mainurl = "{{ url('/') }}";
        var gs = {!! json_encode(DB::table('generalsettings')->where('id','=',1)->first(['is_loader','decimal_separator','thousand_separator','is_cookie','is_talkto','talkto'])) !!};
        var ps_category = {{ $ps->category ?? 0 }};

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

        @if(isset($isDashboard) && $isDashboard)
            var admin_loader = {{ $gs->is_admin_loader ?? 0 }};
            var whole_sell = {{ $gs->wholesell ?? 0 }};
            @if(isset($isVendor) && $isVendor)
                var getattrUrl = '{{ route('vendor-prod-getattributes') }}';
            @else
                var getattrUrl = '{{ route('admin-prod-getattributes') }}';
            @endif
            @php
                $curr = \App\Models\Currency::where('is_default', '=', 1)->first();
            @endphp
            var curr = {!! json_encode($curr) !!};
        @endif
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
      window.applyCartState = function(data) {
        if (!data) return;
        const count = data.cart_count ?? data.totalQty ?? (Array.isArray(data) ? data[0] : null);
        const total = data.cart_total ?? (Array.isArray(data) && data[1] ? data[1] : null);
        const totalQty = data.totalQty ?? count;
        const totalPrice = data.totalPrice ?? null;

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
              if (el.hasAttribute('data-cart-count')) {
                el.setAttribute('data-cart-count', count);
              }
            }
          });
        });

        if (total) {
          const totalElements = document.querySelectorAll('.mini-cart-total, [data-cart-total]');
          totalElements.forEach(function(el) {
            if (el) el.textContent = total;
          });
        }

        window.dispatchEvent(new CustomEvent('cart:updated', {
          detail: {
            ok: data.ok ?? true,
            cart_count: count,
            cart_total: total,
            totalQty: totalQty,
            totalPrice: totalPrice
          }
        }));

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

    {{-- Performance and UX Enhancements --}}
    <script>
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

    if ('loading' in HTMLImageElement.prototype) {
        const images = document.querySelectorAll('img[loading="lazy"]');
        images.forEach(img => {
            img.src = img.dataset.src || img.src;
        });
    }

    @if(app()->getLocale() === 'ar')
    jQuery(document).ready(function($) {
        $('.slick-slider').not('.slick-initialized').each(function() {
            $(this).slick('setOption', 'rtl', true, true);
        });
    });
    @endif

    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('show.bs.modal', function() {
            const scrollBtn = document.getElementById('scrollToTop');
            if (scrollBtn) {
                scrollBtn.style.display = 'none';
            }
        });

        document.addEventListener('hide.bs.modal', function() {
            const scrollBtn = document.getElementById('scrollToTop');
            if (scrollBtn && window.pageYOffset > 300) {
                scrollBtn.style.display = 'block';
            }
        });
    });
    </script>

    {{-- Scroll to Top Button --}}
    <button id="scrollToTop" onclick="window.scrollTo({top: 0, behavior: 'smooth'})"
            style="display: none; position: fixed; bottom: 30px; {{ app()->getLocale() === 'ar' ? 'left' : 'right' }}: 30px; z-index: 1040; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); transition: all 0.3s ease;">
        <i class="fas fa-arrow-up"></i>
    </button>

    <style>
    #scrollToTop:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.5);
    }
    body.modal-open #scrollToTop {
        display: none !important;
    }
    </style>
</body>
</html>
