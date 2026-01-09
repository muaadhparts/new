@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Settlement Dashboard') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="javascript:;">{{ __('Settlements') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Period Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('operator.settlement.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">{{ __('From Date') }}</label>
                    <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('To Date') }}</label>
                    <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                    <a href="{{ route('operator.settlement.index') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Platform Summary Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>{{ __('Total Sales') }}</h6>
                    <h3>{{ $currency->sign }}{{ number_format($platformSummary['sales']['total_sales'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>{{ __('Platform Commission') }}</h6>
                    <h3>{{ $currency->sign }}{{ number_format($platformSummary['platform_revenue']['commission'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>{{ __('Tax Collected') }}</h6>
                    <h3>{{ $currency->sign }}{{ number_format($platformSummary['platform_revenue']['tax'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6>{{ __('COD Collected') }}</h6>
                    <h3>{{ $currency->sign }}{{ number_format($platformSummary['courier']['total_cod_collected'], 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment Flow Breakdown --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <strong>{{ __('Platform Owes Merchants') }}</strong>
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="card-body text-center">
                    <h2 class="text-success">{{ $currency->sign }}{{ number_format($platformSummary['payment_breakdown']['platform_owes_merchants'] ?? 0, 2) }}</h2>
                    <small class="text-muted">{{ $platformSummary['payment_breakdown']['platform_payments'] ?? 0 }} {{ __('orders via platform payment') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                    <strong>{{ __('Merchants Owe Platform') }}</strong>
                    <i class="fas fa-arrow-left"></i>
                </div>
                <div class="card-body text-center">
                    <h2 class="text-warning">{{ $currency->sign }}{{ number_format($platformSummary['payment_breakdown']['merchants_owe_platform'] ?? 0, 2) }}</h2>
                    <small class="text-muted">{{ $platformSummary['payment_breakdown']['merchant_payments'] ?? 0 }} {{ __('orders via merchant payment') }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Pending Settlements Summary --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <strong>{{ __('Pending Merchant Settlements') }}</strong>
                </div>
                <div class="card-body text-center">
                    <h2>{{ $currency->sign }}{{ number_format($platformSummary['pending_settlements']['merchant_payable'], 2) }}</h2>
                    <a href="{{ route('operator.settlement.merchants') }}" class="btn btn-outline-primary">{{ __('Manage Settlements') }}</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <strong>{{ __('Pending Courier Settlements') }}</strong>
                </div>
                <div class="card-body text-center">
                    <h2>{{ $currency->sign }}{{ number_format($platformSummary['pending_settlements']['courier_payable'], 2) }}</h2>
                    <a href="{{ route('operator.settlement.couriers') }}" class="btn btn-outline-info">{{ __('Manage Settlements') }}</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Merchants with Unsettled Balances --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>{{ __('Merchants Awaiting Settlement') }}</strong>
                    <span class="badge bg-primary">{{ $merchantsWithBalances->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Merchant') }}</th>
                                    <th class="text-center">{{ __('Orders') }}</th>
                                    <th class="text-center">{{ __('Direction') }}</th>
                                    <th class="text-end">{{ __('Amount') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($merchantsWithBalances as $merchant)
                                <tr>
                                    <td>{{ $merchant['merchant_name'] }}</td>
                                    <td class="text-center">{{ $merchant['orders_count'] }}</td>
                                    <td class="text-center">
                                        @if($merchant['settlement_direction'] === 'platform_to_merchant')
                                            <span class="badge bg-success" title="{{ __('Platform owes merchant') }}">
                                                <i class="fas fa-arrow-right"></i>
                                            </span>
                                        @else
                                            <span class="badge bg-warning" title="{{ __('Merchant owes platform') }}">
                                                <i class="fas fa-arrow-left"></i>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <span class="{{ $merchant['settlement_direction'] === 'platform_to_merchant' ? 'text-success' : 'text-warning' }}">
                                            {{ $currency->sign }}{{ number_format($merchant['net_balance'], 2) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('operator.settlement.merchant.preview', ['merchant_id' => $merchant['merchant_id']]) }}"
                                           class="btn btn-sm {{ $merchant['settlement_direction'] === 'platform_to_merchant' ? 'btn-success' : 'btn-warning' }}">
                                            {{ __('Settle') }}
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        {{ __('No pending merchant settlements') }}
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Couriers with Unsettled Balances --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>{{ __('Couriers Awaiting Settlement') }}</strong>
                    <span class="badge bg-info">{{ $couriersWithBalances->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Courier') }}</th>
                                    <th class="text-center">{{ __('Deliveries') }}</th>
                                    <th class="text-end">{{ __('Balance') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($couriersWithBalances as $courier)
                                <tr>
                                    <td>{{ $courier['courier_name'] }}</td>
                                    <td class="text-center">{{ $courier['deliveries_count'] }}</td>
                                    <td class="text-end">
                                        @if($courier['settlement_type'] === 'pay_to_courier')
                                            <span class="text-success">+{{ $currency->sign }}{{ number_format($courier['net_balance'], 2) }}</span>
                                            <small class="d-block text-muted">{{ __('Platform owes courier') }}</small>
                                        @else
                                            <span class="text-danger">{{ $currency->sign }}{{ number_format($courier['net_balance'], 2) }}</span>
                                            <small class="d-block text-muted">{{ __('Courier owes platform') }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('operator.settlement.courier.preview', ['courier_id' => $courier['courier_id']]) }}"
                                           class="btn btn-sm btn-info">
                                            {{ __('Create Settlement') }}
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        {{ __('No pending courier settlements') }}
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <strong>{{ __('Quick Actions') }}</strong>
                </div>
                <div class="card-body">
                    <a href="{{ route('operator.settlement.merchants') }}" class="btn btn-outline-primary me-2">
                        <i class="fas fa-store me-1"></i> {{ __('All Merchant Settlements') }}
                    </a>
                    <a href="{{ route('operator.settlement.couriers') }}" class="btn btn-outline-info me-2">
                        <i class="fas fa-motorcycle me-1"></i> {{ __('All Courier Settlements') }}
                    </a>
                    <a href="{{ route('operator.settlement.revenue-report') }}" class="btn btn-outline-success">
                        <i class="fas fa-chart-line me-1"></i> {{ __('Revenue Report') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
