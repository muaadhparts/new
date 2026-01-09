@extends('layouts.operator')
@section('styles')
<link href="{{asset('assets/operator/css/jquery-ui.css')}}" rel="stylesheet" type="text/css">
@endsection
@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Tax Report') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="javascript:;">{{ __('Total Earning') }}</a></li>
                    <li><a href="{{ route('operator-tax-calculate-income') }}">{{ __('Tax Report') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Date Filter --}}
    <form action="{{route('operator-tax-calculate-income')}}" method="GET">
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Start Date') }}</label>
                        <input type="text" autocomplete="off" class="form-control discount_date"
                               value="{{$start_date != '' ? $start_date->format('d-m-Y') : ''}}"
                               name="start_date" placeholder="{{ __('Start Date') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('End Date') }}</label>
                        <input type="text" autocomplete="off" class="form-control discount_date"
                               value="{{$end_date != '' ? $end_date->format('d-m-Y') : ''}}"
                               name="end_date" placeholder="{{ __('End Date') }}">
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
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Total Tax (Filtered)') }}</h6>
                    <h3>{{ $total }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Current Month') }}</h6>
                    <h3>{{ $current_month }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Last 30 Days') }}</h6>
                    <h3>{{ $last_30_days }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Tax by Payment Receiver --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <strong>{{ __('Tax from Platform Payments') }}</strong>
                </div>
                <div class="card-body text-center">
                    <h3 class="text-primary">{{ $tax_from_platform_payments }}</h3>
                    <small class="text-muted">{{ __('Tax collected when platform received payment') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <strong>{{ __('Tax from Merchant Payments') }}</strong>
                </div>
                <div class="card-body text-center">
                    <h3 class="text-warning">{{ $tax_from_merchant_payments }}</h3>
                    <small class="text-muted">{{ __('Tax collected when merchant received payment') }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Tax by Merchant Summary --}}
    @if(isset($by_merchant) && count($by_merchant) > 0)
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
                            <th class="text-center">{{ __('Orders') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($by_merchant as $merchant)
                        <tr>
                            <td>{{ $merchant['merchant_name'] }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($merchant['total_sales'], 2) }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($merchant['total_tax'], 2) }}</td>
                            <td class="text-center">{{ $merchant['orders_count'] }}</td>
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
        <div class="card-header">
            <strong>{{ __('Tax Transactions') }}</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="muaadhtable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th width="5%">{{ __('#') }}</th>
                            <th>{{ __('Purchase #') }}</th>
                            <th>{{ __('Merchant') }}</th>
                            <th>{{ __('Payment Owner') }}</th>
                            <th class="text-end">{{ __('Sale Amount') }}</th>
                            <th class="text-end">{{ __('Tax Amount') }}</th>
                            <th>{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchases as $key => $purchase)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>
                                <a href="{{ route('operator-purchase-show', $purchase->purchase_id) }}">
                                    {{ $purchase->purchase_number }}
                                </a>
                            </td>
                            <td>{{ $purchase->user?->shop_name ?? $purchase->user?->name ?? __('Unknown') }}</td>
                            <td>
                                @if($purchase->payment_owner_id === 0)
                                    <span class="badge bg-primary">{{ __('Platform') }}</span>
                                @else
                                    <span class="badge bg-warning">{{ __('Merchant') }}</span>
                                @endif
                            </td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($purchase->price, 2) }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($purchase->tax_amount, 2) }}</td>
                            <td>{{ $purchase->created_at->format('d-m-Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
    $('#muaadhtable').DataTable({
        order: [[6, 'desc']]
    });
    $(document).on('click', '#reset', function() {
        $('.discount_date').val('');
        location.href = '{{ route('operator-tax-calculate-income') }}';
    });
</script>
@endsection
