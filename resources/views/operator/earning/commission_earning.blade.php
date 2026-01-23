@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Commission Report') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="javascript:;">{{ __('Commission Report') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Summary Cards --}}
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center">
                    <h6 class="mb-2">{{ __('Total Commission') }}</h6>
                    <h3 class="mb-0">{{ $total }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body text-center">
                    <h6 class="mb-2">{{ __('Current Month') }}</h6>
                    <h3 class="mb-0">{{ $current_month }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center">
                    <h6 class="mb-2">{{ __('Last 30 Days') }}</h6>
                    <h3 class="mb-0">{{ $last_30_days }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-secondary text-white h-100">
                <div class="card-body text-center">
                    <h6 class="mb-2">{{ __('Avg. Commission Rate') }}</h6>
                    <h3 class="mb-0">{{ $avg_commission_rate }}%</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics --}}
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <strong>{{ __('Commission Overview') }}</strong>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td>{{ __('Total Sales') }}</td>
                            <td class="text-end"><strong>{{ $total_sales }}</strong></td>
                        </tr>
                        <tr>
                            <td>{{ __('Total Commission') }}</td>
                            <td class="text-end text-success"><strong>{{ $total }}</strong></td>
                        </tr>
                        <tr>
                            <td>{{ __('Average Rate') }}</td>
                            <td class="text-end"><strong>{{ $avg_commission_rate }}%</strong></td>
                        </tr>
                        <tr>
                            <td>{{ __('Total Orders') }}</td>
                            <td class="text-end"><strong>{{ $purchases->count() }}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <strong>{{ __('Commission is Platform Profit') }}</strong>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">
                        <i class="fas fa-info-circle me-2"></i>
                        {{ __('Commission is added ON TOP of merchant prices. Platform never owns items.') }}
                    </p>
                    <p class="text-muted mb-2">
                        <i class="fas fa-store me-2"></i>
                        {{ __('Merchant sale price = Customer price BEFORE commission.') }}
                    </p>
                    <p class="text-muted mb-0">
                        <i class="fas fa-percentage me-2"></i>
                        {{ __('Commission rate can be set per-merchant in merchant settings.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Date Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('operator-commission-income') }}" method="GET">
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
                        <a href="{{ route('operator-commission-income') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Per Merchant Breakdown --}}
    @if(!empty($by_merchant) && count($by_merchant) > 0)
    <div class="card mb-4">
        <div class="card-header">
            <strong>{{ __('Commission by Merchant') }}</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Merchant') }}</th>
                            <th class="text-end">{{ __('Total Sales') }}</th>
                            <th class="text-end">{{ __('Commission') }}</th>
                            <th class="text-end">{{ __('Rate') }}</th>
                            <th class="text-end">{{ __('Orders') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($by_merchant as $merchant)
                        <tr>
                            <td>{{ $merchant['merchant_name'] }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($merchant['total_sales'], 2) }}</td>
                            <td class="text-end text-success">{{ $currency->sign }}{{ number_format($merchant['total_commission'], 2) }}</td>
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

    {{-- Detailed Transactions --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>{{ __('Commission Transactions') }}</strong>
            <span class="badge bg-primary">{{ $purchases->count() }} {{ __('Transactions') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="commissionTable">
                    <thead>
                        <tr>
                            <th>{{ __('Purchase #') }}</th>
                            <th>{{ __('Merchant') }}</th>
                            <th class="text-end">{{ __('Sale Amount') }}</th>
                            <th class="text-end">{{ __('Commission') }}</th>
                            <th class="text-end">{{ __('Net to Merchant') }}</th>
                            <th>{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                        <tr>
                            <td><code>{{ $purchase->purchase_number }}</code></td>
                            <td>{{ $purchase->user?->shop_name ?? $purchase->user?->name ?? '-' }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($purchase->price, 2) }}</td>
                            <td class="text-end text-success">{{ $currency->sign }}{{ number_format($purchase->commission_amount, 2) }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($purchase->net_amount, 2) }}</td>
                            <td>{{ $purchase->created_at->format('d-m-Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">{{ __('No commission transactions found') }}</td>
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
        $('#commissionTable').DataTable({
            order: [[5, 'desc']],
            pageLength: 25
        });
    }
</script>
@endsection
