@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Merchant Account Statement') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.accounts.index') }}">{{ __('Accounts') }}</a></li>
                    <li><a href="javascript:;">{{ __('Merchant Statement') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Merchant Info --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-store me-2"></i>
                <strong>{{ $statement->merchant->name }}</strong>
                <small class="ms-2">({{ $statement->merchant->code }})</small>
            </div>
            <span class="badge bg-light text-dark">{{ $statement->getPeriodLabel() }}</span>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('operator.accounts.merchant-statement', $statement->merchant->reference_id) }}" method="GET" class="row g-3 align-items-end">
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
                    <a href="{{ route('operator.accounts.merchant-statement', $statement->merchant->reference_id) }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                    <button type="button" class="btn btn-outline-success" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> {{ __('Print') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Total Sales') }}</h6>
                    <h3 class="mb-0">{{ $statementDisplay['total_sales_formatted'] }}</h3>
                    <small>{{ __('From Ledger') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Deductions') }}</h6>
                    <h3 class="mb-0">{{ $statementDisplay['deductions_formatted'] }}</h3>
                    <small>{{ __('Commission') }}: {{ $statementDisplay['total_commission_formatted'] }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Net Receivable') }}</h6>
                    <h3 class="mb-0">{{ $statementDisplay['net_receivable_formatted'] }}</h3>
                    <small>{{ __('After deductions') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card {{ $statementDisplay['balance_due'] > 0 ? 'bg-warning' : 'bg-secondary' }} text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Balance Due') }}</h6>
                    <h3 class="mb-0">{{ $statementDisplay['balance_due_formatted'] }}</h3>
                    <small>{{ __('Settlements') }}: {{ $statementDisplay['settlements_received_formatted'] }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Pending Amounts --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-clock me-2"></i>{{ __('Current Pending Amounts') }}
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded text-center">
                                <h6 class="text-muted">{{ __('Receivable from Platform') }}</h6>
                                <h4 class="text-success">{{ $pendingDisplay['from_platform_formatted'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded text-center">
                                <h6 class="text-muted">{{ __('Payable to Platform') }}</h6>
                                <h4 class="text-danger">{{ $pendingDisplay['to_platform_formatted'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 {{ $pendingDisplay['net_receivable'] >= 0 ? 'bg-success' : 'bg-danger' }} text-white rounded text-center">
                                <h6>{{ __('Net Receivable') }}</h6>
                                <h4 class="mb-0">{{ $pendingDisplay['net_receivable_formatted'] }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Account Statement Table --}}
    <div class="card">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-list me-2"></i>{{ __('Account Statement') }}</strong>
            <span class="badge bg-light text-dark">{{ $statement->summary['transaction_count'] }} {{ __('Transactions') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Reference') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th class="text-end">{{ __('Debit') }}</th>
                            <th class="text-end">{{ __('Credit') }}</th>
                            <th class="text-end">{{ __('Balance') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Opening Balance --}}
                        <tr class="table-secondary">
                            <td colspan="4"><strong>{{ __('Opening Balance') }}</strong></td>
                            <td></td>
                            <td></td>
                            <td class="text-end fw-bold">{{ $statementDisplay['opening_balance_formatted'] }}</td>
                            <td></td>
                        </tr>

                        @foreach($statementDisplay['entries'] as $entry)
                        <tr>
                            <td>{{ $entry['date'] }}</td>
                            <td>
                                <small class="text-muted">{{ $entry['ref'] }}</small>
                                @if($entry['merchant_purchase_id'])
                                    <br><a href="{{ route('operator-purchase-show', $entry['purchase_id']) }}" class="small">
                                        #{{ $entry['purchase_id'] }}
                                    </a>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $entry['entry_type_ar'] }}</span>
                            </td>
                            <td>{{ $entry['description_ar'] ?: $entry['description'] }}</td>
                            <td class="text-end {{ $entry['debit'] > 0 ? 'text-danger' : '' }}">
                                {{ $entry['debit_formatted'] }}
                            </td>
                            <td class="text-end {{ $entry['credit'] > 0 ? 'text-success' : '' }}">
                                {{ $entry['credit_formatted'] }}
                            </td>
                            <td class="text-end fw-bold">{{ $entry['balance_formatted'] }}</td>
                            <td>
                                <span class="badge bg-{{ $entry['debt_status'] === 'SETTLED' ? 'success' : ($entry['debt_status'] === 'PENDING' ? 'warning' : 'secondary') }}">
                                    {{ $entry['debt_status_ar'] }}
                                </span>
                            </td>
                        </tr>
                        @endforeach

                        {{-- Closing Balance --}}
                        <tr class="table-dark">
                            <td colspan="4"><strong>{{ __('Closing Balance') }}</strong></td>
                            <td></td>
                            <td></td>
                            <td class="text-end fw-bold">{{ $statementDisplay['closing_balance_formatted'] }}</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Summary Box --}}
    <div class="card mt-4">
        <div class="card-header">
            <i class="fas fa-calculator me-2"></i>{{ __('Period Summary') }}
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <td>{{ __('Total Sales') }}</td>
                            <td class="text-end text-success fw-bold">{{ $statementDisplay['total_sales_formatted'] }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Shipping Earned') }}</td>
                            <td class="text-end text-success">{{ $statementDisplay['shipping_earned_formatted'] }}</td>
                        </tr>
                        <tr class="table-success">
                            <th>{{ __('Total Credits') }}</th>
                            <th class="text-end">{{ $statementDisplay['total_credits_formatted'] }}</th>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <td>{{ __('Commission') }}</td>
                            <td class="text-end text-danger">{{ $statementDisplay['total_commission_formatted'] }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Tax') }}</td>
                            <td class="text-end text-danger">{{ $statementDisplay['total_tax_formatted'] }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('Settlements Received') }}</td>
                            <td class="text-end text-danger">{{ $statementDisplay['settlements_received_formatted'] }}</td>
                        </tr>
                        <tr class="table-danger">
                            <th>{{ __('Total Debits') }}</th>
                            <th class="text-end">{{ $statementDisplay['total_debits_formatted'] }}</th>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Back Button --}}
    <div class="mt-3">
        <a href="{{ route('operator.accounts.merchants') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> {{ __('Back to Merchants') }}
        </a>
        <a href="{{ route('operator.accounts.settlements.create', ['party_id' => $statement->merchant->id]) }}" class="btn btn-success">
            <i class="fas fa-money-bill me-1"></i> {{ __('Create Settlement') }}
        </a>
    </div>
</div>

@push('styles')
<style>
@media print {
    .mr-breadcrumb, form, .btn, .links { display: none !important; }
    .card { border: 1px solid #ddd !important; }
}
</style>
@endpush
@endsection
