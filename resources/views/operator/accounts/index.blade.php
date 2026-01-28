@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Accounting Ledger') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="javascript:;">{{ __('Accounts') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Sync Parties Button --}}
    <div class="mb-3 text-end">
        <form action="{{ route('operator.accounts.sync') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-primary">
                <i class="fas fa-sync me-1"></i> {{ __('Sync Parties') }}
            </button>
        </form>
    </div>

    {{-- Summary Cards by Party Type --}}
    <div class="row mb-4">
        {{-- Merchants --}}
        <div class="col-md-3">
            <div class="card border-primary h-100">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-store me-2"></i>{{ __('Merchants') }}
                </div>
                <div class="card-body">
                    <h5 class="text-muted">{{ $summary['merchants']['count'] }} {{ __('Merchant') }}</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('Receivable') }}:</span>
                        <span class="text-success fw-bold">{{ $currency->formatAmount($summary['merchants']['receivable']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('Payable') }}:</span>
                        <span class="text-danger fw-bold">{{ $currency->formatAmount($summary['merchants']['payable']) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>{{ __('Net') }}:</span>
                        <span class="fw-bold {{ $summary['merchants']['net'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $currency->formatAmount(abs($summary['merchants']['net'])) }}
                        </span>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="{{ route('operator.accounts.merchants') }}" class="btn btn-sm btn-primary w-100">
                        {{ __('View Details') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Couriers --}}
        <div class="col-md-3">
            <div class="card border-info h-100">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-motorcycle me-2"></i>{{ __('Couriers') }}
                </div>
                <div class="card-body">
                    <h5 class="text-muted">{{ $summary['couriers']['count'] }} {{ __('Courier') }}</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('Receivable') }}:</span>
                        <span class="text-success fw-bold">{{ $currency->formatAmount($summary['couriers']['receivable']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('Payable') }}:</span>
                        <span class="text-danger fw-bold">{{ $currency->formatAmount($summary['couriers']['payable']) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>{{ __('Net') }}:</span>
                        <span class="fw-bold {{ $summary['couriers']['net'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $currency->formatAmount(abs($summary['couriers']['net'])) }}
                        </span>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="{{ route('operator.accounts.couriers') }}" class="btn btn-sm btn-info w-100">
                        {{ __('View Details') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Shipping Providers --}}
        <div class="col-md-3">
            <div class="card border-warning h-100">
                <div class="card-header bg-warning text-dark">
                    <i class="fas fa-truck me-2"></i>{{ __('Shipping Companies') }}
                </div>
                <div class="card-body">
                    <h5 class="text-muted">{{ $summary['shipping']['count'] }} {{ __('Company') }}</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('Receivable') }}:</span>
                        <span class="text-success fw-bold">{{ $currency->formatAmount($summary['shipping']['receivable']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('Payable') }}:</span>
                        <span class="text-danger fw-bold">{{ $currency->formatAmount($summary['shipping']['payable']) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>{{ __('Net') }}:</span>
                        <span class="fw-bold {{ $summary['shipping']['net'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $currency->formatAmount(abs($summary['shipping']['net'])) }}
                        </span>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="{{ route('operator.accounts.shipping') }}" class="btn btn-sm btn-warning w-100">
                        {{ __('View Details') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Payment Providers --}}
        <div class="col-md-3">
            <div class="card border-success h-100">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-credit-card me-2"></i>{{ __('Payment Companies') }}
                </div>
                <div class="card-body">
                    <h5 class="text-muted">{{ $summary['payment']['count'] }} {{ __('Company') }}</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('Receivable') }}:</span>
                        <span class="text-success fw-bold">{{ $currency->formatAmount($summary['payment']['receivable']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('Payable') }}:</span>
                        <span class="text-danger fw-bold">{{ $currency->formatAmount($summary['payment']['payable']) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>{{ __('Net') }}:</span>
                        <span class="fw-bold {{ $summary['payment']['net'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $currency->formatAmount(abs($summary['payment']['net'])) }}
                        </span>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="{{ route('operator.accounts.payment') }}" class="btn btn-sm btn-success w-100">
                        {{ __('View Details') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Platform Net Position --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <strong><i class="fas fa-building me-2"></i>{{ __('Platform Net Position') }}</strong>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h6 class="text-muted">{{ __('Total Receivable') }}</h6>
                            <h3 class="text-success">
                                {{ $currency->formatAmount($dashboard['platform_summary']['total_receivable'] ?? 0) }}
                            </h3>
                            <small class="text-muted">{{ __('Amount others owe platform') }}</small>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">{{ __('Total Payable') }}</h6>
                            <h3 class="text-danger">
                                {{ $currency->formatAmount($dashboard['platform_summary']['total_payable'] ?? 0) }}
                            </h3>
                            <small class="text-muted">{{ __('Amount platform owes others') }}</small>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">{{ __('Net Balance') }}</h6>
                            {{-- Net balance pre-computed in Controller --}}
                            <h3 class="{{ $netBalance >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $currency->formatAmount(abs($netBalance)) }}
                                @if($netBalance >= 0)
                                    <small class="text-success">({{ __('Credit') }})</small>
                                @else
                                    <small class="text-danger">({{ __('Debit') }})</small>
                                @endif
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <strong>{{ __('Quick Actions') }}</strong>
                </div>
                <div class="card-body">
                    <a href="{{ route('operator.accounts.settlements.create') }}" class="btn btn-primary me-2">
                        <i class="fas fa-plus me-1"></i> {{ __('New Settlement') }}
                    </a>
                    <a href="{{ route('operator.accounts.settlements') }}" class="btn btn-outline-primary me-2">
                        <i class="fas fa-list me-1"></i> {{ __('All Settlements') }}
                    </a>
                    <a href="{{ route('operator.accounts.shipping-companies') }}" class="btn btn-outline-warning me-2">
                        <i class="fas fa-truck me-1"></i> {{ __('Shipping Companies') }}
                    </a>
                    <div class="btn-group me-2">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-bar me-1"></i> {{ __('Reports') }}
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('operator.accounts.reports.platform') }}"><i class="fas fa-chart-line me-1"></i> {{ __('Platform Revenue') }}</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('operator.accounts.reports.receivables') }}">{{ __('Receivables Report') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('operator.accounts.reports.payables') }}">{{ __('Payables Report') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('operator.accounts.reports.receivables-payables') }}">{{ __('Receivables/Payables') }}</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('operator.accounts.reports.merchants-summary') }}">{{ __('Merchants Summary') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('operator.accounts.reports.couriers') }}">{{ __('Couriers Report') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('operator.accounts.reports.shipping-companies') }}">{{ __('Shipping Report') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('operator.accounts.reports.tax') }}">{{ __('Tax Report') }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Transactions --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>{{ __('Recent Transactions') }}</strong>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Reference') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('From') }}</th>
                                    <th>{{ __('To') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th class="text-end">{{ __('Amount') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $txn)
                                <tr>
                                    <td>
                                        <a href="{{ route('operator.accounts.transaction', $txn) }}">
                                            {{ $txn->transaction_ref }}
                                        </a>
                                    </td>
                                    <td>{{ $txn->transaction_date->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <i class="{{ $txn->fromParty->getIcon() }} me-1"></i>
                                        {{ $txn->fromParty->name }}
                                    </td>
                                    <td>
                                        <i class="{{ $txn->toParty->getIcon() }} me-1"></i>
                                        {{ $txn->toParty->name }}
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $txn->getTypeNameAr() }}</span>
                                    </td>
                                    <td class="text-end fw-bold">
                                        {{ $currency->formatAmount($txn->amount) }}
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $txn->getStatusColor() }}">{{ $txn->getStatusNameAr() }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        {{ __('No transactions yet') }}
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
</div>
@endsection
