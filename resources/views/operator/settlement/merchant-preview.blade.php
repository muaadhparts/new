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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>{{ __('Settlement Summary') }}</strong>
                    @if($summary['settlement_direction'] === 'platform_to_merchant')
                        <span class="badge bg-success">{{ __('Platform Pays Merchant') }}</span>
                    @else
                        <span class="badge bg-warning">{{ __('Merchant Pays Platform') }}</span>
                    @endif
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

                    {{-- Gross Sales Breakdown --}}
                    <h6 class="border-bottom pb-2 mb-3">{{ __('Sales Breakdown') }}</h6>
                    <table class="table table-sm">
                        <tr>
                            <td>{{ __('Total Sales (Gross)') }}</td>
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
                        @if($summary['platform_shipping_fees'] > 0)
                        <tr class="text-muted">
                            <td>{{ __('Platform Shipping Fees') }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($summary['platform_shipping_fees'], 2) }}</td>
                        </tr>
                        @endif
                        <tr class="table-light">
                            <td><strong>{{ __('Net Amount') }}</strong></td>
                            <td class="text-end"><strong>{{ $currency->sign }}{{ number_format($summary['net_amount'], 2) }}</strong></td>
                        </tr>
                    </table>

                    {{-- Payment Owner Breakdown --}}
                    <h6 class="border-bottom pb-2 mb-3 mt-4">{{ __('Payment Flow Breakdown') }}</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-primary mb-3">
                                <div class="card-header bg-primary text-white py-2">
                                    <small><strong>{{ __('Platform Payments') }}</strong></small>
                                </div>
                                <div class="card-body py-2">
                                    <small class="text-muted">{{ $summary['by_payment_owner']['platform_payments']['count'] }} {{ __('orders') }}</small>
                                    <h5 class="mb-0 text-success">{{ $currency->sign }}{{ number_format($summary['platform_owes_merchant'], 2) }}</h5>
                                    <small class="text-muted">{{ __('Platform owes merchant') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-warning mb-3">
                                <div class="card-header bg-warning text-dark py-2">
                                    <small><strong>{{ __('Merchant Payments') }}</strong></small>
                                </div>
                                <div class="card-body py-2">
                                    <small class="text-muted">{{ $summary['by_payment_owner']['merchant_payments']['count'] }} {{ __('orders') }}</small>
                                    <h5 class="mb-0 text-warning">{{ $currency->sign }}{{ number_format($summary['merchant_owes_platform'], 2) }}</h5>
                                    <small class="text-muted">{{ __('Merchant owes platform') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Final Settlement --}}
                    @if($summary['settlement_direction'] === 'platform_to_merchant')
                    <div class="alert alert-success mb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-arrow-right me-2"></i>
                                <strong>{{ __('Platform Pays Merchant') }}</strong>
                            </span>
                            <h4 class="mb-0">{{ $currency->sign }}{{ number_format($summary['net_settlement'], 2) }}</h4>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-warning mb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-arrow-left me-2"></i>
                                <strong>{{ __('Merchant Pays Platform') }}</strong>
                            </span>
                            <h4 class="mb-0">{{ $currency->sign }}{{ number_format($summary['net_settlement'], 2) }}</h4>
                        </div>
                    </div>
                    @endif
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
                                    <th>{{ __('Payment') }}</th>
                                    <th class="text-end">{{ __('Sale') }}</th>
                                    <th class="text-end">{{ __('Balance') }}</th>
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
                                    <td>
                                        @if($purchase->payment_owner_id == 0)
                                            <span class="badge bg-primary">{{ __('Platform') }}</span>
                                        @else
                                            <span class="badge bg-warning">{{ __('Merchant') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ $currency->sign }}{{ number_format($purchase->price, 2) }}</td>
                                    <td class="text-end">
                                        @if($purchase->platform_owes_merchant > 0)
                                            <span class="text-success">+{{ $currency->sign }}{{ number_format($purchase->platform_owes_merchant, 2) }}</span>
                                            <small class="d-block text-muted">{{ __('Platform owes') }}</small>
                                        @elseif($purchase->merchant_owes_platform > 0)
                                            <span class="text-warning">-{{ $currency->sign }}{{ number_format($purchase->merchant_owes_platform, 2) }}</span>
                                            <small class="d-block text-muted">{{ __('Merchant owes') }}</small>
                                        @else
                                            <span class="text-muted">{{ $currency->sign }}0.00</span>
                                        @endif
                                    </td>
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
                <div class="card-header {{ $summary['settlement_direction'] === 'platform_to_merchant' ? 'bg-success' : 'bg-warning' }} text-white">
                    <strong>{{ __('Confirm Settlement') }}</strong>
                </div>
                <div class="card-body">
                    <form action="{{ route('operator.settlement.merchant.create') }}" method="POST">
                        @csrf
                        <input type="hidden" name="merchant_id" value="{{ $summary['merchant_id'] }}">
                        <input type="hidden" name="from_date" value="{{ $fromDate }}">
                        <input type="hidden" name="to_date" value="{{ $toDate }}">

                        {{-- Settlement Direction --}}
                        <div class="mb-3 text-center">
                            @if($summary['settlement_direction'] === 'platform_to_merchant')
                                <div class="bg-success bg-opacity-10 rounded p-3">
                                    <i class="fas fa-arrow-right fa-2x text-success mb-2"></i>
                                    <h6 class="text-success mb-0">{{ __('Platform Pays Merchant') }}</h6>
                                </div>
                            @else
                                <div class="bg-warning bg-opacity-10 rounded p-3">
                                    <i class="fas fa-arrow-left fa-2x text-warning mb-2"></i>
                                    <h6 class="text-warning mb-0">{{ __('Merchant Pays Platform') }}</h6>
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Settlement Amount') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ $currency->sign }}</span>
                                <input type="text" class="form-control form-control-lg text-end fw-bold" value="{{ number_format($summary['net_settlement'], 2) }}" readonly>
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

                        <button type="submit" class="btn {{ $summary['settlement_direction'] === 'platform_to_merchant' ? 'btn-success' : 'btn-warning' }} btn-lg w-100">
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
