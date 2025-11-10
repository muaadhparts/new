@extends('layouts.front')

@section('content')
    <div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="d-flex">
                <!-- sidebar -->
                @include('includes.rider.sidebar')
                <!-- main content -->
                <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                    <div class="ud-page-title-box">
                        <h3 class="ud-page-title">@lang('Dashboard')</h3>
                    </div>

                    <div class="account-information">
                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="account-info-box">
                                    <h5>@lang('Account Information')</h5>
                                    <div class="account-info">
                                        <div class="account-info-item">
                                            <span class="info-title">@lang('Name:') </span>
                                            <span class="info-content">{{ $user->name }}</span>
                                        </div>
                                        <div class="account-info-item">
                                            <span class="info-title">@lang('Email:') </span>
                                            <span class="info-content">
                                                {{ $user->email }}
                                            </span>

                                        </div>
                                        <div class="account-info-item">
                                            <span class="info-title">@lang('Phone:') </span>
                                            <span class="info-content">{{ $user->phone }}</span>
                                        </div>
                                        <div class="account-info-item">
                                            <span class="info-title">@lang('Address:') </span>
                                            <span class="info-content">{{ $user->address }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="account-info-box">
                                    <div class="icon-box">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="32"
                                            viewBox="0 0 28 32" fill="none">
                                            <path
                                                d="M16.1766 21.2588C16.0571 21.128 15.9059 21.0301 15.7496 20.9485C15.4094 20.7711 15.0374 20.6635 14.668 20.5664V23.4289C15.2676 23.3612 15.9559 23.1435 16.2708 22.5838C16.421 22.3164 16.4509 21.9879 16.3897 21.6902C16.3565 21.5289 16.2879 21.3809 16.1766 21.2588Z"
                                                fill="#FFB134" />
                                            <path
                                                d="M16.2676 22.5875C16.2688 22.5855 16.2695 22.5842 16.2707 22.582C16.2697 22.5838 16.2686 22.5856 16.2676 22.5875Z"
                                                fill="black" />
                                            <path
                                                d="M11.8555 16.6824C11.7524 16.8329 11.6877 17.0039 11.6699 17.1857C11.652 17.3678 11.662 17.5705 11.7312 17.7419C11.7971 17.9053 11.9351 18.0259 12.0776 18.1231C12.2375 18.2318 12.4133 18.3167 12.5923 18.3887C12.7426 18.4492 12.9115 18.507 13.0937 18.5629V15.9727C12.6335 16.0708 12.1308 16.2805 11.8555 16.6824Z"
                                                fill="#FFB134" />
                                            <path
                                                d="M16.2828 22.5586C16.2791 22.5653 16.275 22.5723 16.271 22.5797C16.2757 22.5711 16.2794 22.5648 16.2828 22.5586Z"
                                                fill="black" />
                                            <path d="M16.2974 22.5346C16.2985 22.5327 16.2983 22.5328 16.2974 22.5346Z"
                                                fill="black" />
                                            <path
                                                d="M15.6762 6.96269C18.1105 4.91775 19.7371 0.310815 18.7361 0.109318C17.406 -0.158515 14.5178 1.0159 13.1217 1.22282C11.1417 1.46189 8.98519 -0.924293 7.77471 0.405765C6.79049 1.48719 8.48032 5.41999 11.1241 7.13331C3.23652 11.0099 -7.85001 30.4709 11.491 31.883C38.2526 33.8369 24.8521 10.7404 15.6762 6.96269ZM17.9745 22.3105C17.8922 23.0692 17.4901 23.7553 16.8891 24.2211C16.2545 24.713 15.4585 24.9417 14.6677 25.0058V25.8441C14.6677 26.0684 14.5697 26.2853 14.4018 26.4338C14.171 26.638 13.8319 26.6884 13.5518 26.5594C13.2752 26.4321 13.0935 26.1485 13.0935 25.8441V24.9279C12.9579 24.9021 12.8233 24.8715 12.6902 24.835C11.9511 24.6319 11.2655 24.236 10.7647 23.6504C10.5152 23.3585 10.3119 23.0272 10.1712 22.6696C10.1345 22.5762 10.1017 22.481 10.073 22.3848C10.047 22.2977 10.0202 22.2091 10.011 22.1183C9.99538 21.966 10.0256 21.8106 10.0966 21.6749C10.2425 21.3957 10.55 21.2272 10.864 21.2552C11.173 21.2826 11.4439 21.497 11.5412 21.7916C11.5711 21.8823 11.5915 21.9753 11.6251 22.0651C11.6585 22.1542 11.6988 22.2409 11.7461 22.3234C11.8397 22.4859 11.9568 22.6357 12.0917 22.7658C12.3699 23.0338 12.7242 23.2079 13.0935 23.3115V20.1971C12.3713 20.0101 11.6259 19.7686 11.0295 19.305C10.7397 19.0795 10.4938 18.7994 10.3327 18.4679C10.1628 18.118 10.0924 17.7281 10.0895 17.341C10.0866 16.9478 10.1613 16.5582 10.3253 16.2C10.4789 15.8646 10.7 15.5635 10.9712 15.3139C11.551 14.7802 12.3211 14.4826 13.0936 14.3733V14.3047V13.503C13.0936 13.2788 13.1915 13.0618 13.3595 12.9133C13.5903 12.7092 13.9293 12.6587 14.2095 12.7878C14.486 12.9151 14.6678 13.1987 14.6678 13.503V14.3047V14.3685C14.7707 14.3815 14.8733 14.397 14.9754 14.4155C15.734 14.5528 16.4759 14.866 17.0306 15.4129C17.2934 15.6719 17.5085 15.9806 17.6598 16.3171C17.702 16.411 17.739 16.5071 17.771 16.6051C17.8008 16.6964 17.8308 16.7905 17.845 16.8858C17.8678 17.038 17.8446 17.1952 17.7794 17.3344C17.646 17.6196 17.3468 17.8013 17.032 17.7878C16.7226 17.7743 16.4424 17.5729 16.3316 17.2837C16.2986 17.1977 16.2821 17.1067 16.2485 17.0209C16.2145 16.9341 16.1705 16.8513 16.1199 16.7731C16.0214 16.621 15.8938 16.4906 15.7481 16.3831C15.4326 16.1501 15.0499 16.0287 14.6675 15.9609V18.9413C15.1219 19.0501 15.5767 19.1688 16.0121 19.3405C16.6701 19.6 17.2937 19.9978 17.6529 20.6252C17.5971 20.5275 17.5429 20.4322 17.6547 20.6285C17.7645 20.8212 17.7121 20.7294 17.657 20.6327C17.9428 21.1363 18.0366 21.7385 17.9745 22.3105Z"
                                                fill="#FFB134" />
                                            <path
                                                d="M16.2593 22.6035C16.2547 22.6117 16.2507 22.619 16.2476 22.6244C16.2503 22.6197 16.2543 22.6126 16.2593 22.6035Z"
                                                fill="black" />
                                        </svg>
                                    </div>
                                    <h6>@lang('Current Balance')</h6>
                                    <h4>{{ App\Models\Product::vendorConvertPrice($user->balance) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- recent orders -->
                    <h4 class="table-title mt-4">@lang('Recent Orders')</h4>
                    <div class="user-table recent-orders-table table-responsive wow-replaced" data-wow-delay=".1s">
                        <table class="table table-bordered">
                            <tr>
                                <th><span class="header-title">{{ __('#Order') }}</span></th>
                                <th><span class="header-title">{{ __('Service Area') }}</span></th>
                                <th><span class="header-title">{{ __('Pickup Point') }}</span></th>
                                <th><span class="header-title">{{ __('Order Total') }}</span></th>
                                <th><span class="header-title">{{ __('Order Status') }}</span></th>
                                <th><span class="header-title">{{ __('View') }}</span></th>
                            </tr>
                            @forelse ($orders as $order)
                                <tr>
                                    <td data-label="{{ __('#Order') }}">
                                        {{ $order->order->order_number }}
                                    </td>
                                    <td data-label="{{ __('Service Area') }}">
                                        <p>
                                            {{ $order->order->customer_city }}
                                        </p>
                                    </td>

                                    <td data-label="{{ __('Pickup Point') }}">
                                        <p>
                                            {{ $order->pickup->location }}
                                        </p>
                                    </td>

                                    <td data-label="{{ __('Order Total') }}">

                                        @php

                                             $order_shipping = json_decode($order->order->vendor_shipping_id, true) ?? [];
                                            $order_package = json_decode($order->order->vendor_packing_id, true) ?? [];
                                            
                                            // Retrieve vendor-specific shipping and packing IDs
                                            $vendor_shipping_id = $order_shipping[$order->vendor_id] ?? null;
                                            $vendor_package_id = $order_package[$order->vendor_id] ?? null;
                                            
                                            // Retrieve Shipping model or set to null if not found
                                            $shipping = $vendor_shipping_id ? App\Models\Shipping::find($vendor_shipping_id) : null;
                                            
                                            // Retrieve Package model or set to null if not found
                                            $package = $vendor_package_id ? App\Models\Package::find($vendor_package_id) : null;
                                            
                                            // Calculate costs if models are found, default to 0 if null
                                            $shipping_cost = $shipping ? $shipping->price : 0;
                                            $packing_cost = $package ? $package->price : 0;
                                            
                                            // Total extra cost
                                            $extra_price = $shipping_cost + $packing_cost;
                                        @endphp

                                        {{ \PriceHelper::showAdminCurrencyPrice(
                                            ($order->order->vendororders->where('user_id', $order->vendor_id)->sum('price') + $extra_price) *
                                                $order->order->currency_value,
                                            $order->currency_sign,
                                        ) }}
                                    </td>
                                    <td data-label="{{ __('Order Status') }}">
                                        <div class="">
                                            <span
                                                class="px-3 py-2 md-btn rounded {{ $order->status == 'pending' ? 'bg-pending' : 'bg-complete' }} mx-auto">{{ ucwords($order->status) }}
                                            </span>


                                        </div>
                                    </td>
                                    <td data-label="{{ __('View') }}">

                                        <a href="{{ route('rider-order-details', $order->id) }}" class="view-btn">
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
                                        </a>

                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">{{ __('No orders found') }}</td>
                                </tr>
                            @endforelse
                        </table>
                    </div>

                    <!-- account information -->

                </div>
            </div>
        </div>
    </div>
@endsection
