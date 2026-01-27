@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Payment Providers Report') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.accounts.index') }}">{{ __('Accounts') }}</a></li>
                    <li><a href="javascript:;">{{ __('Payment Report') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Summary - values pre-computed in AccountLedgerController (DATA_FLOW_POLICY) --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Providers') }}</h6>
                    <h2>{{ count($report) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Total Receivable') }}</h6>
                    <h2>{{ $currency->sign }}{{ number_format($totalReceivable, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Total Payable') }}</h6>
                    <h2>{{ $currency->sign }}{{ number_format($totalPayable, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card {{ $netBalance >= 0 ? 'bg-success' : 'bg-danger' }} text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Net Balance') }}</h6>
                    <h2>{{ $currency->sign }}{{ number_format(abs($netBalance), 2) }}</h2>
                    <small>{{ $netBalance >= 0 ? __('Credit') : __('Debit') }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Providers Table --}}
    <div class="card">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-credit-card me-2"></i>{{ __('Payment Providers') }}</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Provider') }}</th>
                            <th>{{ __('Code') }}</th>
                            <th class="text-end">{{ __('Receivable') }}</th>
                            <th class="text-end">{{ __('Payable') }}</th>
                            <th class="text-end">{{ __('Net Balance') }}</th>
                            <th class="text-center">{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report as $item)
                        {{-- Direct array access - no @php extraction (DATA_FLOW_POLICY) --}}
                        <tr>
                            <td>
                                <i class="fas fa-credit-card me-1"></i>
                                <strong>{{ $item['party']->name }}</strong>
                            </td>
                            <td><code>{{ $item['party']->code }}</code></td>
                            <td class="text-end text-success fw-bold">
                                {{ $currency->sign }}{{ number_format($item['summary']['total_receivable'], 2) }}
                            </td>
                            <td class="text-end text-danger fw-bold">
                                {{ $currency->sign }}{{ number_format($item['summary']['total_payable'], 2) }}
                            </td>
                            <td class="text-end">
                                <span class="fw-bold {{ $item['summary']['is_net_positive'] ? 'text-success' : 'text-danger' }}">
                                    {{ $currency->sign }}{{ number_format(abs($item['summary']['net_balance']), 2) }}
                                    @if($item['summary']['is_net_positive'])
                                        <i class="fas fa-arrow-up"></i>
                                    @else
                                        <i class="fas fa-arrow-down"></i>
                                    @endif
                                </span>
                            </td>
                            <td class="text-center">
                                @if($item['party']->is_active)
                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('operator.accounts.party.statement', $item['party']) }}" class="btn btn-outline-primary" name="{{ __('Statement') }}">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                    @if($item['summary']['total_payable'] > 0)
                                    <a href="{{ route('operator.accounts.settlements.create', ['party_id' => $item['party']->id]) }}" class="btn btn-outline-success" name="{{ __('Settle') }}">
                                        <i class="fas fa-money-check"></i>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                {{ __('No payment providers found') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
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
