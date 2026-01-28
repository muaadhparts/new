@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Transaction Details') }}: {{ $transaction->transaction_ref }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.accounts.index') }}">{{ __('Accounts') }}</a></li>
                    <li><a href="javascript:;">{{ $transaction->transaction_ref }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    <div class="row">
        {{-- Transaction Info --}}
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-exchange-alt me-2"></i>{{ __('Transaction Information') }}
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <td class="text-muted">{{ __('Reference') }}:</td>
                                    <td><code class="fs-5">{{ $transaction->transaction_ref }}</code></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">{{ __('Date') }}:</td>
                                    <td>{{ $transaction->transaction_date->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">{{ __('Type') }}:</td>
                                    <td><span class="badge bg-secondary fs-6">{{ $transaction->getTypeNameAr() }}</span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">{{ __('Status') }}:</td>
                                    <td><span class="badge bg-{{ $transaction->getStatusColor() }} fs-6">{{ $transaction->getStatusNameAr() }}</span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <td class="text-muted">{{ __('Amount') }}:</td>
                                    <td><span class="fs-4 fw-bold">{{ $currency->formatAmount($transaction->amount) }}</span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">{{ __('Currency') }}:</td>
                                    <td>{{ $transaction->currency }}</td>
                                </tr>
                                @if($transaction->settled_at)
                                <tr>
                                    <td class="text-muted">{{ __('Settled At') }}:</td>
                                    <td>{{ $transaction->settled_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    {{-- Parties --}}
                    <div class="row">
                        <div class="col-md-5">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <small class="text-muted">{{ __('From') }}</small>
                                    <h5>
                                        <i class="{{ $transaction->fromParty->getIcon() }} me-2"></i>
                                        {{ $transaction->fromParty->name }}
                                    </h5>
                                    <small class="text-muted">{{ $transaction->fromParty->getTypeNameAr() }}</small>
                                    <br>
                                    <a href="{{ route('operator.accounts.party.statement', $transaction->fromParty) }}" class="btn btn-sm btn-outline-primary mt-2">
                                        {{ __('View Account') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-center justify-content-center">
                            <i class="fas fa-arrow-right fa-2x text-primary"></i>
                        </div>
                        <div class="col-md-5">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <small class="text-muted">{{ __('To') }}</small>
                                    <h5>
                                        <i class="{{ $transaction->toParty->getIcon() }} me-2"></i>
                                        {{ $transaction->toParty->name }}
                                    </h5>
                                    <small class="text-muted">{{ $transaction->toParty->getTypeNameAr() }}</small>
                                    <br>
                                    <a href="{{ route('operator.accounts.party.statement', $transaction->toParty) }}" class="btn btn-sm btn-outline-primary mt-2">
                                        {{ __('View Account') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Description --}}
                    @if($transaction->description || $transaction->description_ar)
                    <div class="mt-4">
                        <h6>{{ __('Description') }}</h6>
                        <p class="mb-1">{{ $transaction->description_ar }}</p>
                        @if($transaction->description && $transaction->description != $transaction->description_ar)
                            <small class="text-muted">{{ $transaction->description }}</small>
                        @endif
                    </div>
                    @endif
                </div>
            </div>

            {{-- Related Purchase --}}
            @if($transaction->purchase || $transaction->merchantPurchase)
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-shopping-cart me-2"></i>{{ __('Related Purchase') }}
                </div>
                <div class="card-body">
                    @if($transaction->merchantPurchase)
                    <table class="table table-sm">
                        <tr>
                            <td class="text-muted">{{ __('Purchase Number') }}:</td>
                            <td>
                                <a href="{{ route('operator-purchase-show', $transaction->merchantPurchase->purchase_id) }}">
                                    {{ $transaction->merchantPurchase->purchase_number }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Merchant') }}:</td>
                            <td>{{ $transaction->merchantPurchase->merchant->shop_name ?? $transaction->merchantPurchase->merchant->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Total Amount') }}:</td>
                            <td>{{ $currency->formatAmount($transaction->merchantPurchase->total) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Payment Status') }}:</td>
                            <td>{{ $transaction->merchantPurchase->payment_status }}</td>
                        </tr>
                    </table>
                    @elseif($transaction->purchase)
                    <table class="table table-sm">
                        <tr>
                            <td class="text-muted">{{ __('Purchase Number') }}:</td>
                            <td>
                                <a href="{{ route('operator-purchase-show', $transaction->purchase_id) }}">
                                    {{ $transaction->purchase->purchase_number }}
                                </a>
                            </td>
                        </tr>
                    </table>
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- Metadata & Actions --}}
        <div class="col-md-4">
            {{-- Metadata --}}
            @if($transaction->metadata && count($transaction->metadata) > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-2"></i>{{ __('Additional Data') }}
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        @foreach($transaction->metadata as $key => $value)
                        <tr>
                            <td class="text-muted">{{ ucwords(str_replace('_', ' ', $key)) }}:</td>
                            <td>
                                @if(is_array($value))
                                    <pre class="mb-0">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </table>
                </div>
            </div>
            @endif

            {{-- Settlement Batch --}}
            @if($transaction->settlementBatch)
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-file-invoice-dollar me-2"></i>{{ __('Settlement Batch') }}
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="text-muted">{{ __('Batch Ref') }}:</td>
                            <td>
                                <a href="{{ route('operator.accounts.settlements.show', $transaction->settlementBatch) }}">
                                    {{ $transaction->settlementBatch->batch_ref }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Status') }}:</td>
                            <td>
                                <span class="badge bg-{{ $transaction->settlementBatch->getStatusColor() }}">
                                    {{ $transaction->settlementBatch->getStatusNameAr() }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            @endif

            {{-- Audit Info --}}
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-history me-2"></i>{{ __('Audit Information') }}
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="text-muted">{{ __('Created At') }}:</td>
                            <td>{{ $transaction->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        @if($transaction->createdByUser)
                        <tr>
                            <td class="text-muted">{{ __('Created By') }}:</td>
                            <td>{{ $transaction->createdByUser->name }}</td>
                        </tr>
                        @endif
                        @if($transaction->settledByUser)
                        <tr>
                            <td class="text-muted">{{ __('Settled By') }}:</td>
                            <td>{{ $transaction->settledByUser->name }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Back Button --}}
    <div class="mt-3">
        <a href="{{ url()->previous() }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> {{ __('Back') }}
        </a>
    </div>
</div>
@endsection
