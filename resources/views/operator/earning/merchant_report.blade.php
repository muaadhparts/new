@extends('layouts.operator')
@section('styles')
<link href="{{ asset('assets/operator/css/jquery-ui.css') }}" rel="stylesheet" type="text/css">
@endsection
@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Merchant Financial Report') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="javascript:;">{{ __('Reports') }}</a></li>
                    <li><a href="{{ route('operator-merchant-report') }}">{{ __('Merchant Report') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Date Filter --}}
    <form action="{{ route('operator-merchant-report') }}" method="GET">
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Start Date') }}</label>
                        <input type="text" class="form-control discount_date" name="start_date" value="{{ $start_date }}" placeholder="{{ __('From Date') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('End Date') }}</label>
                        <input type="text" class="form-control discount_date" name="end_date" value="{{ $end_date }}" placeholder="{{ __('To Date') }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                        <button type="button" id="reset" class="btn btn-secondary">{{ __('Reset') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @include('alerts.operator.form-both')

    {{-- Summary Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Total Sales') }}</h6>
                    <h3>{{ $total_sales }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Platform Commission') }}</h6>
                    <h3>{{ $total_commissions }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Tax Collected') }}</h6>
                    <h3>{{ $total_taxes }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h6>{{ __('Net to Merchants') }}</h6>
                    <h3>{{ $total_net_to_merchants }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment Owner Breakdown --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white d-flex justify-content-between">
                    <strong>{{ __('Platform Payments') }}</strong>
                    <span class="badge bg-light text-primary">{{ $platform_payments['count'] }} {{ __('orders') }}</span>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h5>{{ $currency->sign }}{{ number_format($platform_payments['total'], 2) }}</h5>
                            <small class="text-muted">{{ __('Total Received') }}</small>
                        </div>
                        <div class="col-6">
                            <h5 class="text-success">{{ $platform_owes_merchants }}</h5>
                            <small class="text-muted">{{ __('Platform Owes Merchants') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark d-flex justify-content-between">
                    <strong>{{ __('Merchant Payments') }}</strong>
                    <span class="badge bg-dark">{{ $merchant_payments['count'] }} {{ __('orders') }}</span>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h5>{{ $currency->sign }}{{ number_format($merchant_payments['total'], 2) }}</h5>
                            <small class="text-muted">{{ __('Merchants Received') }}</small>
                        </div>
                        <div class="col-6">
                            <h5 class="text-danger">{{ $merchants_owe_platform }}</h5>
                            <small class="text-muted">{{ __('Merchants Owe Platform') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Net Platform Position --}}
    <div class="card mb-4">
        <div class="card-header {{ $net_platform_position >= 0 ? 'bg-success' : 'bg-danger' }} text-white">
            <strong>{{ __('Net Platform Position') }}</strong>
        </div>
        <div class="card-body text-center">
            @if($net_platform_position >= 0)
                <h2 class="text-success">+{{ $currency->sign }}{{ number_format($net_platform_position, 2) }}</h2>
                <p class="text-muted mb-0">{{ __('Platform is owed this amount from merchants (commission + tax)') }}</p>
            @else
                <h2 class="text-danger">{{ $currency->sign }}{{ number_format(abs($net_platform_position), 2) }}</h2>
                <p class="text-muted mb-0">{{ __('Platform owes merchants this amount (net earnings)') }}</p>
            @endif
        </div>
    </div>

    {{-- Merchant Breakdown Table --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>{{ __('Per Merchant Breakdown') }}</strong>
            <span class="badge bg-primary">{{ count($merchants) }} {{ __('merchants') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="muaadhtable" class="table table-hover dt-responsive mb-0" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>{{ __('Merchant') }}</th>
                            <th class="text-center">{{ __('Orders') }}</th>
                            <th class="text-end">{{ __('Sales') }}</th>
                            <th class="text-end">{{ __('Commission') }}</th>
                            <th class="text-end">{{ __('Tax') }}</th>
                            <th class="text-end">{{ __('Net') }}</th>
                            <th class="text-center">{{ __('Payment Flow') }}</th>
                            <th class="text-end">{{ __('Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($merchants as $merchant)
                        <tr>
                            <td>
                                <strong>{{ $merchant['merchant_name'] }}</strong>
                                <br><small class="text-muted">ID: {{ $merchant['merchant_id'] }}</small>
                            </td>
                            <td class="text-center">{{ $merchant['orders_count'] }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($merchant['total_sales'], 2) }}</td>
                            <td class="text-end text-success">{{ $currency->sign }}{{ number_format($merchant['total_commission'], 2) }}</td>
                            <td class="text-end text-info">{{ $currency->sign }}{{ number_format($merchant['total_tax'], 2) }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($merchant['total_net'], 2) }}</td>
                            <td class="text-center">
                                @if($merchant['platform_payments_count'] > 0)
                                    <span class="badge bg-primary" title="{{ __('Platform received') }}">
                                        {{ $merchant['platform_payments_count'] }} <i class="fas fa-arrow-down"></i>
                                    </span>
                                @endif
                                @if($merchant['merchant_payments_count'] > 0)
                                    <span class="badge bg-warning" title="{{ __('Merchant received') }}">
                                        {{ $merchant['merchant_payments_count'] }} <i class="fas fa-arrow-up"></i>
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($merchant['net_balance'] >= 0)
                                    <span class="text-success" title="{{ __('Platform owes merchant') }}">
                                        +{{ $currency->sign }}{{ number_format($merchant['net_balance'], 2) }}
                                    </span>
                                @else
                                    <span class="text-danger" title="{{ __('Merchant owes platform') }}">
                                        {{ $currency->sign }}{{ number_format($merchant['net_balance'], 2) }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">{{ __('No Data Found') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(count($merchants) > 0)
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td>{{ __('Total') }}</td>
                            <td class="text-center">{{ $report['total_orders'] }}</td>
                            <td class="text-end">{{ $total_sales }}</td>
                            <td class="text-end text-success">{{ $total_commissions }}</td>
                            <td class="text-end text-info">{{ $total_taxes }}</td>
                            <td class="text-end">{{ $total_net_to_merchants }}</td>
                            <td></td>
                            <td class="text-end">
                                @if($net_platform_position < 0)
                                    <span class="text-success">{{ $currency->sign }}{{ number_format(abs($net_platform_position), 2) }}</span>
                                @else
                                    <span class="text-danger">-{{ $currency->sign }}{{ number_format($net_platform_position, 2) }}</span>
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

    $(".discount_date").datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'yy-mm-dd'
    });
</script>
@endsection
