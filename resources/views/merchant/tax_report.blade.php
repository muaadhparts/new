@extends('layouts.merchant')

@section('content')
<div class="gs-merchant-outlet">
    {{-- Breadcrumb --}}
    <div class="gs-merchant-breadcrumb has-mb">
        <h4 class="text-capitalize">@lang('Tax Report')</h4>
        <ul class="breadcrumb-menu">
            <li><a href="{{ route('merchant.dashboard') }}">@lang('Dashboard')</a></li>
            <li><a href="{{ route('merchant.income') }}">@lang('Financial Dashboard')</a></li>
            <li><a href="#">@lang('Tax Report')</a></li>
        </ul>
    </div>

    <div class="gs-merchant-erning">
        {{-- Date Filter --}}
        <div class="card mb-4">
            <div class="card-body">
                <form action="{{ route('merchant.tax-report') }}" method="GET">
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
                            <a href="{{ route('merchant.tax-report') }}" class="btn btn-secondary">@lang('Reset')</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h6>@lang('Total Tax Collected')</h6>
                        <h3>{{ $total_tax }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h6>@lang('Total Sales (with Tax)')</h6>
                        <h3>{{ $total_sales }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6>@lang('Orders with Tax')</h6>
                        <h3>{{ count($purchases) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tax by Payment Receiver --}}
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <strong>@lang('Tax from Platform Payments')</strong>
                    </div>
                    <div class="card-body text-center">
                        <h3 class="text-primary mb-1">{{ $tax_from_platform_payments }}</h3>
                        <small class="text-muted">@lang('Tax collected when platform received payment')</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <strong>@lang('Tax from Your Payments')</strong>
                    </div>
                    <div class="card-body text-center">
                        <h3 class="text-success mb-1">{{ $tax_from_merchant_payments }}</h3>
                        <small class="text-muted">@lang('Tax collected when you received payment')</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tax Transactions Table --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">@lang('Tax Transactions')</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="taxTable">
                        <thead>
                            <tr>
                                <th>@lang('Purchase #')</th>
                                <th class="text-end">@lang('Sale Amount')</th>
                                <th class="text-end">@lang('Tax Amount')</th>
                                <th class="text-center">@lang('Payment Owner')</th>
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
                                <td class="text-end text-info">{{ $currencySign }}{{ number_format($purchase->tax_amount, 2) }}</td>
                                <td class="text-center">
                                    @if($purchase->payment_owner_id === 0)
                                        <span class="badge bg-primary">@lang('Platform')</span>
                                    @else
                                        <span class="badge bg-success">@lang('You')</span>
                                    @endif
                                </td>
                                <td>{{ $purchase->created_at->format('d-m-Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    @lang('No tax transactions found')
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
    if ($.fn.DataTable) {
        $('#taxTable').DataTable({
            order: [[4, 'desc']],
            pageLength: 25
        });
    }
</script>
@endsection
