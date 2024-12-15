@extends('layouts.vendor')


@section('content')
    <div class="gs-vendor-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-vendor-breadcrumb has-mb">

            <div class="gs-deposit-title d-flex align-items-center gap-4">
                <a href="{{route("vendor-order-index")}}" class="back-btn">
                    <i class="fa-solid fa-arrow-left-long"></i>
                </a>
                <h4 class="text-capitalize">@lang('Order Invoice')</h4>
            </div>


            
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
                    <a href="vendor-dashboard.html" class="text-capitalize">
                        @lang('Dashboard')
                    </a>
                </li>
                <li>
                    <a href="#" class="text-capitalize"> @lang('Order') </a>
                </li>
                <li>
                    <a href="#" class="text-capitalize">@lang('Invoice')  </a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->

        <!-- Vendor Order Invoice start  -->
        <div class="gs-vendor-order-invoice">
            <!-- order address info -->
            <div class="user-purchase-title-wrapper">
                <div>
                    <h4 class="order-number">@lang('Genius Shop')</h4>
                </div>

                <a href="{{route('vendor-order-print',$order->order_number)}}" class="template-btn dark-btn" type="button">
                    @lang('Print Order')
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M18 7V5.2C18 4.0799 18 3.51984 17.782 3.09202C17.5903 2.71569 17.2843 2.40973 16.908 2.21799C16.4802 2 15.9201 2 14.8 2H9.2C8.0799 2 7.51984 2 7.09202 2.21799C6.71569 2.40973 6.40973 2.71569 6.21799 3.09202C6 3.51984 6 4.0799 6 5.2V7M6 18C5.07003 18 4.60504 18 4.22354 17.8978C3.18827 17.6204 2.37962 16.8117 2.10222 15.7765C2 15.395 2 14.93 2 14V11.8C2 10.1198 2 9.27976 2.32698 8.63803C2.6146 8.07354 3.07354 7.6146 3.63803 7.32698C4.27976 7 5.11984 7 6.8 7H17.2C18.8802 7 19.7202 7 20.362 7.32698C20.9265 7.6146 21.3854 8.07354 21.673 8.63803C22 9.27976 22 10.1198 22 11.8V14C22 14.93 22 15.395 21.8978 15.7765C21.6204 16.8117 20.8117 17.6204 19.7765 17.8978C19.395 18 18.93 18 18 18M15 10.5H18M9.2 22H14.8C15.9201 22 16.4802 22 16.908 21.782C17.2843 21.5903 17.5903 21.2843 17.782 20.908C18 20.4802 18 19.9201 18 18.8V17.2C18 16.0799 18 15.5198 17.782 15.092C17.5903 14.7157 17.2843 14.4097 16.908 14.218C16.4802 14 15.9201 14 14.8 14H9.2C8.0799 14 7.51984 14 7.09202 14.218C6.71569 14.4097 6.40973 14.7157 6.21799 15.092C6 15.5198 6 16.0799 6 17.2V18.8C6 19.9201 6 20.4802 6.21799 20.908C6.40973 21.2843 6.71569 21.5903 7.09202 21.782C7.51984 22 8.07989 22 9.2 22Z"
                            stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
            </div>
            <!-- purchase address -->
            <div class="purchase-address-wrapper">


                @php

                    $price = $order
                        ->vendororders()
                        ->where('user_id', '=', $user->id)
                        ->sum('price');

                    if ($order->is_shipping == 1) {
                        $vendor_shipping = json_decode($order->vendor_shipping_id);
                        $user_id = auth()->id();
                        // shipping cost
                        $shipping_id = $vendor_shipping->$user_id;
                        $shipping = App\Models\Shipping::findOrFail($shipping_id);
                        if ($shipping) {
                            $price = $price + round($shipping->price * $order->currency_value, 2);
                        }

                        // packaging cost
                        $vendor_packing_id = json_decode($order->vendor_packing_id);
                        $packing_id = $vendor_packing_id->$user_id;
                        $packaging = App\Models\Package::findOrFail($packing_id);
                        if ($packaging) {
                            $price = $price + round($packaging->price * $order->currency_value, 2);
                        }
                    }

                @endphp





                <!-- Order Details -->
                <div class="address-item w-100">
                    <h5>@lang('Order Details')</h5>
                    <ul>
                        <li>
                            <span class="fw-semibold">@lang('Invoice Number :')</span>
                            <span class="fw-normal">{{ $order->order_number }}</span>
                        </li>
                        <li>
                            <span class="fw-semibold">@lang('Order Date :')</span>
                            <span class="fw-normal">{{ date('d-M-Y H:i:s a', strtotime($order->created_at)) }}</span>
                        </li>

                        @if (isset($shipping))
                            <li>
                                <span class="fw-semibold">@lang('Shipping Method :')</span>
                                <span class="fw-normal">
                                    {{ $shipping->title }} |
                                    {{ \PriceHelper::showOrderCurrencyPrice($shipping->price * $order->currency_value, $order->currency_sign) }}
                                </span>
                            </li>
                        @endif

                        @if (isset($packaging))
                            <li>
                                <span class="fw-semibold">@lang('Packaging Method :')</span>
                                <span class="fw-normal">
                                    {{ $packaging->title }}
                                    |
                                    {{ \PriceHelper::showOrderCurrencyPrice($packaging->price * $order->currency_value, $order->currency_sign) }}</span>
                            </li>
                            </span>
                            </li>
                        @endif


                        <li>
                            <span class="fw-semibold">@lang('Payment Method :')</span>
                            <span class="fw-normal">{{ $order->method }}</span>
                        </li>

                        <li>
                            <span class="fw-semibold">@lang('Transaction ID :')</span>
                            <span class="fw-normal">{{ $order->txnid ?? '--' }}</span>
                        </li>
                        <li>
                            <span class="fw-semibold">@lang('Payment Status :')</span>
                            @if ($order->payment_status == 'Pending')
                                <span class="template-btn danger-btn sm-btn">
                                    @lang('Unpaid')
                                </span>
                            @else
                                <span class="template-btn green-btn sm-btn">
                                    @lang('Paid')
                                </span>
                            @endif

                        </li>
                    </ul>
                </div>


                  <!-- Billing Address -->
                  <div class="address-item">
                    <h5>@lang('Billing Address')</h5>
                    <ul>
                        <li>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M11.9999 15C8.82977 15 6.01065 16.5306 4.21585 18.906C3.82956 19.4172 3.63641 19.6728 3.64273 20.0183C3.64761 20.2852 3.81521 20.6219 4.02522 20.7867C4.29704 21 4.67372 21 5.42708 21H18.5726C19.326 21 19.7027 21 19.9745 20.7867C20.1845 20.6219 20.3521 20.2852 20.357 20.0183C20.3633 19.6728 20.1701 19.4172 19.7839 18.906C17.9891 16.5306 15.1699 15 11.9999 15Z"
                                    stroke="#4C3533" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path
                                    d="M11.9999 12C14.4851 12 16.4999 9.98528 16.4999 7.5C16.4999 5.01472 14.4851 3 11.9999 3C9.51457 3 7.49985 5.01472 7.49985 7.5C7.49985 9.98528 9.51457 12 11.9999 12Z"
                                    stroke="#4C3533" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            {{ $order->customer_name }}
                        </li>

                        @if ($order->customer_address)
                            
                       
                        <li>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M12 12.5C13.6569 12.5 15 11.1569 15 9.5C15 7.84315 13.6569 6.5 12 6.5C10.3431 6.5 9 7.84315 9 9.5C9 11.1569 10.3431 12.5 12 12.5Z"
                                    stroke="#4C3533" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path
                                    d="M12 22C14 18 20 15.4183 20 10C20 5.58172 16.4183 2 12 2C7.58172 2 4 5.58172 4 10C4 15.4183 10 18 12 22Z"
                                    stroke="#4C3533" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            {{ $order->customer_address }}
                        </li>
                        @endif

                        <li>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M8.38028 8.85335C9.07627 10.303 10.0251 11.6616 11.2266 12.8632C12.4282 14.0648 13.7869 15.0136 15.2365 15.7096C15.3612 15.7694 15.4235 15.7994 15.5024 15.8224C15.7828 15.9041 16.127 15.8454 16.3644 15.6754C16.4313 15.6275 16.4884 15.5704 16.6027 15.4561C16.9523 15.1064 17.1271 14.9316 17.3029 14.8174C17.9658 14.3864 18.8204 14.3864 19.4833 14.8174C19.6591 14.9316 19.8339 15.1064 20.1835 15.4561L20.3783 15.6509C20.9098 16.1824 21.1755 16.4481 21.3198 16.7335C21.6069 17.301 21.6069 17.9713 21.3198 18.5389C21.1755 18.8242 20.9098 19.09 20.3783 19.6214L20.2207 19.779C19.6911 20.3087 19.4263 20.5735 19.0662 20.7757C18.6667 21.0001 18.0462 21.1615 17.588 21.1601C17.1751 21.1589 16.8928 21.0788 16.3284 20.9186C13.295 20.0576 10.4326 18.4332 8.04466 16.0452C5.65668 13.6572 4.03221 10.7948 3.17124 7.76144C3.01103 7.19699 2.93092 6.91477 2.9297 6.50182C2.92833 6.0436 3.08969 5.42311 3.31411 5.0236C3.51636 4.66357 3.78117 4.39876 4.3108 3.86913L4.46843 3.7115C4.99987 3.18006 5.2656 2.91433 5.55098 2.76999C6.11854 2.48292 6.7888 2.48292 7.35636 2.76999C7.64174 2.91433 7.90747 3.18006 8.43891 3.7115L8.63378 3.90637C8.98338 4.25597 9.15819 4.43078 9.27247 4.60655C9.70347 5.26945 9.70347 6.12403 9.27247 6.78692C9.15819 6.96269 8.98338 7.1375 8.63378 7.4871C8.51947 7.60142 8.46231 7.65857 8.41447 7.72538C8.24446 7.96281 8.18576 8.30707 8.26748 8.58743C8.29048 8.66632 8.32041 8.72866 8.38028 8.85335Z"
                                    stroke="#4C3533" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>

                            {{ $order->customer_phone }}
                        </li>
                        <li>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M2 7L10.1649 12.7154C10.8261 13.1783 11.1567 13.4097 11.5163 13.4993C11.8339 13.5785 12.1661 13.5785 12.4837 13.4993C12.8433 13.4097 13.1739 13.1783 13.8351 12.7154L22 7M6.8 20H17.2C18.8802 20 19.7202 20 20.362 19.673C20.9265 19.3854 21.3854 18.9265 21.673 18.362C22 17.7202 22 16.8802 22 15.2V8.8C22 7.11984 22 6.27976 21.673 5.63803C21.3854 5.07354 20.9265 4.6146 20.362 4.32698C19.7202 4 18.8802 4 17.2 4H6.8C5.11984 4 4.27976 4 3.63803 4.32698C3.07354 4.6146 2.6146 5.07354 2.32698 5.63803C2 6.27976 2 7.11984 2 8.8V15.2C2 16.8802 2 17.7202 2.32698 18.362C2.6146 18.9265 3.07354 19.3854 3.63803 19.673C4.27976 20 5.11984 20 6.8 20Z"
                                    stroke="#4C3533" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>

                            {{ $order->customer_email }}
                        </li>
                    </ul>
                </div>


                @if ($order->dp == 0)
                <!-- shipping address -->
                <div class="address-item">
                    <h5>@lang('Shipping Address')</h5>
                    <ul>

                        @if ($order->shipping == 'pickup')
                        <li class="info-list-item">
                            <span class="info-type">@lang('Pickup Location :')</span> <span
                                class="info">{{ $order->pickup_location }}</span>
                        </li>
                        @else



                        <li>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M11.9999 15C8.82977 15 6.01065 16.5306 4.21585 18.906C3.82956 19.4172 3.63641 19.6728 3.64273 20.0183C3.64761 20.2852 3.81521 20.6219 4.02522 20.7867C4.29704 21 4.67372 21 5.42708 21H18.5726C19.326 21 19.7027 21 19.9745 20.7867C20.1845 20.6219 20.3521 20.2852 20.357 20.0183C20.3633 19.6728 20.1701 19.4172 19.7839 18.906C17.9891 16.5306 15.1699 15 11.9999 15Z"
                                    stroke="#4C3533" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path
                                    d="M11.9999 12C14.4851 12 16.4999 9.98528 16.4999 7.5C16.4999 5.01472 14.4851 3 11.9999 3C9.51457 3 7.49985 5.01472 7.49985 7.5C7.49985 9.98528 9.51457 12 11.9999 12Z"
                                    stroke="#4C3533" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            {{ $order->shipping_name == null ? $order->customer_name : $order->shipping_name }}
                        </li>

                        @if ($order->shipping_address != null || $order->customer_address != null )
                          
                        <li>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M12 12.5C13.6569 12.5 15 11.1569 15 9.5C15 7.84315 13.6569 6.5 12 6.5C10.3431 6.5 9 7.84315 9 9.5C9 11.1569 10.3431 12.5 12 12.5Z"
                                    stroke="#4C3533" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path
                                    d="M12 22C14 18 20 15.4183 20 10C20 5.58172 16.4183 2 12 2C7.58172 2 4 5.58172 4 10C4 15.4183 10 18 12 22Z"
                                    stroke="#4C3533" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            {{ $order->shipping_address == null ? $order->customer_address : $order->shipping_address }}
                        </li>
                        @endif


                        <li>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M8.38028 8.85335C9.07627 10.303 10.0251 11.6616 11.2266 12.8632C12.4282 14.0648 13.7869 15.0136 15.2365 15.7096C15.3612 15.7694 15.4235 15.7994 15.5024 15.8224C15.7828 15.9041 16.127 15.8454 16.3644 15.6754C16.4313 15.6275 16.4884 15.5704 16.6027 15.4561C16.9523 15.1064 17.1271 14.9316 17.3029 14.8174C17.9658 14.3864 18.8204 14.3864 19.4833 14.8174C19.6591 14.9316 19.8339 15.1064 20.1835 15.4561L20.3783 15.6509C20.9098 16.1824 21.1755 16.4481 21.3198 16.7335C21.6069 17.301 21.6069 17.9713 21.3198 18.5389C21.1755 18.8242 20.9098 19.09 20.3783 19.6214L20.2207 19.779C19.6911 20.3087 19.4263 20.5735 19.0662 20.7757C18.6667 21.0001 18.0462 21.1615 17.588 21.1601C17.1751 21.1589 16.8928 21.0788 16.3284 20.9186C13.295 20.0576 10.4326 18.4332 8.04466 16.0452C5.65668 13.6572 4.03221 10.7948 3.17124 7.76144C3.01103 7.19699 2.93092 6.91477 2.9297 6.50182C2.92833 6.0436 3.08969 5.42311 3.31411 5.0236C3.51636 4.66357 3.78117 4.39876 4.3108 3.86913L4.46843 3.7115C4.99987 3.18006 5.2656 2.91433 5.55098 2.76999C6.11854 2.48292 6.7888 2.48292 7.35636 2.76999C7.64174 2.91433 7.90747 3.18006 8.43891 3.7115L8.63378 3.90637C8.98338 4.25597 9.15819 4.43078 9.27247 4.60655C9.70347 5.26945 9.70347 6.12403 9.27247 6.78692C9.15819 6.96269 8.98338 7.1375 8.63378 7.4871C8.51947 7.60142 8.46231 7.65857 8.41447 7.72538C8.24446 7.96281 8.18576 8.30707 8.26748 8.58743C8.29048 8.66632 8.32041 8.72866 8.38028 8.85335Z"
                                    stroke="#4C3533" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>

                            {{ $order->shipping_phone == null ? $order->customer_phone : $order->shipping_phone }}
                        </li>
                        <li>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M2 7L10.1649 12.7154C10.8261 13.1783 11.1567 13.4097 11.5163 13.4993C11.8339 13.5785 12.1661 13.5785 12.4837 13.4993C12.8433 13.4097 13.1739 13.1783 13.8351 12.7154L22 7M6.8 20H17.2C18.8802 20 19.7202 20 20.362 19.673C20.9265 19.3854 21.3854 18.9265 21.673 18.362C22 17.7202 22 16.8802 22 15.2V8.8C22 7.11984 22 6.27976 21.673 5.63803C21.3854 5.07354 20.9265 4.6146 20.362 4.32698C19.7202 4 18.8802 4 17.2 4H6.8C5.11984 4 4.27976 4 3.63803 4.32698C3.07354 4.6146 2.6146 5.07354 2.32698 5.63803C2 6.27976 2 7.11984 2 8.8V15.2C2 16.8802 2 17.7202 2.32698 18.362C2.6146 18.9265 3.07354 19.3854 3.63803 19.673C4.27976 20 5.11984 20 6.8 20Z"
                                    stroke="#4C3533" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>

                            {{ $order->shipping_email == null ? $order->customer_email : $order->shipping_email }}
                        </li>
                        @endif
                    </ul>
                </div>
                @endif



















            </div>
            <!-- ordered products table -->
            <div class="vendor-table-wrapper order-details-table-wrapper">
                <div class="user-table table-responsive  position-relative">
                    <h4 class="table-title">@lang('Products Ordered')</h4>
                    <table class="gs-data-table w-100">
                        <thead>
                            <tr>
                                <th><span class="header-title">@lang('Product Title')</span></th>
                                <th><span class="header-title">@lang('Details')</span></th>
                                <th><span class="header-title">@lang('Total Price')</span></th>
                            </tr>
                        </thead>
                        <tbody>

                            @php
                                $subtotal = 0;
                                $data = 0;
                                $tax = 0;

                            @endphp

                            @foreach ($cart['items'] as $key => $product)
                                @if ($product['item']['user_id'] != 0)
                                    @if ($product['item']['user_id'] == $user->id)
                                        <tr>

                                            <td>

                                                @if ($product['item']['user_id'] != 0)
                                                    @php
                                                        $user = App\Models\User::find($product['item']['user_id']);
                                                    @endphp
                                                    @if (isset($user))
                                                        <span class="content product-title d-inline-block">
                                                            <a target="_blank"
                                                                href="{{ route('front.product', $product['item']['slug']) }}">
                                                                {{ mb_strlen($product['item']['name'], 'UTF-8') > 30
                                                                    ? mb_substr($product['item']['name'], 0, 30, 'UTF-8') . '...'
                                                                    : $product['item']['name'] }}
                                                            </a>
                                                        </span>
                                                    @else
                                                        <span class="content product-title d-inline-block"><a
                                                                href="javascript:;">
                                                                {{ mb_strlen($product['item']['name'], 'UTF-8') > 30
                                                                    ? mb_substr($product['item']['name'], 0, 30, 'UTF-8') . '...'
                                                                    : $product['item']['name'] }}
                                                            </a>
                                                        </span>
                                                    @endif
                                                @endif


                                                @if ($product['license'] != '')
                                                    <a href="javascript:;" data-toggle="modal"
                                                        data-target="#confirm-delete" class="btn btn-info product-btn"
                                                        id="license" style="padding: 5px 12px;"><i
                                                            class="fa fa-eye"></i>
                                                        {{ __('View License') }}</a>
                                                @endif

                                            </td>
                                            <!-- Details -->
                                            <td class="text-start">
                                                <div class="rider">

                                                    @if ($product['size'])
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="key">@lang('Size :')</span>
                                                            <span
                                                                class="value">{{ str_replace('-', '', $product['size']) }}</span>
                                                        </div>
                                                    @endif

                                                    @if ($product['color'])
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="key">{{ __('Color') }} :</span>
                                                            <span
                                                                style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; border-radius: 50%; background: #{{ $product['color'] }};"
                                                                class="value"></span>
                                                        </div>
                                                    @endif

                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="key">@lang('Price :')</span>
                                                        <span
                                                            class="value">{{ \PriceHelper::showOrderCurrencyPrice($product['item_price'] * $order->currency_value, $order->currency_sign) }}</span>
                                                    </div>



                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="key">@lang('Qty :')</span>
                                                        <span class="value">{{ $product['qty'] }}
                                                            {{ $product['item']['measure'] }}</span>
                                                    </div>

                                                    @if (!empty($product['keys']))
                                                        @foreach (array_combine(explode(',', $product['keys']), explode(',', $product['values'])) as $key => $value)
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span
                                                                    class="key">{{ ucwords(str_replace('_', ' ', $key)) }}
                                                                    :</span>
                                                                <span class="value">{{ $value }}</span>
                                                            </div>
                                                        @endforeach
                                                    @endif

                                                </div>
                                            </td>
                                            <!-- Total Price -->
                                            <td class="text-start">
                                                <span class="content ">
                                                    {{ \PriceHelper::showOrderCurrencyPrice($product['price'] * $order->currency_value, $order->currency_sign) }}
                                                    <small>{{ $product['discount'] == 0 ? '' : '(' . $product['discount'] . '% ' . __('Off') . ')' }}</small>
                                                </span>
                                            </td>

                                            @php
                                                $subtotal += round($product['price'] * $order->currency_value, 2);
                                            @endphp
                                        </tr>
                                    @endif
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- orders calculation  -->
            <ul class="calculation-list">
                <li class="calculation-list-item">
                    <span class="amount-type">@lang('Subtotal')</span> <span
                        class="amount">{{ \PriceHelper::showOrderCurrencyPrice($subtotal, $order->currency_sign) }}</span>
                </li>


                @if (Auth::user()->id == $order->vendor_shipping_id)
                    @if ($order->shipping_cost != 0)
                        <li class="calculation-list-item">
                            <span class="amount-type">@lang('Shipping Cost')</span> <span
                                class="amount">{{ \PriceHelper::showOrderCurrencyPrice($order->shipping_cost, $order->currency_sign) }}</span>
                        </li>

                        @php
                            $data += round($order->shipping_cost, 2);
                        @endphp
                    @endif
                @endif
                @if (Auth::user()->id == $order->vendor_packing_id)
                    @if ($order->packing_cost != 0)
                        <li class="calculation-list-item">
                            <span class="amount-type">@lang('Packaging Cost')</span> <span
                                class="amount">{{ \PriceHelper::showOrderCurrencyPrice($order->packing_cost, $order->currency_sign) }}</span>
                        </li>
                        @php
                            $data += round($order->packing_cost, 2);
                        @endphp
                    @endif
                @endif

                @if ($order->tax != 0)
                    @php
                        $tax = ($subtotal / 100) * $order->tax;
                        $subtotal = $subtotal + $tax;
                    @endphp

                    <li class="calculation-list-item">
                        <span class="amount-type">@lang('TAX')({{ $order->currency_sign }})</span> <span
                            class="amount">{{ \PriceHelper::showOrderCurrencyPrice($tax, $order->currency_sign) }}</span>
                    </li>
                @endif





                <li class="calculation-list-item">
                    <span class="amount-type">@lang('Total')</span> <span
                        class="amount">{{ \PriceHelper::showOrderCurrencyPrice($subtotal + $data, $order->currency_sign) }}</span>
                </li>
            </ul>
        </div>
        <!-- Vendor Order Invoice end  -->
    </div>
    <!-- outlet end  -->
    </div>
    </div>
    </div>
@endsection
