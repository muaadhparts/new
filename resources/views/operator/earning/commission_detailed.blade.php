@extends('layouts.operator')
@section('styles')
<link href="{{ asset('assets/operator/css/jquery-ui.css') }}" rel="stylesheet" type="text/css">
<style>
    .summary-card {
        background: var(--surface-primary, #fff);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .summary-card h6 {
        color: var(--text-secondary, #6c757d);
        font-size: 12px;
        margin-bottom: 8px;
        text-transform: uppercase;
    }
    .summary-card h4 {
        color: var(--text-primary, #212529);
        margin: 0;
        font-size: 24px;
    }
    .summary-card.primary h4 { color: var(--action-primary, #7c3aed); }
    .summary-card.success h4 { color: var(--action-success, #28a745); }
    .merchant-summary-section {
        background: var(--surface-secondary, #f8f9fa);
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }
</style>
@endsection
@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Detailed Commission Report') }}</h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Total Earning') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('operator-commission-detailed') }}">{{ __('Detailed Commission') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <form action="{{ route('operator-commission-detailed') }}" method="GET">
        <div class="catalogItem-area">
            <div class="row text-center p-3">
                @include('alerts.operator.form-both')
                <div class="col-sm-6 col-lg-4 offset-lg-2 col-md-6 mt-3">
                    <input type="text" autocomplete="off" class="form-control discount_date"
                        value="{{ $start_date }}" name="start_date" placeholder="{{ __('From Date') }}">
                </div>
                <div class="col-sm-6 col-lg-4 col-md-6 mt-3">
                    <input type="text" autocomplete="off" class="form-control discount_date"
                        value="{{ $end_date }}" name="end_date" placeholder="{{ __('To Date') }}">
                </div>
                <div class="col-sm-12 mt-3">
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                    <button type="button" id="reset" class="btn btn-secondary">{{ __('Reset') }}</button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row px-3 mb-4">
                <div class="col-lg-6 col-md-6">
                    <div class="summary-card primary">
                        <h6>{{ __('Total Sales') }}</h6>
                        <h4>{{ $total_sales }}</h4>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="summary-card success">
                        <h6>{{ __('Total Commission Earned') }}</h6>
                        <h4>{{ $total_commission }}</h4>
                    </div>
                </div>
            </div>

            <!-- Merchant Summary Section -->
            @if($merchantSummary->count() > 0)
            <div class="px-3 mb-4">
                <div class="merchant-summary-section">
                    <h5 class="mb-3">{{ __('Commission by Merchant') }}</h5>
                    <div class="row">
                        @foreach($merchantSummary as $summary)
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">{{ $summary['merchant_name'] }}</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">{{ __('Sales') }}:</span>
                                        <span>{{ $currency->sign }}{{ number_format($summary['total_sales'], 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">{{ __('Commission') }}:</span>
                                        <span class="text-success">{{ $currency->sign }}{{ number_format($summary['total_commission'], 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">{{ __('Orders') }}:</span>
                                        <span class="badge bg-info">{{ $summary['orders_count'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @include('alerts.operator.form-success')

            <!-- Detailed Transactions Table -->
            <div class="mr-table allproduct">
                <div class="table-responsive">
                    <table id="muaadhtable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th width="5%">{{ __('#') }}</th>
                                <th width="15%">{{ __('Purchase Number') }}</th>
                                <th width="20%">{{ __('Merchant') }}</th>
                                <th width="15%">{{ __('Sale Amount') }}</th>
                                <th width="15%">{{ __('Commission') }}</th>
                                <th width="15%">{{ __('Net to Merchant') }}</th>
                                <th width="15%">{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($purchases as $key => $purchase)
                            @php
                                $currSign = $purchase->purchase->currency_sign ?? $currency->sign;
                                $currVal = $purchase->purchase->currency_value ?? 1;
                            @endphp
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>
                                    <a href="{{ route('operator-purchase-invoice', $purchase->purchase_id) }}">
                                        {{ $purchase->purchase->purchase_number ?? 'N/A' }}
                                    </a>
                                </td>
                                <td>
                                    {{ $purchase->user->shop_name ?? $purchase->user->name ?? __('Unknown') }}
                                </td>
                                <td>
                                    {{ $currSign }}{{ number_format($purchase->price * $currVal, 2) }}
                                </td>
                                <td class="text-success">
                                    {{ $currSign }}{{ number_format(($purchase->commission_amount ?? 0) * $currVal, 2) }}
                                </td>
                                <td class="text-primary">
                                    {{ $currSign }}{{ number_format(($purchase->net_amount ?? $purchase->price) * $currVal, 2) }}
                                </td>
                                <td>
                                    {{ $purchase->created_at->format('d-m-Y') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">{{ __('No Commission Data Found') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
    $('#muaadhtable').DataTable({
        order: [[6, 'desc']]
    });

    $(document).on('click', '#reset', function() {
        $('.discount_date').val('');
        location.href = '{{ route('operator-commission-detailed') }}';
    });

    var dateToday = new Date();
    $(".discount_date").datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'yy-mm-dd'
    });
</script>
@endsection
