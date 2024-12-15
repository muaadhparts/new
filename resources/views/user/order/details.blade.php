@extends('layouts.front')

@section('content')
    <div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="d-flex">
                <!-- sidebar -->
                @include('includes.user.sidebar')
                <!-- main content -->
                <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                    <!-- page title -->
                    <div class="gs-deposit-title ms-0 mb-4 d-flex align-items-center">
                        <a href="{{ route('user-orders') }}" class="back-btn">
                            <i class="fa-solid fa-arrow-left-long"></i>
                        </a>

                        <h3 class="ud-page-title">@lang('Purchase Items')</h3>
                    </div>



                    @include('includes.user.order-process')


                    <!-- order address info -->
                    <div class="user-purchase-title-wrapper">
                        <div>
                            <h4 class="order-number">@lang("Order")# {{ $order->order_number }}[{{ $order->status }}]</h4>
                            <span>@lang('Order Date') {{ date('d-M-Y', strtotime($order->created_at)) }}</span>
                        </div>

                        <a class="template-btn dark-btn" href="{{ route('user-order-print', $order->id) }}" target="_blank">
                            @lang('Print Order')
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M18 7V5.2C18 4.0799 18 3.51984 17.782 3.09202C17.5903 2.71569 17.2843 2.40973 16.908 2.21799C16.4802 2 15.9201 2 14.8 2H9.2C8.0799 2 7.51984 2 7.09202 2.21799C6.71569 2.40973 6.40973 2.71569 6.21799 3.09202C6 3.51984 6 4.0799 6 5.2V7M6 18C5.07003 18 4.60504 18 4.22354 17.8978C3.18827 17.6204 2.37962 16.8117 2.10222 15.7765C2 15.395 2 14.93 2 14V11.8C2 10.1198 2 9.27976 2.32698 8.63803C2.6146 8.07354 3.07354 7.6146 3.63803 7.32698C4.27976 7 5.11984 7 6.8 7H17.2C18.8802 7 19.7202 7 20.362 7.32698C20.9265 7.6146 21.3854 8.07354 21.673 8.63803C22 9.27976 22 10.1198 22 11.8V14C22 14.93 22 15.395 21.8978 15.7765C21.6204 16.8117 20.8117 17.6204 19.7765 17.8978C19.395 18 18.93 18 18 18M15 10.5H18M9.2 22H14.8C15.9201 22 16.4802 22 16.908 21.782C17.2843 21.5903 17.5903 21.2843 17.782 20.908C18 20.4802 18 19.9201 18 18.8V17.2C18 16.0799 18 15.5198 17.782 15.092C17.5903 14.7157 17.2843 14.4097 16.908 14.218C16.4802 14 15.9201 14 14.8 14H9.2C8.0799 14 7.51984 14 7.09202 14.218C6.71569 14.4097 6.40973 14.7157 6.21799 15.092C6 15.5198 6 16.0799 6 17.2V18.8C6 19.9201 6 20.4802 6.21799 20.908C6.40973 21.2843 6.71569 21.5903 7.09202 21.782C7.51984 22 8.07989 22 9.2 22Z"
                                    stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                    </div>

                    <div class="purchase-address-wrapper">



                        @if ($order->dp == 1)
                            <div class="address-item">
                                <h5>@lang('Shipping Address')</h5>
                                <ul>
                                    <li>
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M11.9999 15C8.82977 15 6.01065 16.5306 4.21585 18.906C3.82956 19.4172 3.63641 19.6728 3.64273 20.0183C3.64761 20.2852 3.81521 20.6219 4.02522 20.7867C4.29704 21 4.67372 21 5.42708 21H18.5726C19.326 21 19.7027 21 19.9745 20.7867C20.1845 20.6219 20.3521 20.2852 20.357 20.0183C20.3633 19.6728 20.1701 19.4172 19.7839 18.906C17.9891 16.5306 15.1699 15 11.9999 15Z"
                                                stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path
                                                d="M11.9999 12C14.4851 12 16.4999 9.98528 16.4999 7.5C16.4999 5.01472 14.4851 3 11.9999 3C9.51457 3 7.49985 5.01472 7.49985 7.5C7.49985 9.98528 9.51457 12 11.9999 12Z"
                                                stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>
                                        {{ $order->customer_name }}
                                    </li>
                                    <li>
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M12 12.5C13.6569 12.5 15 11.1569 15 9.5C15 7.84315 13.6569 6.5 12 6.5C10.3431 6.5 9 7.84315 9 9.5C9 11.1569 10.3431 12.5 12 12.5Z"
                                                stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path
                                                d="M12 22C14 18 20 15.4183 20 10C20 5.58172 16.4183 2 12 2C7.58172 2 4 5.58172 4 10C4 15.4183 10 18 12 22Z"
                                                stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>
                                        {{ $order->customer_address }},{{ $order->customer_city }}-{{ $order->customer_zip }}
                                    </li>
                                    <li>
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M8.38028 8.85335C9.07627 10.303 10.0251 11.6616 11.2266 12.8632C12.4282 14.0648 13.7869 15.0136 15.2365 15.7096C15.3612 15.7694 15.4235 15.7994 15.5024 15.8224C15.7828 15.9041 16.127 15.8454 16.3644 15.6754C16.4313 15.6275 16.4884 15.5704 16.6027 15.4561C16.9523 15.1064 17.1271 14.9316 17.3029 14.8174C17.9658 14.3864 18.8204 14.3864 19.4833 14.8174C19.6591 14.9316 19.8339 15.1064 20.1835 15.4561L20.3783 15.6509C20.9098 16.1824 21.1755 16.4481 21.3198 16.7335C21.6069 17.301 21.6069 17.9713 21.3198 18.5389C21.1755 18.8242 20.9098 19.09 20.3783 19.6214L20.2207 19.779C19.6911 20.3087 19.4263 20.5735 19.0662 20.7757C18.6667 21.0001 18.0462 21.1615 17.588 21.1601C17.1751 21.1589 16.8928 21.0788 16.3284 20.9186C13.295 20.0576 10.4326 18.4332 8.04466 16.0452C5.65668 13.6572 4.03221 10.7948 3.17124 7.76144C3.01103 7.19699 2.93092 6.91477 2.9297 6.50182C2.92833 6.0436 3.08969 5.42311 3.31411 5.0236C3.51636 4.66357 3.78117 4.39876 4.3108 3.86913L4.46843 3.7115C4.99987 3.18006 5.2656 2.91433 5.55098 2.76999C6.11854 2.48292 6.7888 2.48292 7.35636 2.76999C7.64174 2.91433 7.90747 3.18006 8.43891 3.7115L8.63378 3.90637C8.98338 4.25597 9.15819 4.43078 9.27247 4.60655C9.70347 5.26945 9.70347 6.12403 9.27247 6.78692C9.15819 6.96269 8.98338 7.1375 8.63378 7.4871C8.51947 7.60142 8.46231 7.65857 8.41447 7.72538C8.24446 7.96281 8.18576 8.30707 8.26748 8.58743C8.29048 8.66632 8.32041 8.72866 8.38028 8.85335Z"
                                                stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>

                                        {{ $order->customer_phone }}
                                    </li>
                                    <li>
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M2 7L10.1649 12.7154C10.8261 13.1783 11.1567 13.4097 11.5163 13.4993C11.8339 13.5785 12.1661 13.5785 12.4837 13.4993C12.8433 13.4097 13.1739 13.1783 13.8351 12.7154L22 7M6.8 20H17.2C18.8802 20 19.7202 20 20.362 19.673C20.9265 19.3854 21.3854 18.9265 21.673 18.362C22 17.7202 22 16.8802 22 15.2V8.8C22 7.11984 22 6.27976 21.673 5.63803C21.3854 5.07354 20.9265 4.6146 20.362 4.32698C19.7202 4 18.8802 4 17.2 4H6.8C5.11984 4 4.27976 4 3.63803 4.32698C3.07354 4.6146 2.6146 5.07354 2.32698 5.63803C2 6.27976 2 7.11984 2 8.8V15.2C2 16.8802 2 17.7202 2.32698 18.362C2.6146 18.9265 3.07354 19.3854 3.63803 19.673C4.27976 20 5.11984 20 6.8 20Z"
                                                stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>

                                        {{ $order->customer_email }}
                                    </li>
                                </ul>
                            </div>
                        @else
                            @if ($order->shipping == 'shipto')
                                <div class="address-item">
                                    @if ($order->dp == 0)
                                        <h5>
                                            @lang('Shipping Address')
                                        </h5>
                                    @else
                                        <h5>
                                            @lang('User Information')
                                        </h5>
                                    @endif
                                    <ul>
                                        <li>
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M11.9999 15C8.82977 15 6.01065 16.5306 4.21585 18.906C3.82956 19.4172 3.63641 19.6728 3.64273 20.0183C3.64761 20.2852 3.81521 20.6219 4.02522 20.7867C4.29704 21 4.67372 21 5.42708 21H18.5726C19.326 21 19.7027 21 19.9745 20.7867C20.1845 20.6219 20.3521 20.2852 20.357 20.0183C20.3633 19.6728 20.1701 19.4172 19.7839 18.906C17.9891 16.5306 15.1699 15 11.9999 15Z"
                                                    stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                                <path
                                                    d="M11.9999 12C14.4851 12 16.4999 9.98528 16.4999 7.5C16.4999 5.01472 14.4851 3 11.9999 3C9.51457 3 7.49985 5.01472 7.49985 7.5C7.49985 9.98528 9.51457 12 11.9999 12Z"
                                                    stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                            {{ $order->shipping_name == null ? $order->customer_name : $order->shipping_name }}
                                        </li>
                                        <li>
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M12 12.5C13.6569 12.5 15 11.1569 15 9.5C15 7.84315 13.6569 6.5 12 6.5C10.3431 6.5 9 7.84315 9 9.5C9 11.1569 10.3431 12.5 12 12.5Z"
                                                    stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                                <path
                                                    d="M12 22C14 18 20 15.4183 20 10C20 5.58172 16.4183 2 12 2C7.58172 2 4 5.58172 4 10C4 15.4183 10 18 12 22Z"
                                                    stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                            {{ $order->shipping_address == null ? $order->customer_address : $order->shipping_address }}<br>
                                            {{ $order->shipping_city == null ? $order->customer_city : $order->shipping_city }}-{{ $order->shipping_zip == null ? $order->customer_zip : $order->shipping_zip }}
                                        </li>
                                        <li>
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M8.38028 8.85335C9.07627 10.303 10.0251 11.6616 11.2266 12.8632C12.4282 14.0648 13.7869 15.0136 15.2365 15.7096C15.3612 15.7694 15.4235 15.7994 15.5024 15.8224C15.7828 15.9041 16.127 15.8454 16.3644 15.6754C16.4313 15.6275 16.4884 15.5704 16.6027 15.4561C16.9523 15.1064 17.1271 14.9316 17.3029 14.8174C17.9658 14.3864 18.8204 14.3864 19.4833 14.8174C19.6591 14.9316 19.8339 15.1064 20.1835 15.4561L20.3783 15.6509C20.9098 16.1824 21.1755 16.4481 21.3198 16.7335C21.6069 17.301 21.6069 17.9713 21.3198 18.5389C21.1755 18.8242 20.9098 19.09 20.3783 19.6214L20.2207 19.779C19.6911 20.3087 19.4263 20.5735 19.0662 20.7757C18.6667 21.0001 18.0462 21.1615 17.588 21.1601C17.1751 21.1589 16.8928 21.0788 16.3284 20.9186C13.295 20.0576 10.4326 18.4332 8.04466 16.0452C5.65668 13.6572 4.03221 10.7948 3.17124 7.76144C3.01103 7.19699 2.93092 6.91477 2.9297 6.50182C2.92833 6.0436 3.08969 5.42311 3.31411 5.0236C3.51636 4.66357 3.78117 4.39876 4.3108 3.86913L4.46843 3.7115C4.99987 3.18006 5.2656 2.91433 5.55098 2.76999C6.11854 2.48292 6.7888 2.48292 7.35636 2.76999C7.64174 2.91433 7.90747 3.18006 8.43891 3.7115L8.63378 3.90637C8.98338 4.25597 9.15819 4.43078 9.27247 4.60655C9.70347 5.26945 9.70347 6.12403 9.27247 6.78692C9.15819 6.96269 8.98338 7.1375 8.63378 7.4871C8.51947 7.60142 8.46231 7.65857 8.41447 7.72538C8.24446 7.96281 8.18576 8.30707 8.26748 8.58743C8.29048 8.66632 8.32041 8.72866 8.38028 8.85335Z"
                                                    stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>

                                            {{ $order->shipping_phone == null ? $order->customer_phone : $order->shipping_phone }}
                                        </li>
                                        <li>
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M2 7L10.1649 12.7154C10.8261 13.1783 11.1567 13.4097 11.5163 13.4993C11.8339 13.5785 12.1661 13.5785 12.4837 13.4993C12.8433 13.4097 13.1739 13.1783 13.8351 12.7154L22 7M6.8 20H17.2C18.8802 20 19.7202 20 20.362 19.673C20.9265 19.3854 21.3854 18.9265 21.673 18.362C22 17.7202 22 16.8802 22 15.2V8.8C22 7.11984 22 6.27976 21.673 5.63803C21.3854 5.07354 20.9265 4.6146 20.362 4.32698C19.7202 4 18.8802 4 17.2 4H6.8C5.11984 4 4.27976 4 3.63803 4.32698C3.07354 4.6146 2.6146 5.07354 2.32698 5.63803C2 6.27976 2 7.11984 2 8.8V15.2C2 16.8802 2 17.7202 2.32698 18.362C2.6146 18.9265 3.07354 19.3854 3.63803 19.673C4.27976 20 5.11984 20 6.8 20Z"
                                                    stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>

                                            {{ $order->shipping_email == null ? $order->customer_email : $order->shipping_email }}
                                        </li>
                                    </ul>
                                </div>
                            @else
                                <div class="address-item">
                                    <h5>
                                        @lang('Pickup Address')
                                    </h5>
                                    <ul>

                                        <li>
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M12 12.5C13.6569 12.5 15 11.1569 15 9.5C15 7.84315 13.6569 6.5 12 6.5C10.3431 6.5 9 7.84315 9 9.5C9 11.1569 10.3431 12.5 12 12.5Z"
                                                    stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                                <path
                                                    d="M12 22C14 18 20 15.4183 20 10C20 5.58172 16.4183 2 12 2C7.58172 2 4 5.58172 4 10C4 15.4183 10 18 12 22Z"
                                                    stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>

                                            {{ $order->pickup_location }}
                                        </li>
                                    </ul>
                                </div>
                            @endif
                        @endif


                        @if ($order->dp == 0)
                            <div class="address-item">
                                <h5>@lang('Billing Address')</h5>
                                <ul>
                                    <li>
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M11.9999 15C8.82977 15 6.01065 16.5306 4.21585 18.906C3.82956 19.4172 3.63641 19.6728 3.64273 20.0183C3.64761 20.2852 3.81521 20.6219 4.02522 20.7867C4.29704 21 4.67372 21 5.42708 21H18.5726C19.326 21 19.7027 21 19.9745 20.7867C20.1845 20.6219 20.3521 20.2852 20.357 20.0183C20.3633 19.6728 20.1701 19.4172 19.7839 18.906C17.9891 16.5306 15.1699 15 11.9999 15Z"
                                                stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path
                                                d="M11.9999 12C14.4851 12 16.4999 9.98528 16.4999 7.5C16.4999 5.01472 14.4851 3 11.9999 3C9.51457 3 7.49985 5.01472 7.49985 7.5C7.49985 9.98528 9.51457 12 11.9999 12Z"
                                                stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>
                                        {{ $order->customer_name }}
                                    </li>
                                    <li>
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M12 12.5C13.6569 12.5 15 11.1569 15 9.5C15 7.84315 13.6569 6.5 12 6.5C10.3431 6.5 9 7.84315 9 9.5C9 11.1569 10.3431 12.5 12 12.5Z"
                                                stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path
                                                d="M12 22C14 18 20 15.4183 20 10C20 5.58172 16.4183 2 12 2C7.58172 2 4 5.58172 4 10C4 15.4183 10 18 12 22Z"
                                                stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>
                                        {{ $order->customer_address }},{{ $order->customer_city }}-{{ $order->customer_zip }}
                                    </li>
                                    <li>
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M8.38028 8.85335C9.07627 10.303 10.0251 11.6616 11.2266 12.8632C12.4282 14.0648 13.7869 15.0136 15.2365 15.7096C15.3612 15.7694 15.4235 15.7994 15.5024 15.8224C15.7828 15.9041 16.127 15.8454 16.3644 15.6754C16.4313 15.6275 16.4884 15.5704 16.6027 15.4561C16.9523 15.1064 17.1271 14.9316 17.3029 14.8174C17.9658 14.3864 18.8204 14.3864 19.4833 14.8174C19.6591 14.9316 19.8339 15.1064 20.1835 15.4561L20.3783 15.6509C20.9098 16.1824 21.1755 16.4481 21.3198 16.7335C21.6069 17.301 21.6069 17.9713 21.3198 18.5389C21.1755 18.8242 20.9098 19.09 20.3783 19.6214L20.2207 19.779C19.6911 20.3087 19.4263 20.5735 19.0662 20.7757C18.6667 21.0001 18.0462 21.1615 17.588 21.1601C17.1751 21.1589 16.8928 21.0788 16.3284 20.9186C13.295 20.0576 10.4326 18.4332 8.04466 16.0452C5.65668 13.6572 4.03221 10.7948 3.17124 7.76144C3.01103 7.19699 2.93092 6.91477 2.9297 6.50182C2.92833 6.0436 3.08969 5.42311 3.31411 5.0236C3.51636 4.66357 3.78117 4.39876 4.3108 3.86913L4.46843 3.7115C4.99987 3.18006 5.2656 2.91433 5.55098 2.76999C6.11854 2.48292 6.7888 2.48292 7.35636 2.76999C7.64174 2.91433 7.90747 3.18006 8.43891 3.7115L8.63378 3.90637C8.98338 4.25597 9.15819 4.43078 9.27247 4.60655C9.70347 5.26945 9.70347 6.12403 9.27247 6.78692C9.15819 6.96269 8.98338 7.1375 8.63378 7.4871C8.51947 7.60142 8.46231 7.65857 8.41447 7.72538C8.24446 7.96281 8.18576 8.30707 8.26748 8.58743C8.29048 8.66632 8.32041 8.72866 8.38028 8.85335Z"
                                                stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>

                                        {{ $order->customer_phone }}
                                    </li>
                                    <li>
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M2 7L10.1649 12.7154C10.8261 13.1783 11.1567 13.4097 11.5163 13.4993C11.8339 13.5785 12.1661 13.5785 12.4837 13.4993C12.8433 13.4097 13.1739 13.1783 13.8351 12.7154L22 7M6.8 20H17.2C18.8802 20 19.7202 20 20.362 19.673C20.9265 19.3854 21.3854 18.9265 21.673 18.362C22 17.7202 22 16.8802 22 15.2V8.8C22 7.11984 22 6.27976 21.673 5.63803C21.3854 5.07354 20.9265 4.6146 20.362 4.32698C19.7202 4 18.8802 4 17.2 4H6.8C5.11984 4 4.27976 4 3.63803 4.32698C3.07354 4.6146 2.6146 5.07354 2.32698 5.63803C2 6.27976 2 7.11984 2 8.8V15.2C2 16.8802 2 17.7202 2.32698 18.362C2.6146 18.9265 3.07354 19.3854 3.63803 19.673C4.27976 20 5.11984 20 6.8 20Z"
                                                stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>

                                        {{ $order->customer_email }}
                                    </li>
                                </ul>
                            </div>
                        @endif



                        <div class="address-item">
                            <h5>@lang('Payment Information')</h5>
                            <ul>
                                <li>
                                    <span>@lang('Payment Status :') </span>
                                    <span class="pay-status">
                                        @if ($order->payment_status == 'Pending')
                                            <strong class="text-danger">{{ __('Unpaid') }}</strong>
                                        @else
                                            <strong class="text-success">{{ __('Paid') }}</strong>
                                        @endif
                                    </span>
                                </li>


                                <li><span>@lang('Tax :') </span>
                                    {{ \PriceHelper::showOrderCurrencyPrice($order->tax / $order->currency_value, $order->currency_sign) }}
                                </li>
                                <li><span>@lang('Paid Amount :') </span>
                                    {{ \PriceHelper::showOrderCurrencyPrice($order->pay_amount * $order->currency_value, $order->currency_sign) }}
                                </li>
                                <li><span>@lang('Payment Method:') </span>
                                    {{ $order->method }}
                                    @if ($order->method != 'Cash On Delivery' && $order->method != 'Wallet')
                                        <br>
                                        <span> {{ __('Transaction ID') }} : </span> {{ $order->txnid }}
                                    @endif
                                </li>
                            </ul>
                        </div>
                        <!-- Shipping Method -->
                        @if ($order->dp == 0)
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <h5>@lang('Shipping Method')</h5>
                                <div class="payment-information">

                                    @if ($order->shipping == 'shipto')
                                        <p>{{ __('Ship To Address') }}</p>
                                    @else
                                        <p>{{ __('Pick Up') }}</p>
                                    @endif
                                </div>

                            </div>
                        @endif

                    </div>

                    <h4 class="order-products-header d-flex align-items-center justify-content-center mb-24 wow-replaced"
                        data-wow-delay=".1s">@lang('Ordered Products:')
                    </h4>

                    <!-- ordered products table -->

                    <div class="user-table-wrapper all-orders-table-wrapper wow-replaced" data-wow-delay=".1s">

                        <div class="user-table table-responsive position-relative">
                            <table class="gs-data-table w-100">
                                <tr class="thead-bg">
                                    <th><span class="title">@lang('ID#')</span></th>
                                    <th><span class="title">@lang('Product Name')</span></th>
                                    <th><span class="title">@lang('Details')</span></th>
                                    <th><span class="title">@lang('Unit Price')</span></th>
                                    <th><span class="title">@lang('Total Price')</span></th>
                                </tr>

                                @foreach ($cart['items'] as $product)
                                    <tr class="tbody-product">
                                        <td><b><span class="td-title">{{ $product['item']['id'] }}</span></b></td>

                                        <td class="td-product-name">

                                            <div class="td-title td-product-namee">
                                                <input type="hidden" value="{{ $product['license'] }}">
                                                @if ($product['item']['user_id'] != 0)
                                                    @php
                                                        $user = App\Models\User::find($product['item']['user_id']);
                                                    @endphp
                                                    @if (isset($user))
                                                        <b><a class="a_title_link d-block title-hover-color"
                                                                target="_blank"
                                                                href="{{ route('front.product', $product['item']['slug']) }}">{{ mb_strlen($product['item']['name'], 'UTF-8') > 50
                                                                    ? mb_substr($product['item']['name'], 0, 50, 'UTF-8') . '...'
                                                                    : $product['item']['name'] }}</a></b>
                                                    @else
                                                        <b>
                                                            <a class="a_title_link d-block title-hover-color"
                                                                target="_blank"
                                                                href="{{ route('front.product', $product['item']['slug']) }}">
                                                                {{ mb_strlen($product['item']['name'], 'UTF-8') > 50
                                                                    ? mb_substr($product['item']['name'], 0, 50, 'UTF-8') . '...'
                                                                    : $product['item']['name'] }}
                                                            </a>
                                                        </b>
                                                    @endif
                                                @else
                                                    <b>
                                                        <a class="a_title_link d-block title-hover-color" target="_blank"
                                                            href="{{ route('front.product', $product['item']['slug']) }}">
                                                            {{ mb_strlen($product['item']['name'], 'UTF-8') > 50
                                                                ? mb_substr($product['item']['name'], 0, 50, 'UTF-8') . '...'
                                                                : $product['item']['name'] }}
                                                        </a>
                                                    </b>
                                                @endif
                                                @if ($product['item']['type'] != 'Physical')
                                                    @if ($order->payment_status == 'Completed')
                                                        @if ($product['item']['file'] != null)
                                                            <a class="title-hover-color"
                                                                href="{{ route('user-order-download', ['slug' => $order->order_number, 'id' => $product['item']['id']]) }}"
                                                                class="btn btn-sm btn-primary">
                                                                <i class="fa fa-download"></i>
                                                                {{ __('Download') }}
                                                            </a>
                                                        @else
                                                            <a class="title-hover-color" target="_blank"
                                                                href="{{ $product['item']['link'] }}"
                                                                class="btn btn-sm btn-primary">
                                                                <i class="fa fa-download"></i>
                                                                {{ __('Download') }}
                                                            </a>
                                                        @endif
                                                        @if ($product['license'] != '')
                                                            <a href="javascript:;" data-toggle="modal"
                                                                data-target="#licence"
                                                                class="btn btn-sm btn-info product-btn" id="license"><i
                                                                    class="fa fa-eye"></i>
                                                                {{ __('View License') }}</a>
                                                        @endif
                                                    @endif
                                                @endif
                                            </div>

                                            <span class="license-key">Licenes key: ...</span>
                                        </td>

                                        <td>
                                            <ul>
                                                <li><b><span>@lang('Quantity:')</span></b> {{ $product['qty'] }}</li>
                                                @if (!empty($product['size']))
                                                    <li><b><span>@lang('Size:')</span></b>
                                                        {{ $product['item']['measure'] }}{{ str_replace('-', '', $product['size']) }}
                                                    </li>
                                                @endif
                                                @if (!empty($product['color']))
                                                    <li><b><span>Color:</span></b>
                                                        <span id="color-bar"
                                                            style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; border-radius: 50%; background: #{{ $product['color'] }};"></span>
                                                    </li>
                                                @endif

                                                @if (!empty($product['keys']))
                                                    @foreach (array_combine(explode(',', $product['keys']), explode(',', $product['values'])) as $key => $value)
                                                        <li><b><span>{{ ucwords(str_replace('_', ' ', $key)) }}:</span></b>
                                                            {{ $value }}</li>
                                                    @endforeach
                                                @endif
                                            </ul>
                                        </td>

                                        <td><b><span
                                                    class="td-title">{{ \PriceHelper::showCurrencyPrice($product['item_price'] * $order->currency_value) }}</span></b>
                                        </td>
                                        <td>
                                            <b><span class="td-title">{{ \PriceHelper::showCurrencyPrice($product['price'] * $order->currency_value) }}
                                                    <small>{{ $product['discount'] == 0 ? '' : '(' . $product['discount'] . '% ' . __('Off') . ')' }}</small></small></span></b>
                                        </td>
                                    </tr>
                                @endforeach

                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="license" tabindex="-1" role="dialog" aria-labelledby="modal1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header d-block text-center">
                    <h4 class="modal-title d-inline-block">{{ __('License Key') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-center">{{ __('The Licenes Key is :') }} <span id="key"></span></p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection
