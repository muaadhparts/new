@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Account Statement') }}: {{ $party->name }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.accounts.index') }}">{{ __('Accounts') }}</a></li>
                    <li><a href="javascript:;">{{ $party->name }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Party Info Card --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="{{ $party->getIcon() }} me-2"></i>{{ __('Party Information') }}
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="text-muted">{{ __('Code') }}:</td>
                            <td><code>{{ $party->code }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Type') }}:</td>
                            <td>{{ $party->getTypeNameAr() }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Name') }}:</td>
                            <td><strong>{{ $party->name }}</strong></td>
                        </tr>
                        @if($party->email)
                        <tr>
                            <td class="text-muted">{{ __('Email') }}:</td>
                            <td>{{ $party->email }}</td>
                        </tr>
                        @endif
                        @if($party->phone)
                        <tr>
                            <td class="text-muted">{{ __('Phone') }}:</td>
                            <td>{{ $party->phone }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-muted">{{ __('Status') }}:</td>
                            <td>
                                @if($party->is_active)
                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-calculator me-2"></i>{{ __('Balance Summary') }}
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h6 class="text-muted">{{ __('Total Receivable') }}</h6>
                            <h3 class="text-success">{{ $currency->sign }}{{ number_format($summary['total_receivable'], 2) }}</h3>
                            <small class="text-muted">{{ __('Others owe this party') }}</small>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">{{ __('Total Payable') }}</h6>
                            <h3 class="text-danger">{{ $currency->sign }}{{ number_format($summary['total_payable'], 2) }}</h3>
                            <small class="text-muted">{{ __('This party owes others') }}</small>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">{{ __('Net Balance') }}</h6>
                            <h3 class="{{ $summary['is_net_positive'] ? 'text-success' : 'text-danger' }}">
                                {{ $currency->sign }}{{ number_format(abs($summary['net_balance']), 2) }}
                            </h3>
                            <small class="{{ $summary['is_net_positive'] ? 'text-success' : 'text-danger' }}">
                                {{ $summary['is_net_positive'] ? __('Credit Balance') : __('Debit Balance') }}
                            </small>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    @if($summary['total_payable'] > 0)
                        <a href="{{ route('operator.accounts.settlements.create', ['party_id' => $party->id]) }}" class="btn btn-success">
                            <i class="fas fa-money-check me-1"></i> {{ __('Create Settlement') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('operator.accounts.party.statement', $party) }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">{{ __('Counterparty') }}</label>
                    <select name="counterparty_id" class="form-select">
                        <option value="">{{ __('All Parties') }}</option>
                        @foreach($counterparties->groupBy('party_type') as $type => $group)
                            <optgroup label="{{ $group->first()->getTypeNameAr() }}">
                                @foreach($group as $cp)
                                    <option value="{{ $cp->id }}" {{ $selectedCounterparty && $selectedCounterparty->id == $cp->id ? 'selected' : '' }}>
                                        {{ $cp->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('From Date') }}</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('To Date') }}</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                    <a href="{{ route('operator.accounts.party.statement', $party) }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Statement --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>
                <i class="fas fa-file-invoice me-2"></i>{{ __('Account Statement') }}
                @if($selectedCounterparty)
                    - {{ $selectedCounterparty->name }}
                @endif
            </strong>
            <span class="badge bg-primary">{{ count($statement['statement']) }} {{ __('Transaction') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Reference') }}</th>
                            <th>{{ __('Counterparty') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th class="text-end">{{ __('Debit') }}</th>
                            <th class="text-end">{{ __('Credit') }}</th>
                            <th class="text-end">{{ __('Balance') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($statement['statement'] as $row)
                        @php $txn = $row['transaction']; $isDebit = $row['is_debit']; $isCredit = $row['is_credit']; $counterparty = $isDebit ? $txn->toParty : $txn->fromParty; @endphp
                        <tr>
                            <td>{{ $txn->transaction_date->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route('operator.accounts.transaction', $txn) }}">
                                    {{ $txn->transaction_ref }}
                                </a>
                            </td>
                            <td>
                                <i class="{{ $counterparty->getIcon() }} me-1"></i>
                                {{ $counterparty->name }}
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $txn->getTypeNameAr() }}</span>
                            </td>
                            <td>
                                <small>{{ $txn->description_ar ?: $txn->description }}</small>
                            </td>
                            <td class="text-end">
                                @if($isDebit)
                                    <span class="text-danger">{{ $currency->sign }}{{ number_format($txn->amount, 2) }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($isCredit)
                                    <span class="text-success">{{ $currency->sign }}{{ number_format($txn->amount, 2) }}</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold">
                                {{ $currency->sign }}{{ number_format($row['running_balance'], 2) }}
                            </td>
                            <td>
                                <span class="badge bg-{{ $txn->getStatusColor() }}">{{ $txn->getStatusNameAr() }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                {{ __('No transactions found') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(count($statement['statement']) > 0)
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="5" class="text-end">{{ __('Totals') }}:</td>
                            <td class="text-end text-danger">{{ $currency->sign }}{{ number_format($statement['total_debits'], 2) }}</td>
                            <td class="text-end text-success">{{ $currency->sign }}{{ number_format($statement['total_credits'], 2) }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($statement['closing_balance'], 2) }}</td>
                            <td></td>
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
