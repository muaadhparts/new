@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">
                    {{ __('Create Courier Settlement') }}
                    <a class="add-btn" href="{{ route('operator.settlement.index') }}">
                        <i class="fas fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </h4>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    @if($summary['total_deliveries'] == 0)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ __('No unsettled deliveries found for this courier.') }}
        </div>
    @else

    <div class="row">
        <div class="col-md-8">
            {{-- Courier Summary --}}
            <div class="card mb-4">
                <div class="card-header">
                    <strong>{{ __('Courier Summary') }}: {{ $summary['courier']->name }}</strong>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-4">
                        <div class="col-md-3">
                            <h4>{{ $summary['total_deliveries'] }}</h4>
                            <p class="text-muted">{{ __('Deliveries') }}</p>
                        </div>
                        <div class="col-md-3">
                            <h4>{{ $currency->sign }}{{ number_format($summary['cod_collected'], 2) }}</h4>
                            <p class="text-muted">{{ __('COD Collected') }}</p>
                        </div>
                        <div class="col-md-3">
                            <h4>{{ $currency->sign }}{{ number_format($summary['fees_earned'], 2) }}</h4>
                            <p class="text-muted">{{ __('Fees Earned') }}</p>
                        </div>
                        <div class="col-md-3">
                            @if($summary['settlement_type'] === 'pay_to_courier')
                                <h4 class="text-success">+{{ $currency->sign }}{{ number_format($summary['net_balance'], 2) }}</h4>
                                <p class="text-muted">{{ __('Platform Owes') }}</p>
                            @else
                                <h4 class="text-danger">{{ $currency->sign }}{{ number_format($summary['net_balance'], 2) }}</h4>
                                <p class="text-muted">{{ __('Courier Owes') }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="alert {{ $summary['settlement_type'] === 'pay_to_courier' ? 'alert-success' : 'alert-warning' }}">
                        @if($summary['settlement_type'] === 'pay_to_courier')
                            <i class="fas fa-arrow-right me-2"></i>
                            {{ __('Platform needs to pay courier') }} <strong>{{ $currency->sign }}{{ number_format($summary['net_balance'], 2) }}</strong>
                            <p class="mb-0 mt-2 small">{{ __('Courier earned more in fees than COD collected') }}</p>
                        @else
                            <i class="fas fa-arrow-left me-2"></i>
                            {{ __('Courier needs to pay platform') }} <strong>{{ $currency->sign }}{{ number_format($summary['net_balance'], 2) }}</strong>
                            <p class="mb-0 mt-2 small">{{ __('Courier collected more COD than earned in fees') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Deliveries List --}}
            <div class="card">
                <div class="card-header">
                    <strong>{{ __('Deliveries to Settle') }}</strong>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Order #') }}</th>
                                    <th>{{ __('Delivered') }}</th>
                                    <th>{{ __('Payment') }}</th>
                                    <th class="text-end">{{ __('COD') }}</th>
                                    <th class="text-end">{{ __('Fee') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($summary['deliveries'] as $delivery)
                                <tr>
                                    <td>
                                        <a href="{{ route('operator-purchase-show', $delivery->purchase_id) }}" target="_blank">
                                            {{ $delivery->purchase->purchase_number ?? '#'.$delivery->purchase_id }}
                                        </a>
                                    </td>
                                    <td>{{ $delivery->delivered_at ? $delivery->delivered_at->format('d M Y') : '-' }}</td>
                                    <td>
                                        @if($delivery->payment_method === 'cod')
                                            <span class="badge bg-warning">COD</span>
                                        @else
                                            <span class="badge bg-success">{{ __('Prepaid') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ $currency->sign }}{{ number_format($delivery->cod_amount, 2) }}</td>
                                    <td class="text-end">{{ $currency->sign }}{{ number_format($delivery->delivery_fee, 2) }}</td>
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
                    <strong>{{ __('Create Settlement') }}</strong>
                </div>
                <div class="card-body">
                    <form action="{{ route('operator.settlement.courier.create') }}" method="POST">
                        @csrf
                        <input type="hidden" name="courier_id" value="{{ $summary['courier_id'] }}">
                        <input type="hidden" name="type" value="{{ $summary['settlement_type'] }}">

                        <div class="mb-3">
                            <label class="form-label">{{ __('Settlement Type') }}</label>
                            <input type="text" class="form-control" value="{{ $summary['settlement_type'] === 'pay_to_courier' ? __('Pay to Courier') : __('Receive from Courier') }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Amount') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ $currency->sign }}</span>
                                <input type="number" name="amount" class="form-control form-control-lg text-end"
                                       value="{{ $summary['net_balance'] }}" step="0.01" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Payment Method') }}</label>
                            <select name="payment_method" class="form-control">
                                <option value="">{{ __('Select...') }}</option>
                                <option value="cash">{{ __('Cash') }}</option>
                                <option value="bank_transfer">{{ __('Bank Transfer') }}</option>
                                <option value="wallet">{{ __('Wallet') }}</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Reference') }}</label>
                            <input type="text" name="reference" class="form-control" placeholder="{{ __('Transaction ID, etc.') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Notes') }}</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
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
