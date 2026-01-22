@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Shipping Companies Financial Report') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.accounts.index') }}">{{ __('Accounts') }}</a></li>
                    <li><a href="javascript:;">{{ __('Shipping Companies') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('operator.accounts.reports.shipping-companies') }}" method="GET" class="row g-3 align-items-end">
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
                    <a href="{{ route('operator.accounts.reports.shipping-companies') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Totals Summary --}}
    @php
        $totalFeesEarned = $companies->sum('fees_earned');
        $totalCodCollected = $companies->sum('cod_collected');
        $totalReceivable = $companies->sum('receivable_from_platform');
        $totalPayable = $companies->sum('payable_to_platform');
    @endphp

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Shipping Fees') }}</h6>
                    <h3 class="mb-0">{{ $currency->sign }}{{ number_format($totalFeesEarned, 2) }}</h3>
                    <small>{{ __('Earned by providers') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h6>{{ __('COD Collected') }}</h6>
                    <h3 class="mb-0">{{ $currency->sign }}{{ number_format($totalCodCollected, 2) }}</h3>
                    <small>{{ __('By shipping companies') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Receivable from Platform') }}</h6>
                    <h3 class="mb-0">{{ $currency->sign }}{{ number_format($totalReceivable, 2) }}</h3>
                    <small>{{ __('Platform owes them') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Payable to Platform') }}</h6>
                    <h3 class="mb-0">{{ $currency->sign }}{{ number_format($totalPayable, 2) }}</h3>
                    <small>{{ __('They owe platform') }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Companies Table --}}
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-truck me-2"></i>{{ __('All Shipping Companies') }}</strong>
            <span class="badge bg-light text-dark">{{ $companies->count() }} {{ __('Companies') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Company') }}</th>
                            <th class="text-end">{{ __('Fees Earned') }}</th>
                            <th class="text-end">{{ __('COD Collected') }}</th>
                            <th class="text-end">{{ __('Receivable') }}</th>
                            <th class="text-end">{{ __('Payable') }}</th>
                            <th class="text-end">{{ __('Net Balance') }}</th>
                            <th class="text-center">{{ __('Shipments') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companies as $data)
                        <tr>
                            <td>
                                <strong>{{ $data['company']->name }}</strong>
                                <br><small class="text-muted">{{ $data['company']->code }}</small>
                            </td>
                            <td class="text-end text-primary fw-bold">{{ $currency->sign }}{{ number_format($data['fees_earned'], 2) }}</td>
                            <td class="text-end text-info">{{ $currency->sign }}{{ number_format($data['cod_collected'], 2) }}</td>
                            <td class="text-end text-success">{{ $currency->sign }}{{ number_format($data['receivable_from_platform'], 2) }}</td>
                            <td class="text-end text-danger">{{ $currency->sign }}{{ number_format($data['payable_to_platform'], 2) }}</td>
                            <td class="text-end {{ $data['net_balance'] >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                                {{ $currency->sign }}{{ number_format($data['net_balance'], 2) }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $data['shipment_count'] }}</span>
                            </td>
                            <td>
                                <a href="{{ route('operator.accounts.shipping-company.statement', $data['company']->code ?? $data['company']->id) }}"
                                   class="btn btn-sm btn-outline-primary" title="{{ __('View Statement') }}">
                                    <i class="fas fa-file-invoice"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                {{ __('No shipping companies found') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($companies->count() > 0)
                    <tfoot class="table-dark">
                        <tr class="fw-bold">
                            <th>{{ __('Total') }}</th>
                            <th class="text-end">{{ $currency->sign }}{{ number_format($totalFeesEarned, 2) }}</th>
                            <th class="text-end">{{ $currency->sign }}{{ number_format($totalCodCollected, 2) }}</th>
                            <th class="text-end">{{ $currency->sign }}{{ number_format($totalReceivable, 2) }}</th>
                            <th class="text-end">{{ $currency->sign }}{{ number_format($totalPayable, 2) }}</th>
                            <th class="text-end">{{ $currency->sign }}{{ number_format($totalReceivable - $totalPayable, 2) }}</th>
                            <th class="text-center">{{ $companies->sum('shipment_count') }}</th>
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
