@extends('layouts.operator')
@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Create Settlement') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator-courier-balances') }}">{{ __('Courier Balances') }}</a></li>
                    <li><a href="#">{{ __('Create Settlement') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="catalogItem-area">
        <div class="row">
            <!-- Courier Info & Settlement Calculation -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('Courier Information') }}</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>{{ __('Name') }}:</strong></td>
                                <td>{{ $courier->name }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Email') }}:</strong></td>
                                <td>{{ $courier->email }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Phone') }}:</strong></td>
                                <td>{{ $courier->phone }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Current Balance') }}:</strong></td>
                                <td>
                                    <span class="{{ $courier->balance >= 0 ? 'text-success' : 'text-danger' }}">
                                        <strong>{{ $currency->sign }}{{ number_format($courier->balance, 2) }}</strong>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('Settlement Calculation') }}</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <td>{{ __('COD Amount Collected') }}</td>
                                <td class="text-end text-danger">
                                    {{ $currency->sign }}{{ number_format($settlementCalc['cod_amount'] ?? 0, 2) }}
                                    <br><small class="text-muted">({{ __('Courier owes to Platform') }})</small>
                                </td>
                            </tr>
                            <tr>
                                <td>{{ __('Delivery Fees (Online)') }}</td>
                                <td class="text-end text-success">
                                    {{ $currency->sign }}{{ number_format($settlementCalc['fees_earned_online'] ?? 0, 2) }}
                                </td>
                            </tr>
                            <tr>
                                <td>{{ __('Delivery Fees (COD)') }}</td>
                                <td class="text-end text-success">
                                    {{ $currency->sign }}{{ number_format($settlementCalc['fees_earned_cod'] ?? 0, 2) }}
                                </td>
                            </tr>
                            <tr class="table-active">
                                <td><strong>{{ __('Net Amount') }}</strong></td>
                                @php
                                    $netAmount = $settlementCalc['net_amount'] ?? 0;
                                @endphp
                                <td class="text-end {{ $netAmount >= 0 ? 'text-success' : 'text-danger' }}">
                                    <strong>{{ $currency->sign }}{{ number_format(abs($netAmount), 2) }}</strong>
                                    <br>
                                    @if($netAmount >= 0)
                                        <small>({{ __('Platform pays Courier') }})</small>
                                    @else
                                        <small>({{ __('Courier pays Platform') }})</small>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Settlement Form -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('Create New Settlement') }}</h5>
                    </div>
                    <div class="card-body">
                        @include('alerts.operator.form-both')

                        <form action="{{ route('operator-courier-store-settlement', $courier->id) }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">{{ __('Settlement Type') }} *</label>
                                <select name="type" class="form-select" required>
                                    <option value="pay_to_courier" {{ ($settlementCalc['settlement_type'] ?? '') == 'pay_to_courier' ? 'selected' : '' }}>
                                        {{ __('Pay to Courier') }} ({{ __('Platform owes to Courier') }})
                                    </option>
                                    <option value="receive_from_courier" {{ ($settlementCalc['settlement_type'] ?? '') == 'receive_from_courier' ? 'selected' : '' }}>
                                        {{ __('Receive from Courier') }} ({{ __('Courier owes to Platform') }})
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('Amount') }} ({{ $currency->sign }}) *</label>
                                <input type="number" name="amount" class="form-control" step="0.01" min="0.01"
                                       value="{{ number_format(abs($netAmount), 2, '.', '') }}" required>
                                <small class="text-muted">{{ __('Suggested amount based on unsettled deliveries') }}</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('Payment Method') }}</label>
                                <select name="payment_method" class="form-select">
                                    <option value="">{{ __('Select...') }}</option>
                                    <option value="cash">{{ __('Cash') }}</option>
                                    <option value="bank_transfer">{{ __('Bank Transfer') }}</option>
                                    <option value="check">{{ __('Check') }}</option>
                                    <option value="other">{{ __('Other') }}</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('Reference Number') }}</label>
                                <input type="text" name="reference_number" class="form-control"
                                       placeholder="{{ __('Transaction ID, Check Number, etc.') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('Notes') }}</label>
                                <textarea name="notes" class="form-control" rows="3"
                                          placeholder="{{ __('Optional notes about this settlement') }}"></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> {{ __('Create Settlement') }}
                                </button>
                                <a href="{{ route('operator-courier-balances') }}" class="btn btn-secondary">
                                    {{ __('Cancel') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Unsettled Deliveries Info -->
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ __('Unsettled Deliveries') }}</h5>
                        <span class="badge bg-warning">{{ $unsettled->count() }}</span>
                    </div>
                    <div class="card-body">
                        @if($unsettled->count() > 0)
                            <p class="text-muted">
                                {{ __('There are :count unsettled deliveries for this courier.', ['count' => $unsettled->count()]) }}
                            </p>
                            <a href="{{ route('operator-courier-unsettled', $courier->id) }}" class="btn btn-sm btn-outline-primary">
                                {{ __('View All Unsettled Deliveries') }}
                            </a>
                        @else
                            <p class="text-success mb-0">
                                <i class="fas fa-check-circle"></i> {{ __('All deliveries are settled.') }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
