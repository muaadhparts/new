@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">
                    {{ __('Settlement') }}: {{ $settlement->settlement_number }}
                    <a class="add-btn" href="{{ route('operator.settlement.merchants') }}">
                        <i class="fas fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.settlement.index') }}">{{ __('Settlements') }}</a></li>
                    <li><a href="{{ route('operator.settlement.merchants') }}">{{ __('Merchant Settlements') }}</a></li>
                    <li><a href="javascript:;">{{ $settlement->settlement_number }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    <div class="row">
        <div class="col-md-8">
            {{-- Settlement Details --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>{{ __('Settlement Details') }}</strong>
                    <span class="badge {{ $settlement->getStatusBadgeClass() }} fs-6">
                        {{ $settlement->getStatusLabel() }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>{{ $settlement->merchant->shop_name ?? $settlement->merchant->name }}</h5>
                            <p class="text-muted mb-0">{{ $settlement->merchant->email }}</p>
                            <p class="text-muted">{{ $settlement->merchant->phone ?? '' }}</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <p><strong>{{ __('Settlement #') }}:</strong> {{ $settlement->settlement_number }}</p>
                            <p><strong>{{ __('Period') }}:</strong> {{ $settlement->period_start->format('d M Y') }} - {{ $settlement->period_end->format('d M Y') }}</p>
                            <p><strong>{{ __('Created') }}:</strong> {{ $settlement->created_at->format('d M Y H:i') }}</p>
                        </div>
                    </div>

                    <hr>

                    <table class="table table-borderless">
                        <tr>
                            <td width="60%">{{ __('Total Sales') }} ({{ $settlement->orders_count }} {{ __('orders') }})</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($settlement->total_sales, 2) }}</td>
                        </tr>
                        <tr class="text-danger">
                            <td>{{ __('Platform Commission') }}</td>
                            <td class="text-end">- {{ $currency->sign }}{{ number_format($settlement->total_commission, 2) }}</td>
                        </tr>
                        @if($settlement->total_tax > 0)
                        <tr class="text-muted">
                            <td>{{ __('Tax Collected') }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($settlement->total_tax, 2) }}</td>
                        </tr>
                        @endif
                        @if($settlement->total_shipping > 0)
                        <tr class="text-muted">
                            <td>{{ __('Shipping') }}</td>
                            <td class="text-end">{{ $currency->sign }}{{ number_format($settlement->total_shipping, 2) }}</td>
                        </tr>
                        @endif
                        @if($settlement->total_deductions > 0)
                        <tr class="text-danger">
                            <td>{{ __('Other Deductions') }}</td>
                            <td class="text-end">- {{ $currency->sign }}{{ number_format($settlement->total_deductions, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="table-success fs-5">
                            <td><strong>{{ __('Net Payable') }}</strong></td>
                            <td class="text-end"><strong>{{ $currency->sign }}{{ number_format($settlement->net_payable, 2) }}</strong></td>
                        </tr>
                    </table>

                    @if($settlement->isPaid())
                    <hr>
                    <div class="alert alert-success">
                        <strong>{{ __('Payment Information') }}</strong><br>
                        <p class="mb-0">{{ __('Method') }}: {{ $settlement->payment_method }}</p>
                        @if($settlement->payment_reference)
                        <p class="mb-0">{{ __('Reference') }}: {{ $settlement->payment_reference }}</p>
                        @endif
                        <p class="mb-0">{{ __('Paid on') }}: {{ $settlement->paid_at->format('d M Y H:i') }}</p>
                    </div>
                    @endif

                    @if($settlement->notes)
                    <hr>
                    <p><strong>{{ __('Notes') }}:</strong></p>
                    <p class="text-muted">{{ $settlement->notes }}</p>
                    @endif
                </div>
            </div>

            {{-- Orders List --}}
            <div class="card">
                <div class="card-header">
                    <strong>{{ __('Included Orders') }}</strong>
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
                                @foreach($settlement->items as $item)
                                <tr>
                                    <td>
                                        <a href="{{ route('operator-purchase-show', $item->merchantPurchase->purchase_id) }}" target="_blank">
                                            {{ $item->merchantPurchase->purchase_number }}
                                        </a>
                                    </td>
                                    <td>{{ $item->merchantPurchase->created_at->format('d M Y') }}</td>
                                    <td class="text-end">{{ $currency->sign }}{{ number_format($item->sale_amount, 2) }}</td>
                                    <td class="text-end text-danger">{{ $currency->sign }}{{ number_format($item->commission_amount, 2) }}</td>
                                    <td class="text-end">{{ $currency->sign }}{{ number_format($item->net_amount, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            {{-- Actions --}}
            <div class="card">
                <div class="card-header">
                    <strong>{{ __('Actions') }}</strong>
                </div>
                <div class="card-body">
                    @if($settlement->isDraft())
                        <form action="{{ route('operator.settlement.merchant.submit', $settlement->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="fas fa-paper-plane me-2"></i> {{ __('Submit for Approval') }}
                            </button>
                        </form>
                    @endif

                    @if($settlement->isPending())
                        <form action="{{ route('operator.settlement.merchant.approve', $settlement->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-check me-2"></i> {{ __('Approve Settlement') }}
                            </button>
                        </form>
                    @endif

                    @if($settlement->isApproved())
                        <form action="{{ route('operator.settlement.merchant.pay', $settlement->id) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">{{ __('Payment Method') }}</label>
                                <select name="payment_method" class="form-control" required>
                                    <option value="">{{ __('Select...') }}</option>
                                    <option value="bank_transfer">{{ __('Bank Transfer') }}</option>
                                    <option value="wallet">{{ __('Add to Wallet') }}</option>
                                    <option value="cash">{{ __('Cash') }}</option>
                                    <option value="other">{{ __('Other') }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Reference Number') }}</label>
                                <input type="text" name="payment_reference" class="form-control" placeholder="{{ __('Transaction ID, etc.') }}">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-money-bill me-2"></i> {{ __('Mark as Paid') }}
                            </button>
                        </form>
                    @endif

                    @if($settlement->canBeCancelled())
                        <hr>
                        <form action="{{ route('operator.settlement.merchant.cancel', $settlement->id) }}" method="POST"
                              onsubmit="return confirm('{{ __('Are you sure you want to cancel this settlement?') }}')">
                            @csrf
                            @method('DELETE')
                            <div class="mb-3">
                                <label class="form-label">{{ __('Reason (Optional)') }}</label>
                                <input type="text" name="reason" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="fas fa-times me-2"></i> {{ __('Cancel Settlement') }}
                            </button>
                        </form>
                    @endif

                    @if($settlement->isPaid())
                        <div class="alert alert-success text-center mb-0">
                            <i class="fas fa-check-circle fa-3x mb-2"></i>
                            <h5>{{ __('Settlement Completed') }}</h5>
                            <p class="mb-0">{{ __('Paid on') }} {{ $settlement->paid_at->format('d M Y') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Audit Trail --}}
            <div class="card mt-4">
                <div class="card-header">
                    <strong>{{ __('Audit Trail') }}</strong>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-plus-circle text-primary me-2"></i>
                            {{ __('Created') }}: {{ $settlement->created_at->format('d M Y H:i') }}
                        </li>
                        @if($settlement->approved_at)
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            {{ __('Approved') }}: {{ $settlement->approved_at->format('d M Y H:i') }}
                        </li>
                        @endif
                        @if($settlement->paid_at)
                        <li class="mb-2">
                            <i class="fas fa-money-bill text-success me-2"></i>
                            {{ __('Paid') }}: {{ $settlement->paid_at->format('d M Y H:i') }}
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
