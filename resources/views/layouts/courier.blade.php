{{--
================================================================================
    COURIER LAYOUT - Minimal layout for delivery drivers
================================================================================
    No header, no footer - just the courier panel
================================================================================
--}}
<!DOCTYPE html>
<html lang="{{ $langg->name ?? 'en' }}" dir="{{ $langg && $langg->rtl == 1 ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Courier Panel')) - {{ $gs->site_name }}</title>

    @if($langg && $langg->rtl == 1)
        <link rel="stylesheet" href="{{ asset('assets/front/css/bootstrap.rtl.min.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('assets/front/css/bootstrap.min.css') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('assets/front/css/all.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/nice-select.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/datatables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/style.css') }}?v={{ filemtime(public_path('assets/front/css/style.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/muaadh-system.css') }}?v={{ filemtime(public_path('assets/front/css/muaadh-system.css')) }}">

    @if($langg && $langg->rtl == 1)
        <link rel="stylesheet" href="{{ asset('assets/front/css/rtl.css') }}">
    @endif

    @themeStyles
    <link rel="icon" href="{{ asset('assets/images/' . $gs->favicon) }}">
    @yield('css')
</head>

<body class="m-theme-scope courier-panel-body">

    {{-- Simple Top Bar with Language --}}
    <div class="courier-topbar bg-light border-bottom py-2">
        <div class="container d-flex justify-content-between align-items-center">
            {{-- Mobile Menu Toggle --}}
            <button class="btn btn-sm btn-outline-dark d-xl-none me-2 courier-menu-toggle" type="button">
                <i class="fas fa-bars"></i>
            </button>
            <a href="{{ route('courier-dashboard') }}" class="text-decoration-none">
                <strong>{{ $gs->site_name }}</strong>
            </a>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-globe me-1"></i>
                    {{ $currentLanguage?->language ?? 'EN' }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @foreach ($languges as $language)
                        <li>
                            <a class="dropdown-item {{ Session::get('language') == $language->id ? 'active' : '' }}"
                               href="{{ route('front.language', $language->id) }}">
                                {{ $language->language }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    {{-- Courier Mobile Sidebar --}}
    @include('includes.courier.mobile-header')
    <div class="overlay"></div>

    {{-- Main Content --}}
    @yield('content')

    {{-- Essential JS --}}
    <script src="{{ asset('assets/front/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/front/js/nice-select.js') }}"></script>
    <script src="{{ asset('assets/front/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/front/js/toastr.min.js') }}"></script>
    <script src="{{ asset('assets/front/js/script.js') }}?v={{ time() }}"></script>

    <script>
        "use strict";
        var mainurl = "{{ url('/') }}";

        $.ajaxSetup({
            beforeSend: function(xhr) {
                const token = $('meta[name="csrf-token"]').attr('content');
                if (token) {
                    xhr.setRequestHeader('X-CSRF-TOKEN', token);
                }
            }
        });

        // Courier mobile menu toggle
        $('.courier-menu-toggle').on('click', function() {
            $('.mobile-menu').addClass('active');
            $('.overlay').addClass('active');
        });

        $('.overlay').on('click', function() {
            $('.mobile-menu').removeClass('active');
            $('.overlay').removeClass('active');
        });

        // Close menu button inside mobile menu
        $('.mobile-menu .close-menu, .mobile-menu .cross-btn').on('click', function() {
            $('.mobile-menu').removeClass('active');
            $('.overlay').removeClass('active');
        });
    </script>

    @yield('scripts')

    {{-- Toastr Notifications --}}
    @if(Session::has('success'))
        <script>toastr.success("{{ Session::get('success') }}");</script>
    @endif
    @if(Session::has('unsuccess'))
        <script>toastr.error("{{ Session::get('unsuccess') }}");</script>
    @endif
    @if(Session::has('warning'))
        <script>toastr.warning("{{ Session::get('warning') }}");</script>
    @endif

</body>
</html>
