@extends('layouts.merchant')

@section('content')
<div class="gs-merchant-outlet">
    {{-- Breadcrumb --}}
    <div class="gs-merchant-breadcrumb has-mb">
        <h4 class="text-capitalize">@lang('Account Statement')</h4>
        <ul class="breadcrumb-menu">
            <li><a href="{{ route('merchant.dashboard') }}">@lang('Dashboard')</a></li>
            <li><a href="{{ route('merchant.income') }}">@lang('Financial Dashboard')</a></li>
            <li><a href="#">@lang('Account Statement')</a></li>
        </ul>
    </div>

    <div class="gs-merchant-erning">
        {{-- Date Filter --}}
        <div class="card mb-4">
            <div class="card-body">
                <form action="{{ route('merchant.statement') }}" method="GET">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">@lang('From Date')</label>
                            <input type="date" class="form-control" name="start_date" value="{{ $start_date }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">@lang('To Date')</label>
                            <input type="date" class="form-control" name="end_date" value="{{ $end_date }}">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">@lang('Filter')</button>
                            <a href="{{ route('merchant.statement') }}" class="btn btn-secondary">@lang('Reset')</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6>@lang('Total Credit')</h6>
                        <h3>{{ $total_credit }}</h3>
                        <small>@lang('Platform owes you')</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h6>@lang('Total Debit')</h6>
                        <h3>{{ $total_debit }}</h3>
                        <small>@lang('You owe platform')</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card {{ $closing_balance >= 0 ? 'bg-info' : 'bg-warning' }} text-white">
                    <div class="card-body text-center">
                        <h6>@lang('Closing Balance')</h6>
                        <h3>{{ $currencySign }}{{ number_format(abs($closing_balance), 2) }}</h3>
                        @if($closing_balance >= 0)
                            <small>@lang('Platform owes you')</small>
                        @else
                            <small>@lang('You owe platform')</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Opening Balance --}}
        <div class="card mb-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <strong>@lang('Opening Balance')</strong>
                <span class="{{ $opening_balance >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $currencySign }}{{ number_format(abs($opening_balance), 2) }}
                    @if($opening_balance >= 0)
                        <small class="text-muted">(@lang('Platform owed you'))</small>
                    @else
                        <small class="text-muted">(@lang('You owed platform'))</small>
                    @endif
                </span>
            </div>
        </div>

        {{-- Statement Table --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">@lang('Account Statement')</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="statementTable">
                        <thead>
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
                            @forelse($statement as $entry)
                            <tr>
                                <td>{{ $entry['date']->format('d-m-Y') }}</td>
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
                                    {{-- payment_owner = 'merchant' means payment_owner_id > 0 = ALWAYS PAID --}}
                                    {{-- payment_owner = 'platform' means payment_owner_id = 0 = depends on settlement_status --}}
                                    @if($entry['payment_owner'] === 'merchant')
                                        {{-- Merchant received payment directly - ALWAYS PAID --}}
                                        <span class="badge bg-success">@lang('Paid')</span>
                                    @else
                                        {{-- Platform received payment - check settlement_status --}}
                                        @if($entry['settlement_status'] === 'settled')
                                            <span class="badge bg-success">@lang('Settled')</span>
                                        @else
                                            <span class="badge bg-warning">@lang('Pending')</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-end text-success">
                                    @if($entry['credit'] > 0)
                                        {{ $currencySign }}{{ number_format($entry['credit'], 2) }}
                                    @endif
                                </td>
                                <td class="text-end text-danger">
                                    @if($entry['debit'] > 0)
                                        {{ $currencySign }}{{ number_format($entry['debit'], 2) }}
                                    @endif
                                </td>
                                <td class="text-end {{ $entry['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    <strong>{{ $currencySign }}{{ number_format(abs($entry['balance']), 2) }}</strong>
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
                                    @lang('No transactions found')
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
                                    <strong>{{ $currencySign }}{{ number_format(abs($closing_balance), 2) }}</strong>
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

        {{-- Legend --}}
        <div class="card mt-4">
            <div class="card-body">
                <h6>@lang('Understanding Your Statement')</h6>
                <div class="row">
                    <div class="col-md-4">
                        <p class="text-success mb-1"><i class="fas fa-plus-circle"></i> <strong>@lang('Credit (CR)')</strong></p>
                        <ul class="small text-muted">
                            <li>@lang('Net earnings from your sales after commission')</li>
                            <li>@lang('Amount platform owes you')</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <p class="text-danger mb-1"><i class="fas fa-minus-circle"></i> <strong>@lang('Debit (DR)')</strong></p>
                        <ul class="small text-muted">
                            <li>@lang('Commission owed when you received payment directly')</li>
                            <li>@lang('Amount you owe to platform')</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <p class="text-info mb-1"><i class="fas fa-info-circle"></i> <strong>@lang('Status')</strong></p>
                        <ul class="small text-muted">
                            <li><span class="badge bg-success">@lang('Paid')</span> @lang('You received payment directly')</li>
                            <li><span class="badge bg-success">@lang('Settled')</span> @lang('Platform paid you')</li>
                            <li><span class="badge bg-warning">@lang('Pending')</span> @lang('Awaiting settlement')</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script type="text/javascript">
    if ($.fn.DataTable) {
        $('#statementTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 25,
            ordering: false,
            language: {
                lengthMenu: "@lang('Show _MENU_ entries')",
                info: "@lang('Showing _START_ to _END_ of _TOTAL_ entries')",
                infoEmpty: "@lang('Showing 0 to 0 of 0 entries')",
                infoFiltered: "@lang('(filtered from _MAX_ total entries)')",
                zeroRecords: "@lang('No matching records found')",
                search: "@lang('Search:')",
                paginate: {
                    first: "@lang('First')",
                    last: "@lang('Last')",
                    next: "@lang('Next')",
                    previous: "@lang('Previous')"
                },
                emptyTable: "@lang('No data available in table')",
                loadingRecords: "@lang('Loading...')",
                processing: "@lang('Processing...')"
            }
        });
    }
</script>
@endsection
