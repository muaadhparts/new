@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Platform Financial Report') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.accounts.index') }}">{{ __('Accounts') }}</a></li>
                    <li><a href="javascript:;">{{ __('Platform Report') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('operator.accounts.reports.platform') }}" method="GET" class="row g-3 align-items-end">
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
                    <a href="{{ route('operator.accounts.reports.platform') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Revenue Section - الدخل الحقيقي للمنصة --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-coins me-2"></i>{{ __('Platform Revenue') }}
                    <small class="d-block">{{ __('This is the ONLY income that belongs to the platform') }}</small>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="text-center p-4 bg-light rounded">
                                <h6 class="text-muted">{{ __('Commission Earned') }}</h6>
                                <h2 class="text-success mb-0">{{ $currency->sign }}{{ number_format($report['revenue']['commission_earned'], 2) }}</h2>
                                <small class="text-muted">{{ __('From merchant sales') }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center p-4 bg-success text-white rounded">
                                <h6>{{ __('Total Platform Revenue') }}</h6>
                                <h2 class="mb-0">{{ $currency->sign }}{{ number_format($report['revenue']['total'], 2) }}</h2>
                                <small>{{ __('Commission is the only platform income') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Collections Section - المبالغ المحصلة (ليست دخل) --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-hand-holding-usd me-2"></i>{{ __('Collections (Pass-through Money)') }}
                    <small class="d-block">{{ __('Money collected on behalf of others - NOT platform income') }}</small>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <h6 class="text-muted">{{ __('Total Collected') }}</h6>
                                <h4 class="text-info">{{ $currency->sign }}{{ number_format($report['collections']['total_collected'], 2) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <h6 class="text-muted">{{ __('For Merchants') }}</h6>
                                <h4>{{ $currency->sign }}{{ number_format($report['collections']['for_merchants'], 2) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <h6 class="text-muted">{{ __('For Tax Authority') }}</h6>
                                <h4>{{ $currency->sign }}{{ number_format($report['collections']['for_tax_authority'], 2) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <h6 class="text-muted">{{ __('For Shipping') }}</h6>
                                <h4>{{ $currency->sign }}{{ number_format($report['collections']['for_shipping_companies'], 2) }}</h4>
                            </div>
                        </div>
                    </div>
                    @if($report['collections']['cod_collected'] > 0)
                    <div class="mt-3 p-3 bg-light rounded">
                        <strong>{{ __('COD Collected') }}:</strong> {{ $currency->sign }}{{ number_format($report['collections']['cod_collected'], 2) }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        {{-- Liabilities Section - الالتزامات --}}
        <div class="col-md-6">
            <div class="card border-danger h-100">
                <div class="card-header bg-danger text-white">
                    <i class="fas fa-file-invoice-dollar me-2"></i>{{ __('Liabilities (What Platform Owes)') }}
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>{{ __('To Merchants') }}</td>
                            <td class="text-end text-danger fw-bold">{{ $currency->sign }}{{ number_format($report['liabilities']['to_merchants'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('To Tax Authority') }}</td>
                            <td class="text-end text-danger fw-bold">{{ $currency->sign }}{{ number_format($report['liabilities']['to_tax_authority'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('To Shipping Companies') }}</td>
                            <td class="text-end text-danger fw-bold">{{ $currency->sign }}{{ number_format($report['liabilities']['to_shipping_companies'], 2) }}</td>
                        </tr>
                        <tr class="table-danger">
                            <th>{{ __('Total Liabilities') }}</th>
                            <th class="text-end">{{ $currency->sign }}{{ number_format($report['liabilities']['total'], 2) }}</th>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Receivables Section - المستحقات --}}
        <div class="col-md-6">
            <div class="card border-primary h-100">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-hand-holding-usd me-2"></i>{{ __('Receivables (Owed to Platform)') }}
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>{{ __('From Couriers') }}</td>
                            <td class="text-end text-primary fw-bold">{{ $currency->sign }}{{ number_format($report['receivables']['from_couriers'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('From Shipping Companies') }}</td>
                            <td class="text-end text-primary fw-bold">{{ $currency->sign }}{{ number_format($report['receivables']['from_shipping_companies'], 2) }}</td>
                        </tr>
                        <tr class="table-primary">
                            <th>{{ __('Total Receivables') }}</th>
                            <th class="text-end">{{ $currency->sign }}{{ number_format($report['receivables']['total'], 2) }}</th>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Net Position --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card {{ $report['net_position'] >= 0 ? 'border-success' : 'border-danger' }}">
                <div class="card-body text-center">
                    <h5 class="text-muted mb-3">{{ __('Net Financial Position') }}</h5>
                    <h1 class="{{ $report['net_position'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $currency->sign }}{{ number_format($report['net_position'], 2) }}
                    </h1>
                    <p class="text-muted mb-0">
                        {{ __('Revenue + Receivables - Liabilities') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-link me-2"></i>{{ __('Detailed Reports') }}
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="{{ route('operator.accounts.reports.merchants-summary') }}" class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-store me-1"></i> {{ __('Merchants Summary') }}
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('operator.accounts.reports.tax') }}" class="btn btn-outline-info w-100 mb-2">
                                <i class="fas fa-receipt me-1"></i> {{ __('Tax Report') }}
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('operator.accounts.reports.couriers') }}" class="btn btn-outline-warning w-100 mb-2">
                                <i class="fas fa-motorcycle me-1"></i> {{ __('Couriers Report') }}
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('operator.accounts.reports.receivables-payables') }}" class="btn btn-outline-danger w-100 mb-2">
                                <i class="fas fa-balance-scale me-1"></i> {{ __('Receivables/Payables') }}
                            </a>
                        </div>
                    </div>
                </div>
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
