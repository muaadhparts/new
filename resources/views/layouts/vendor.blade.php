<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@lang('Vendor Dashboard')</title>
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
    <link rel="stylesheet" href="{{ asset('assets/front/css/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front') }}/css/style.css">
    <link href="{{ asset('assets/admin/css/jquery.tagit.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/front') }}/css/custom.css">
    <link rel="stylesheet" href="{{ asset('assets/vendor') }}/css/custom.css">
    <link rel="icon" href="{{ asset('assets/images/' . $gs->favicon) }}">
    @include('includes.frontend.extra_head')
    @yield('css')
    <!--favicon-->

</head>
@php
    $user = auth()->user();
@endphp

<body>

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
    <script src="{{ asset('assets/front') }}/js/script.js"></script>

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


</body>

</html>
