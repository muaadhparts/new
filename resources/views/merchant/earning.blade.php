@extends('layouts.merchant')

@section('content')
<div class="gs-merchant-outlet">
    {{-- Breadcrumb --}}
    <div class="gs-merchant-breadcrumb has-mb">
        <h4 class="text-capitalize">@lang('Financial Dashboard')</h4>
        <ul class="breadcrumb-menu">
            <li><a href="{{ route('merchant.dashboard') }}">@lang('Dashboard')</a></li>
            <li><a href="#">@lang('Financial Dashboard')</a></li>
        </ul>
    </div>

    <div class="gs-merchant-erning">
        {{-- Summary Cards --}}
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="mb-2">@lang('Total Sales')</h6>
                        <h3 class="mb-0">{{ $total_sales }}</h3>
                        <small>{{ $total_orders }} @lang('orders')</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-danger text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="mb-2">@lang('Platform Deductions')</h6>
                        <h3 class="mb-0">{{ $total_commission }}</h3>
                        <small>@lang('Commission')</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-success text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="mb-2">@lang('Net Earnings')</h6>
                        <h3 class="mb-0">{{ $total_net }}</h3>
                        <small>@lang('After deductions')</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card {{ $net_balance >= 0 ? 'bg-info' : 'bg-warning' }} text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="mb-2">@lang('Current Balance')</h6>
                        <h3 class="mb-0">{{ $currencySign }}{{ number_format(abs($net_balance), 2) }}</h3>
                        @if($net_balance >= 0)
                            <small>@lang('Platform owes you')</small>
                        @else
                            <small>@lang('You owe platform')</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Settlement Balance Cards --}}
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card border-success">
                    <div class="card-header bg-success text-white d-flex justify-content-between">
                        <strong>@lang('Platform Owes You')</strong>
                        <span class="badge bg-light text-success">{{ $platform_payments['count'] }} @lang('orders')</span>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <h4 class="text-success mb-1">{{ $currencySign }}{{ number_format($platform_owes_merchant, 2) }}</h4>
                                <small class="text-muted">@lang('Amount to receive')</small>
                            </div>
                            <div class="col-6">
                                <h4 class="mb-1">{{ $currencySign }}{{ number_format($platform_payments['total'], 2) }}</h4>
                                <small class="text-muted">@lang('Total from platform payments')</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark d-flex justify-content-between">
                        <strong>@lang('You Owe Platform')</strong>
                        <span class="badge bg-dark">{{ $merchant_payments['count'] }} @lang('orders')</span>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <h4 class="text-danger mb-1">{{ $currencySign }}{{ number_format($merchant_owes_platform, 2) }}</h4>
                                <small class="text-muted">@lang('Amount to pay')</small>
                            </div>
                            <div class="col-6">
                                <h4 class="mb-1">{{ $currencySign }}{{ number_format($merchant_payments['total'], 2) }}</h4>
                                <small class="text-muted">@lang('Total from your payments')</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Financial Breakdown --}}
        <div class="row mb-4">
            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">@lang('Sales Breakdown')</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td>@lang('Gross Sales')</td>
                                <td class="text-end"><strong>{{ $total_sales }}</strong></td>
                            </tr>
                            <tr class="text-danger">
                                <td>@lang('Platform Commission')</td>
                                <td class="text-end">-{{ $total_commission }}</td>
                            </tr>
                            <tr class="text-muted">
                                <td>@lang('Tax Collected')</td>
                                <td class="text-end">{{ $total_tax }}</td>
                            </tr>
                            @if($report['total_platform_shipping_fee'] > 0)
                            <tr class="text-muted">
                                <td>@lang('Platform Shipping Fee')</td>
                                <td class="text-end">-{{ $total_platform_shipping_fee }}</td>
                            </tr>
                            @endif
                            <tr class="border-top">
                                <td><strong>@lang('Net Earnings')</strong></td>
                                <td class="text-end text-success"><strong>{{ $total_net }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">@lang('Delivery Statistics')</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td><i class="fas fa-motorcycle me-2"></i>@lang('Courier Deliveries')</td>
                                <td class="text-end">
                                    <span class="badge bg-warning">{{ $courier_deliveries['count'] }}</span>
                                    <span class="ms-2">{{ $total_courier_fee }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-truck me-2"></i>@lang('Platform Shipping')</td>
                                <td class="text-end">
                                    <span class="badge bg-primary">{{ $platform_shipping['count'] }}</span>
                                    <span class="ms-2">{{ $currencySign }}{{ number_format($platform_shipping['cost'], 2) }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-store me-2"></i>@lang('Your Shipping')</td>
                                <td class="text-end">
                                    <span class="badge bg-success">{{ $merchant_shipping['count'] }}</span>
                                    <span class="ms-2">{{ $currencySign }}{{ number_format($merchant_shipping['cost'], 2) }}</span>
                                </td>
                            </tr>
                            <tr class="border-top">
                                <td><strong>@lang('Total Shipping')</strong></td>
                                <td class="text-end"><strong>{{ $total_shipping_cost }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Date Filter --}}
        <div class="card mb-4">
            <div class="card-body">
                <form action="{{ route('merchant.income') }}" method="GET">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">@lang('From Date')</label>
                            <input type="text" class="form-control discount_date" name="start_date"
                                   placeholder="@lang('From Date')" value="{{ $start_date }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">@lang('To Date')</label>
                            <input type="text" class="form-control discount_date" name="end_date"
                                   placeholder="@lang('To Date')" value="{{ $end_date }}">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">@lang('Filter')</button>
                            <button type="button" id="reset" class="btn btn-secondary">@lang('Reset')</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Transactions Table --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">@lang('Transactions')</h5>
                <span class="badge bg-primary">{{ count($purchases) }} @lang('transactions')</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="earningsTable">
                        <thead>
                            <tr>
                                <th>@lang('Purchase #')</th>
                                <th class="text-end">@lang('Gross')</th>
                                <th class="text-end">@lang('Commission')</th>
                                <th class="text-end">@lang('Tax')</th>
                                <th class="text-end">@lang('Net')</th>
                                <th class="text-center">@lang('Payment')</th>
                                <th class="text-center">@lang('Shipping')</th>
                                <th class="text-end">@lang('Balance')</th>
                                <th>@lang('Date')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchases as $purchase)
                            <tr>
                                <td>
                                    <a href="{{ route('merchant-purchase-invoice', $purchase->purchase_number) }}">
                                        {{ $purchase->purchase_number }}
                                    </a>
                                </td>
                                <td class="text-end">{{ $currencySign }}{{ number_format($purchase->price, 2) }}</td>
                                <td class="text-end text-danger">-{{ $currencySign }}{{ number_format($purchase->commission_amount, 2) }}</td>
                                <td class="text-end">{{ $currencySign }}{{ number_format($purchase->tax_amount, 2) }}</td>
                                <td class="text-end text-success">{{ $currencySign }}{{ number_format($purchase->net_amount, 2) }}</td>
                                <td class="text-center">
                                    @if($purchase->payment_owner_id === 0)
                                        <span class="badge bg-primary" name="@lang('Platform received payment')">
                                            <i class="fas fa-building"></i>
                                        </span>
                                    @else
                                        <span class="badge bg-success" name="@lang('You received payment')">
                                            <i class="fas fa-store"></i>
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($purchase->shipping_type === 'courier')
                                        <span class="badge bg-warning" name="@lang('Courier')">
                                            <i class="fas fa-motorcycle"></i>
                                        </span>
                                    @elseif($purchase->shipping_owner_id === 0)
                                        <span class="badge bg-primary" name="@lang('Platform Shipping')">
                                            <i class="fas fa-truck"></i>
                                        </span>
                                    @else
                                        <span class="badge bg-success" name="@lang('Your Shipping')">
                                            <i class="fas fa-store"></i>
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($purchase->platform_owes_merchant > 0)
                                        <span class="text-success" name="@lang('Platform owes you')">
                                            +{{ $currencySign }}{{ number_format($purchase->platform_owes_merchant, 2) }}
                                        </span>
                                    @elseif($purchase->merchant_owes_platform > 0)
                                        <span class="text-danger" name="@lang('You owe platform')">
                                            -{{ $currencySign }}{{ number_format($purchase->merchant_owes_platform, 2) }}
                                        </span>
                                    @else
                                        <span class="text-muted">{{ $currencySign }}0.00</span>
                                    @endif
                                </td>
                                <td>{{ $purchase->created_at->format('d-m-Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    @lang('No transactions found')
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script type="text/javascript">
    $(document).on('click', '#reset', function() {
        $('.discount_date').val('');
        location.href = '{{ route('merchant.income') }}';
    });

    $(".discount_date").datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'yy-mm-dd'
    });

    if ($.fn.DataTable) {
        $('#earningsTable').DataTable({
            order: [[8, 'desc']],
            pageLength: 25,
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
