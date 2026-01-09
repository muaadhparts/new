@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">
                    {{ __('Platform Revenue Report') }}
                    <a class="add-btn" href="{{ route('operator.settlement.index') }}">
                        <i class="fas fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </h4>
            </div>
        </div>
    </div>

    {{-- Period Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('operator.settlement.revenue-report') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">{{ __('From Date') }}</label>
                    <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('To Date') }}</label>
                    <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">{{ __('Generate Report') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Revenue Summary --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4>{{ $currency->sign }}{{ number_format($summary['sales']['total_sales'], 2) }}</h4>
                    <p class="mb-0">{{ __('Total Sales') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4>{{ $currency->sign }}{{ number_format($summary['platform_revenue']['commission'], 2) }}</h4>
                    <p class="mb-0">{{ __('Commission Revenue') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4>{{ $currency->sign }}{{ number_format($summary['platform_revenue']['tax'], 2) }}</h4>
                    <p class="mb-0">{{ __('Tax Collected') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark text-white">
                <div class="card-body text-center">
                    <h4>{{ $currency->sign }}{{ number_format($summary['platform_revenue']['total'], 2) }}</h4>
                    <p class="mb-0">{{ __('Total Platform Revenue') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Detailed Breakdown --}}
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <strong>{{ __('Sales Breakdown') }}</strong>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td>{{ __('Total Sales') }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($summary['sales']['total_sales'], 2) }}</td>
                        </tr>
                        <tr class="text-success">
                            <td>{{ __('Platform Commission') }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($summary['sales']['total_commission'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Tax Collected') }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($summary['sales']['total_tax'], 2) }}</td>
                        </tr>
                        <tr class="table-light">
                            <td><strong>{{ __('Net to Merchants') }}</strong></td>
                            <td class="text-end"><strong>{{ $currency->sign }}{{ number_format($summary['sales']['net_to_merchants'], 2) }}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <strong>{{ __('Courier Operations') }}</strong>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td>{{ __('Total COD Collected') }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($summary['courier']['total_cod_collected'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Courier Fees Paid') }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($summary['courier']['total_fees'], 2) }}</td>
                        </tr>
                        <tr class="table-light">
                            <td><strong>{{ __('Net Courier Balance') }}</strong></td>
                            <td class="text-end">
                                @if($summary['courier']['net_courier_balance'] > 0)
                                    <strong class="text-success">{{ $currency->sign }}{{ number_format($summary['courier']['net_courier_balance'], 2) }}</strong>
                                    <small class="d-block text-muted">{{ __('Couriers owe platform') }}</small>
                                @else
                                    <strong class="text-danger">{{ $currency->sign }}{{ number_format(abs($summary['courier']['net_courier_balance']), 2) }}</strong>
                                    <small class="d-block text-muted">{{ __('Platform owes couriers') }}</small>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Pending Settlements --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <strong>{{ __('Pending Merchant Payouts') }}</strong>
                </div>
                <div class="card-body text-center">
                    <h3>{{ $currency->sign }}{{ number_format($summary['pending_settlements']['merchant_payable'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <strong>{{ __('Pending Courier Settlements') }}</strong>
                </div>
                <div class="card-body text-center">
                    <h3>{{ $currency->sign }}{{ number_format($summary['pending_settlements']['courier_payable'], 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Settlements --}}
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <strong>{{ __('Recent Merchant Settlements') }}</strong>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Merchant') }}</th>
                                    <th class="text-end">{{ __('Amount') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($history['merchant_settlements']->take(5) as $settlement)
                                <tr>
                                    <td>{{ $settlement->merchant->shop_name ?? $settlement->merchant->name ?? 'N/A' }}</td>
                                    <td class="text-end">{{ $currency->sign }}{{ number_format($settlement->net_payable, 2) }}</td>
                                    <td><span class="badge {{ $settlement->getStatusBadgeClass() }}">{{ $settlement->getStatusLabel() }}</span></td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted">{{ __('No settlements') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <strong>{{ __('Recent Courier Settlements') }}</strong>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Courier') }}</th>
                                    <th class="text-end">{{ __('Amount') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($history['courier_settlements']->take(5) as $settlement)
                                <tr>
                                    <td>{{ $settlement->courier->name ?? 'N/A' }}</td>
                                    <td class="text-end">{{ $currency->sign }}{{ number_format($settlement->amount, 2) }}</td>
                                    <td><span class="badge {{ $settlement->isCompleted() ? 'bg-success' : ($settlement->isPending() ? 'bg-warning' : 'bg-danger') }}">{{ $settlement->getStatusLabel() }}</span></td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted">{{ __('No settlements') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
