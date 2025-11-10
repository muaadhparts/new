@extends('layouts.vendor')

@section('content')
    <!-- outlet start  -->
    <div class="gs-vendor-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-vendor-breadcrumb has-mb">
            <h4 class="text-capitalize">@lang('Order Delivery')</h4>
            <ul class="breadcrumb-menu">
                <li>
                    <a href="{{ route('vendor.dashboard') }}" class="text-capitalize">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="#4C3533" class="home-icon-vendor-panel-breadcrumb">
                            <path
                                d="M9 21V13.6C9 13.0399 9 12.7599 9.109 12.546C9.20487 12.3578 9.35785 12.2049 9.54601 12.109C9.75993 12 10.04 12 10.6 12H13.4C13.9601 12 14.2401 12 14.454 12.109C14.6422 12.2049 14.7951 12.3578 14.891 12.546C15 12.7599 15 13.0399 15 13.6V21M2 9.5L11.04 2.72C11.3843 2.46181 11.5564 2.33271 11.7454 2.28294C11.9123 2.23902 12.0877 2.23902 12.2546 2.28295C12.4436 2.33271 12.6157 2.46181 12.96 2.72L22 9.5M4 8V17.8C4 18.9201 4 19.4802 4.21799 19.908C4.40974 20.2843 4.7157 20.5903 5.09202 20.782C5.51985 21 6.0799 21 7.2 21H16.8C17.9201 21 18.4802 21 18.908 20.782C19.2843 20.5903 19.5903 20.2843 19.782 19.908C20 19.4802 20 18.9201 20 17.8V8L13.92 3.44C13.2315 2.92361 12.8872 2.66542 12.5091 2.56589C12.1754 2.47804 11.8246 2.47804 11.4909 2.56589C11.1128 2.66542 10.7685 2.92361 10.08 3.44L4 8Z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('vendor.dashboard') }}" class="text-capitalize">
                        @lang('Dashboard')
                    </a>
                </li>

                <li>
                    <a href="javascript:;" class="text-capitalize">
                        @lang('Order Delivery')
                    </a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->

        <!-- Table area start  -->
        <div class="vendor-table-wrapper all-orders-table-wrapper">
            <div class="user-table table-responsive position-relative">
                <table class="gs-data-table w-100">
                    <thead>
                        <tr>
                            <th>{{ __('Order Number') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Total Cost') }}</th>
                            <th>{{ __('Payment Method') }}</th>
                            <th>{{ __('Rider') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($datas as $data)
                            <tr>
                                <!-- Order Number -->
                                <td><span class="content">{{ $data->order_number }}</span></td>


                                <td class="text-start">
                                    <div class="customer">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="key">@lang('Name:')</span>
                                            <span class="value">{{ $data->customer_name }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="key">@lang('Email:')</span>
                                            <span class="value">{{ $data->customer_email }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="key">@lang('Phone:')</span>
                                            <span class="value">{{ $data->customer_phone }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="key">@lang('Country:')</span>
                                            <span class="value">{{ $data->customer_country }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="key">@lang('City:')</span>
                                            <span class="value">{{ $data->customer_city }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="key">@lang('Postal Code:')</span>
                                            <span class="value">{{ $data->customer_zip }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="key">@lang('Address:')</span>
                                            <span class="value">{{ $data->customer_address }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="key">@lang('Order Date:')</span>
                                            <span class="value">{{ $data->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </td>





                                <!-- Total Cost -->
                                <td>
                                    @php
                                      $order = App\Models\Order::findOrFail($data->id);
                                        $user = auth()->user();
                                        $user_id = $user->id;
                                        
                                        // Calculate base price for the vendor orders by the user
                                        $price = $order->vendororders()->where('user_id', $user_id)->sum('price');
                                        
                                        // Check if shipping is required
                                        if ($order->is_shipping == 1) {
                                            // Decode the JSON data and access shipping ID
                                            $vendor_shipping = json_decode($order->vendor_shipping_id);
                                            $vendor_packing_id = json_decode($order->vendor_packing_id);
                                        
                                            // Attempt to retrieve shipping cost if the ID exists
                                            $shipping_id = optional($vendor_shipping)->$user_id ?? null;
                                            if ($shipping_id) {
                                                $shipping = App\Models\Shipping::find($shipping_id);
                                                if ($shipping) {
                                                    $price += round($shipping->price * $order->currency_value, 2);
                                                }
                                            }
                                        
                                            // Attempt to retrieve packaging cost if the ID exists
                                            $packing_id = optional($vendor_packing_id)->$user_id ?? null;
                                            if ($packing_id) {
                                                $packaging = App\Models\Package::find($packing_id);
                                                if ($packaging) {
                                                    $price += round($packaging->price * $order->currency_value, 2);
                                                }
                                            }
                                        }

                                    @endphp
                                    <span
                                        class="content">{{ PriceHelper::showOrderCurrencyPrice($price, $data->currency_sign) }}</span>
                                </td>
                                <!-- Payment Method -->
                                <td>
                                    <span class="content">
                                        {{ $data->method }}
                                    </span>
                                </td>
                                <!-- Status -->




                                <td class="text-start">
                                    @php
                                        $delivery = App\Models\DeliveryRider::where('order_id', $data->id)
                                            ->whereVendorId(auth()->id())
                                            ->first();
                                    @endphp


                                    <div class="rider">
                                        @if ($delivery)
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="key">@lang('Rider :')</span>
                                                <span class="value">{{ $delivery->rider->name }}</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="key">@lang('Delivery Cost :')</span>
                                                <span
                                                    class="value">{{ PriceHelper::showAdminCurrencyPrice($delivery->servicearea->price) }}</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="key">@lang('Pickup Point :')</span>
                                                <span class="value">{{ $delivery->pickup->location }}</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="key">@lang('Status :')</span>
                                                <button type="button" disabled=""
                                                    class="template-btn md-btn yellow-btn">
                                                    {{ $delivery->status }}
                                                </button>
                                            </div>
                                        @else
                                            <span class="template-btn md-btn primary-btn">
                                                @lang('Not Assigned')
                                            </span>
                                        @endif
                                    </div>
                                </td>




                                <!-- Actions -->
                                <td>



                                    @php
                                        $delevery = App\Models\DeliveryRider::where('vendor_id', auth()->id())
                                            ->where('order_id', $data->id)
                                            ->first();
                                        $delevery = App\Models\DeliveryRider::where('vendor_id', auth()->id())
                                            ->where('order_id', $data->id)
                                            ->first();
                                    @endphp

                                    @if ($delevery && $delevery->status == 'delivered')
                                        <a href="{{ route('vendor-order-show', $data->order_number) }}"
                                            class="template-btn md-btn black-btn mx-auto">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <g clip-path="url(#clip0_548_165891)">
                                                    <path
                                                        d="M12 4.84668C7.41454 4.84668 3.25621 7.35543 0.187788 11.4303C-0.0625959 11.7641 -0.0625959 12.2305 0.187788 12.5644C3.25621 16.6442 7.41454 19.1529 12 19.1529C16.5855 19.1529 20.7438 16.6442 23.8122 12.5693C24.0626 12.2354 24.0626 11.769 23.8122 11.4352C20.7438 7.35543 16.5855 4.84668 12 4.84668ZM12.3289 17.0369C9.28506 17.2284 6.7714 14.7196 6.96287 11.6709C7.11998 9.1572 9.15741 7.11977 11.6711 6.96267C14.7149 6.7712 17.2286 9.27994 17.0371 12.3287C16.8751 14.8375 14.8377 16.8749 12.3289 17.0369ZM12.1767 14.7098C10.537 14.8129 9.18196 13.4628 9.28997 11.8231C9.37343 10.468 10.4732 9.37322 11.8282 9.28485C13.4679 9.18175 14.823 10.5319 14.7149 12.1716C14.6266 13.5316 13.5268 14.6264 12.1767 14.7098Z"
                                                        fill="white"></path>
                                                </g>
                                                <defs>
                                                    <clipPath id="clip0_548_165891">
                                                        <rect width="24" height="24" fill="white"></rect>
                                                    </clipPath>
                                                </defs>
                                            </svg>
                                            @lang('View Order')
                                        </a>
                                    @else
                                        <a href="{{ route('vendor-order-show', $data->order_number) }}"
                                            class="template-btn md-btn black-btn mx-auto searchDeliveryRider" data-bs-toggle="modal"
                                            data-bs-target="#riderList" customer-city="{{ $data->customer_city }}"
                                            order_id="{{ $data->id }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none">
                                                <path
                                                    d="M20 21C20 19.6044 20 18.9067 19.8278 18.3389C19.44 17.0605 18.4395 16.06 17.1611 15.6722C16.5933 15.5 15.8956 15.5 14.5 15.5H9.5C8.10444 15.5 7.40665 15.5 6.83886 15.6722C5.56045 16.06 4.56004 17.0605 4.17224 18.3389C4 18.9067 4 19.6044 4 21M16.5 7.5C16.5 9.98528 14.4853 12 12 12C9.51472 12 7.5 9.98528 7.5 7.5C7.5 5.01472 9.51472 3 12 3C14.4853 3 16.5 5.01472 16.5 7.5Z"
                                                    stroke="white" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round"></path>
                                            </svg>
                                            @lang('Assign Rider')
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Table area end  -->
    </div>
    <!-- outlet end  -->



    <div class="modal gs-modal fade" id="riderList" tabindex="-1" aria-hidden="true">
        <form action="{{ route('vendor-rider-search-submit') }}"
            class="modal-dialog assign-rider-modal-dialog modal-dialog-centered" method="POST">
            @csrf
            <input type="hidden" name="order_id" value="" id="vendor_rider_find_order_id">

            <div class="modal-content assign-rider-modal-content form-group">
                <div class="modal-header w-100">
                    <h4 class="title">@lang('Assign Rider')</h4>
                    <button type="button" data-bs-dismiss="modal">
                        <i class="fa-regular fa-circle-xmark gs-modal-close-btn"></i>
                    </button>

                </div>
                <!-- modal body start  -->
                <!-- Select Rider -->
                <div class="input-label-wrapper w-100">
                    <label>@lang('Select Rider')</label>
                    <div class="dropdown-container">
                        <select class="form-control nice-select nice-list form__control" name="rider_id" id="redierList"
                            required>

                        </select>
                    </div>
                </div>
                <!-- Select Pickup Point -->
                <div class="input-label-wrapper w-100">
                    <label>@lang('Select Rider')</label>
                    <div class="dropdown-container">
                        <select class="form-control nice-select form__control" name="pickup_point_id"
                            id="pickup_point_id" required>
                            <option value="" selected>@lang('Select Pickup Point')</option>
                            @foreach (App\Models\PickupPoint::where('user_id', auth()->id())->whereStatus(1)->get() as $pickup)
                                <option value="{{ $pickup->id }}">{{ $pickup->location }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>


                <div class="col-lg-12 py-3 d-none viewRider">
                    <div><strong>
                        @lang('Rider Name :') <span id="ridername"></span>
                        </strong></div>
                    <div>
                        <strong>
                            @lang('Delivery Cost :') <span id="ridercost"></span>
                        </strong>
                    </div>
                    <div>
                        <strong>
                            @lang('Service Area :') <span id="serviceArea"></span>
                        </strong>
                    </div>

                </div>

                <!-- Assign Rider Button  -->
                <button class="template-btn" data-bs-dismiss="modal" type="submit">@lang('Assign Rider')</button>
                <!-- modal body end  -->
            </div>
        </form>
    </div>
@endsection
@section('script')
    {{-- DATA TABLE --}}
    <script src="{{ asset('assets/front/js/select2.min.js') }}"></script>

    <script type="text/javascript">
        (function($) {
            "use strict";
            $(document).on('change', '.vendor-btn', function() {
                $('#vendor-status').modal('show');
                $('#vendor-status').find('.btn-ok').attr('href', $(this).val());
            });

            $(document).on('click', '.searchDeliveryRider', function() {
                let city = $(this).attr('customer-city');
                let order_id = $(this).attr('order_id');
                $('#vendor_rider_find_order_id').val(order_id);
                $.get("{{ route('vendor.find.rider') }}", {
                    city: city
                }, function(data) {

                    $('#redierList').html(data.riders);
                    $(".nice-list").niceSelect("update");
                })
            })


            $(document).on('change', '#redierList', function() {
                let rider_id = $(this).val();
                let area = $(this).find('option:selected').attr('area');
                let riderName = $(this).find('option:selected').attr('riderName');
                let rider_cost = $(this).find('option:selected').attr('riderCost');
                $('#ridername').text(riderName);
                $('#serviceArea').text(area);
                $('#ridercost').text(rider_cost);
                $('.viewRider').removeClass('d-none');

            })

            $('.rider_select2').select2({
                placeholder: "Select Rider",
                allowClear: true
            });

            $('.pickup_select2').select2({
                placeholder: "Select Pickup Point",
                allowClear: true
            });


            $(document).on('submit', '#riderSearchForm', function(e) {
                e.preventDefault();
                var form = $(this);
                var actionUrl = form.attr('action');

                $.ajax({
                    type: "POST",
                    url: actionUrl,
                    data: form.serialize(), // serializes the form's elements.
                    success: function(data) {
                        if (data.success == true) {
                            $('#riderList').modal('hide');
                            $('#redierList').val(null).trigger('change');
                            $('.viewRider').addClass('d-none');
                            $('#vendor_rider_find_order_id').val('');
                            $.notify(data.message, "success");
                            table.ajax.reload();
                        }
                    }
                });
            })


        })(jQuery);
    </script>
@endsection
