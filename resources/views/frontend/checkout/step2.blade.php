@extends('layouts.front')
@section('content')
    <section class="gs-breadcrumb-section bg-class"
        data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">@lang('Checkout')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="{{ route('front.checkout') }}">@lang('Checkout')</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <div class="gs-checkout-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 wow-replaced" data-wow-delay=".1s">
                    <div class="checkout-step-wrapper">
                        <span class="line"></span>
                        <span class="line-2"></span>
                        <span class="line-3 d-none"></span>
                        <div class="single-step active">
                            <span class="step-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none">
                                    <path d="M20 6L9 17L4 12" stroke="white" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span class="step-txt">@lang('Address')</span>
                        </div>
                        <div class="single-step active">
                            <span class="step-btn">2</span>
                            <span class="step-txt">@lang('Details')</span>
                        </div>
                        <div class="single-step">
                            <span class="step-btn">3</span>
                            <span class="step-txt">@lang('Payment')</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- address-->
            <form class="address-wrapper" action="{{ route('front.checkout.step2.submit') }}" method="POST">
                @csrf
                <div class="row gy-4">
                    <div class="col-lg-7 col-xl-8 wow-replaced" data-wow-delay=".2s">
                        <div class="shipping-billing-address-wrapper">
                            <!-- shipping address -->
                            <div class="single-addres">
                                <div class="title-wrapper d-flex justify-content-between">
                                    <h5>@lang('Billing Address')</h5>
                                    <a class="edit-btn" href="{{ route('front.checkout') }}">@lang('Edit')</a>
                                </div>

                                <ul>
                                    <li>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path
                                                d="M11.9999 15C8.82977 15 6.01065 16.5306 4.21585 18.906C3.82956 19.4172 3.63641 19.6728 3.64273 20.0183C3.64761 20.2852 3.81521 20.6219 4.02522 20.7867C4.29704 21 4.67372 21 5.42708 21H18.5726C19.326 21 19.7027 21 19.9745 20.7867C20.1845 20.6219 20.3521 20.2852 20.357 20.0183C20.3633 19.6728 20.1701 19.4172 19.7839 18.906C17.9891 16.5306 15.1699 15 11.9999 15Z"
                                                stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path
                                                d="M11.9999 12C14.4851 12 16.4999 9.98528 16.4999 7.5C16.4999 5.01472 14.4851 3 11.9999 3C9.51457 3 7.49985 5.01472 7.49985 7.5C7.49985 9.98528 9.51457 12 11.9999 12Z"
                                                stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>

                                        <span class="title">{{ $step1->customer_name }}</span>
                                    </li>
                                    <li>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path
                                                d="M12 12.5C13.6569 12.5 15 11.1569 15 9.5C15 7.84315 13.6569 6.5 12 6.5C10.3431 6.5 9 7.84315 9 9.5C9 11.1569 10.3431 12.5 12 12.5Z"
                                                stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path
                                                d="M12 22C14 18 20 15.4183 20 10C20 5.58172 16.4183 2 12 2C7.58172 2 4 5.58172 4 10C4 15.4183 10 18 12 22Z"
                                                stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>

                                        <span class="title">{{ $step1->customer_address }}</span>
                                    </li>
                                    <li>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path
                                                d="M8.38028 8.85335C9.07627 10.303 10.0251 11.6616 11.2266 12.8632C12.4282 14.0648 13.7869 15.0136 15.2365 15.7096C15.3612 15.7694 15.4235 15.7994 15.5024 15.8224C15.7828 15.9041 16.127 15.8454 16.3644 15.6754C16.4313 15.6275 16.4884 15.5704 16.6027 15.4561C16.9523 15.1064 17.1271 14.9316 17.3029 14.8174C17.9658 14.3864 18.8204 14.3864 19.4833 14.8174C19.6591 14.9316 19.8339 15.1064 20.1835 15.4561L20.3783 15.6509C20.9098 16.1824 21.1755 16.4481 21.3198 16.7335C21.6069 17.301 21.6069 17.9713 21.3198 18.5389C21.1755 18.8242 20.9098 19.09 20.3783 19.6214L20.2207 19.779C19.6911 20.3087 19.4263 20.5735 19.0662 20.7757C18.6667 21.0001 18.0462 21.1615 17.588 21.1601C17.1751 21.1589 16.8928 21.0788 16.3284 20.9186C13.295 20.0576 10.4326 18.4332 8.04466 16.0452C5.65668 13.6572 4.03221 10.7948 3.17124 7.76144C3.01103 7.19699 2.93092 6.91477 2.9297 6.50182C2.92833 6.0436 3.08969 5.42311 3.31411 5.0236C3.51636 4.66357 3.78117 4.39876 4.3108 3.86913L4.46843 3.7115C4.99987 3.18006 5.2656 2.91433 5.55098 2.76999C6.11854 2.48292 6.7888 2.48292 7.35636 2.76999C7.64174 2.91433 7.90747 3.18006 8.43891 3.7115L8.63378 3.90637C8.98338 4.25597 9.15819 4.43078 9.27247 4.60655C9.70347 5.26945 9.70347 6.12403 9.27247 6.78692C9.15819 6.96269 8.98338 7.1375 8.63378 7.4871C8.51947 7.60142 8.46231 7.65857 8.41447 7.72538C8.24446 7.96281 8.18576 8.30707 8.26748 8.58743C8.29048 8.66632 8.32041 8.72866 8.38028 8.85335Z"
                                                stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>

                                        <span class="title">{{ $step1->customer_phone }}</span>
                                    </li>
                                    <li>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path
                                                d="M2 7L10.1649 12.7154C10.8261 13.1783 11.1567 13.4097 11.5163 13.4993C11.8339 13.5785 12.1661 13.5785 12.4837 13.4993C12.8433 13.4097 13.1739 13.1783 13.8351 12.7154L22 7M6.8 20H17.2C18.8802 20 19.7202 20 20.362 19.673C20.9265 19.3854 21.3854 18.9265 21.673 18.362C22 17.7202 22 16.8802 22 15.2V8.8C22 7.11984 22 6.27976 21.673 5.63803C21.3854 5.07354 20.9265 4.6146 20.362 4.32698C19.7202 4 18.8802 4 17.2 4H6.8C5.11984 4 4.27976 4 3.63803 4.32698C3.07354 4.6146 2.6146 5.07354 2.32698 5.63803C2 6.27976 2 7.11984 2 8.8V15.2C2 16.8802 2 17.7202 2.32698 18.362C2.6146 18.9265 3.07354 19.3854 3.63803 19.673C4.27976 20 5.11984 20 6.8 20Z"
                                                stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>

                                        <span class="title">{{ $step1->customer_email }}</span>
                                    </li>
                                </ul>
                            </div>

                            @if (isset($step1->is_shipping) && $step1->is_shipping)
                                <div class="single-addres">
                                    <div class="title-wrapper">
                                        <h5>@lang('Shipping Address')</h5>
                                    </div>

                                    <ul>
                                        @if ($step1->shipping_name)
                                            <li>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none">
                                                    <path
                                                        d="M11.9999 15C8.82977 15 6.01065 16.5306 4.21585 18.906C3.82956 19.4172 3.63641 19.6728 3.64273 20.0183C3.64761 20.2852 3.81521 20.6219 4.02522 20.7867C4.29704 21 4.67372 21 5.42708 21H18.5726C19.326 21 19.7027 21 19.9745 20.7867C20.1845 20.6219 20.3521 20.2852 20.357 20.0183C20.3633 19.6728 20.1701 19.4172 19.7839 18.906C17.9891 16.5306 15.1699 15 11.9999 15Z"
                                                        stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                    <path
                                                        d="M11.9999 12C14.4851 12 16.4999 9.98528 16.4999 7.5C16.4999 5.01472 14.4851 3 11.9999 3C9.51457 3 7.49985 5.01472 7.49985 7.5C7.49985 9.98528 9.51457 12 11.9999 12Z"
                                                        stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>

                                                <span class="title">{{ $step1->shipping_name }}</span>
                                            </li>
                                        @endif

                                        @if ($step1->shipping_address)
                                            <li>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none">
                                                    <path
                                                        d="M12 12.5C13.6569 12.5 15 11.1569 15 9.5C15 7.84315 13.6569 6.5 12 6.5C10.3431 6.5 9 7.84315 9 9.5C9 11.1569 10.3431 12.5 12 12.5Z"
                                                        stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                    <path
                                                        d="M12 22C14 18 20 15.4183 20 10C20 5.58172 16.4183 2 12 2C7.58172 2 4 5.58172 4 10C4 15.4183 10 18 12 22Z"
                                                        stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>

                                                <span class="title">{{ $step1->shipping_address }}</span>
                                            </li>
                                        @endif
                                        @if ($step1->shipping_phone)
                                            <li>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none">
                                                    <path
                                                        d="M8.38028 8.85335C9.07627 10.303 10.0251 11.6616 11.2266 12.8632C12.4282 14.0648 13.7869 15.0136 15.2365 15.7096C15.3612 15.7694 15.4235 15.7994 15.5024 15.8224C15.7828 15.9041 16.127 15.8454 16.3644 15.6754C16.4313 15.6275 16.4884 15.5704 16.6027 15.4561C16.9523 15.1064 17.1271 14.9316 17.3029 14.8174C17.9658 14.3864 18.8204 14.3864 19.4833 14.8174C19.6591 14.9316 19.8339 15.1064 20.1835 15.4561L20.3783 15.6509C20.9098 16.1824 21.1755 16.4481 21.3198 16.7335C21.6069 17.301 21.6069 17.9713 21.3198 18.5389C21.1755 18.8242 20.9098 19.09 20.3783 19.6214L20.2207 19.779C19.6911 20.3087 19.4263 20.5735 19.0662 20.7757C18.6667 21.0001 18.0462 21.1615 17.588 21.1601C17.1751 21.1589 16.8928 21.0788 16.3284 20.9186C13.295 20.0576 10.4326 18.4332 8.04466 16.0452C5.65668 13.6572 4.03221 10.7948 3.17124 7.76144C3.01103 7.19699 2.93092 6.91477 2.9297 6.50182C2.92833 6.0436 3.08969 5.42311 3.31411 5.0236C3.51636 4.66357 3.78117 4.39876 4.3108 3.86913L4.46843 3.7115C4.99987 3.18006 5.2656 2.91433 5.55098 2.76999C6.11854 2.48292 6.7888 2.48292 7.35636 2.76999C7.64174 2.91433 7.90747 3.18006 8.43891 3.7115L8.63378 3.90637C8.98338 4.25597 9.15819 4.43078 9.27247 4.60655C9.70347 5.26945 9.70347 6.12403 9.27247 6.78692C9.15819 6.96269 8.98338 7.1375 8.63378 7.4871C8.51947 7.60142 8.46231 7.65857 8.41447 7.72538C8.24446 7.96281 8.18576 8.30707 8.26748 8.58743C8.29048 8.66632 8.32041 8.72866 8.38028 8.85335Z"
                                                        stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>

                                                <span class="title">{{ $step1->shipping_phone }}</span>
                                            </li>
                                        @endif

                                        <li>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none">
                                                <path
                                                    d="M2 7L10.1649 12.7154C10.8261 13.1783 11.1567 13.4097 11.5163 13.4993C11.8339 13.5785 12.1661 13.5785 12.4837 13.4993C12.8433 13.4097 13.1739 13.1783 13.8351 12.7154L22 7M6.8 20H17.2C18.8802 20 19.7202 20 20.362 19.673C20.9265 19.3854 21.3854 18.9265 21.673 18.362C22 17.7202 22 16.8802 22 15.2V8.8C22 7.11984 22 6.27976 21.673 5.63803C21.3854 5.07354 20.9265 4.6146 20.362 4.32698C19.7202 4 18.8802 4 17.2 4H6.8C5.11984 4 4.27976 4 3.63803 4.32698C3.07354 4.6146 2.6146 5.07354 2.32698 5.63803C2 6.27976 2 7.11984 2 8.8V15.2C2 16.8802 2 17.7202 2.32698 18.362C2.6146 18.9265 3.07354 19.3854 3.63803 19.673C4.27976 20 5.11984 20 6.8 20Z"
                                                    stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>

                                            <span class="title">{{ $step1->customer_email }}</span>
                                        </li>
                                    </ul>
                                </div>
                            @endif

                        </div>

                        @php
                            foreach ($products as $key => $item) {
                                $userId = $item['user_id'];
                                if (!isset($resultArray[$userId])) {
                                    $resultArray[$userId] = [];
                                }
                                $resultArray[$userId][$key] = $item;
                            }

                        @endphp

                        @php
                            $is_Digital = 1;
                        @endphp

                        @foreach ($resultArray as $vendor_id => $array_product)
                            @php

                                if ($vendor_id != 0) {
                                    $shipping = App\Models\Shipping::where('user_id', $vendor_id)->get();
                                    $packaging = App\Models\Package::where('user_id', $vendor_id)->get();
                                    $vendor = App\Models\User::findOrFail($vendor_id);
                                } else {
                                    $shipping = App\Models\Shipping::where('user_id', 0)->get();
                                    $packaging = App\Models\Package::where('user_id', 0)->get();
                                    $vendor = App\Models\Admin::findOrFail(1);
                                }

                            @endphp

                            <div class="product-infos-wrapper wow-replaced" data-wow-delay=".2s">
                                <!-- shop-info-wrapper -->

                                <!-- product list  -->
                                <div class="product-list">
                                    @foreach ($array_product as $product)
                                        @php
                                            if ($product['dp'] == 0) {
                                                $is_Digital = 0;
                                            }
                                        @endphp
                                        <div class="checkout-single-product wow-replaced" data-wow-delay=".1s">
                                            <div class="img-wrapper">
                                                <a href="#">
                                                    <img width="200" class="img-cls"
                                                        src="{{ asset('assets/images/products/' . $product['item']['photo']) }}"
                                                        alt="product">
                                                </a>
                                            </div>
                                            <div class="content-wrapper">
                                                <h6>
                                                    <a class="product-title"
                                                        href="{{ route('front.product', $product['item']['slug']) }}"
                                                        target="_blank">
                                                        {{ $product['item']['name'] }}
                                                    </a>
                                                </h6>

                                                <ul class="product-specifications-list">
                                                    <li>
                                                        <span class="specification-name">@lang('Price :')</span>
                                                        <span class="specification">
                                                            {{ App\Models\Product::convertPrice($product['item_price']) }}</span>
                                                    </li>
                                                    <li>
                                                        <span class="specification-name">@lang('Quantity :')</span>
                                                        <span class="specification">{{ $product['qty'] }}</span>
                                                    </li>
                                                    @if (!empty($product['size']))
                                                        <li>
                                                            <span class="specification-name">{{ __('Size') }} : </span>
                                                            <span
                                                                class="specification">{{ str_replace('-', ' ', $product['size']) }}</span>
                                                        </li>
                                                    @endif


                                                    @if (!empty($product['color']))
                                                        <li>
                                                            <span class="specification-name">@lang('Color :') </span>
                                                            <span class="specification"
                                                                style="border: 10px solid {{ $product['color'] == '' ? ' white' : '#' . $product['color'] }};"></span>
                                                        </li>
                                                    @endif

                                                    @if (!empty($product['keys']))
                                                        @foreach (array_combine(explode(',', $product['keys']), explode(',', $product['values'])) as $key => $value)
                                                            <li>
                                                                <span
                                                                    class="specification-name">{{ ucwords(str_replace('_', ' ', $key)) }}
                                                                    : </span>
                                                                <span class="specification">{{ $value }}</span>
                                                            </li>
                                                        @endforeach
                                                    @endif

                                                    <li>
                                                        <span class="specification-name">@lang('Total Price :') </span>
                                                        <span
                                                            class="specification">{{ App\Models\Product::convertPrice($product['price']) }}
                                                            {{ $product['discount'] == 0 ? '' : '(' . $product['discount'] . '%' . __('Off') . ')' }}</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    @endforeach

                                </div>


                                @if ($gs->multiple_shipping == 1)
                                    <div class="shop-info-wrapper">
                                        <ul>
                                            <li>
                                                <span><b>@lang('Shop Name :')</b></span>
                                                <span>{{ $vendor->shop_name }}</span>
                                            </li>
                                            <li>
                                                <span><b>@lang('Shop Phone :')</b></span>
                                                <span>{{ $vendor->phone }}</span>
                                            </li>
                                            <li>
                                                <span><b>@lang('Shop Address:')</b></span>
                                                <span>{{ $vendor->address }}</span>
                                            </li>
                                            <li>

                                            </li>
                                        </ul>


                                        @if ($is_Digital == 0)
                                            <div class="d-flex flex-wrap gap-2 mb-3 bg-light-white p-4">
                                                <span class="label mr-2">
                                                    <b>{{ __('Packageing :') }}</b>
                                                </span>
                                                <p id="packing_text{{ $vendor_id }}">
                                                    {{ isset($packaging[0])
                                                        ? $packaging[0]['title'] . '+' . $curr->sign . round($packaging[0]['price'] * $curr->value, 2)
                                                        : 'Package not found' }}
                                                </p>
                                                <button type="button" class="template-btn sm-btn" data-bs-toggle="modal"
                                                    data-bs-target="#vendor_package{{ $vendor_id }}">
                                                    {{ __('Select Package') }}
                                                </button>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2 mb-3 bg-light-white p-4">
                                                <span class="label mr-2">
                                                    <b>{{ __('Shipping :') }}</b>
                                                </span>
                                                <p id="shipping_text{{ $vendor_id }}">
                                                    {{ isset($shipping[0])
                                                        ? $shipping[0]['title'] . '+' . $curr->sign . round($shipping[0]['price'] * $curr->value, 2)
                                                        : 'Package not found' }}
                                                </p>
                                                <button type="button" class="template-btn sm-btn" data-bs-toggle="modal"
                                                    data-bs-target="#vendor_shipping{{ $vendor_id }}">
                                                    {{ __('Select Shipping') }}
                                                </button>
                                            </div>
                                            @include('includes.frontend.vendor_shipping', [
                                                'shipping' => $shipping,
                                                'vendor_id' => $vendor_id,
                                            ])
                                            @include('includes.frontend.vendor_packaging', [
                                                'packaging' => $packaging,
                                                'vendor_id' => $vendor_id,
                                            ])
                                        @endif
                                    </div>
                                @else
                                    <div class="shop-info-wrapper">
                                        <ul>
                                            <li>
                                                <span><b>@lang('Shop Name :')</b></span>
                                                <span>{{ $vendor->shop_name }}</span>
                                            </li>
                                            <li>
                                                <span><b>@lang('Shop Phone :')</b></span>
                                                <span>{{ $vendor->phone }}</span>
                                            </li>
                                            <li>
                                                <span><b>@lang('Shop Address:')</b></span>
                                                <span>{{ $vendor->address }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                @endif



                            </div>
                            @php
                                $is_Digital = 1;
                            @endphp
                        @endforeach



                    </div>
                    <div class="col-lg-5 col-xl-4 wow-replaced" data-wow-delay=".2s">
                        <div class="summary-box">
                            <h4 class="form-title">@lang('Summery')</h4>


                            @if ($digital == 0)
                                <!-- shipping methods -->
                                @if ($gs->multiple_shipping == 0)
                                    <div class="summary-inner-box">
                                        <h6 class="summary-title">@lang('Shipping Method')</h6>
                                        <div class="inputs-wrapper">

                                            @foreach ($shipping_data as $data)
                                                <div class="gs-radio-wrapper">
                                                    <input type="radio" class="shipping"
                                                        data-price="{{ round($data->price * $curr->value, 2) }}"
                                                        data-form="{{ $data->title }}"
                                                        id="free-shepping{{ $data->id }}" name="shipping_id"
                                                        value="{{ $data->id }}" {{ $loop->first ? 'checked' : '' }}>
                                                    <label class="icon-label" for="free-shepping{{ $data->id }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="20"
                                                            height="20" viewBox="0 0 20 20" fill="none">
                                                            <rect x="0.5" y="0.5" width="19" height="19"
                                                                rx="9.5" fill="#FDFDFD" />
                                                            <rect x="0.5" y="0.5" width="19" height="19"
                                                                rx="9.5" stroke="#EE1243" />
                                                            <circle cx="10" cy="10" r="4" fill="#EE1243" />
                                                        </svg>
                                                    </label>
                                                    <label for="free-shepping{{ $data->id }}">
                                                        {{ $data->title }}
                                                        @if ($data->price != 0)
                                                            +
                                                            {{ $curr->sign }}{{ round($data->price * $curr->value, 2) }}
                                                        @endif
                                                        <small>{{ $data->subtitle }}</small>
                                                    </label>
                                                </div>
                                            @endforeach

                                        </div>
                                    </div>

                                    <!-- Packaging -->
                                    <div class="summary-inner-box">
                                        <h6 class="summary-title">@lang('Packaging')</h6>
                                        <div class="inputs-wrapper">

                                            @foreach ($package_data as $data)
                                                <div class="gs-radio-wrapper">
                                                    <input type="radio" class="packing"
                                                        data-price="{{ round($data->price * $curr->value, 2) }}"
                                                        data-form="{{ $data->title }}"
                                                        id="free-package{{ $data->id }}" name="packeging_id"
                                                        value="{{ $data->id }}" {{ $loop->first ? 'checked' : '' }}>
                                                    <label class="icon-label" for="free-package{{ $data->id }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="20"
                                                            height="20" viewBox="0 0 20 20" fill="none">
                                                            <rect x="0.5" y="0.5" width="19" height="19"
                                                                rx="9.5" fill="#FDFDFD" />
                                                            <rect x="0.5" y="0.5" width="19" height="19"
                                                                rx="9.5" stroke="#EE1243" />
                                                            <circle cx="10" cy="10" r="4" fill="#EE1243" />
                                                        </svg>
                                                    </label>
                                                    <label for="free-package{{ $data->id }}">
                                                        {{ $data->title }}
                                                        @if ($data->price != 0)
                                                            +
                                                            {{ $curr->sign }}{{ round($data->price * $curr->value, 2) }}
                                                        @endif
                                                        <small>{{ $data->subtitle }}</small>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endif


                            <!-- Price Details -->
                            <div class="summary-inner-box">
                                <h6 class="summary-title">@lang('Price Details')</h6>
                                <div class="details-wrapper">
                                    <div class="price-details">
                                        <span>@lang('Total MRP')</span>
                                        <span
                                            class="right-side cart-total">{{ Session::has('cart') ? App\Models\Product::convertPrice(Session::get('cart')->totalPrice) : '0.00' }}</span>
                                    </div>


                                    <div class="price-details tax_show d-none">
                                        <span>@lang('Tax')</span>
                                        <span class="right-side original_tax original_tax">0</span>
                                    </div>


                                    @if (Session::has('coupon'))
                                        <div class="price-details">
                                            <span>@lang('Discount') <span
                                                    class="dpercent">{{ Session::get('coupon_percentage') == 0 ? '' : '(' . Session::get('coupon_percentage') . ')' }}</span></span>
                                            @if ($gs->currency_format == 0)
                                                <span id="discount"
                                                    class="right-side">{{ $curr->sign }}{{ Session::get('coupon') }}</span>
                                            @else
                                                <span id="discount"
                                                    class="right-side">{{ Session::get('coupon') }}{{ $curr->sign }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="price-details d-none">
                                            <span>@lang('Discount') <span class="dpercent"></span></span>
                                            <span id="discount"
                                                class="right-side">{{ $curr->sign }}{{ Session::get('coupon') }}</span>
                                        </div>
                                    @endif

                                    @if ($digital == 0)
                                        <div class="price-details">
                                            <span>@lang('Shipping Cost')</span>
                                            <span
                                                class="right-side shipping_cost_view">{{ App\Models\Product::convertPrice(0) }}</span>
                                        </div>



                                        <div class="price-details">
                                            <span>@lang('Packaging Cost')</span>
                                            <span
                                                class="right-side packing_cost_view">{{ App\Models\Product::convertPrice(0) }}</span>
                                        </div>
                                    @endif
                                </div>

                                <hr>
                                <div class="final-price">
                                    <span>@lang('Final Price')</span>
                                    @if (Session::has('coupon_total'))
                                        @if ($gs->currency_format == 0)
                                            <span class="total-amount"
                                                id="final-cost">{{ $curr->sign }}{{ $totalPrice }}</span>
                                        @else
                                            <span class="total-amount"
                                                id="final-cost">{{ $totalPrice }}{{ $curr->sign }}</span>
                                        @endif
                                    @elseif(Session::has('coupon_total1'))
                                        <span class="total-amount" id="final-cost">
                                            {{ Session::get('coupon_total1') }}</span>
                                    @else
                                        <span class="total-amount"
                                            id="final-cost">{{ App\Models\Product::convertPrice($totalPrice) }}</span>
                                    @endif
                                </div>
                            </div>

                            <!-- btn wrapper -->
                            <div class="summary-inner-box">
                                <div class="btn-wrappers">
                                    <button type="submit" class="template-btn w-100">
                                        @lang('Continue')
                                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="24"
                                            viewBox="0 0 25 24" fill="none">
                                            <g clip-path="url(#clip0_489_34176)">
                                                <path
                                                    d="M23.62 9.9099L19.75 5.9999C19.657 5.90617 19.5464 5.83178 19.4246 5.78101C19.3027 5.73024 19.172 5.7041 19.04 5.7041C18.908 5.7041 18.7773 5.73024 18.6554 5.78101C18.5336 5.83178 18.423 5.90617 18.33 5.9999C18.1437 6.18726 18.0392 6.44071 18.0392 6.7049C18.0392 6.96909 18.1437 7.22254 18.33 7.4099L21.89 10.9999H1.5C1.23478 10.9999 0.98043 11.1053 0.792893 11.2928C0.605357 11.4803 0.5 11.7347 0.5 11.9999H0.5C0.5 12.2651 0.605357 12.5195 0.792893 12.707C0.98043 12.8945 1.23478 12.9999 1.5 12.9999H21.95L18.33 16.6099C18.2363 16.7029 18.1619 16.8135 18.1111 16.9353C18.0603 17.0572 18.0342 17.1879 18.0342 17.3199C18.0342 17.4519 18.0603 17.5826 18.1111 17.7045C18.1619 17.8263 18.2363 17.9369 18.33 18.0299C18.423 18.1236 18.5336 18.198 18.6554 18.2488C18.7773 18.2996 18.908 18.3257 19.04 18.3257C19.172 18.3257 19.3027 18.2996 19.4246 18.2488C19.5464 18.198 19.657 18.1236 19.75 18.0299L23.62 14.1499C24.1818 13.5874 24.4974 12.8249 24.4974 12.0299C24.4974 11.2349 24.1818 10.4724 23.62 9.9099Z"
                                                    fill="white" />
                                            </g>
                                            <defs>
                                                <clipPath id="clip0_489_34176">
                                                    <rect width="24" height="24" fill="white"
                                                        transform="translate(0.5)" />
                                                </clipPath>
                                            </defs>
                                        </svg>
                                    </button>
                                    <a href="{{ route('front.checkout') }}" class="template-btn dark-outline w-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="24"
                                            viewBox="0 0 25 24" fill="none">
                                            <g clip-path="url(#clip0_489_34179)">
                                                <path
                                                    d="M1.38 9.9099L5.25 5.9999C5.34296 5.90617 5.45357 5.83178 5.57542 5.78101C5.69728 5.73024 5.82799 5.7041 5.96 5.7041C6.09201 5.7041 6.22272 5.73024 6.34458 5.78101C6.46643 5.83178 6.57704 5.90617 6.67 5.9999C6.85625 6.18726 6.96079 6.44071 6.96079 6.7049C6.96079 6.96909 6.85625 7.22254 6.67 7.4099L3.11 10.9999H23.5C23.7652 10.9999 24.0196 11.1053 24.2071 11.2928C24.3946 11.4803 24.5 11.7347 24.5 11.9999V11.9999C24.5 12.2651 24.3946 12.5195 24.2071 12.707C24.0196 12.8945 23.7652 12.9999 23.5 12.9999H3.05L6.67 16.6099C6.76373 16.7029 6.83812 16.8135 6.88889 16.9353C6.93966 17.0572 6.9658 17.1879 6.9658 17.3199C6.9658 17.4519 6.93966 17.5826 6.88889 17.7045C6.83812 17.8263 6.76373 17.9369 6.67 18.0299C6.57704 18.1236 6.46643 18.198 6.34458 18.2488C6.22272 18.2996 6.09201 18.3257 5.96 18.3257C5.82799 18.3257 5.69728 18.2996 5.57542 18.2488C5.45357 18.198 5.34296 18.1236 5.25 18.0299L1.38 14.1499C0.818197 13.5874 0.50264 12.8249 0.50264 12.0299C0.50264 11.2349 0.818197 10.4724 1.38 9.9099Z"
                                                    fill="#030712" />
                                            </g>
                                            <defs>
                                                <clipPath id="clip0_489_34179">
                                                    <rect width="24" height="24" fill="white"
                                                        transform="matrix(-1 0 0 1 24.5 0)" />
                                                </clipPath>
                                            </defs>
                                        </svg>
                                        @lang('Back to Previous Step')
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                @if ($gs->multiple_shipping == 0 && $digital == 0)
                    <input type="hidden" name="shipping_id" id="multi_shipping_id"
                        value="{{ @$shipping_data[0]->id }}">
                    <input type="hidden" name="packaging_id" id="multi_packaging_id"
                        value="{{ @$package_data[0]->id }}">
                @endif


                <input type="hidden" name="dp" value="{{ $digital }}">
                <input type="hidden" id="input_tax" name="tax" value="">
                <input type="hidden" id="input_tax_type" name="tax_type" value="">
                <input type="hidden" name="totalQty" value="{{ $totalQty }}">
                <input type="hidden" name="vendor_shipping_id" value="{{ $vendor_shipping_id }}">
                <input type="hidden" name="vendor_packing_id" value="{{ $vendor_packing_id }}">
                <input type="hidden" name="currency_sign" value="{{ $curr->sign }}">
                <input type="hidden" name="currency_name" value="{{ $curr->name }}">
                <input type="hidden" name="currency_value" value="{{ $curr->value }}">
                @php
                @endphp
                @if (Session::has('coupon_total'))
                    <input type="hidden" name="total" id="grandtotal"
                        value="{{ round($totalPrice * $curr->value, 2) }}">
                    <input type="hidden" id="tgrandtotal" value="{{ $totalPrice }}">
                @elseif(Session::has('coupon_total1'))
                    <input type="hidden" name="total" id="grandtotal"
                        value="{{ preg_replace(' /[^0-9,.]/', '', Session::get('coupon_total1')) }}">
                    <input type="hidden" id="tgrandtotal"
                        value="{{ preg_replace(' /[^0-9,.]/', '', Session::get('coupon_total1')) }}">
                @else
                    <input type="hidden" name="total" id="grandtotal"
                        value="{{ round($totalPrice * $curr->value, 2) }}">
                    <input type="hidden" id="tgrandtotal" value="{{ round($totalPrice * $curr->value, 2) }}">
                @endif
                <input type="hidden" id="original_tax" value="0">
                <input type="hidden" id="wallet-price" name="wallet_price" value="0">
                <input type="hidden" id="ttotal"
                    value="{{ Session::has('cart') ? App\Models\Product::convertPrice(Session::get('cart')->totalPrice) : '0' }}">
                <input type="hidden" name="coupon_code" id="coupon_code"
                    value="{{ Session::has('coupon_code') ? Session::get('coupon_code') : '' }}">
                <input type="hidden" name="coupon_discount" id="coupon_discount"
                    value="{{ Session::has('coupon') ? Session::get('coupon') : '' }}">
                <input type="hidden" name="coupon_id" id="coupon_id"
                    value="{{ Session::has('coupon') ? Session::get('coupon_id') : '' }}">
                <input type="hidden" name="user_id" id="user_id"
                    value="{{ Auth::guard('web')->check() ? Auth::guard('web')->user()->id : '' }}">






            </form>
        </div>
    </div>
    <!--  checkout wrapper end-->

    @php
        $country = App\Models\Country::where('country_name', $step1->customer_country)->first();
        $isState = isset($step1->customer_state) ? $step1->customer_state : 0;
    @endphp
    <input type="hidden" id="select_country" name="country_id" value="{{ $country->id }}">
    <input type="hidden" id="state_id" name="state_id"
        value="{{ isset($step1->customer_state) ? $step1->customer_state : 0 }}">
    <input type="hidden" id="is_state" name="is_state" value="{{ $isState }}">
    <input type="hidden" id="state_url" name="state_url" value=" {{ route('country.wise.state', $country->id) }}">
@endsection



@section('script')
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <script src="https://js.stripe.com/v3/"></script>





    <script type="text/javascript">
        var coup = 0;
        var pos = {{ $gs->currency_format }};
        let mship = 0;
        let mpack = 0;


        var ftotal = parseFloat($('#grandtotal').val());
        ftotal = parseFloat(ftotal).toFixed(2);

        if (pos == 0) {
            $('#final-cost').html('{{ $curr->sign }}' + ftotal)
        } else {
            $('#final-cost').html(ftotal + '{{ $curr->sign }}')
        }
        $('#grandtotal').val(ftotal);

        let original_tax = 0;

        $(document).ready(function() {
            getShipping();
            getPacking();

            let country_id = $('#select_country').val();
            let state_id = $('#state_id').val();
            let is_state = $('#is_state').val();
            let state_url = $('#state_url').val();


            if (is_state == 1) {
                if (is_state == 1) {
                    $('.select_state').removeClass('d-none');
                    $.get(state_url, function(response) {
                        $('#show_state').html(response.data);
                        tax_submit(country_id, response.state);
                    });

                } else {
                    tax_submit(country_id, state_id);
                    hide_state();
                }
            } else {
                tax_submit(country_id, state_id);
                hide_state();
            }
        });


        function hide_state() {
            $('.select_state').addClass('d-none');
        }


        function tax_submit(country_id, state_id) {

            $('.gocover').show();
            var total = $("#ttotal").val();
            var ship = 0;
            $.ajax({
                type: "GET",
                url: mainurl + "/country/tax/check",

                data: {
                    state_id: state_id,
                    country_id: country_id,
                    total: total,
                    shipping_cost: ship
                },
                success: function(data) {

                    $('#grandtotal').val(data[0]);
                    $('#tgrandtotal').val(data[0]);
                    $('#original_tax').val(data[1]);
                    $('.tax_show').removeClass('d-none');
                    $('#input_tax').val(data[11]);
                    $('#input_tax_type').val(data[12]);
                    $('.original_tax').html(parseFloat(data[1]) + "%");
                    var ttotal = parseFloat($('#grandtotal').val());
                    var tttotal = parseFloat($('#grandtotal').val()) + (parseFloat(mship) + parseFloat(mpack));
                    ttotal = parseFloat(ttotal).toFixed(2);
                    tttotal = parseFloat(tttotal).toFixed(2);
                    $('#grandtotal').val(data[0] + parseFloat(mship) + parseFloat(mpack));
                    if (pos == 0) {
                        $('#final-cost').html('{{ $curr->sign }}' + tttotal);
                        $('.total-cost-dum #total-cost').html('{{ $curr->sign }}' + ttotal);
                    } else {
                        $('#total-cost').html('');
                        $('#final-cost').html(tttotal + '{{ $curr->sign }}');
                        $('.total-cost-dum #total-cost').html(ttotal + '{{ $curr->sign }}');
                    }
                    $('.gocover').hide();
                }
            });
        }


        $('.shipping').on('click', function() {
            getShipping();

            let ref = $(this).attr('ref');
            let view = $(this).attr('view');
            let title = $(this).attr('data-form');
            $('#shipping_text' + ref).html(title + '+' + view);
            var ttotal = parseFloat($('#tgrandtotal').val()) + parseFloat(mship) + parseFloat(mpack);
            ttotal = parseFloat(ttotal).toFixed(2);
            if (pos == 0) {
                $('#final-cost').html('{{ $curr->sign }}' + ttotal);
            } else {
                $('#final-cost').html(ttotal + '{{ $curr->sign }}');
            }
            $('#grandtotal').val(ttotal);
            $('#multi_shipping_id').val($(this).val());

        })


        $('.packing').on('click', function() {
            getPacking();
            let ref = $(this).attr('ref');
            let view = $(this).attr('view');
            let title = $(this).attr('data-form');
            $('#packing_text' + ref).html(title + '+' + view);
            var ttotal = parseFloat($('#tgrandtotal').val()) + parseFloat(mship) + parseFloat(mpack);
            ttotal = parseFloat(ttotal).toFixed(2);
            if (pos == 0) {
                $('#final-cost').html('{{ $curr->sign }}' + ttotal);
            } else {
                $('#final-cost').html(ttotal + '{{ $curr->sign }}');
            }
            $('#grandtotal').val(ttotal);
            $('#multi_packaging_id').val($(this).val());
        })


        function getShipping() {
            mship = 0;
            $('.shipping').each(function() {
                if ($(this).is(':checked')) {
                    mship += parseFloat($(this).attr('data-price'));
                }
                $('.shipping_cost_view').html('{{ $curr->sign }}' + mship);
            });
        }

        function getPacking() {
            mpack = 0;
            $('.packing').each(function() {
                if ($(this).is(':checked')) {
                    mpack += parseFloat($(this).attr('data-price'));
                }
                $('.packing_cost_view').html('{{ $curr->sign }}' + mpack);
            });
        }
    </script>
@endsection
