@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Detailed Commission Report') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator-commission-income') }}">{{ __('Commission Report') }}</a></li>
                    <li><a href="javascript:;">{{ __('Detailed') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Summary Cards --}}
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center">
                    <h6 class="mb-2">{{ __('Total Commission') }}</h6>
                    <h3 class="mb-0">{{ $total_commission }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center">
                    <h6 class="mb-2">{{ __('Total Sales') }}</h6>
                    <h3 class="mb-0">{{ $total_sales }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card bg-secondary text-white h-100">
                <div class="card-body text-center">
                    <h6 class="mb-2">{{ __('Avg. Commission Rate') }}</h6>
                    <h3 class="mb-0">{{ $avg_commission_rate }}%</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Date Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('operator-commission-detailed') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('From Date') }}</label>
                        <input type="text" class="form-control discount_date" name="start_date"
                               placeholder="{{ __('From Date') }}" value="{{ $start_date }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('To Date') }}</label>
                        <input type="text" class="form-control discount_date" name="end_date"
                               placeholder="{{ __('To Date') }}" value="{{ $end_date }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                        <a href="{{ route('operator-commission-detailed') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Per Merchant Summary --}}
    @if(!empty($merchantSummary) && count($merchantSummary) > 0)
    <div class="card mb-4">
        <div class="card-header">
            <strong>{{ __('Merchant Summary') }}</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Merchant') }}</th>
                            <th class="text-end">{{ __('Sales') }}</th>
                            <th class="text-end">{{ __('Commission') }}</th>
                            <th class="text-end">{{ __('Rate') }}</th>
                            <th class="text-end">{{ __('Orders') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($merchantSummary as $merchant)
                        <tr>
                            <td>{{ $merchant['merchant_name'] }}</td>
                            <td class="text-end">{{ $currency->formatAmount($merchant['total_sales']) }}</td>
                            <td class="text-end text-success">{{ $currency->formatAmount($merchant['total_commission']) }}</td>
                            <td class="text-end">{{ $merchant['avg_commission_rate'] }}%</td>
                            <td class="text-end">{{ $merchant['orders_count'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- All Transactions --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>{{ __('All Transactions') }}</strong>
            <span class="badge bg-primary">{{ $purchases->count() }} {{ __('Transactions') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="detailedTable">
                    <thead>
                        <tr>
                            <th>{{ __('Purchase #') }}</th>
                            <th>{{ __('Merchant') }}</th>
                            <th class="text-end">{{ __('Sale') }}</th>
                            <th class="text-end">{{ __('Commission') }}</th>
                            <th class="text-end">{{ __('Tax') }}</th>
                            <th class="text-end">{{ __('Net') }}</th>
                            <th>{{ __('Payment') }}</th>
                            <th>{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                        <tr>
                            <td><code>{{ $purchase->purchase_number }}</code></td>
                            <td>{{ $purchase->user?->shop_name ?? $purchase->user?->name ?? '-' }}</td>
                            <td class="text-end">{{ $currency->formatAmount($purchase->price) }}</td>
                            <td class="text-end text-success">{{ $currency->formatAmount($purchase->commission_amount) }}</td>
                            <td class="text-end">{{ $currency->formatAmount($purchase->tax_amount) }}</td>
                            <td class="text-end">{{ $currency->formatAmount($purchase->net_amount) }}</td>
                            <td>
                                @if($purchase->payment_owner_id === 0)
                                    <span class="badge bg-primary">{{ __('Platform') }}</span>
                                @else
                                    <span class="badge bg-warning">{{ __('Merchant') }}</span>
                                @endif
                            </td>
                            <td>{{ $purchase->created_at->format('d-m-Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">{{ __('No transactions found') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script type="text/javascript">
    $(".discount_date").datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'yy-mm-dd'
    });

    if ($.fn.DataTable) {
        $('#detailedTable').DataTable({
            order: [[7, 'desc']],
            pageLength: 50
        });
    }
</script>
@endsection
