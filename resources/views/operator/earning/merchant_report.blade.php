@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Merchant Financial Report') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="javascript:;">{{ __('Merchant Report') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Summary Cards --}}
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center">
                    <h6 class="mb-2">{{ __('Total Sales') }}</h6>
                    <h3 class="mb-0">{{ $total_sales }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center">
                    <h6 class="mb-2">{{ __('Total Commissions') }}</h6>
                    <h3 class="mb-0">{{ $total_commissions }}</h3>
                    <small>{{ __('Platform Profit') }}</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body text-center">
                    <h6 class="mb-2">{{ __('Total Taxes') }}</h6>
                    <h3 class="mb-0">{{ $total_taxes }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-secondary text-white h-100">
                <div class="card-body text-center">
                    <h6 class="mb-2">{{ __('Net to Merchants') }}</h6>
                    <h3 class="mb-0">{{ $total_net_to_merchants }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Settlement Balance --}}
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-success h-100">
                <div class="card-header bg-success text-white">
                    <strong>{{ __('Platform Owes Merchants') }}</strong>
                </div>
                <div class="card-body text-center">
                    <h3>{{ $platform_owes_merchants }}</h3>
                    <small class="text-muted">{{ __('From platform payment gateways') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-warning h-100">
                <div class="card-header bg-warning text-dark">
                    <strong>{{ __('Merchants Owe Platform') }}</strong>
                </div>
                <div class="card-body text-center">
                    <h3>{{ $merchants_owe_platform }}</h3>
                    <small class="text-muted">{{ __('From merchant payment gateways') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-{{ $net_platform_position >= 0 ? 'success' : 'danger' }} h-100">
                <div class="card-header bg-{{ $net_platform_position >= 0 ? 'success' : 'danger' }} text-white">
                    <strong>{{ __('Net Platform Position') }}</strong>
                </div>
                <div class="card-body text-center">
                    <h3>{{ $currency->sign }}{{ number_format(abs($net_platform_position), 2) }}</h3>
                    @if($net_platform_position >= 0)
                        <small class="text-success">{{ __('Platform is owed this amount') }}</small>
                    @else
                        <small class="text-danger">{{ __('Platform owes this amount') }}</small>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Payment Method Breakdown --}}
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <strong>{{ __('Platform Payments') }}</strong>
                </div>
                <div class="card-body text-center">
                    <h4>{{ $currency->sign }}{{ number_format($platform_payments['total'] ?? 0, 2) }}</h4>
                    <span class="badge bg-primary">{{ $platform_payments['count'] ?? 0 }} {{ __('Orders') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <strong>{{ __('Merchant Payments') }}</strong>
                </div>
                <div class="card-body text-center">
                    <h4>{{ $currency->sign }}{{ number_format($merchant_payments['total'] ?? 0, 2) }}</h4>
                    <span class="badge bg-warning">{{ $merchant_payments['count'] ?? 0 }} {{ __('Orders') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Date Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('operator-merchant-report') }}" method="GET">
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
                        <a href="{{ route('operator-merchant-report') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Per Merchant Table --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>{{ __('Per Merchant Breakdown') }}</strong>
            <span class="badge bg-primary">{{ count($merchants) }} {{ __('Merchants') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="merchantTable">
                    <thead>
                        <tr>
                            <th>{{ __('Merchant') }}</th>
                            <th class="text-end">{{ __('Sales') }}</th>
                            <th class="text-end">{{ __('Commission') }}</th>
                            <th class="text-end">{{ __('Tax') }}</th>
                            <th class="text-end">{{ __('Net') }}</th>
                            <th class="text-end">{{ __('Balance') }}</th>
                            <th class="text-end">{{ __('Orders') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($merchants as $merchant)
                        <tr>
                            <td>{{ $merchant['merchant_name'] }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($merchant['total_sales'], 2) }}</td>
                            <td class="text-end text-success">{{ $currency->sign }}{{ number_format($merchant['total_commission'], 2) }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($merchant['total_tax'], 2) }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($merchant['total_net'], 2) }}</td>
                            <td class="text-end {{ $merchant['net_balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $currency->sign }}{{ number_format($merchant['net_balance'], 2) }}
                                @if($merchant['net_balance'] > 0)
                                    <small>({{ __('Owed') }})</small>
                                @elseif($merchant['net_balance'] < 0)
                                    <small>({{ __('Owes') }})</small>
                                @endif
                            </td>
                            <td class="text-end">{{ $merchant['orders_count'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">{{ __('No merchants found') }}</td>
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
        $('#merchantTable').DataTable({
            order: [[1, 'desc']],
            pageLength: 25
        });
    }
</script>
@endsection
