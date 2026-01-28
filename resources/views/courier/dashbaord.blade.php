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
                        <h3 class="ud-page-name">@lang('Dashboard')</h3>
                    </div>

                    <!-- Financial Summary Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-3 col-md-6">
                            <div class="account-info-box text-center {{ $report['is_in_debt'] ? 'border-danger' : ($report['has_credit'] ? 'border-success' : '') }}">
                                <h6>@lang('Current Balance')</h6>
                                <h4 class="{{ $report['is_in_debt'] ? 'text-danger' : 'text-success' }}">
                                    {{ $report['current_balance_formatted'] }}
                                </h4>
                                @if($report['is_in_debt'])
                                    <small class="text-danger">@lang('You owe to platform')</small>
                                @elseif($report['has_credit'])
                                    <small class="text-success">@lang('Platform owes you')</small>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="account-info-box text-center">
                                <h6>@lang('COD Collected')</h6>
                                <h4 class="text-warning">{{ $report['total_collected_formatted'] }}</h4>
                                <small class="text-muted">@lang('Total cash collected')</small>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="account-info-box text-center">
                                <h6>@lang('Fees Earned')</h6>
                                <h4 class="text-primary">{{ $report['total_fees_earned_formatted'] }}</h4>
                                <small class="text-muted">@lang('Delivery fees earned')</small>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="account-info-box text-center">
                                <h6>@lang('Total Deliveries')</h6>
                                <h4>{{ $report['deliveries_count'] }}</h4>
                                <small class="text-muted">{{ $report['deliveries_completed'] }} @lang('completed')</small>
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
                                        <span class="info-name">@lang('COD Deliveries')</span>
                                        <span class="badge bg-warning">{{ $report['cod_deliveries'] }}</span>
                                    </div>
                                    <div class="account-info-item d-flex justify-content-between">
                                        <span class="info-name">@lang('Online Payment Deliveries')</span>
                                        <span class="badge bg-info">{{ $report['online_deliveries'] }}</span>
                                    </div>
                                    <div class="account-info-item d-flex justify-content-between">
                                        <span class="info-name">@lang('Pending Deliveries')</span>
                                        <span class="badge bg-secondary">{{ $report['deliveries_pending'] }}</span>
                                    </div>
                                    <div class="account-info-item d-flex justify-content-between">
                                        <span class="info-name">@lang('Unsettled Deliveries')</span>
                                        <span class="badge bg-danger">{{ $report['unsettled_deliveries'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="account-info-box">
                                <h5>@lang('Account Information')</h5>
                                <div class="account-info">
                                    <div class="account-info-item">
                                        <span class="info-name">@lang('Name:') </span>
                                        <span class="info-content">{{ $user['name'] }}</span>
                                    </div>
                                    <div class="account-info-item">
                                        <span class="info-name">@lang('Email:') </span>
                                        <span class="info-content">{{ $user['email'] }}</span>
                                    </div>
                                    <div class="account-info-item">
                                        <span class="info-name">@lang('Phone:') </span>
                                        <span class="info-content">{{ $user['phone'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- recent purchases -->
                    <h4 class="table-name mt-4">@lang('Recent Purchases')</h4>
                    <div class="user-table recent-orders-table table-responsive wow-replaced" data-wow-delay=".1s">
                        <table class="table table-bordered">
                            <tr>
                                <th><span class="header-name">{{ __('#Purchase') }}</span></th>
                                <th><span class="header-name">{{ __('Service Area') }}</span></th>
                                <th><span class="header-name">{{ __('Merchant Location') }}</span></th>
                                <th><span class="header-name">{{ __('Purchase Total') }}</span></th>
                                <th><span class="header-name">{{ __('Purchase Status') }}</span></th>
                                <th><span class="header-name">{{ __('View') }}</span></th>
                            </tr>
                            @forelse ($purchases as $purchase)
                            <tr>
                                <td data-label="{{ __('#Purchase') }}">
                                    {{ $purchase['purchase_number'] }}
                                </td>
                                <td data-label="{{ __('Service Area') }}">
                                    <p>{{ $purchase['customer_city'] }}</p>
                                </td>
                                <td data-label="{{ __('Warehouse Location') }}">
                                    <p>{{ $purchase['branch_location'] }}</p>
                                </td>
                                <td data-label="{{ __('Purchase Total') }}">
                                    {{ $purchase['total_formatted'] }}
                                    @if($purchase['is_cod'])
                                        <br><span class="badge bg-warning text-dark">COD</span>
                                    @endif
                                </td>
                                <td data-label="{{ __('Purchase Status') }}">
                                    <span class="badge {{ $purchase['status']['class'] }}">
                                        <i class="fas {{ $purchase['status']['icon'] }}"></i>
                                        {{ $purchase['status']['label'] }}
                                    </span>
                                </td>
                                <td data-label="{{ __('View') }}">
                                    <a href="{{ $purchase['details_url'] }}" class="template-btn sm-btn blue-btn">
                                        @lang('View')
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <p class="text-muted mb-0">@lang('No deliveries yet')</p>
                                </td>
                            </tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- user dashboard wrapper end -->
@endsection
