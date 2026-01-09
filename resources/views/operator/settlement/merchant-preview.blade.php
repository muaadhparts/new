@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">
                    {{ __('Create Merchant Settlement') }}
                    <a class="add-btn" href="{{ route('operator.settlement.index') }}">
                        <i class="fas fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.settlement.index') }}">{{ __('Settlements') }}</a></li>
                    <li><a href="javascript:;">{{ __('Create Merchant Settlement') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    @if($summary['orders_count'] == 0)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ __('No unsettled orders found for this merchant in the selected period.') }}
        </div>
    @else

    <div class="row">
        <div class="col-md-8">
            {{-- Settlement Summary --}}
            <div class="card mb-4">
                <div class="card-header">
                    <strong>{{ __('Settlement Summary') }}</strong>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>{{ $summary['merchant']->shop_name ?? $summary['merchant']->name }}</h5>
                            <p class="text-muted">{{ $summary['merchant']->email }}</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <p><strong>{{ __('Period') }}:</strong> {{ $summary['period_start'] }} - {{ $summary['period_end'] }}</p>
                            <p><strong>{{ __('Orders') }}:</strong> {{ $summary['orders_count'] }}</p>
                        </div>
                    </div>

                    <table class="table">
                        <tr>
                            <td>{{ __('Total Sales') }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($summary['total_sales'], 2) }}</td>
                        </tr>
                        <tr class="text-danger">
                            <td>{{ __('Platform Commission') }} (-)</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($summary['total_commission'], 2) }}</td>
                        </tr>
                        <tr class="text-muted">
                            <td>{{ __('Tax Collected') }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($summary['total_tax'], 2) }}</td>
                        </tr>
                        <tr class="text-muted">
                            <td>{{ __('Shipping') }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($summary['total_shipping'], 2) }}</td>
                        </tr>
                        <tr class="table-success">
                            <td><strong>{{ __('Net Payable to Merchant') }}</strong></td>
                            <td class="text-end"><strong>{{ $currency->sign }}{{ number_format($summary['net_payable'], 2) }}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Orders List --}}
            <div class="card">
                <div class="card-header">
                    <strong>{{ __('Orders Included') }}</strong>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Order #') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th class="text-end">{{ __('Sale') }}</th>
                                    <th class="text-end">{{ __('Commission') }}</th>
                                    <th class="text-end">{{ __('Net') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($summary['purchases'] as $purchase)
                                <tr>
                                    <td>
                                        <a href="{{ route('operator-purchase-show', $purchase->purchase_id) }}" target="_blank">
                                            {{ $purchase->purchase_number }}
                                        </a>
                                    </td>
                                    <td>{{ $purchase->created_at->format('d M Y') }}</td>
                                    <td class="text-end">{{ $currency->sign }}{{ number_format($purchase->price, 2) }}</td>
                                    <td class="text-end text-danger">{{ $currency->sign }}{{ number_format($purchase->commission_amount, 2) }}</td>
                                    <td class="text-end">{{ $currency->sign }}{{ number_format($purchase->net_amount, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            {{-- Create Settlement Form --}}
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <strong>{{ __('Confirm Settlement') }}</strong>
                </div>
                <div class="card-body">
                    <form action="{{ route('operator.settlement.merchant.create') }}" method="POST">
                        @csrf
                        <input type="hidden" name="merchant_id" value="{{ $summary['merchant_id'] }}">
                        <input type="hidden" name="from_date" value="{{ $fromDate }}">
                        <input type="hidden" name="to_date" value="{{ $toDate }}">

                        <div class="mb-3">
                            <label class="form-label">{{ __('Net Payable') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ $currency->sign }}</span>
                                <input type="text" class="form-control form-control-lg text-end" value="{{ number_format($summary['net_payable'], 2) }}" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Notes (Optional)') }}</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="{{ __('Add any notes for this settlement...') }}"></textarea>
                        </div>

                        <div class="alert alert-info">
                            <small>
                                <i class="fas fa-info-circle me-1"></i>
                                {{ __('This will create a settlement in Draft status. You can review and approve it before marking as paid.') }}
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-check me-2"></i> {{ __('Create Settlement') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @endif
</div>
@endsection
