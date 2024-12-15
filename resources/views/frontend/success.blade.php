@extends('layouts.front')




@section('content')
    <section class="gs-breadcrumb-section bg-class"
        data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">@lang('Success')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="#">@lang('Success')</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>



    <div class="gs-success-invoice-section">
        <div class="container">
            <div class="success-invoice-box">
                <div class="success-invoice-header wow-replaced" data-wow-delay=".1s">
                    <svg class="succss-icon" xmlns="http://www.w3.org/2000/svg" width="121" height="120" viewBox="0 0 121 120"
                        fill="none">
                        <g clip-path="url(#clip0_6740_32691)">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M60.5 0C27.3935 0 0.5 26.8935 0.5 60C0.5 93.1065 27.3935 120 60.5 120C93.6065 120 120.5 93.1065 120.5 60C120.5 26.8935 93.6065 0 60.5 0Z"
                                fill="#27BE69" />
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M60.5 0C59.6354 0 58.7777 0.0291416 57.9219 0.0654602C89.8339 1.41977 115.344 27.7582 115.344 60C115.344 92.2418 89.8339 118.58 57.9219 119.935C58.7777 119.971 59.6354 120 60.5 120C93.6064 120 120.5 93.1064 120.5 60C120.5 26.8936 93.6064 2.34375e-08 60.5 0Z"
                                fill="#1F9854" />
                            <path
                                d="M94.9671 29.5313C92.7603 29.5315 90.6412 30.4107 89.0816 31.972L49.0109 72.079L31.9185 54.971C31.9183 54.9709 31.9181 54.9707 31.918 54.9705C28.69 51.7403 23.3755 51.7403 20.1475 54.9705C20.1474 54.9707 20.1472 54.9709 20.1471 54.971C16.9197 58.202 16.9197 63.5146 20.1471 66.7456C20.1472 66.7456 20.1474 66.7456 20.1475 66.7456L40.2534 86.8698C45.0476 91.6682 52.9746 91.6684 57.7688 86.8698L100.853 43.7466C104.08 40.5157 104.08 35.203 100.853 31.972C99.2934 30.4107 97.1748 29.5316 94.968 29.5313C94.9677 29.5312 94.9674 29.5312 94.9671 29.5313Z"
                                fill="#E8F5E9" />
                            <path
                                d="M94.9675 29.5312C94.0834 29.5313 93.2155 29.6786 92.3899 29.9483C93.6248 30.3516 94.7625 31.0363 95.6972 31.972C98.9245 35.203 98.9245 40.5157 95.6972 43.7466L52.6129 86.8698C50.868 88.6163 48.7064 89.7189 46.4336 90.1941C50.4053 91.0243 54.72 89.9218 57.7692 86.8698L100.853 43.7466C104.081 40.5157 104.081 35.203 100.853 31.972C99.2938 30.4107 97.1752 29.5316 94.9684 29.5312H94.9675Z"
                                fill="#C8E6C9" />
                        </g>
                        <defs>
                            <clipPath id="clip0_6740_32691">
                                <rect width="120" height="120" fill="white" transform="translate(0.5)" />
                            </clipPath>
                        </defs>
                    </svg>
                    <h3>@lang('THANK YOU FOR YOUR PURCHASE')</h3>
                    <h5>@lang("We'll email you an order confirmation with details and tracking info")</h5>
                    <a href="{{ route('front.index') }}" class="template-btn btn-success-page">@lang('Get Back to Our Homepage')</a>
                </div>
                <div class=" success-invoice-body wow-replaced" data-wow-delay=".1s">
                    <h4>@lang('Order#') {{ $order->order_number }}</h4>
                    <p>@lang('Order Date') {{ date('d-M-Y', strtotime($order->created_at)) }}</p>
                </div>
                <div class="row order-details-area wow-replaced" data-wow-delay=".1s">


                    @if ($order->dp == 1)
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <h5>@lang('Shipping Address')</h5>
                            <div class="name d-flex gap-12">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none">
                                    <path
                                        d="M11.9999 15C8.82977 15 6.01065 16.5306 4.21585 18.906C3.82956 19.4172 3.63641 19.6728 3.64273 20.0183C3.64761 20.2852 3.81521 20.6219 4.02522 20.7867C4.29704 21 4.67372 21 5.42708 21H18.5726C19.326 21 19.7027 21 19.9745 20.7867C20.1845 20.6219 20.3521 20.2852 20.357 20.0183C20.3633 19.6728 20.1701 19.4172 19.7839 18.906C17.9891 16.5306 15.1699 15 11.9999 15Z"
                                        stroke="#4C3533" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path
                                        d="M11.9999 12C14.4851 12 16.4999 9.98528 16.4999 7.5C16.4999 5.01472 14.4851 3 11.9999 3C9.51457 3 7.49985 5.01472 7.49985 7.5C7.49985 9.98528 9.51457 12 11.9999 12Z"
                                        stroke="#4C3533" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <p>{{ $order->customer_name }}</p>
                            </div>
                            <div class="address d-flex gap-12">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none">
                                    <path
                                        d="M12 12.5C13.6569 12.5 15 11.1569 15 9.5C15 7.84315 13.6569 6.5 12 6.5C10.3431 6.5 9 7.84315 9 9.5C9 11.1569 10.3431 12.5 12 12.5Z"
                                        stroke="#4C3533" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path
                                        d="M12 22C14 18 20 15.4183 20 10C20 5.58172 16.4183 2 12 2C7.58172 2 4 5.58172 4 10C4 15.4183 10 18 12 22Z"
                                        stroke="#4C3533" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <p>{{ $order->customer_address }} {{ $order->customer_city }}-{{ $order->customer_zip }}</p>
                            </div>
                            <div class="contact-number d-flex gap-12">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none">
                                    <path
                                        d="M8.38028 8.85335C9.07627 10.303 10.0251 11.6616 11.2266 12.8632C12.4282 14.0648 13.7869 15.0136 15.2365 15.7096C15.3612 15.7694 15.4235 15.7994 15.5024 15.8224C15.7828 15.9041 16.127 15.8454 16.3644 15.6754C16.4313 15.6275 16.4884 15.5704 16.6027 15.4561C16.9523 15.1064 17.1271 14.9316 17.3029 14.8174C17.9658 14.3864 18.8204 14.3864 19.4833 14.8174C19.6591 14.9316 19.8339 15.1064 20.1835 15.4561L20.3783 15.6509C20.9098 16.1824 21.1755 16.4481 21.3198 16.7335C21.6069 17.301 21.6069 17.9713 21.3198 18.5389C21.1755 18.8242 20.9098 19.09 20.3783 19.6214L20.2207 19.779C19.6911 20.3087 19.4263 20.5735 19.0662 20.7757C18.6667 21.0001 18.0462 21.1615 17.588 21.1601C17.1751 21.1589 16.8928 21.0788 16.3284 20.9186C13.295 20.0576 10.4326 18.4332 8.04466 16.0452C5.65668 13.6572 4.03221 10.7948 3.17124 7.76144C3.01103 7.19699 2.93092 6.91477 2.9297 6.50182C2.92833 6.0436 3.08969 5.42311 3.31411 5.0236C3.51636 4.66357 3.78117 4.39876 4.3108 3.86913L4.46843 3.7115C4.99987 3.18006 5.2656 2.91433 5.55098 2.76999C6.11854 2.48292 6.7888 2.48292 7.35636 2.76999C7.64174 2.91433 7.90747 3.18006 8.43891 3.7115L8.63378 3.90637C8.98338 4.25597 9.15819 4.43078 9.27247 4.60655C9.70347 5.26945 9.70347 6.12403 9.27247 6.78692C9.15819 6.96269 8.98338 7.1375 8.63378 7.4871C8.51947 7.60142 8.46231 7.65857 8.41447 7.72538C8.24446 7.96281 8.18576 8.30707 8.26748 8.58743C8.29048 8.66632 8.32041 8.72866 8.38028 8.85335Z"
                                        stroke="#4C3533" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <p>{{ $order->customer_phone }}</p>
                            </div>
                            <div class="mail d-flex gap-12">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none">
                                    <path
                                        d="M2 7L10.1649 12.7154C10.8261 13.1783 11.1567 13.4097 11.5163 13.4993C11.8339 13.5785 12.1661 13.5785 12.4837 13.4993C12.8433 13.4097 13.1739 13.1783 13.8351 12.7154L22 7M6.8 20H17.2C18.8802 20 19.7202 20 20.362 19.673C20.9265 19.3854 21.3854 18.9265 21.673 18.362C22 17.7202 22 16.8802 22 15.2V8.8C22 7.11984 22 6.27976 21.673 5.63803C21.3854 5.07354 20.9265 4.6146 20.362 4.32698C19.7202 4 18.8802 4 17.2 4H6.8C5.11984 4 4.27976 4 3.63803 4.32698C3.07354 4.6146 2.6146 5.07354 2.32698 5.63803C2 6.27976 2 7.11984 2 8.8V15.2C2 16.8802 2 17.7202 2.32698 18.362C2.6146 18.9265 3.07354 19.3854 3.63803 19.673C4.27976 20 5.11984 20 6.8 20Z"
                                        stroke="#4C3533" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <p>{{ $order->customer_email }}</p>
                            </div>
                        </div>
                    @else
                        @if ($order->shipping == 'shipto')
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <h5>
                                    @if ($order->dp == 0)
                                    @lang('Shipping Address')
                                    @else
                                    @lang('User Information')
                                    @endif


                                </h5>
                                <div class="name d-flex gap-12">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <path
                                            d="M11.9999 15C8.82977 15 6.01065 16.5306 4.21585 18.906C3.82956 19.4172 3.63641 19.6728 3.64273 20.0183C3.64761 20.2852 3.81521 20.6219 4.02522 20.7867C4.29704 21 4.67372 21 5.42708 21H18.5726C19.326 21 19.7027 21 19.9745 20.7867C20.1845 20.6219 20.3521 20.2852 20.357 20.0183C20.3633 19.6728 20.1701 19.4172 19.7839 18.906C17.9891 16.5306 15.1699 15 11.9999 15Z"
                                            stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                        <path
                                            d="M11.9999 12C14.4851 12 16.4999 9.98528 16.4999 7.5C16.4999 5.01472 14.4851 3 11.9999 3C9.51457 3 7.49985 5.01472 7.49985 7.5C7.49985 9.98528 9.51457 12 11.9999 12Z"
                                            stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    <p>{{ $order->shipping_name == null ? $order->customer_name : $order->shipping_name }}
                                    </p>
                                </div>
                                <div class="address d-flex gap-12">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <path
                                            d="M12 12.5C13.6569 12.5 15 11.1569 15 9.5C15 7.84315 13.6569 6.5 12 6.5C10.3431 6.5 9 7.84315 9 9.5C9 11.1569 10.3431 12.5 12 12.5Z"
                                            stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                        <path
                                            d="M12 22C14 18 20 15.4183 20 10C20 5.58172 16.4183 2 12 2C7.58172 2 4 5.58172 4 10C4 15.4183 10 18 12 22Z"
                                            stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    <p>{{ $order->shipping_address == null ? $order->customer_address : $order->shipping_address }}<br>
                                        {{ $order->shipping_city == null ? $order->customer_city : $order->shipping_city }}-{{ $order->shipping_zip == null ? $order->customer_zip : $order->shipping_zip }}
                                    </p>
                                </div>
                                <div class="contact-number d-flex gap-12">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <path
                                            d="M8.38028 8.85335C9.07627 10.303 10.0251 11.6616 11.2266 12.8632C12.4282 14.0648 13.7869 15.0136 15.2365 15.7096C15.3612 15.7694 15.4235 15.7994 15.5024 15.8224C15.7828 15.9041 16.127 15.8454 16.3644 15.6754C16.4313 15.6275 16.4884 15.5704 16.6027 15.4561C16.9523 15.1064 17.1271 14.9316 17.3029 14.8174C17.9658 14.3864 18.8204 14.3864 19.4833 14.8174C19.6591 14.9316 19.8339 15.1064 20.1835 15.4561L20.3783 15.6509C20.9098 16.1824 21.1755 16.4481 21.3198 16.7335C21.6069 17.301 21.6069 17.9713 21.3198 18.5389C21.1755 18.8242 20.9098 19.09 20.3783 19.6214L20.2207 19.779C19.6911 20.3087 19.4263 20.5735 19.0662 20.7757C18.6667 21.0001 18.0462 21.1615 17.588 21.1601C17.1751 21.1589 16.8928 21.0788 16.3284 20.9186C13.295 20.0576 10.4326 18.4332 8.04466 16.0452C5.65668 13.6572 4.03221 10.7948 3.17124 7.76144C3.01103 7.19699 2.93092 6.91477 2.9297 6.50182C2.92833 6.0436 3.08969 5.42311 3.31411 5.0236C3.51636 4.66357 3.78117 4.39876 4.3108 3.86913L4.46843 3.7115C4.99987 3.18006 5.2656 2.91433 5.55098 2.76999C6.11854 2.48292 6.7888 2.48292 7.35636 2.76999C7.64174 2.91433 7.90747 3.18006 8.43891 3.7115L8.63378 3.90637C8.98338 4.25597 9.15819 4.43078 9.27247 4.60655C9.70347 5.26945 9.70347 6.12403 9.27247 6.78692C9.15819 6.96269 8.98338 7.1375 8.63378 7.4871C8.51947 7.60142 8.46231 7.65857 8.41447 7.72538C8.24446 7.96281 8.18576 8.30707 8.26748 8.58743C8.29048 8.66632 8.32041 8.72866 8.38028 8.85335Z"
                                            stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    <p>{{ $order->shipping_phone == null ? $order->customer_phone : $order->shipping_phone }}
                                    </p>
                                </div>
                                <div class="mail d-flex gap-12">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <path
                                            d="M2 7L10.1649 12.7154C10.8261 13.1783 11.1567 13.4097 11.5163 13.4993C11.8339 13.5785 12.1661 13.5785 12.4837 13.4993C12.8433 13.4097 13.1739 13.1783 13.8351 12.7154L22 7M6.8 20H17.2C18.8802 20 19.7202 20 20.362 19.673C20.9265 19.3854 21.3854 18.9265 21.673 18.362C22 17.7202 22 16.8802 22 15.2V8.8C22 7.11984 22 6.27976 21.673 5.63803C21.3854 5.07354 20.9265 4.6146 20.362 4.32698C19.7202 4 18.8802 4 17.2 4H6.8C5.11984 4 4.27976 4 3.63803 4.32698C3.07354 4.6146 2.6146 5.07354 2.32698 5.63803C2 6.27976 2 7.11984 2 8.8V15.2C2 16.8802 2 17.7202 2.32698 18.362C2.6146 18.9265 3.07354 19.3854 3.63803 19.673C4.27976 20 5.11984 20 6.8 20Z"
                                            stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    <p>{{ $order->shipping_email == null ? $order->customer_email : $order->shipping_email }}
                                    </p>
                                </div>
                            </div>
                        @else
                            <div class="col-lg-6 col-md-6 col-sm-12">
                                <h5>@lang('PickUp Location')</h5>

                                <div class="address d-flex gap-12">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none">
                                        <path
                                            d="M12 12.5C13.6569 12.5 15 11.1569 15 9.5C15 7.84315 13.6569 6.5 12 6.5C10.3431 6.5 9 7.84315 9 9.5C9 11.1569 10.3431 12.5 12 12.5Z"
                                            stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                        <path
                                            d="M12 22C14 18 20 15.4183 20 10C20 5.58172 16.4183 2 12 2C7.58172 2 4 5.58172 4 10C4 15.4183 10 18 12 22Z"
                                            stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    <p>{{ $order->pickup_location }}</p>
                                </div>

                            </div>
                        @endif
                    @endif





                    @if ($order->dp == 0)
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <h5>@lang('Billing Address')</h5>
                            <div class="name d-flex gap-12">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none">
                                    <path
                                        d="M11.9999 15C8.82977 15 6.01065 16.5306 4.21585 18.906C3.82956 19.4172 3.63641 19.6728 3.64273 20.0183C3.64761 20.2852 3.81521 20.6219 4.02522 20.7867C4.29704 21 4.67372 21 5.42708 21H18.5726C19.326 21 19.7027 21 19.9745 20.7867C20.1845 20.6219 20.3521 20.2852 20.357 20.0183C20.3633 19.6728 20.1701 19.4172 19.7839 18.906C17.9891 16.5306 15.1699 15 11.9999 15Z"
                                        stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <path
                                        d="M11.9999 12C14.4851 12 16.4999 9.98528 16.4999 7.5C16.4999 5.01472 14.4851 3 11.9999 3C9.51457 3 7.49985 5.01472 7.49985 7.5C7.49985 9.98528 9.51457 12 11.9999 12Z"
                                        stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                                <p>{{ $order->customer_name }}</p>
                            </div>
                            <div class="address d-flex gap-12">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none">
                                    <path
                                        d="M12 12.5C13.6569 12.5 15 11.1569 15 9.5C15 7.84315 13.6569 6.5 12 6.5C10.3431 6.5 9 7.84315 9 9.5C9 11.1569 10.3431 12.5 12 12.5Z"
                                        stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <path
                                        d="M12 22C14 18 20 15.4183 20 10C20 5.58172 16.4183 2 12 2C7.58172 2 4 5.58172 4 10C4 15.4183 10 18 12 22Z"
                                        stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                                <p>{{ $order->customer_address }}
                                    {{ $order->customer_city }}-{{ $order->customer_zip }}</p>
                            </div>
                            <div class="contact-number d-flex gap-12">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none">
                                    <path
                                        d="M8.38028 8.85335C9.07627 10.303 10.0251 11.6616 11.2266 12.8632C12.4282 14.0648 13.7869 15.0136 15.2365 15.7096C15.3612 15.7694 15.4235 15.7994 15.5024 15.8224C15.7828 15.9041 16.127 15.8454 16.3644 15.6754C16.4313 15.6275 16.4884 15.5704 16.6027 15.4561C16.9523 15.1064 17.1271 14.9316 17.3029 14.8174C17.9658 14.3864 18.8204 14.3864 19.4833 14.8174C19.6591 14.9316 19.8339 15.1064 20.1835 15.4561L20.3783 15.6509C20.9098 16.1824 21.1755 16.4481 21.3198 16.7335C21.6069 17.301 21.6069 17.9713 21.3198 18.5389C21.1755 18.8242 20.9098 19.09 20.3783 19.6214L20.2207 19.779C19.6911 20.3087 19.4263 20.5735 19.0662 20.7757C18.6667 21.0001 18.0462 21.1615 17.588 21.1601C17.1751 21.1589 16.8928 21.0788 16.3284 20.9186C13.295 20.0576 10.4326 18.4332 8.04466 16.0452C5.65668 13.6572 4.03221 10.7948 3.17124 7.76144C3.01103 7.19699 2.93092 6.91477 2.9297 6.50182C2.92833 6.0436 3.08969 5.42311 3.31411 5.0236C3.51636 4.66357 3.78117 4.39876 4.3108 3.86913L4.46843 3.7115C4.99987 3.18006 5.2656 2.91433 5.55098 2.76999C6.11854 2.48292 6.7888 2.48292 7.35636 2.76999C7.64174 2.91433 7.90747 3.18006 8.43891 3.7115L8.63378 3.90637C8.98338 4.25597 9.15819 4.43078 9.27247 4.60655C9.70347 5.26945 9.70347 6.12403 9.27247 6.78692C9.15819 6.96269 8.98338 7.1375 8.63378 7.4871C8.51947 7.60142 8.46231 7.65857 8.41447 7.72538C8.24446 7.96281 8.18576 8.30707 8.26748 8.58743C8.29048 8.66632 8.32041 8.72866 8.38028 8.85335Z"
                                        stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                                <p>{{ $order->customer_phone }}</p>
                            </div>
                            <div class="mail d-flex gap-12">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none">
                                    <path
                                        d="M2 7L10.1649 12.7154C10.8261 13.1783 11.1567 13.4097 11.5163 13.4993C11.8339 13.5785 12.1661 13.5785 12.4837 13.4993C12.8433 13.4097 13.1739 13.1783 13.8351 12.7154L22 7M6.8 20H17.2C18.8802 20 19.7202 20 20.362 19.673C20.9265 19.3854 21.3854 18.9265 21.673 18.362C22 17.7202 22 16.8802 22 15.2V8.8C22 7.11984 22 6.27976 21.673 5.63803C21.3854 5.07354 20.9265 4.6146 20.362 4.32698C19.7202 4 18.8802 4 17.2 4H6.8C5.11984 4 4.27976 4 3.63803 4.32698C3.07354 4.6146 2.6146 5.07354 2.32698 5.63803C2 6.27976 2 7.11984 2 8.8V15.2C2 16.8802 2 17.7202 2.32698 18.362C2.6146 18.9265 3.07354 19.3854 3.63803 19.673C4.27976 20 5.11984 20 6.8 20Z"
                                        stroke="#4C3533" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                                <p>{{ $order->customer_email }}</p>
                            </div>
                        </div>
                    @endif



                    <div class="col-lg-6 col-md-6 col-sm-12 payment-information">
                        <h5>@lang('Payment Information')</h5>
                        <div class="payment-information">

                            @if ($gs->multiple_shipping == 0)
                                @if ($order->shipping_cost != 0)
                                    <p>{{ $order->shipping_title }}:
                                        {{ \PriceHelper::showOrderCurrencyPrice($order->shipping_cost, $order->currency_sign) }}
                                    </p>
                                @endif


                                @if ($order->packing_cost != 0)
                                    <p>{{ $order->packing_title }}:
                                        {{ \PriceHelper::showOrderCurrencyPrice($order->packing_cost, $order->currency_sign) }}
                                    </p>
                                @endif
                            @else
                                @if ($order->shipping_cost != 0)
                                    <p>{{ __('Shipping Cost') }}:
                                        {{ \PriceHelper::showOrderCurrencyPrice($order->shipping_cost * $order->currency_value, $order->currency_sign) }}
                                    </p>
                                @endif


                                @if ($order->packing_cost != 0)
                                    <p>{{ __('Packing Cost') }}:
                                        {{ \PriceHelper::showOrderCurrencyPrice($order->packing_cost * $order->currency_value, $order->currency_sign) }}
                                    </p>
                                @endif
                            @endif




                            <p>@lang('Tax :')
                                {{ \PriceHelper::showOrderCurrencyPrice($order->tax / $order->currency_value, $order->currency_sign) }}
                            </p>
                            <p>@lang('Paid Amount:')

                                @if ($order->method != 'Wallet')
                                    {{ \PriceHelper::showOrderCurrencyPrice(
                                        ($order->pay_amount + $order->wallet_price) * $order->currency_value,
                                        $order->currency_sign,
                                    ) }}
                                @else
                                    {{ \PriceHelper::showOrderCurrencyPrice($order->wallet_price * $order->currency_value, $order->currency_sign) }}
                                @endif
                            </p>
                            <p>@lang('Payment Method:')
                                {{ $order->method }}
                                @if ($order->method != 'Cash On Delivery' && $order->method != 'Wallet')
                                    <br>
                                    {{ __('Transaction ID:') }} {{ $order->txnid }}
                                @endif
                            </p>
                        </div>
                    </div>
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
                <div class="ordered-products wow-replaced" data-wow-delay=".1s">
                    <h4>@lang('Ordered Products:')</h4>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr class="wow-replaced" data-wow-delay=".1s">
                                    <th class="d-none d-lg-table-cell">@lang('Product Image')</th>
                                    <th>@lang('Product Details')</th>
                                    <th class="d-none d-lg-table-cell">@lang('Unit Price')</th>
                                    <th class="d-none d-lg-table-cell">@lang('Total')</th>
                                </tr>
                            </thead>
                            <tbody>


                                @foreach ($tempcart->items as $product)
                                    <tr class="wow-replaced" data-wow-delay=".1s">
                                        <td colspan="1" class="product-img d-none d-lg-table-cell">
                                            <img src="{{ asset('assets/images/products/' . $product['item']['photo']) }}"
                                                alt="">
                                        </td>
                                        <td class="product-details">
                                            <img src="{{ asset('assets/images/products/' . $product['item']['photo']) }}"
                                                alt="" class="d-lg-none d-table-cell pb-24 small-device-img">
                                            <h6>{{ $product['item']['name'] }}</h6>
                                            <p><span>@lang('Quantity:')</span> {{ $product['qty'] }}</p>
                                            <p><span>Size:</span>
                                                @if (!empty($product['size']))
                                                    <b>{{ __('Size') }}</b>:
                                                    {{ $product['item']['measure'] }}{{ str_replace('-', '                                                                ', $product['size']) }}
                                                    <br>
                                                @endif

                                            </p>
                                            <p><span>@lang('Color:')</span>

                                                @if (!empty($product['color']))
                                                    <div class="d-flex mt-2">
                                                        <b>{{ __('Color') }}</b>: <span class="color-show-btn mt-1 ms-3" id="color-bar"
                                                            style="background-color: #{{ $product['color'] == '' ? ' white' : $product['color'] }};"></span>
                                                    </div>
                                                @endif
                                            </p>

                                            @if (!empty($product['keys']))
                                                @foreach (array_combine(explode(',', $product['keys']), explode(',', $product['values'])) as $key => $value)
                                                    <p><span>{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                                                        {{ $value }}</p>
                                                @endforeach
                                            @endif

                                            <p class="d-lg-none d-table-cell"><span>@lang('Unit Price:')</span>
                                                {{ \PriceHelper::showCurrencyPrice($product['item_price'] * $order->currency_value) }}
                                            </p>
                                            <p class="d-lg-none"><span>@lang('Total Price:')</span>{{ \PriceHelper::showCurrencyPrice($product['price'] * $order->currency_value) }}
                                                <small>{{ $product['discount'] == 0 ? '' : '(' . $product['discount'] . '% ' . __('Off') . ')' }}</small>
                                            </p>

                                        </td>
                                        <td class="d-none d-lg-table-cell">
                                            {{ \PriceHelper::showCurrencyPrice($product['item_price'] * $order->currency_value) }}
                                        </td>
                                        <td class="d-none d-lg-table-cell">
                                            {{ \PriceHelper::showCurrencyPrice($product['price'] * $order->currency_value) }}
                                            <small>{{ $product['discount'] == 0 ? '' : '(' . $product['discount'] . '% ' . __('Off') . ')' }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
