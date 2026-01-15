{{-- Shared Party List Template --}}
{{-- Required variables: $parties, $partyType, $currency, $name, $icon, $headerColor --}}

<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ $name }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.accounts.index') }}">{{ __('Accounts') }}</a></li>
                    <li><a href="javascript:;">{{ $name }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Search & Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Search') }}</label>
                    <input type="text" name="search" class="form-control" placeholder="{{ __('Name or Code') }}" value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="has_balance" value="1" id="hasBalance" {{ request('has_balance') ? 'checked' : '' }}>
                        <label class="form-check-label" for="hasBalance">
                            {{ __('With Pending Balance Only') }}
                        </label>
                    </div>
                </div>
                <div class="col-md-5 text-end">
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                    <a href="{{ url()->current() }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Parties Table --}}
    <div class="card">
        <div class="card-header bg-{{ $headerColor }} text-white d-flex justify-content-between align-items-center">
            <strong><i class="{{ $icon }} me-2"></i>{{ $name }}</strong>
            <span class="badge bg-light text-dark">{{ $parties->total() }} {{ __('Party') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th class="text-center">{{ __('Transactions') }}</th>
                            <th class="text-end">{{ __('Receivable') }}</th>
                            <th class="text-end">{{ __('Payable') }}</th>
                            <th class="text-end">{{ __('Net Balance') }}</th>
                            <th class="text-center">{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($parties as $party)
                        <tr>
                            <td>
                                <code>{{ $party->code }}</code>
                            </td>
                            <td>
                                <strong>{{ $party->name }}</strong>
                                @if($party->email)
                                    <br><small class="text-muted">{{ $party->email }}</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $party->transactions_count }}</span>
                            </td>
                            <td class="text-end">
                                <span class="text-success fw-bold">
                                    {{ $currency->sign }}{{ number_format($party->summary['total_receivable'] ?? 0, 2) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="text-danger fw-bold">
                                    {{ $currency->sign }}{{ number_format($party->summary['total_payable'] ?? 0, 2) }}
                                </span>
                            </td>
                            <td class="text-end">
                                @php
                                    $netBalance = ($party->summary['total_receivable'] ?? 0) - ($party->summary['total_payable'] ?? 0);
                                @endphp
                                <span class="fw-bold {{ $netBalance >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $currency->sign }}{{ number_format(abs($netBalance), 2) }}
                                    @if($netBalance >= 0)
                                        <i class="fas fa-arrow-up text-success"></i>
                                    @else
                                        <i class="fas fa-arrow-down text-danger"></i>
                                    @endif
                                </span>
                            </td>
                            <td class="text-center">
                                @if($party->is_active)
                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('operator.accounts.party.statement', $party) }}" class="btn btn-outline-primary" name="{{ __('Statement') }}">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                    @if(($party->summary['total_payable'] ?? 0) > 0)
                                        <a href="{{ route('operator.accounts.settlements.create', ['party_id' => $party->id]) }}" class="btn btn-outline-success" name="{{ __('Create Settlement') }}">
                                            <i class="fas fa-money-check"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                {{ __('No parties found') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($parties->hasPages())
        <div class="card-footer">
            {{ $parties->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
