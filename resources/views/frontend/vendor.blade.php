@extends('layouts.front')

@section('content')
    <section class="gs-breadcrumb-section bg-class"
        data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">@lang('Vednor Shop')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="javascript:;">@lang('Vednor Shop :') </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>


    <div class="gs-blog-wrapper">
        <div class="container">
            <div class="row flex-column-reverse flex-lg-row">
                <div class="col-12 col-lg-4 col-xl-3 mt-40 mt-lg-0">
                    <div class="gs-product-sidebar-wrapper">
                        <div class="single-product-widget contact-vendor-wrapper">
                            <h5 class="widget-title">@lang('Contact Vendor')</h5>
                            <div class="img-wrapper">
                                <img src="{{ asset('assets/images/users/' . $vendor->photo) }}" alt="vendor img">
                            </div>
                            <ul>
                                <li><span><b>@lang('Store Name:') </b>{{ $vendor->shop_name }}</span></li>
                                <li><span><b>@lang('Owner Name:') </b>{{ $vendor->owner_name }}</span></li>
                                <li><span><b>@lang('Phone:') </b> {{ $vendor->shop_number }}</span></li>
                                <li><span><b>@lang('Email:') </b>{{ $vendor->email }}</span></li>
                                <li><span><b>@lang('Address:') </b>{{ $vendor->shop_address }}</span></li>
                            </ul>
                            @if (!auth()->id() == $vendor->id)
                            @if (auth()->check())
                            <form action="{{ route('user-contact') }}" method="POST">
                                @csrf 
                                <input type="hidden" name="user_id" value="{{auth()->id()}}">
                                <div class="vendor-input-wrapper">
                                   
                                    <div class="input-wrapper">
                                        <input class="input-cls" id="email" name="email" type="email"
                                            placeholder="@lang('Email')" readonly value="{{$vendor->email}}" />
                                    </div>
                                    <div class="input-wrapper">
                                        <input class="input-cls" id="subject" name="subject" type="tel"
                                            placeholder="@lang('Subject')" />
                                    </div>
                                    <input type="hidden" name="vendor_id" value="{{ auth()->id() }}">
                                    <div class="input-wrapper">
                                        <textarea placeholder="@lang('Type Your Message...')" name="message" class="input-cls txtarea-cls" rows="3"></textarea>
                                    </div>
                                    <div class="input-wrapper">
                                        <button class="template-btn w-100" type="submit">@lang('Send Message')</button>
                                    </div>
                                </div>
                            </form>
                            @else
                            <div class="input-wrapper">
                                <a href="{{route("user.login")}}" class="template-btn w-100" type="button">@lang('Login for Send Message')</a>
                            </div>
                            @endif  
                            @endif
                            
                            
                        </div>
                        <!-- Price Range -->
                        <div class="single-product-widget">
                            <h5 class="widget-title">@lang('Price Range')</h5>
                            <div class="price-range">
                                <div class="d-none">
                                    <!-- start value -->
                                    <input id="start_value" type="number" name="min"
                                        value="{{ isset($_GET['min']) ? $_GET['min'] : $gs->min_price }}">
                                    <!-- end value -->
                                    <input id="end_value" type="number"
                                        value="{{ isset($_GET['max']) ? $_GET['max'] : $gs->max_price }}">
                                    <!-- max value -->
                                    <input id="max_value" type="number" name="max" value="{{ $gs->max_price }}">
                                </div>
                                <div id="slider-range"></div>

                                <input type="text" id="amount" readonly class="range_output">
                            </div>

                            <button class="template-btn mt-3 w-100" id="price_filter">@lang('Apply Filter')</button>
                            <a href="{{ route('front.category') }}"
                                class="template-btn dark-btn w-100 mt-3">@lang('Clear Filter')</a>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-8 col-xl-9 gs-main-blog-wrapper">

                    @php
                        if (request()->input('view_check') == null || request()->input('view_check') == 'grid-view') {
                            $view = 'grid-view';
                        } else {
                            $view = 'list-view';
                        }
                    @endphp

                    <!-- product nav wrapper -->
                    <div class=" product-nav-wrapper">
                        <h5>@lang('Total Products Found:') {{ $vprods->count() }}</h5>
                        <div class="filter-wrapper">
                            <div class="sort-wrapper">
                                <h5>@lang('Sort by:')</h5>

                                <select class="nice-select" id="sortby" name="sort">
                                    <option value="date_desc">{{ __('Latest Product') }}</option>
                                    <option value="date_asc">{{ __('Oldest Product') }}</option>
                                    <option value="price_asc">{{ __('Lowest Price') }}</option>
                                    <option value="price_desc">{{ __('Highest Price') }}</option>
                                </select>
                            </div>
                            <!-- list and grid view tab btns  start -->
                            <div class="btn-wrapper nav d-none d-xl-inline-block" role="tablist">
                                <button class="grid-btn check_view {{ $view == 'list-view' ? 'active' : '' }}"
                                    data-shopview="list-view" type="button" data-bs-toggle="tab"
                                    data-bs-target="#layout-list-pane" role="tab" aria-controls="layout-list-pane"
                                    aria-selected="{{ $view == 'list-view' ? 'true' : 'false' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="31" height="24"
                                        viewBox="0 0 31 24" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M1.33331 18.7575H3.90917C4.64356 18.7575 5.24248 19.3564 5.24248 20.0908V22.6666C5.24248 23.401 4.64356 24 3.90917 24H1.33331C0.598918 24 0 23.4011 0 22.6666V20.0908C0 19.3564 0.598918 18.7575 1.33331 18.7575ZM10.7121 0H29.44C30.1744 0 30.7734 0.598986 30.7734 1.33331V3.90917C30.7734 4.64349 30.1744 5.24248 29.44 5.24248C15.6911 5.24248 24.461 5.24248 10.7121 5.24248C9.97775 5.24248 9.37876 4.64356 9.37876 3.90917V1.33331C9.37876 0.598918 9.97775 0 10.7121 0ZM1.33331 0H3.90917C4.64356 0 5.24248 0.598986 5.24248 1.33331V3.90917C5.24248 4.64356 4.64356 5.24248 3.90917 5.24248H1.33331C0.598918 5.24248 0 4.64356 0 3.90917V1.33331C0 0.598918 0.598918 0 1.33331 0ZM10.7121 9.37869H29.44C30.1744 9.37869 30.7734 9.97768 30.7734 10.712V13.2879C30.7734 14.0222 30.1744 14.6212 29.44 14.6212C15.6911 14.6212 24.461 14.6212 10.7121 14.6212C9.97775 14.6212 9.37876 14.0223 9.37876 13.2879V10.712C9.37876 9.97761 9.97775 9.37869 10.7121 9.37869ZM1.33331 9.37869H3.90917C4.64356 9.37869 5.24248 9.97768 5.24248 10.712V13.2879C5.24248 14.0223 4.64356 14.6212 3.90917 14.6212H1.33331C0.598918 14.6212 0 14.0223 0 13.2879V10.712C0 9.97761 0.598918 9.37869 1.33331 9.37869ZM10.7121 18.7575H29.44C30.1744 18.7575 30.7734 19.3564 30.7734 20.0908V22.6666C30.7734 23.4009 30.1744 23.9999 29.44 23.9999C15.6911 23.9999 24.461 23.9999 10.7121 23.9999C9.97775 23.9999 9.37876 23.401 9.37876 22.6666V20.0908C9.37876 19.3564 9.97775 18.7575 10.7121 18.7575Z"
                                            fill="#978D8F" />
                                    </svg>
                                </button>
                                <button class="grid-btn check_view  {{ $view == 'grid-view' ? 'active' : '' }}"
                                    type="button" data-shopview="grid-view" data-bs-toggle="tab"
                                    data-bs-target="#layout-grid-pane" role="tab" aria-controls="layout-grid-pane"
                                    aria-selected="{{ $view == 'grid-view' ? 'true' : 'false' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="24"
                                        viewBox="0 0 25 24" fill="none">
                                        <path
                                            d="M9.5685 0H2.8222C1.69252 0 0.773438 0.919078 0.773438 2.04877V8.79506C0.773438 9.92475 1.69252 10.8438 2.8222 10.8438H9.5685C10.6982 10.8438 11.6173 9.92475 11.6173 8.79506V2.04877C11.6173 0.919078 10.6982 0 9.5685 0Z"
                                            fill="#978D8F" />
                                        <path
                                            d="M22.7248 0H15.9785C14.8488 0 13.9297 0.919078 13.9297 2.04877V8.79506C13.9297 9.92475 14.8488 10.8438 15.9785 10.8438H22.7248C23.8544 10.8438 24.7735 9.92475 24.7735 8.79506V2.04877C24.7735 0.919078 23.8544 0 22.7248 0Z"
                                            fill="#978D8F" />
                                        <path
                                            d="M9.5685 13.1562H2.8222C1.69252 13.1562 0.773438 14.0753 0.773438 15.205V21.9513C0.773438 23.081 1.69252 24.0001 2.8222 24.0001H9.5685C10.6982 24.0001 11.6173 23.081 11.6173 21.9513V15.205C11.6173 14.0753 10.6982 13.1562 9.5685 13.1562Z"
                                            fill="#978D8F" />
                                        <path
                                            d="M22.7248 13.1562H15.9785C14.8488 13.1562 13.9297 14.0753 13.9297 15.205V21.9513C13.9297 23.081 14.8488 24.0001 15.9785 24.0001H22.7248C23.8544 24.0001 24.7735 23.081 24.7735 21.9513V15.205C24.7735 14.0753 23.8544 13.1562 22.7248 13.1562Z"
                                            fill="#978D8F" />
                                    </svg>
                                </button>
                            </div>
                            <!-- list and grid view tab btns  end -->
                        </div>
                    </div>



                    @if ($vprods->count() == 0)
                        <!-- product nav wrapper for no data found -->
                        <div class="product-nav-wrapper d-flex justify-content-center ">
                            <h5>@lang('No Product Found')</h5>
                        </div>
                    @else
                        <!-- main content -->
                        <div class="tab-content" id="myTabContent">
                            <!-- product list view start  -->
                            <div class="tab-pane fade {{ $view == 'list-view' ? 'show active' : '' }}"
                                id="layout-list-pane" role="tabpanel" tabindex="0">
                                <div class="row gy-4 mt-20 ">
                                    @foreach ($vprods as $product)
                                        @include('includes.frontend.list_view_product')
                                    @endforeach
                                </div>
                            </div>

                            <div class="tab-pane fade {{ $view == 'grid-view' ? 'show active' : '' }}  "
                                id="layout-grid-pane" role="tabpanel" tabindex="0">
                                <div class="row gy-4 mt-20">
                                    @foreach ($vprods as $product)
                                        @include('includes.frontend.home_product', [
                                            'class' => 'col-sm-6 col-md-6 col-xl-4',
                                        ])
                                    @endforeach
                                </div>
                            </div>
                            <!-- product grid view end  -->
                        </div>
                        {{ $vprods->links('includes.frontend.pagination') }}
                    @endif

                </div>
            </div>
        </div>
    </div>

    <input type="hidden" id="update_min_price" value="">
    <input type="hidden" id="update_max_price" value="">


@endsection
@section('script')
    <script>
        $(document).on("click", "#price_filter", function() {
            let amountString = $("#amount").val();

            amountString = amountString.replace(/\$/g, '');

            // Split the string into two amounts
            let amounts = amountString.split('-');

            // Trim whitespace from each amount
            let amount1 = amounts[0].trim();
            let amount2 = amounts[1].trim();


            $("#update_min_price").val(amount1);
            $("#update_max_price").val(amount2);

            filter();

        });



        // when dynamic attribute changes
        $(".attribute-input, #sortby, #pageby").on('change', function() {
            $(".ajax-loader").show();
            filter();
        });


        function filter() {
            let filterlink =
                '{{ route('front.vendor', str_replace(' ', '-', $vendor->shop_name)) }}';
            let params = new URLSearchParams();


            $(".attribute-input").each(function() {
                if ($(this).is(':checked')) {
                    params.append($(this).attr('name'), $(this).val());
                }
            });

            if ($("#sortby").val() != '') {
                params.append($("#sortby").attr('name'), $("#sortby").val());
            }

            if ($("#start_value").val() != '') {
                params.append($("#start_value").attr('name'), $("#start_value").val());
            }

            let check_view = $('.check_view.active').data('shopview');

            if (check_view) {
                params.append('view_check', check_view);
            }

            if ($("#update_min_price").val() != '') {
                params.append('min', $("#update_min_price").val());
            }
            if ($("#update_max_price").val() != '') {
                params.append('max', $("#update_max_price").val());
            }

            filterlink += '?' + params.toString();

            console.log(filterlink);
            location.href = filterlink;
        }

        // append parameters to pagination links
        function addToPagination() {
            $('ul.pagination li a').each(function() {
                let url = $(this).attr('href');
                let queryString = '?' + url.split('?')[1]; // "?page=1234...."
                let urlParams = new URLSearchParams(queryString);
                let page = urlParams.get('page'); // value of 'page' parameter

                let fullUrl =
                    '{{ route('front.category', [Request::route('category'), Request::route('subcategory'), Request::route('childcategory')]) }}';
                let params = new URLSearchParams();

                $(".attribute-input").each(function() {
                    if ($(this).is(':checked')) {
                        params.append($(this).attr('name'), $(this).val());
                    }
                });

                if ($("#sortby").val() != '') {
                    params.append('sort', $("#sortby").val());
                }


                if ($("#pageby").val() != '') {
                    params.append('pageby', $("#pageby").val());
                }

                params.append('page', page);

                $(this).attr('href', fullUrl + '?' + params.toString());
            });
        }
    </script>

    <script type="text/javascript">
        (function($) {
            "use strict";
            $(function() {
                const start_value = $("#start_value").val();
                const end_value = $("#end_value").val();
                const max_value = $("#max_value").val();

                $("#slider-range").slider({
                    range: true,
                    min: 0,
                    max: max_value,
                    values: [start_value, end_value],
                    step: 10,
                    slide: function(event, ui) {
                        $("#amount").val("$" + ui.values[0] + " - $" + ui.values[1]);
                    },
                });
                $("#amount").val(
                    "$" +
                    $("#slider-range").slider("values", 0) +
                    " - $" +
                    $("#slider-range").slider("values", 5000)
                );
            });

        })(jQuery);
    </script>
@endsection
