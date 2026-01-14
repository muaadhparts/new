@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Merchants Summary Report') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.accounts.index') }}">{{ __('Accounts') }}</a></li>
                    <li><a href="javascript:;">{{ __('Merchants Summary') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('operator.accounts.reports.merchants-summary') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">{{ __('From Date') }}</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('To Date') }}</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                    <a href="{{ route('operator.accounts.reports.merchants-summary') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Totals Summary --}}
    @php
        $totalSales = $merchants->sum('total_sales');
        $totalCommission = $merchants->sum('total_commission');
        $totalTax = $merchants->sum('total_tax');
        $totalBalanceDue = $merchants->sum('balance_due');
    @endphp

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Total Sales') }}</h6>
                    <h3 class="mb-0">{{ $currency->sign }}{{ number_format($totalSales, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Total Commission') }}</h6>
                    <h3 class="mb-0">{{ $currency->sign }}{{ number_format($totalCommission, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Total Tax') }}</h6>
                    <h3 class="mb-0">{{ $currency->sign }}{{ number_format($totalTax, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Total Balance Due') }}</h6>
                    <h3 class="mb-0">{{ $currency->sign }}{{ number_format($totalBalanceDue, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Merchants Table --}}
    <div class="card">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-store me-2"></i>{{ __('All Merchants') }}</strong>
            <span class="badge bg-light text-dark">{{ $merchants->count() }} {{ __('Merchants') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Merchant') }}</th>
                            <th class="text-end">{{ __('Sales') }}</th>
                            <th class="text-end">{{ __('Commission') }}</th>
                            <th class="text-end">{{ __('Tax') }}</th>
                            <th class="text-end">{{ __('Net Receivable') }}</th>
                            <th class="text-end">{{ __('Settlements') }}</th>
                            <th class="text-end">{{ __('Balance Due') }}</th>
                            <th class="text-center">{{ __('Transactions') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($merchants as $data)
                        <tr>
                            <td>
                                <strong>{{ $data['merchant']->name }}</strong>
                                <br><small class="text-muted">{{ $data['merchant']->code }}</small>
                            </td>
                            <td class="text-end text-success fw-bold">{{ $currency->sign }}{{ number_format($data['total_sales'], 2) }}</td>
                            <td class="text-end text-primary">{{ $currency->sign }}{{ number_format($data['total_commission'], 2) }}</td>
                            <td class="text-end text-info">{{ $currency->sign }}{{ number_format($data['total_tax'], 2) }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($data['net_receivable'], 2) }}</td>
                            <td class="text-end text-muted">{{ $currency->sign }}{{ number_format($data['settlements_received'], 2) }}</td>
                            <td class="text-end {{ $data['balance_due'] > 0 ? 'text-warning fw-bold' : 'text-success' }}">
                                {{ $currency->sign }}{{ number_format($data['balance_due'], 2) }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $data['transaction_count'] }}</span>
                            </td>
                            <td>
                                <a href="{{ route('operator.accounts.merchant-statement', $data['merchant']->reference_id) }}"
                                   class="btn btn-sm btn-outline-primary" title="{{ __('View Statement') }}">
                                    <i class="fas fa-file-invoice"></i>
                                </a>
                                @if($data['balance_due'] > 0)
                                <a href="{{ route('operator.accounts.settlements.create', ['party_id' => $data['merchant']->id]) }}"
                                   class="btn btn-sm btn-outline-success" title="{{ __('Create Settlement') }}">
                                    <i class="fas fa-money-bill"></i>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                {{ __('No merchants found') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($merchants->count() > 0)
                    <tfoot class="table-dark">
                        <tr class="fw-bold">
                            <th>{{ __('Total') }}</th>
                            <th class="text-end">{{ $currency->sign }}{{ number_format($totalSales, 2) }}</th>
                            <th class="text-end">{{ $currency->sign }}{{ number_format($totalCommission, 2) }}</th>
                            <th class="text-end">{{ $currency->sign }}{{ number_format($totalTax, 2) }}</th>
                            <th class="text-end">{{ $currency->sign }}{{ number_format($totalSales - $totalCommission - $totalTax, 2) }}</th>
                            <th class="text-end">{{ $currency->sign }}{{ number_format($merchants->sum('settlements_received'), 2) }}</th>
                            <th class="text-end">{{ $currency->sign }}{{ number_format($totalBalanceDue, 2) }}</th>
                            <th class="text-center">{{ $merchants->sum('transaction_count') }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Back Button --}}
    <div class="mt-3">
        <a href="{{ route('operator.accounts.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> {{ __('Back to Accounts') }}
        </a>
    </div>
</div>
@endsection
