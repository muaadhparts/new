@extends('layouts.courier')

@section('content')
    <div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="d-flex">
                <!-- sidebar -->
                @include('includes.courier.sidebar')
                <!-- main content -->
                <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                    <div class="ud-page-name-box">
                        <h3 class="ud-page-name">@lang('Accounting Summary')</h3>
                    </div>

                    <!-- Current Balance Card -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-4 col-md-6">
                            <div class="account-info-box text-center {{ ($report['current_balance'] ?? 0) < 0 ? 'border-danger' : (($report['current_balance'] ?? 0) > 0 ? 'border-success' : '') }}">
                                <h6>@lang('Current Balance')</h6>
                                <h3 class="{{ ($report['current_balance'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $currency->sign ?? 'SAR ' }}{{ number_format($report['current_balance'] ?? 0, 2) }}
                                </h3>
                                @if(($report['is_in_debt'] ?? false))
                                    <small class="text-danger">@lang('You owe to platform')</small>
                                @elseif(($report['has_credit'] ?? false))
                                    <small class="text-success">@lang('Platform owes you')</small>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="account-info-box text-center">
                                <h6>@lang('Total COD Collected')</h6>
                                <h3 class="text-warning">{{ $currency->sign ?? 'SAR ' }}{{ number_format($report['total_cod_collected'] ?? 0, 2) }}</h3>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="account-info-box text-center">
                                <h6>@lang('Total Fees Earned')</h6>
                                <h3 class="text-success">{{ $currency->sign ?? 'SAR ' }}{{ number_format($report['total_fees_earned'] ?? 0, 2) }}</h3>
                            </div>
                        </div>
                    </div>

                    <!-- Current Settlement Calculation -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-6">
                            <div class="account-info-box">
                                <h5>@lang('Unsettled Deliveries Breakdown')</h5>
                                <div class="account-info">
                                    <div class="account-info-item d-flex justify-content-between">
                                        <span class="info-name">@lang('COD Amount (You Owe)')</span>
                                        <span class="text-danger">{{ $currency->sign ?? 'SAR ' }}{{ number_format($settlementCalc['cod_amount'] ?? 0, 2) }}</span>
                                    </div>
                                    <div class="account-info-item d-flex justify-content-between">
                                        <span class="info-name">@lang('Fees Earned (Online)')</span>
                                        <span class="text-success">{{ $currency->sign ?? 'SAR ' }}{{ number_format($settlementCalc['fees_earned_online'] ?? 0, 2) }}</span>
                                    </div>
                                    <div class="account-info-item d-flex justify-content-between">
                                        <span class="info-name">@lang('Fees Earned (COD)')</span>
                                        <span class="text-success">{{ $currency->sign ?? 'SAR ' }}{{ number_format($settlementCalc['fees_earned_cod'] ?? 0, 2) }}</span>
                                    </div>
                                    <hr>
                                    <div class="account-info-item d-flex justify-content-between">
                                        <span class="info-name"><strong>@lang('Net Amount')</strong></span>
                                        {{-- Net amount pre-computed in Controller (DATA_FLOW_POLICY) --}}
                                        <span class="{{ $netAmount >= 0 ? 'text-success' : 'text-danger' }}">
                                            <strong>{{ $currency->sign ?? 'SAR ' }}{{ number_format(abs($netAmount), 2) }}</strong>
                                            @if($netAmount >= 0)
                                                <small>(@lang('Platform owes you'))</small>
                                            @else
                                                <small>(@lang('You owe to platform'))</small>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="account-info-box">
                                <h5>@lang('How It Works')</h5>
                                <div class="account-info">
                                    <p class="mb-2">
                                        <i class="fas fa-money-bill-wave text-warning me-2"></i>
                                        @lang('When you collect COD, you owe that amount to the platform.')
                                    </p>
                                    <p class="mb-2">
                                        <i class="fas fa-truck text-success me-2"></i>
                                        @lang('You earn delivery fees for every successful delivery.')
                                    </p>
                                    <p class="mb-2">
                                        <i class="fas fa-balance-scale text-info me-2"></i>
                                        @lang('Net amount = Your earnings - COD collected.')
                                    </p>
                                    <p class="mb-0">
                                        <i class="fas fa-hand-holding-usd text-primary me-2"></i>
                                        @lang('Settlements are processed by the admin via the accounting system.')
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Unsettled Deliveries -->
                    <h4 class="table-name mt-4">@lang('Unsettled Deliveries')</h4>
                    <div class="user-table recent-orders-table table-responsive wow-replaced" data-wow-delay=".1s">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th><span class="header-name">{{ __('#') }}</span></th>
                                    <th><span class="header-name">{{ __('Purchase') }}</span></th>
                                    <th><span class="header-name">{{ __('Payment') }}</span></th>
                                    <th><span class="header-name">{{ __('COD Amount') }}</span></th>
                                    <th><span class="header-name">{{ __('Delivery Fee') }}</span></th>
                                    <th><span class="header-name">{{ __('Date') }}</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($unsettledDeliveries as $key => $delivery)
                                    <tr>
                                        <td data-label="{{ __('#') }}">
                                            {{ $key + 1 }}
                                        </td>
                                        <td data-label="{{ __('Purchase') }}">
                                            @if($delivery->purchase)
                                                <a href="{{ route('courier-purchase-details', $delivery->id) }}">
                                                    {{ $delivery->purchase->purchase_number }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td data-label="{{ __('Payment') }}">
                                            @if($delivery->payment_method === 'cod')
                                                <span class="badge bg-warning">@lang('COD')</span>
                                            @else
                                                <span class="badge bg-success">@lang('Online')</span>
                                            @endif
                                        </td>
                                        <td data-label="{{ __('COD Amount') }}">
                                            @if($delivery->payment_method === 'cod')
                                                <span class="text-danger">{{ $currency->sign ?? 'SAR ' }}{{ number_format($delivery->purchase_amount ?? 0, 2) }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td data-label="{{ __('Delivery Fee') }}">
                                            <span class="text-success">{{ $currency->sign ?? 'SAR ' }}{{ number_format($delivery->delivery_fee ?? 0, 2) }}</span>
                                        </td>
                                        <td data-label="{{ __('Date') }}">
                                            {{ $delivery->delivered_at ? $delivery->delivered_at->format('d-m-Y') : $delivery->created_at->format('d-m-Y') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">{{ __('No unsettled deliveries') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
