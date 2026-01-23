@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Tax Report') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="javascript:;">{{ __('Tax Report') }}</a></li>
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
                    <h6 class="mb-2">{{ __('Total Tax Collected') }}</h6>
                    <h3 class="mb-0">{{ $total }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body text-center">
                    <h6 class="mb-2">{{ __('Current Month') }}</h6>
                    <h3 class="mb-0">{{ $current_month }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center">
                    <h6 class="mb-2">{{ __('Last 30 Days') }}</h6>
                    <h3 class="mb-0">{{ $last_30_days }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Tax by Payment Method --}}
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card border-success h-100">
                <div class="card-header bg-success text-white">
                    <strong>{{ __('Tax from Platform Payments') }}</strong>
                </div>
                <div class="card-body text-center">
                    <h3>{{ $tax_from_platform_payments }}</h3>
                    <small class="text-muted">{{ __('Tax collected via platform payment gateways') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card border-warning h-100">
                <div class="card-header bg-warning text-dark">
                    <strong>{{ __('Tax from Merchant Payments') }}</strong>
                </div>
                <div class="card-body text-center">
                    <h3>{{ $tax_from_merchant_payments }}</h3>
                    <small class="text-muted">{{ __('Tax collected via merchant payment gateways') }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Date Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('operator-tax-calculate-income') }}" method="GET">
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
                        <a href="{{ route('operator-tax-calculate-income') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Per Merchant Breakdown --}}
    @if(!empty($by_merchant) && count($by_merchant) > 0)
    <div class="card mb-4">
        <div class="card-header">
            <strong>{{ __('Tax by Merchant') }}</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Merchant') }}</th>
                            <th class="text-end">{{ __('Total Sales') }}</th>
                            <th class="text-end">{{ __('Tax Collected') }}</th>
                            <th class="text-end">{{ __('Orders') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($by_merchant as $merchant)
                        <tr>
                            <td>{{ $merchant['merchant_name'] }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($merchant['total_sales'], 2) }}</td>
                            <td class="text-end text-success">{{ $currency->sign }}{{ number_format($merchant['total_tax'], 2) }}</td>
                            <td class="text-end">{{ $merchant['orders_count'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Detailed Transactions --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>{{ __('Tax Transactions') }}</strong>
            <span class="badge bg-primary">{{ $purchases->count() }} {{ __('Transactions') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="taxTable">
                    <thead>
                        <tr>
                            <th>{{ __('Purchase #') }}</th>
                            <th>{{ __('Merchant') }}</th>
                            <th class="text-end">{{ __('Sale Amount') }}</th>
                            <th class="text-end">{{ __('Tax Amount') }}</th>
                            <th>{{ __('Payment') }}</th>
                            <th>{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                        <tr>
                            <td><code>{{ $purchase->purchase_number }}</code></td>
                            <td>{{ $purchase->user?->shop_name ?? $purchase->user?->name ?? '-' }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($purchase->price, 2) }}</td>
                            <td class="text-end text-success">{{ $currency->sign }}{{ number_format($purchase->tax_amount, 2) }}</td>
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
                            <td colspan="6" class="text-center py-4 text-muted">{{ __('No tax transactions found') }}</td>
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
        $('#taxTable').DataTable({
            order: [[5, 'desc']],
            pageLength: 25
        });
    }
</script>
@endsection
