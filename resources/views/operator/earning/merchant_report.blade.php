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
    .summary-card.danger h4 { color: var(--action-danger, #dc3545); }
    .summary-card.warning h4 { color: var(--action-warning, #ffc107); }
</style>
@endsection
@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Merchant Report') }}</h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Total Earning') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('operator-merchant-report') }}">{{ __('Merchant Report') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <form action="{{ route('operator-merchant-report') }}" method="GET">
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
                <div class="col-lg-3 col-md-6">
                    <div class="summary-card primary">
                        <h6>{{ __('Total Sales') }}</h6>
                        <h4>{{ $total_sales }}</h4>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="summary-card success">
                        <h6>{{ __('Platform Commissions') }}</h6>
                        <h4>{{ $total_commissions }}</h4>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="summary-card warning">
                        <h6>{{ __('Total Taxes') }}</h6>
                        <h4>{{ $total_taxes }}</h4>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="summary-card danger">
                        <h6>{{ __('Net to Merchants') }}</h6>
                        <h4>{{ $total_net_to_merchants }}</h4>
                    </div>
                </div>
            </div>

            @include('alerts.operator.form-success')

            <!-- Merchants Table -->
            <div class="mr-table allproduct">
                <div class="table-responsive">
                    <table id="muaadhtable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th width="5%">{{ __('#') }}</th>
                                <th width="20%">{{ __('Merchant') }}</th>
                                <th width="12%">{{ __('Total Sales') }}</th>
                                <th width="12%">{{ __('Commission') }}</th>
                                <th width="10%">{{ __('Tax') }}</th>
                                <th width="12%">{{ __('Net Amount') }}</th>
                                <th width="8%">{{ __('Orders') }}</th>
                                <th width="10%">{{ __('Merchant Pay') }}</th>
                                <th width="10%">{{ __('Platform Pay') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($merchants as $key => $merchant)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>
                                    <strong>{{ $merchant['merchant_name'] }}</strong>
                                    <br>
                                    <small class="text-muted">ID: {{ $merchant['merchant_id'] }}</small>
                                </td>
                                <td>
                                    {{ $currency->sign }}{{ number_format($merchant['total_sales'], 2) }}
                                </td>
                                <td class="text-success">
                                    {{ $currency->sign }}{{ number_format($merchant['total_commission'], 2) }}
                                </td>
                                <td>
                                    {{ $currency->sign }}{{ number_format($merchant['total_tax'], 2) }}
                                </td>
                                <td class="text-primary">
                                    <strong>{{ $currency->sign }}{{ number_format($merchant['total_net'], 2) }}</strong>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $merchant['orders_count'] }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-success">{{ $merchant['merchant_payments_count'] }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $merchant['platform_payments_count'] }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center">{{ __('No Data Found') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if(count($merchants) > 0)
                        <tfoot>
                            <tr class="table-active">
                                <td colspan="2"><strong>{{ __('Totals') }}</strong></td>
                                <td><strong>{{ $total_sales }}</strong></td>
                                <td class="text-success"><strong>{{ $total_commissions }}</strong></td>
                                <td><strong>{{ $total_taxes }}</strong></td>
                                <td class="text-primary"><strong>{{ $total_net_to_merchants }}</strong></td>
                                <td><strong>{{ collect($merchants)->sum('orders_count') }}</strong></td>
                                <td><strong>{{ collect($merchants)->sum('merchant_payments_count') }}</strong></td>
                                <td><strong>{{ collect($merchants)->sum('platform_payments_count') }}</strong></td>
                            </tr>
                        </tfoot>
                        @endif
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
        order: [[2, 'desc']]
    });

    $(document).on('click', '#reset', function() {
        $('.discount_date').val('');
        location.href = '{{ route('operator-merchant-report') }}';
    });

    var dateToday = new Date();
    $(".discount_date").datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'yy-mm-dd'
    });
</script>
@endsection
