@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Payables Report') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.accounts.index') }}">{{ __('Accounts') }}</a></li>
                    <li><a href="javascript:;">{{ __('Payables') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Summary Card --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Total Payable') }}</h6>
                    <h2>{{ $currency->formatAmount($total) }}</h2>
                    <small>{{ __('Amount platform owes others') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Number of Parties') }}</h6>
                    <h2>{{ $payables->count() }}</h2>
                    <small>{{ __('Parties owed by platform') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Average Balance') }}</h6>
                    <h2>{{ $payables->count() > 0 ? $currency->formatAmount($total / $payables->count()) : $currency->formatAmount(0) }}</h2>
                    <small>{{ __('Per party') }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Payables Table --}}
    <div class="card">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-arrow-up me-2"></i>{{ __('Platform Payables') }}</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Party') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th class="text-end">{{ __('Total Amount') }}</th>
                            <th class="text-end">{{ __('Pending') }}</th>
                            <th class="text-end">{{ __('Settled') }}</th>
                            <th class="text-center">{{ __('Transactions') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payables as $balance)
                        <tr>
                            <td>
                                <i class="{{ $balance->counterparty->getIcon() }} me-1"></i>
                                <strong>{{ $balance->counterparty->name }}</strong>
                                <br><small class="text-muted">{{ $balance->counterparty->code }}</small>
                            </td>
                            <td>{{ $balance->counterparty->getTypeNameAr() }}</td>
                            <td class="text-end">{{ $currency->formatAmount($balance->total_amount) }}</td>
                            <td class="text-end text-danger fw-bold">{{ $currency->formatAmount($balance->pending_amount) }}</td>
                            <td class="text-end text-muted">{{ $currency->formatAmount($balance->settled_amount) }}</td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $balance->transaction_count }}</span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('operator.accounts.party.statement', $balance->counterparty) }}" class="btn btn-outline-primary">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                    <a href="{{ route('operator.accounts.settlements.create', ['party_id' => $balance->counterparty->id]) }}" class="btn btn-outline-success">
                                        <i class="fas fa-money-check"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                {{ __('No pending payables') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($payables->count() > 0)
                    <tfoot class="table-danger">
                        <tr class="fw-bold">
                            <td colspan="3" class="text-end">{{ __('Total') }}:</td>
                            <td class="text-end">{{ $currency->formatAmount($total) }}</td>
                            <td colspan="3"></td>
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
