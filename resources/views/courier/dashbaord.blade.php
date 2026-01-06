@extends('layouts.front')

@section('content')
    <div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="d-flex">
                <!-- sidebar -->
                @include('includes.courier.sidebar')
                <!-- main content -->
                <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                    <div class="ud-page-title-box">
                        <h3 class="ud-page-title">@lang('Dashboard')</h3>
                    </div>

                    <!-- Financial Summary Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-3 col-md-6">
                            <div class="account-info-box text-center {{ ($report['current_balance'] ?? 0) < 0 ? 'border-danger' : (($report['current_balance'] ?? 0) > 0 ? 'border-success' : '') }}">
                                <h6>@lang('Current Balance')</h6>
                                <h4 class="{{ ($report['current_balance'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $currency->sign ?? 'SAR ' }}{{ number_format($report['current_balance'] ?? 0, 2) }}
                                </h4>
                                @if(($report['is_in_debt'] ?? false))
                                    <small class="text-danger">@lang('You owe to platform')</small>
                                @elseif(($report['has_credit'] ?? false))
                                    <small class="text-success">@lang('Platform owes you')</small>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="account-info-box text-center">
                                <h6>@lang('COD Collected')</h6>
                                <h4 class="text-warning">{{ $currency->sign ?? 'SAR ' }}{{ number_format($report['total_collected'] ?? 0, 2) }}</h4>
                                <small class="text-muted">@lang('Total cash collected')</small>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="account-info-box text-center">
                                <h6>@lang('Fees Earned')</h6>
                                <h4 class="text-primary">{{ $currency->sign ?? 'SAR ' }}{{ number_format($report['total_fees_earned'] ?? 0, 2) }}</h4>
                                <small class="text-muted">@lang('Delivery fees earned')</small>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="account-info-box text-center">
                                <h6>@lang('Total Deliveries')</h6>
                                <h4>{{ $report['deliveries_count'] ?? 0 }}</h4>
                                <small class="text-muted">{{ $report['deliveries_completed'] ?? 0 }} @lang('completed')</small>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Stats -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-6">
                            <div class="account-info-box">
                                <h5>@lang('Delivery Statistics')</h5>
                                <div class="account-info">
                                    <div class="account-info-item d-flex justify-content-between">
                                        <span class="info-title">@lang('COD Deliveries')</span>
                                        <span class="badge bg-warning">{{ $report['cod_deliveries'] ?? 0 }}</span>
                                    </div>
                                    <div class="account-info-item d-flex justify-content-between">
                                        <span class="info-title">@lang('Online Payment Deliveries')</span>
                                        <span class="badge bg-info">{{ $report['online_deliveries'] ?? 0 }}</span>
                                    </div>
                                    <div class="account-info-item d-flex justify-content-between">
                                        <span class="info-title">@lang('Pending Deliveries')</span>
                                        <span class="badge bg-secondary">{{ $report['deliveries_pending'] ?? 0 }}</span>
                                    </div>
                                    <div class="account-info-item d-flex justify-content-between">
                                        <span class="info-title">@lang('Unsettled Deliveries')</span>
                                        <span class="badge bg-danger">{{ $report['unsettled_deliveries'] ?? 0 }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="account-info-box">
                                <h5>@lang('Account Information')</h5>
                                <div class="account-info">
                                    <div class="account-info-item">
                                        <span class="info-title">@lang('Name:') </span>
                                        <span class="info-content">{{ $user->name }}</span>
                                    </div>
                                    <div class="account-info-item">
                                        <span class="info-title">@lang('Email:') </span>
                                        <span class="info-content">{{ $user->email }}</span>
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
                    </div>

                    <!-- recent purchases -->
                    <h4 class="table-title mt-4">@lang('Recent Purchases')</h4>
                    <div class="user-table recent-orders-table table-responsive wow-replaced" data-wow-delay=".1s">
                        <table class="table table-bordered">
                            <tr>
                                <th><span class="header-title">{{ __('#Purchase') }}</span></th>
                                <th><span class="header-title">{{ __('Service Area') }}</span></th>
                                <th><span class="header-title">{{ __('Pickup Point') }}</span></th>
                                <th><span class="header-title">{{ __('Purchase Total') }}</span></th>
                                <th><span class="header-title">{{ __('Purchase Status') }}</span></th>
                                <th><span class="header-title">{{ __('View') }}</span></th>
                            </tr>
                            @forelse ($purchases as $purchase)
                                <tr>
                                    <td data-label="{{ __('#Purchase') }}">
                                        {{ $purchase->purchase->purchase_number }}
                                    </td>
                                    <td data-label="{{ __('Service Area') }}">
                                        <p>
                                            {{ $purchase->purchase->customer_city }}
                                        </p>
                                    </td>

                                    <td data-label="{{ __('Pickup Point') }}">
                                        <p>
                                            {{ $purchase->pickup->location }}
                                        </p>
                                    </td>

                                    <td data-label="{{ __('Purchase Total') }}">

                                        @php

                                             $purchase_shipping = json_decode($purchase->purchase->merchant_shipping_id, true) ?? [];
                                            $purchase_package = json_decode($purchase->purchase->merchant_packing_id, true) ?? [];

                                            // Retrieve merchant-specific shipping and packing IDs
                                            $merchant_shipping_id = $purchase_shipping[$purchase->merchant_id] ?? null;
                                            $merchant_package_id = $purchase_package[$purchase->merchant_id] ?? null;

                                            // Retrieve Shipping model or set to null if not found
                                            $shipping = $merchant_shipping_id ? App\Models\Shipping::find($merchant_shipping_id) : null;

                                            // Retrieve Package model or set to null if not found
                                            $package = $merchant_package_id ? App\Models\Package::find($merchant_package_id) : null;

                                            // Calculate costs if models are found, default to 0 if null
                                            $shipping_cost = $shipping ? $shipping->price : 0;
                                            $packing_cost = $package ? $package->price : 0;

                                            // Total extra cost
                                            $extra_price = $shipping_cost + $packing_cost;
                                        @endphp

                                        {{ \PriceHelper::showAdminCurrencyPrice(
                                            ($purchase->purchase->merchantPurchases->where('user_id', $purchase->merchant_id)->sum('price') + $extra_price) *
                                                $purchase->purchase->currency_value,
                                            $purchase->currency_sign,
                                        ) }}
                                    </td>
                                    <td data-label="{{ __('Purchase Status') }}">
                                        <div class="">
                                            <span
                                                class="px-3 py-2 md-btn rounded {{ $purchase->status == 'pending' ? 'bg-pending' : 'bg-complete' }} mx-auto">{{ ucwords($purchase->status) }}
                                            </span>


                                        </div>
                                    </td>
                                    <td data-label="{{ __('View') }}">

                                        <a href="{{ route('courier-purchase-details', $purchase->id) }}" class="view-btn">
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
                                    <td colspan="6">{{ __('No purchases found') }}</td>
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
