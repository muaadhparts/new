@extends('layouts.front')
@section('content')
    <section class="gs-breadcrumb-section">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">@lang('Checkout')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="{{ route('front.cart') }}">@lang('Cart')</a></li>
                        <li>@lang('Checkout')</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <div class="gs-checkout-wrapper muaadh-section-gray">
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
            <form class="address-wrapper" action="{{ route('front.checkout.merchant.step2.submit', $merchant_id) }}" method="POST">
                @csrf
                <div class="row gy-4">
                    <div class="col-lg-7 col-xl-8 wow-replaced" data-wow-delay=".2s">
                        <div class="shipping-billing-address-wrapper">
                            <!-- shipping address -->
                            <div class="single-addres">
                                <div class="title-wrapper d-flex justify-content-between">
                                    <h5>@lang('Billing Address')</h5>
                                    <a class="edit-btn" href="{{ route('front.checkout.merchant', $merchant_id) }}">@lang('Edit')</a>
                                </div>

                                <ul>
                                    <li>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path
                                                d="M11.9999 15C8.82977 15 6.01065 16.5306 4.21585 18.906C3.82956 19.4172 3.63641 19.6728 3.64273 20.0183C3.64761 20.2852 3.81521 20.6219 4.02522 20.7867C4.29704 21 4.67372 21 5.42708 21H18.5726C19.326 21 19.7027 21 19.9745 20.7867C20.1845 20.6219 20.3521 20.2852 20.357 20.0183C20.3633 19.6728 20.1701 19.4172 19.7839 18.906C17.9891 16.5306 15.1699 15 11.9999 15Z"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path
                                                d="M11.9999 12C14.4851 12 16.4999 9.98528 16.4999 7.5C16.4999 5.01472 14.4851 3 11.9999 3C9.51457 3 7.49985 5.01472 7.49985 7.5C7.49985 9.98528 9.51457 12 11.9999 12Z"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>

                                        <span class="title">{{ $step1->customer_name }}</span>
                                    </li>
                                    <li>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path
                                                d="M12 12.5C13.6569 12.5 15 11.1569 15 9.5C15 7.84315 13.6569 6.5 12 6.5C10.3431 6.5 9 7.84315 9 9.5C9 11.1569 10.3431 12.5 12 12.5Z"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path
                                                d="M12 22C14 18 20 15.4183 20 10C20 5.58172 16.4183 2 12 2C7.58172 2 4 5.58172 4 10C4 15.4183 10 18 12 22Z"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>

                                        <span class="title">{{ $step1->customer_address }}</span>
                                    </li>
                                    <li>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path
                                                d="M8.38028 8.85335C9.07627 10.303 10.0251 11.6616 11.2266 12.8632C12.4282 14.0648 13.7869 15.0136 15.2365 15.7096C15.3612 15.7694 15.4235 15.7994 15.5024 15.8224C15.7828 15.9041 16.127 15.8454 16.3644 15.6754C16.4313 15.6275 16.4884 15.5704 16.6027 15.4561C16.9523 15.1064 17.1271 14.9316 17.3029 14.8174C17.9658 14.3864 18.8204 14.3864 19.4833 14.8174C19.6591 14.9316 19.8339 15.1064 20.1835 15.4561L20.3783 15.6509C20.9098 16.1824 21.1755 16.4481 21.3198 16.7335C21.6069 17.301 21.6069 17.9713 21.3198 18.5389C21.1755 18.8242 20.9098 19.09 20.3783 19.6214L20.2207 19.779C19.6911 20.3087 19.4263 20.5735 19.0662 20.7757C18.6667 21.0001 18.0462 21.1615 17.588 21.1601C17.1751 21.1589 16.8928 21.0788 16.3284 20.9186C13.295 20.0576 10.4326 18.4332 8.04466 16.0452C5.65668 13.6572 4.03221 10.7948 3.17124 7.76144C3.01103 7.19699 2.93092 6.91477 2.9297 6.50182C2.92833 6.0436 3.08969 5.42311 3.31411 5.0236C3.51636 4.66357 3.78117 4.39876 4.3108 3.86913L4.46843 3.7115C4.99987 3.18006 5.2656 2.91433 5.55098 2.76999C6.11854 2.48292 6.7888 2.48292 7.35636 2.76999C7.64174 2.91433 7.90747 3.18006 8.43891 3.7115L8.63378 3.90637C8.98338 4.25597 9.15819 4.43078 9.27247 4.60655C9.70347 5.26945 9.70347 6.12403 9.27247 6.78692C9.15819 6.96269 8.98338 7.1375 8.63378 7.4871C8.51947 7.60142 8.46231 7.65857 8.41447 7.72538C8.24446 7.96281 8.18576 8.30707 8.26748 8.58743C8.29048 8.66632 8.32041 8.72866 8.38028 8.85335Z"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>

                                        <span class="title">{{ $step1->customer_phone }}</span>
                                    </li>
                                    <li>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <path
                                                d="M2 7L10.1649 12.7154C10.8261 13.1783 11.1567 13.4097 11.5163 13.4993C11.8339 13.5785 12.1661 13.5785 12.4837 13.4993C12.8433 13.4097 13.1739 13.1783 13.8351 12.7154L22 7M6.8 20H17.2C18.8802 20 19.7202 20 20.362 19.673C20.9265 19.3854 21.3854 18.9265 21.673 18.362C22 17.7202 22 16.8802 22 15.2V8.8C22 7.11984 22 6.27976 21.673 5.63803C21.3854 5.07354 20.9265 4.6146 20.362 4.32698C19.7202 4 18.8802 4 17.2 4H6.8C5.11984 4 4.27976 4 3.63803 4.32698C3.07354 4.6146 2.6146 5.07354 2.32698 5.63803C2 6.27976 2 7.11984 2 8.8V15.2C2 16.8802 2 17.7202 2.32698 18.362C2.6146 18.9265 3.07354 19.3854 3.63803 19.673C4.27976 20 5.11984 20 6.8 20Z"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>

                                        <span class="title">{{ $step1->customer_email }}</span>
                                    </li>
                                </ul>
                            </div>

                        </div>

                        @php
                            foreach ($catalogItems as $key => $item) {
                                $userId = $item['user_id'];
                                if (!isset($resultArray[$userId])) {
                                    $resultArray[$userId] = [];
                                }
                                $resultArray[$userId][$key] = $item;
                            }

                        @endphp

                        @foreach ($resultArray as $loop_merchant_id => $array_product)
                            @php
                                $merchantInfo = $merchantData[$loop_merchant_id] ?? null;
                                $shipping = isset($merchantInfo['shipping']) ? collect($merchantInfo['shipping']) : collect();
                                $packaging = $merchantInfo['packaging'] ?? collect();
                                $groupedShipping = $merchantInfo['grouped_shipping'] ?? collect();
                                $providerLabels = $merchantInfo['provider_labels'] ?? [
                                    'manual' => __('Manual Shipping'),
                                    'debts' => __('Debts Shipping'),
                                    'tryoto' => __('Smart Shipping (Tryoto)'),
                                ];

                                $orderTotal = 0;
                                foreach ($array_product as $cartItem) {
                                    $orderTotal += $cartItem['price'] ?? 0;
                                }
                                $orderTotalConverted = round($orderTotal * $curr->value, 2);
                            @endphp
                            <input type="hidden" data-order-total="{{ $loop_merchant_id }}" data-amount="{{ $orderTotalConverted }}" />

                            <div class="shipping-options-wrapper wow-replaced" data-wow-delay=".2s">

                                @if ($gs->multiple_shipping == 1)
                                    <div class="shop-info-wrapper">

                                        {{-- Tax Display for this Merchant --}}
                                        @if(isset($step1->merchant_tax_data[$merchant_id]))
                                            @php
                                                $merchantTax = $step1->merchant_tax_data[$merchant_id];
                                                $merchantTaxRate = $merchantTax['tax_rate'] ?? 0;
                                                $merchantTaxAmount = $merchantTax['tax_amount'] ?? 0;
                                            @endphp
                                            @if($merchantTaxRate > 0)
                                            <div class="d-flex flex-wrap gap-2 mb-3 bg-light-white p-4 align-items-center">
                                                <span class="label mr-2">
                                                    <b>{{ __('Tax') }} ({{ $merchantTaxRate }}%):</b>
                                                </span>
                                                <span class="fw-bold text-success">
                                                    {{ App\Models\CatalogItem::convertPrice($merchantTaxAmount) }}
                                                </span>
                                                @if(isset($step1->tax_location))
                                                <small class="text-muted ms-2">({{ $step1->tax_location }})</small>
                                                @endif
                                            </div>
                                            @endif
                                        @endif

                                        @if ($packaging->isNotEmpty())
                                            <div class="d-flex flex-wrap gap-2 mb-3 bg-light-white p-4">
                                                <span class="label mr-2">
                                                    <b>{{ __('Packageing :') }}</b>
                                                </span>
                                                <p id="packing_text{{ $merchant_id }}">
                                                    @lang('Not Selected')
                                                </p>
                                                <button type="button" class="template-btn sm-btn" data-bs-toggle="modal"
                                                    data-bs-target="#merchant_package{{ $merchant_id }}">
                                                    {{ __('Select Package') }}
                                                </button>
                                            </div>
                                        @endif
                                        {{-- Local Courier Option --}}
                                        @if(isset($courier_available) && $courier_available && isset($available_couriers) && count($available_couriers) > 0)
                                            <div class="d-flex flex-wrap gap-2 mb-3 bg-light-white p-4">
                                                <span class="label mr-2">
                                                    <b><i class="fas fa-motorcycle me-2"></i>{{ __('Local Courier:') }}</b>
                                                </span>

                                                {{-- Selected courier display --}}
                                                <span id="selected-courier-display" class="d-none">
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>
                                                        <span id="selected-courier-name"></span>
                                                        (<span id="selected-courier-price"></span>)
                                                    </span>
                                                    <button type="button" class="btn btn-sm btn-link p-0 ms-2" data-bs-toggle="modal" data-bs-target="#courierSelectionModal">
                                                        @lang('Change')
                                                    </button>
                                                </span>

                                                {{-- Select courier button --}}
                                                <span id="select-courier-btn-wrapper">
                                                    <button type="button" class="template-btn sm-btn" data-bs-toggle="modal" data-bs-target="#courierSelectionModal">
                                                        @lang('Select Courier')
                                                    </button>
                                                </span>

                                                <small class="text-muted ms-auto">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    @lang('Fast delivery in your area')
                                                </small>
                                            </div>

                                            {{-- Hidden fields for courier data --}}
                                            <input type="hidden" name="delivery_type" id="delivery_type" value="shipping">
                                            <input type="hidden" name="courier_id" id="selected_courier_id" value="">
                                            <input type="hidden" name="courier_fee" id="selected_courier_fee" value="0">
                                            <input type="hidden" name="service_area_id" id="selected_service_area_id" value="">
                                            <input type="hidden" name="customer_city_id" value="{{ $customer_city_id ?? '' }}">
                                        @endif

                                        <div class="d-flex flex-wrap gap-2 mb-3 bg-light-white p-4">
                                            <span class="label mr-2">
                                                <b>{{ __('Shipping Methods:') }}</b>
                                            </span>

                                            {{-- Dynamic buttons for each provider --}}
                                            @foreach($groupedShipping as $provider => $methods)
                                                @php
                                                    $providerLabel = $providerLabels[$provider] ?? ucfirst($provider);
                                                    $modalId = "merchant_{$provider}_shipping_{$merchant_id}";
                                                @endphp

                                                <button type="button" class="template-btn sm-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#{{ $modalId }}">
                                                    {{ $providerLabel }}
                                                </button>
                                            @endforeach

                                            {{-- Display selected shipping --}}
                                            <p id="shipping_text{{ $merchant_id }}" class="ms-auto mb-0">
                                                @lang('Not Selected')
                                            </p>
                                        </div>

                                        {{-- Include modals for each provider --}}
                                        @foreach($groupedShipping as $provider => $methods)
                                            @php
                                                $providerLabel = $providerLabels[$provider] ?? ucfirst($provider);
                                                $modalId = "merchant_{$provider}_shipping_{$merchant_id}";
                                            @endphp

                                            @if($provider === 'tryoto')
                                                {{-- Tryoto Modal with API Component --}}
                                                @include('includes.frontend.tryoto_shipping_modal', [
                                                    'modalId' => $modalId,
                                                    'providerLabel' => $providerLabel,
                                                    'merchant_id' => $merchant_id,
                                                    'array_product' => $array_product,
                                                    'curr' => $curr,
                                                    'gs' => $gs,
                                                ])
                                            @else
                                                {{-- Manual/Debts Modal --}}
                                                @include('includes.frontend.provider_shipping_modal', [
                                                    'modalId' => $modalId,
                                                    'provider' => $provider,
                                                    'providerLabel' => $providerLabel,
                                                    'methods' => $methods,
                                                    'merchant_id' => $merchant_id,
                                                    'array_product' => $array_product,
                                                    'curr' => $curr,
                                                ])
                                            @endif
                                        @endforeach
                                        @if($packaging->isNotEmpty())
                                            @include('includes.frontend.merchant_packaging', [
                                                'packaging' => $packaging,
                                                'merchant_id' => $merchant_id,
                                            ])
                                        @endif
                                    </div>
                                @endif



                            </div>
                        @endforeach



                    </div>
                    <div class="col-lg-5 col-xl-4 wow-replaced" data-wow-delay=".2s">
                        <div class="summary-box">
                            <h4 class="form-title">@lang('Summery')</h4>


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
                                                            rx="9.5" fill="var(--surface-primary, #fff)" />
                                                        <rect x="0.5" y="0.5" width="19" height="19"
                                                            rx="9.5" stroke="currentColor" />
                                                        <circle cx="10" cy="10" r="4" fill="currentColor" />
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
                                @if($package_data->isNotEmpty())
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
                                                                rx="9.5" fill="var(--surface-primary, #fff)" />
                                                            <rect x="0.5" y="0.5" width="19" height="19"
                                                                rx="9.5" stroke="currentColor" />
                                                            <circle cx="10" cy="10" r="4" fill="currentColor" />
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


                            {{-- Price Summary --}}
                            @include('includes.checkout-price-summary', [
                                'step' => 2,
                                'catalogItemsTotal' => $catalogItemsTotal ?? $totalPrice,
                                'totalPrice' => $totalPrice, // Backward compatibility
                                'curr' => $curr,
                                'gs' => $gs,
                                'step1' => $step1 ?? null
                            ])

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
                                    <a href="{{ route('front.checkout.merchant', $merchant_id) }}" class="template-btn dark-outline w-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="24"
                                            viewBox="0 0 25 24" fill="none">
                                            <g clip-path="url(#clip0_489_34179)">
                                                <path
                                                    d="M1.38 9.9099L5.25 5.9999C5.34296 5.90617 5.45357 5.83178 5.57542 5.78101C5.69728 5.73024 5.82799 5.7041 5.96 5.7041C6.09201 5.7041 6.22272 5.73024 6.34458 5.78101C6.46643 5.83178 6.57704 5.90617 6.67 5.9999C6.85625 6.18726 6.96079 6.44071 6.96079 6.7049C6.96079 6.96909 6.85625 7.22254 6.67 7.4099L3.11 10.9999H23.5C23.7652 10.9999 24.0196 11.1053 24.2071 11.2928C24.3946 11.4803 24.5 11.7347 24.5 11.9999V11.9999C24.5 12.2651 24.3946 12.5195 24.2071 12.707C24.0196 12.8945 23.7652 12.9999 23.5 12.9999H3.05L6.67 16.6099C6.76373 16.7029 6.83812 16.8135 6.88889 16.9353C6.93966 17.0572 6.9658 17.1879 6.9658 17.3199C6.9658 17.4519 6.93966 17.5826 6.88889 17.7045C6.83812 17.8263 6.76373 17.9369 6.67 18.0299C6.57704 18.1236 6.46643 18.198 6.34458 18.2488C6.22272 18.2996 6.09201 18.3257 5.96 18.3257C5.82799 18.3257 5.69728 18.2996 5.57542 18.2488C5.45357 18.198 5.34296 18.1236 5.25 18.0299L1.38 14.1499C0.818197 13.5874 0.50264 12.8249 0.50264 12.0299C0.50264 11.2349 0.818197 10.4724 1.38 9.9099Z"
                                                    fill="currentColor" />
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


                @if ($gs->multiple_shipping == 0)
                    <input type="hidden" name="shipping_id" id="multi_shipping_id"
                        value="{{ @$shipping_data[0]->id }}">
                    <input type="hidden" name="packaging_id" id="multi_packaging_id"
                        value="{{ @$package_data[0]->id }}">
                @endif


                <input type="hidden" id="input_tax" name="tax" value="">
                <input type="hidden" id="input_tax_type" name="tax_type" value="">
                <input type="hidden" name="totalQty" value="{{ $totalQty }}">
                <input type="hidden" name="merchant_shipping_id" value="{{ $merchant_shipping_id }}">
                <input type="hidden" name="merchant_packing_id" value="{{ $merchant_packing_id }}">
                <input type="hidden" name="currency_sign" value="{{ $curr->sign }}">
                <input type="hidden" name="currency_name" value="{{ $curr->name }}">
                <input type="hidden" name="currency_value" value="{{ $curr->value }}">
                @php
                    $taxAmount = $step1->total_tax_amount ?? $step1->tax_amount ?? 0;
                    $totalWithTax = $totalPrice + $taxAmount;
                @endphp
                @if (Session::has('discount_total'))
                    <input type="hidden" name="total" id="grandtotal"
                        value="{{ round($totalWithTax * $curr->value, 2) }}">
                    <input type="hidden" id="tgrandtotal" value="{{ $totalPrice }}">
                    <input type="hidden" id="tax_amount_value" value="{{ round($taxAmount * $curr->value, 2) }}">
                @elseif(Session::has('discount_total1'))
                    <input type="hidden" name="total" id="grandtotal"
                        value="{{ preg_replace(' /[^0-9,.]/', '', Session::get('discount_total1')) + round($taxAmount * $curr->value, 2) }}">
                    <input type="hidden" id="tgrandtotal"
                        value="{{ preg_replace(' /[^0-9,.]/', '', Session::get('discount_total1')) }}">
                    <input type="hidden" id="tax_amount_value" value="{{ round($taxAmount * $curr->value, 2) }}">
                @else
                    <input type="hidden" name="total" id="grandtotal"
                        value="{{ round($totalWithTax * $curr->value, 2) }}">
                    <input type="hidden" id="tgrandtotal" value="{{ round($totalPrice * $curr->value, 2) }}">
                    <input type="hidden" id="tax_amount_value" value="{{ round($taxAmount * $curr->value, 2) }}">
                @endif
                <input type="hidden" id="original_tax" value="0">
                <input type="hidden" id="wallet-price" name="wallet_price" value="0">
                {{-- ttotal must be numeric (no currency sign) for calculations --}}
                <input type="hidden" id="ttotal"
                    value="{{ round($totalPrice * $curr->value, 2) }}">
                <input type="hidden" name="discount_code" id="discount_code"
                    value="{{ Session::has('discount_code_value') ? Session::get('discount_code_value') : '' }}">
                <input type="hidden" name="discount_amount" id="discount_amount"
                    value="{{ Session::has('discount_code') ? Session::get('discount_code') : '' }}">
                <input type="hidden" name="discount_code_id" id="discount_code_id"
                    value="{{ Session::has('discount_code') ? Session::get('discount_code_id') : '' }}">
                <input type="hidden" name="user_id" id="user_id"
                    value="{{ Auth::guard('web')->check() ? Auth::guard('web')->user()->id : '' }}">






            </form>
        </div>
    </div>
    <!--  checkout wrapper end-->

    @php
        $country = $preloadedCountry ?? null;
        $countryId = $country ? $country->id : 0;
    @endphp
    <input type="hidden" id="select_country" name="country_id" value="{{ $countryId }}">

    {{-- ============================================== --}}
    {{-- COURIER SELECTION MODAL --}}
    {{-- ============================================== --}}
    @if(isset($courier_available) && $courier_available && isset($available_couriers) && count($available_couriers) > 0)
    <div class="modal fade" id="courierSelectionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border: none; border-radius: 12px; overflow: hidden;">
                <div class="modal-header" style="background: var(--action-success, #28a745); color: #fff; border: none;">
                    <h5 class="modal-title">
                        <i class="fas fa-motorcycle me-2"></i>
                        @lang('Select Local Courier')
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="p-3" style="background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                        <p class="mb-0 text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            @lang('Select a courier to deliver your order. Prices shown are for your area.')
                        </p>
                    </div>
                    <div class="courier-list p-3">
                        @foreach($available_couriers as $courier)
                            @php
                                $courierPriceConverted = round($courier['delivery_fee'] * $curr->value, 2);
                                $priceDisplay = $gs->currency_format == 0
                                    ? $curr->sign . $courierPriceConverted
                                    : $courierPriceConverted . $curr->sign;
                            @endphp
                            <div class="courier-item border rounded p-3 mb-3"
                                 data-courier-id="{{ $courier['courier_id'] }}"
                                 data-courier-name="{{ $courier['courier_name'] }}"
                                 data-courier-fee="{{ $courierPriceConverted }}"
                                 data-service-area-id="{{ $courier['service_area_id'] }}"
                                 onclick="selectCourier(this)"
                                 style="cursor: pointer; transition: all 0.2s;">
                                <div class="row align-items-center">
                                    {{-- Courier Photo --}}
                                    <div class="col-auto">
                                        <img src="{{ $courier['courier_photo'] ?? asset('assets/images/noimage.png') }}"
                                             alt="{{ $courier['courier_name'] }}"
                                             class="rounded-circle"
                                             style="width: 60px; height: 60px; object-fit: cover; border: 3px solid var(--action-success);">
                                    </div>
                                    {{-- Courier Details --}}
                                    <div class="col">
                                        <h6 class="mb-1 fw-bold">{{ $courier['courier_name'] }}</h6>
                                        <div class="text-muted small">
                                            @if(!empty($courier['city_name']))
                                                <span class="me-3">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    {{ $courier['city_name'] }}
                                                </span>
                                            @endif
                                            @if(!empty($courier['courier_phone']))
                                                <span>
                                                    <i class="fas fa-phone me-1"></i>
                                                    {{ $courier['courier_phone'] }}
                                                </span>
                                            @endif
                                        </div>
                                        @if(!empty($courier['distance_km']))
                                            <small class="text-info">
                                                <i class="fas fa-route me-1"></i>
                                                {{ number_format($courier['distance_km'], 1) }} @lang('km away')
                                            </small>
                                        @endif
                                    </div>
                                    {{-- Price --}}
                                    <div class="col-auto text-end">
                                        <div class="mb-1">
                                            <span class="badge bg-success fs-5 px-3 py-2">{{ $priceDisplay }}</span>
                                        </div>
                                        <small class="text-muted">@lang('Delivery Fee')</small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer" style="background: #f8f9fa; border-top: 1px solid #dee2e6;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('Cancel')</button>
                </div>
            </div>
        </div>
    </div>
    <style>
        .courier-item {
            border: 2px solid #e9ecef !important;
        }
        .courier-item:hover {
            border-color: var(--action-success, #28a745) !important;
            background-color: rgba(40, 167, 69, 0.05);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .courier-item.selected {
            border-color: var(--action-success, #28a745) !important;
            background-color: rgba(40, 167, 69, 0.1);
        }
    </style>
    @endif
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

        let original_tax = 0;

        $(document).ready(function() {
            @if(isset($step2) && $step2)
                @if(isset($step2->saved_shipping_selections) && is_array($step2->saved_shipping_selections))
                    // Restore shipping selections
                    @foreach($step2->saved_shipping_selections as $merchantId => $shippingValue)
                        // Find and check the radio for this merchant
                        const shippingRadio{{ $merchantId }} = $('input.shipping[name="shipping[{{ $merchantId }}]"][value="{{ $shippingValue }}"]');
                        if (shippingRadio{{ $merchantId }}.length > 0) {
                            shippingRadio{{ $merchantId }}.prop('checked', true);
                        }
                    @endforeach
                @endif

                @if(isset($step2->saved_packing_selections) && is_array($step2->saved_packing_selections))
                    // Restore packing selections
                    @foreach($step2->saved_packing_selections as $merchantId => $packingValue)
                        const packingRadio{{ $merchantId }} = $('input.packing[name="packeging[{{ $merchantId }}]"][value="{{ $packingValue }}"]');
                        if (packingRadio{{ $merchantId }}.length > 0) {
                            packingRadio{{ $merchantId }}.prop('checked', true);
                        }
                    @endforeach
                @endif

                // Display saved shipping text
                @if(isset($step2->shipping_company))
                    $('#shipping_text{{ $merchant_id ?? 0 }}').html('{{ $step2->shipping_company }}');
                @endif

                // Display saved packing text
                @if(isset($step2->packing_company))
                    $('#packing_text{{ $merchant_id ?? 0 }}').html('{{ $step2->packing_company }}');
                @endif
            @endif

            updateFinalTotal();

            let is_state = $('#is_state').val();
            if (is_state == 1) {
                $('.select_state').removeClass('d-none');
            } else {
                hide_state();
            }
        });

        /**
         * Select courier from modal
         */
        window.selectCourier = function(element) {
            const courierId = $(element).data('courier-id');
            const courierName = $(element).data('courier-name');
            const courierFee = parseFloat($(element).data('courier-fee')) || 0;
            const serviceAreaId = $(element).data('service-area-id');

            // Update hidden fields
            $('#selected_courier_id').val(courierId);
            $('#selected_courier_fee').val(courierFee);
            $('#selected_service_area_id').val(serviceAreaId);
            $('#delivery_type').val('local_courier');

            // Update display in the header section
            const currSign = '{{ $curr->sign }}';
            const currFormat = {{ $gs->currency_format }};
            let priceDisplay = currFormat == 0
                ? currSign + courierFee.toFixed(2)
                : courierFee.toFixed(2) + currSign;

            $('#selected-courier-name').text(courierName);
            $('#selected-courier-price').text(priceDisplay);
            $('#selected-courier-display').removeClass('d-none');
            $('#select-courier-btn-wrapper').addClass('d-none');

            // Highlight selected item in modal
            $('.courier-item').removeClass('selected');
            $(element).addClass('selected');

            // Clear shipping selections (courier replaces shipping)
            $('input.shipping').prop('checked', false);
            // Clear shipping text display
            $('[id^="shipping_text"]').text('{{ __("Not Selected") }}');

            // Update price summary
            updateCourierInSummary(courierName, courierFee);

            // Close modal
            $('#courierSelectionModal').modal('hide');
        };

        /**
         * Update courier fee in price summary
         */
        function updateCourierInSummary(courierName, fee) {
            const currSign = '{{ $curr->sign }}';
            const currFormat = {{ $gs->currency_format }};
            let priceDisplay = currFormat == 0
                ? currSign + fee.toFixed(2)
                : fee.toFixed(2) + currSign;

            // Show courier row, hide shipping row
            $('#shipping-row').addClass('d-none');
            $('#free-shipping-row').addClass('d-none');
            $('#courier-row').removeClass('d-none');

            // Update courier display in summary
            $('#courier-name-summary').text(' - ' + courierName);
            $('#courier-fee-display').text(priceDisplay);

            // Update the shipping cost hidden field with courier fee
            // This ensures the total calculation includes the courier fee
            $('#price-shipping-cost').val(fee);

            // Recalculate total using PriceSummary
            if (typeof PriceSummary !== 'undefined') {
                PriceSummary.updateShipping(fee);
            } else {
                // Fallback manual calculation
                let catalogItemsTotal = parseFloat($('#price-catalogItems-total').val()) || 0;
                let discountAmount = parseFloat($('#price-discount-amount').val()) || 0;
                let taxAmount = parseFloat($('#price-tax-amount').val()) || 0;
                let packingCost = parseFloat($('#price-packing-cost').val()) || 0;

                let grandTotal = catalogItemsTotal - discountAmount + taxAmount + fee + packingCost;
                $('#price-grand-total').val(grandTotal.toFixed(2));
                $('#grand-total-display').text(currFormat == 0 ? currSign + grandTotal.toFixed(2) : grandTotal.toFixed(2) + currSign);
                $('#grandtotal').val(grandTotal.toFixed(2));
            }
        }

        /**
         * Reset courier selection (when shipping is selected instead)
         */
        function resetCourierSelection() {
            // Hide courier row, show shipping row
            $('#courier-row').addClass('d-none');
            $('#shipping-row').removeClass('d-none');

            // Clear courier hidden fields
            $('#selected_courier_id').val('');
            $('#selected_courier_fee').val('0');
            $('#selected_service_area_id').val('');
            $('#delivery_type').val('shipping');

            // Reset header display
            $('#selected-courier-display').addClass('d-none');
            $('#select-courier-btn-wrapper').removeClass('d-none');
        }


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
                    // data[1] = tax rate (e.g., 15)
                    // data[11] = tax amount
                    // data[12] = tax type

                    var taxRate = parseFloat(data[1]) || 0;
                    var taxAmount = parseFloat(data[11]) || 0;

                    // Update hidden fields for form submission
                    $('#original_tax').val(taxRate);
                    $('#tax_amount_value').val(taxAmount);
                    $('#input_tax').val(taxAmount);
                    $('#input_tax_type').val(data[12]);

                    if (typeof PriceSummary !== 'undefined') {
                        PriceSummary.updateTax(taxRate, taxAmount);
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

            resetCourierSelection();
            updateFinalTotal();

            $('#multi_shipping_id').val($(this).val());
        })


        $('.packing').on('click', function() {
            getPacking();
            let ref = $(this).attr('ref');
            let view = $(this).attr('view');
            let title = $(this).attr('data-form');
            $('#packing_text' + ref).html(title + '+' + view);

            updateFinalTotal();

            $('#multi_packaging_id').val($(this).val());
            $('input[name="merchant_packing_id"]').val($(this).val());
        })


        window.getShipping = function getShipping() {
            mship = 0;
            let originalShipping = 0;
            let isFreeShipping = false;

            const checkedShipping = $('input.shipping:checked, input.shipping-option:checked, input.tryoto-radio:checked');

            checkedShipping.each(function() {
                const originalPrice = parseFloat($(this).attr('data-price')) || 0;
                const freeAbove = parseFloat($(this).attr('data-free-above')) || 0;
                const merchantId = $(this).attr('ref') || $(this).attr('name')?.match(/\[(\d+)\]/)?.[1];
                const merchantTotal = getMerchantTotal(merchantId);

                originalShipping += originalPrice;

                if (freeAbove > 0 && merchantTotal >= freeAbove) {
                    isFreeShipping = true;
                } else {
                    mship += originalPrice;
                }
            });

            if (typeof PriceSummary !== 'undefined') {
                PriceSummary.updateShipping(mship, originalShipping, isFreeShipping);
            }
        }

        window.getMerchantTotal = function getMerchantTotal(merchantId) {
            const merchantTotalEl = $('[data-order-total="' + merchantId + '"]');
            if (merchantTotalEl.length > 0) {
                return parseFloat(merchantTotalEl.attr('data-amount')) || 0;
            }
            // Fallback: use total cart
            return parseFloat($('#ttotal').val()) || 0;
        }

        function getPacking() {
            mpack = 0;
            const checkedPacking = $('.packing:checked');

            checkedPacking.each(function() {
                mpack += parseFloat($(this).attr('data-price')) || 0;
            });
            if (typeof PriceSummary !== 'undefined') {
                PriceSummary.updatePacking(mpack);
            }
        }

        window.updateFinalTotal = function updateFinalTotal() {
            if (typeof PriceSummary !== 'undefined') {
                PriceSummary.recalculateTotal();
            }
        }

    </script>
@endsection
