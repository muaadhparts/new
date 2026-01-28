@extends('layouts.merchant')

@section('content')
<div class="gs-merchant-outlet">
    {{-- Breadcrumb --}}
    <div class="gs-merchant-breadcrumb has-mb">
        <h4 class="text-capitalize">@lang('Monthly Ledger')</h4>
        <ul class="breadcrumb-menu">
            <li><a href="{{ route('merchant.dashboard') }}">@lang('Dashboard')</a></li>
            <li><a href="{{ route('merchant.income') }}">@lang('Financial Dashboard')</a></li>
            <li><a href="#">@lang('Monthly Ledger')</a></li>
        </ul>
    </div>

    <div class="gs-merchant-erning">
        {{-- Month Selector --}}
        <div class="card mb-4">
            <div class="card-body">
                <form action="{{ route('merchant.monthly-ledger') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">@lang('Select Month')</label>
                        <select name="month" class="form-select">
                            @foreach($months as $m)
                                <option value="{{ $m['value'] }}" {{ $current_month == $m['value'] ? 'selected' : '' }}>
                                    {{ $m['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> @lang('Show')
                        </button>
                        <a href="{{ route('merchant.monthly-ledger.pdf', ['month' => $current_month]) }}" class="btn btn-success">
                            <i class="fas fa-file-pdf"></i> @lang('Export PDF')
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Month Header --}}
        <div class="card mb-4 bg-primary text-white">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $month_label }}</h3>
                <small>@lang('Monthly Financial Ledger')</small>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h6 class="text-muted">@lang('Total Sales')</h6>
                        <h4 class="text-primary">{{ $total_sales }}</h4>
                        <small class="text-muted">{{ $total_orders }} @lang('orders')</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h6 class="text-muted">@lang('Commission')</h6>
                        <h4 class="text-danger">{{ $total_commission }}</h4>
                        <small class="text-muted">@lang('Platform fee')</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h6 class="text-muted">@lang('Tax')</h6>
                        <h4 class="text-warning">{{ $total_tax }}</h4>
                        <small class="text-muted">@lang('Collected')</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h6 class="text-muted">@lang('Net Earnings')</h6>
                        <h4 class="text-success">{{ $total_net }}</h4>
                        <small class="text-muted">@lang('Your share')</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Balance Summary --}}
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6>@lang('Opening Balance')</h6>
                        <h4 class="{{ $opening_balance >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ monetaryUnit()->format(abs($opening_balance)) }}
                            @if($opening_balance >= 0)
                                <small class="badge bg-success">CR</small>
                            @else
                                <small class="badge bg-danger">DR</small>
                            @endif
                        </h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6>@lang('Total Credit')</h6>
                        <h4>{{ $total_credit }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h6>@lang('Total Debit')</h6>
                        <h4>{{ $total_debit }}</h4>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ledger Table --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">@lang('Ledger Entries')</h5>
                <span class="badge bg-info">{{ count($statement) }} @lang('entries')</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="ledgerTable">
                        <thead class="table-light">
                            <tr>
                                <th>@lang('Date')</th>
                                <th>@lang('Description')</th>
                                <th>@lang('Reference')</th>
                                <th class="text-center">@lang('Status')</th>
                                <th class="text-end">@lang('Credit')</th>
                                <th class="text-end">@lang('Debit')</th>
                                <th class="text-end">@lang('Balance')</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Opening Balance Row --}}
                            <tr class="table-warning">
                                <td>-</td>
                                <td colspan="3"><strong>@lang('Opening Balance')</strong></td>
                                <td class="text-end">-</td>
                                <td class="text-end">-</td>
                                <td class="text-end">
                                    <strong>{{ monetaryUnit()->format(abs($opening_balance)) }}</strong>
                                    @if($opening_balance >= 0)
                                        <small class="badge bg-success">CR</small>
                                    @else
                                        <small class="badge bg-danger">DR</small>
                                    @endif
                                </td>
                            </tr>

                            @forelse($statement as $entry)
                            <tr>
                                <td>{{ $entry['date_formatted'] }}</td>
                                <td>{{ $entry['description'] }}</td>
                                <td>
                                    @if(isset($entry['purchase_number']))
                                        <a href="{{ route('merchant-purchase-invoice', $entry['purchase_number']) }}">
                                            {{ $entry['purchase_number'] }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($entry['payment_owner'] === 'merchant')
                                        <span class="badge bg-success">@lang('Paid')</span>
                                    @elseif($entry['settlement_status'] === 'settled')
                                        <span class="badge bg-success">@lang('Settled')</span>
                                    @else
                                        <span class="badge bg-warning">@lang('Pending')</span>
                                    @endif
                                </td>
                                <td class="text-end text-success">
                                    @if($entry['credit'] > 0)
                                        {{ monetaryUnit()->format($entry['credit']) }}
                                    @endif
                                </td>
                                <td class="text-end text-danger">
                                    @if($entry['debit'] > 0)
                                        {{ monetaryUnit()->format($entry['debit']) }}
                                    @endif
                                </td>
                                <td class="text-end {{ $entry['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    <strong>{{ monetaryUnit()->format(abs($entry['balance'])) }}</strong>
                                    @if($entry['balance'] >= 0)
                                        <small class="badge bg-success">CR</small>
                                    @else
                                        <small class="badge bg-danger">DR</small>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    @lang('No transactions found for this month')
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if(count($statement) > 0)
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td colspan="4" class="text-end">@lang('Closing Balance')</td>
                                <td class="text-end text-success">{{ $total_credit }}</td>
                                <td class="text-end text-danger">{{ $total_debit }}</td>
                                <td class="text-end {{ $closing_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                    <strong>{{ monetaryUnit()->format(abs($closing_balance)) }}</strong>
                                    @if($closing_balance >= 0)
                                        <small class="badge bg-success">CR</small>
                                    @else
                                        <small class="badge bg-danger">DR</small>
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script type="text/javascript">
    if ($.fn.DataTable) {
        $('#ledgerTable').DataTable({
            order: [],
            pageLength: 50,
            ordering: false,
            language: {
                lengthMenu: "@lang('Show _MENU_ entries')",
                info: "@lang('Showing _START_ to _END_ of _TOTAL_ entries')",
                search: "@lang('Search:')",
                paginate: {
                    first: "@lang('First')",
                    last: "@lang('Last')",
                    next: "@lang('Next')",
                    previous: "@lang('Previous')"
                },
                emptyTable: "@lang('No data available')"
            }
        });
    }
</script>
@endsection
