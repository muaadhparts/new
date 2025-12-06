{{--
================================================================================
    MUAADH THEME - VENDOR LAYOUT
================================================================================
    CSS GUIDELINES FOR AI AGENTS:
    -----------------------------
    1. The ONLY file for adding/modifying custom CSS is: public/assets/front/css/MUAADH.css
    2. DO NOT add <style> tags in Blade files - move all styles to MUAADH.css
    3. DO NOT create new CSS files - use MUAADH.css sections instead
    4. DO NOT modify style.css (legacy base file)
    5. Use CSS variables from MUAADH.css (--muaadh-primary, --muaadh-radius, etc.)
    6. Add new styles under appropriate section comments in MUAADH.css

    FILE STRUCTURE:
    - style.css = Legacy base (DO NOT MODIFY)
    - MUAADH.css = Main theme file (ALL CUSTOMIZATIONS HERE)
    - Vendor CSS in assets/vendor/css = Vendor-specific only
================================================================================
--}}
<!DOCTYPE html>
<html lang="en" dir="{{ $langg && $langg->rtl == 1 ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@lang('Vendor Dashboard')</title>
    <!--Essential css files-->
    @if($langg && $langg->rtl == 1)
        <link rel="stylesheet" href="{{ asset('assets/front') }}/css/bootstrap.rtl.min.css">
    @else
        <link rel="stylesheet" href="{{ asset('assets/front') }}/css/bootstrap.min.css">
    @endif
    <link rel="stylesheet" href="{{ asset('assets/front/css/all.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front') }}/css/slick.css">
    <link rel="stylesheet" href="{{ asset('assets/front') }}/css/nice-select.css">
    <link rel="stylesheet" href="{{ asset('assets/front') }}/css/jquery-ui.css">
    <link rel="stylesheet" href="{{ asset('assets/front') }}/css/animate.css">
    <link rel="stylesheet" href="{{ asset('assets/front/css/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front') }}/css/datatables.min.css">
    <link rel="stylesheet" href="{{ asset('assets/front') }}/css/style.css?v={{ time() }}">
    <link href="{{ asset('assets/admin/css/jquery.tagit.css') }}" rel="stylesheet" />
    @if($langg && $langg->rtl == 1)
        <link rel="stylesheet" href="{{ asset('assets/front') }}/css/rtl.css">
    @endif
    <link rel="stylesheet" href="{{ asset('assets/vendor') }}/css/custom.css">
    {{-- MUAADH Theme - Unified Styles (Main Theme File) --}}
    <link rel="stylesheet" href="{{ asset('assets/front/css/MUAADH.css') }}">
    {{-- Theme Colors - Generated from Admin Panel --}}
    <link rel="stylesheet" href="{{ asset('assets/front/css/theme-colors.css') }}?v={{ filemtime(public_path('assets/front/css/theme-colors.css')) }}">
    <link rel="icon" href="{{ asset('assets/images/' . $gs->favicon) }}">
    @include('includes.frontend.extra_head')
    @livewireStyles

    {{-- Hide bottom layer --}}
    <style>
        .frontend-header-wrapper .header-top {
            display: none !important;
        }
    </style>

    @yield('css')
    <!--favicon-->

</head>
@php
    $user = auth()->user();
    $categories = App\Models\Category::with('subs')->where('status', 1)->get();
    $pages = App\Models\Page::get();
    $currencies = App\Models\Currency::all();
    $languges = App\Models\Language::all();
@endphp

<body>

    <div class="frontend-header-wrapper">
        {{-- Frontend Header --}}
        @include('includes.frontend.header')
    </div>

    {{-- Vendor Dashboard Mobile Sidebar --}}
    @include('includes.vendor.vendor-mobile-header')

    <!-- overlay -->
    <div class="overlay"></div>

    <!-- user dashboard wrapper start -->
    <div class="gs-user-panel-review">
        <div class="d-flex">
            <!-- sidebar -->
            @include('includes.vendor.sidebar')

            <!-- main content (header and outlet) -->
            <div class="gs-vendor-header-outlet-wrapper">
                <!-- header start  -->
                @include('includes.vendor.header')
                <!-- header end  -->

                <!-- outlet start  -->
                @yield('content')
                <!-- outlet end  -->
            </div>
        </div>
    </div>
    <!-- user dashboard wrapper end -->


    <div class="modal gs-modal fade" id="confirm-detete-modal" tabindex="-1" aria-hidden="true">
        <form id="delete_url" class="modal-dialog confirm-delete-modal-dialog modal-dialog-centered" method="POST">
            <div class="modal-content confirm-delete-modal-content form-group">
                <div class="modal-header delete-modal-header w-100">
                    <div class="title-des-wrapper">
                        <h4 class="title">@lang('Confirm Delete ?')</h4>
                        <h5 class="sub-title">
                        @lang('Are you sure you want to delete this item?')
                        </h5>
                    </div>
                </div>
                <!-- modal body start  -->

                <!-- Buttons  -->
                <div class="row row-cols-2 w-100">
                    @csrf
                    @method('DELETE')
                    <div class="col">
                        <button type="submit" class="template-btn black-btn w-100" id="">@lang('Delete')</button>
                    </div>
                    <div class="col">
                        <button class="template-btn w-100" data-bs-dismiss="modal" type="button">@lang('Cancel')</button>
                    </div>
                </div>
                <!-- modal body end  -->
            </div>
        </form>
    </div>

    <!--Esential Js Files-->
    <script src="{{ asset('assets/front') }}/js/jquery.min.js"></script>
    <script src="{{ asset('assets/front') }}/js/jquery-ui.js"></script>
    <script src="{{ asset('assets/front') }}/js/nice-select.js"></script>
    <script src="{{ asset('assets/front') }}/js/slick.js"></script>
    <script src="{{ asset('assets/front') }}/js/wow.js"></script>
    <script src="{{ asset('assets/front') }}/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/front') }}/js/datatables.min.js"></script>
    <script src="{{ asset('assets/front') }}/js/jquery.waypoints.min.js"></script>
    <script src="{{ asset('assets/front') }}/js/apexcharts.js"></script>
    <script src="{{ asset('assets/admin/js/tag-it.js') }}"></script>
    <script src="{{ asset('assets/front/js/toastr.min.js') }}"></script>
    <script src="{{ asset('assets/front') }}/js/jquery.counterup.js"></script>
    <script src="{{ asset('assets/front') }}/js/script.js?v={{ time() }}"></script>

    <script type="text/javascript">
        var mainurl = "{{ url('/') }}";
        var admin_loader = {{ $gs->is_admin_loader }};
        var whole_sell = {{ $gs->wholesell }};
        var getattrUrl = '{{ route('vendor-prod-getattributes') }}';
        var curr = {!! json_encode($curr) !!};
        var lang = {
            'additional_price': '{{ __('0.00 (Additional Price)') }}'
        };
    </script>

    @yield('script')

    <script src="{{ asset('assets/vendor') }}/js/myscript.js"></script>


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

@livewireScripts

</body>

</html>
