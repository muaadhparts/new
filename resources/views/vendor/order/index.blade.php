@extends('layouts.vendor')

@section('content')
<!-- outlet start  -->
<div class="gs-vendor-outlet">
    <!-- breadcrumb start  -->
    <div class="gs-vendor-breadcrumb has-mb">
        <h4 class="text-capitalize">@lang('All Orders')</h4>
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
                <a href="#" class="text-capitalize">
                    @lang('All Order')
                </a>
            </li>
        </ul>
    </div>
    <!-- breadcrumb end -->

    <!--search box  -->
    <div class="d-flex justify-content-end">
        <form class="gs-extra-search-box-form form-group mb-4" action="">
            <input placeholder="search.." class="form-control" type="text">
            <button class="template-btn" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
    </div>


    <!-- Table area start  -->
    <div class="vendor-table-wrapper all-orders-table-wrapper">
        <div class="user-table table-responsive position-relative">
            <table class="gs-data-table w-100">
                <thead>
                    <tr>
                        <th><span class="header-title">@lang('Order Number')</span></th>
                        <th><span class="header-title">@lang('Total Qty')</span></th>
                        <th><span class="header-title">@lang('Total Cost')</span></th>
                        <th><span class="header-title">@lang('Payment Method')</span></th>
                        <th><span class="header-title">@lang('Status')</span></th>
                        <th class="text-center">
                            <span class="header-title">@lang('Actions')</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                    @php
                    $order_v = App\Models\Order::findOrFail($order->id);

                    $price = $order_v
                    ->vendororders()
                    ->where('user_id', '=', $user->id)
                    ->sum('price');

                    if ($order_v->is_shipping == 1) {
                    $vendor_shipping = json_decode($order_v->vendor_shipping_id);

                    $user_id = (int) auth()->id();

                    // shipping cost
                    $shipping_id = $vendor_shipping->$user_id;
                    $shipping = App\Models\Shipping::findOrFail($shipping_id);
                    if ($shipping) {
                    $price = $price + round($shipping->price * $order_v->currency_value, 2);
                    }

                    // packaging cost
                    $vendor_packing_id = json_decode($order_v->vendor_packing_id);
                    $packing_id = $vendor_packing_id->$user_id;
                    $packaging = App\Models\Package::findOrFail($packing_id);
                    if ($packaging) {
                    $price = $price + round($packaging->price * $order_v->currency_value, 2);
                    }
                    }
                    @endphp
                    <tr>
                        <!-- Order Number -->
                        <td><span class="content">{{ $order->order_number }}</span></td>
                        <!-- Total Qty -->
                        <td class="text-start">
                            <span class="content">
                                {{ $order->vendororders()->where('user_id', '=', $user->id)->sum('qty') }}
                            </span>
                        </td>
                        <!-- Total Cost -->
                        <td><span class="content">{{ PriceHelper::showOrderCurrencyPrice($price, $order->currency_sign)
                                }}</span>
                        </td>
                        <!-- Payment Method -->
                        <td>
                            <span class="content">
                                {{ $order->method }}
                            </span>
                        </td>
                        <!-- Status -->
                        <td>

                            <span class="content">
                                @if ($order->status == 'pending')
                                <span class="bg-pending status-btn">{{ $order->status }}</span>
                                @elseif ($order->status == 'processing')
                                <span class="bg-processing status-btn">{{ $order->status }}</span>
                                @elseif ($order->status == 'completed')
                                <span class="bg-complete status-btn">{{ $order->status }}</span>
                                @elseif ($order->status == 'declined')
                                <span class="bg-declined status-btn">{{ $order->status }}</span>
                                @endif
                            </span>
                        </td>
                        <!-- Actions -->
                        <td>
                            <div class="table-icon-btns-wrapper">
                                <a href="{{ route('vendor-order-show', $order->order_number) }}" class="view-btn">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <g clip-path="url(#clip0_548_165891)">
                                            <path
                                                d="M12 4.84668C7.41454 4.84668 3.25621 7.35543 0.187788 11.4303C-0.0625959 11.7641 -0.0625959 12.2305 0.187788 12.5644C3.25621 16.6442 7.41454 19.1529 12 19.1529C16.5855 19.1529 20.7438 16.6442 23.8122 12.5693C24.0626 12.2354 24.0626 11.769 23.8122 11.4352C20.7438 7.35543 16.5855 4.84668 12 4.84668ZM12.3289 17.0369C9.28506 17.2284 6.7714 14.7196 6.96287 11.6709C7.11998 9.1572 9.15741 7.11977 11.6711 6.96267C14.7149 6.7712 17.2286 9.27994 17.0371 12.3287C16.8751 14.8375 14.8377 16.8749 12.3289 17.0369ZM12.1767 14.7098C10.537 14.8129 9.18196 13.4628 9.28997 11.8231C9.37343 10.468 10.4732 9.37322 11.8282 9.28485C13.4679 9.18175 14.823 10.5319 14.7149 12.1716C14.6266 13.5316 13.5268 14.6264 12.1767 14.7098Z"
                                                fill="white" />
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_548_165891">
                                                <rect width="24" height="24" fill="white" />
                                            </clipPath>
                                        </defs>
                                    </svg>
                                </a>
                            </div>
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
@endsection
