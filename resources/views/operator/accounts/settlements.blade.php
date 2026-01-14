@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Settlement Transactions') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.accounts.index') }}">{{ __('Accounts') }}</a></li>
                    <li><a href="javascript:;">{{ __('Settlements') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Actions --}}
    <div class="mb-3">
        <a href="{{ route('operator.accounts.settlements.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> {{ __('New Settlement') }}
        </a>
    </div>

    {{-- Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('operator.accounts.settlements') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Status') }}</label>
                    <select name="status" class="form-select">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>{{ __('Completed') }}</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
                    </select>
                </div>
                <div class="col-md-4 text-end">
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                    <a href="{{ route('operator.accounts.settlements') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Settlements Table --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-money-check me-2"></i>{{ __('Settlement Transactions') }}</strong>
            <span class="badge bg-primary">{{ $settlements->total() }} {{ __('Settlement') }}</span>
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
                            <th class="text-end">{{ __('Amount') }}</th>
                            <th>{{ __('Payment Method') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($settlements as $batch)
                        <tr>
                            <td>
                                <a href="{{ route('operator.accounts.settlements.show', $batch) }}">
                                    <code>{{ $batch->batch_ref }}</code>
                                </a>
                            </td>
                            <td>{{ $batch->created_at->format('Y-m-d') }}</td>
                            <td>
                                <i class="{{ $batch->fromParty->getIcon() }} me-1"></i>
                                {{ $batch->fromParty->name }}
                            </td>
                            <td>
                                <i class="{{ $batch->toParty->getIcon() }} me-1"></i>
                                {{ $batch->toParty->name }}
                            </td>
                            <td class="text-end fw-bold">
                                {{ $currency->sign }}{{ number_format($batch->total_amount, 2) }}
                            </td>
                            <td>{{ $batch->getPaymentMethodNameAr() }}</td>
                            <td>
                                <span class="badge bg-{{ $batch->getStatusColor() }}">{{ $batch->getStatusNameAr() }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('operator.accounts.settlements.show', $batch) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                {{ __('No settlements found') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($settlements->hasPages())
        <div class="card-footer">
            {{ $settlements->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
