@extends('layouts.front')
@section('css')
    <link rel="stylesheet" href="{{ asset('assets/front/css/datatables.css') }}">
@endsection
@section('content')
    <div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="d-flex">
                <!-- sidebar -->
                @include('includes.rider.sidebar')
                <!-- main content -->
                <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                    <!-- page title -->
                    <div class="ud-page-title-box">
                        <!-- mobile sidebar trigger btn -->

                        <h3 class="ud-page-title">
                            @if (request()->input('type') == 'complete')
                                {{ __('Complete Orders') }}
                            @else
                                {{ __('Pending Orders') }}
                            @endif
                        </h3>
                    </div>

                    <!--  order status steps -->

                    <div class="user-table table-responsive position-relative">

                        <table class="gs-data-table w-100">
                            <thead>
                                <tr>

                                    <th>{{ __('#Order') }}</th>
                                    <th>{{ __('Service Area') }}</th>
                                    <th>{{ __('Pickup Point') }}</th>
                                    <th>{{ __('Order Total') }}</th>
                                    <th>{{ __('Order Status') }}</th>
                                    <th>{{ __('View') }}</th>
                                </tr>

                            </thead>
                            <tbody>
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
                            </tbody>
                        </table>
                    </div>
                    {{ $orders->links('includes.frontend.pagination') }}
                </div>
            </div>
        </div>
    </div>
    <!-- user dashboard wrapper end -->
@endsection
@section('script')
    <script src="{{ asset('assets/front/js/dataTables.min.js') }}" defer></script>
    <script src="{{ asset('assets/front/js/user.js') }}" defer></script>
@endsection
