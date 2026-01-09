@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">
                    {{ __('Courier Settlement') }} #{{ $settlement->id }}
                    <a class="add-btn" href="{{ route('operator.settlement.couriers') }}">
                        <i class="fas fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </h4>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>{{ __('Settlement Details') }}</strong>
                    @if($settlement->isPending())
                        <span class="badge bg-warning fs-6">{{ __('Pending') }}</span>
                    @elseif($settlement->isCompleted())
                        <span class="badge bg-success fs-6">{{ __('Completed') }}</span>
                    @else
                        <span class="badge bg-danger fs-6">{{ __('Cancelled') }}</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>{{ $settlement->courier->name }}</h5>
                            <p class="text-muted">{{ $settlement->courier->phone ?? '' }}</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <p><strong>{{ __('Created') }}:</strong> {{ $settlement->created_at->format('d M Y H:i') }}</p>
                            @if($settlement->processed_at)
                            <p><strong>{{ __('Processed') }}:</strong> {{ $settlement->processed_at->format('d M Y H:i') }}</p>
                            @endif
                        </div>
                    </div>

                    <hr>

                    <div class="row text-center">
                        <div class="col-md-4">
                            <h5 class="{{ $settlement->isPaymentToCourier() ? 'text-success' : 'text-warning' }}">
                                {{ $settlement->getTypeLabel() }}
                            </h5>
                            <p class="text-muted">{{ __('Type') }}</p>
                        </div>
                        <div class="col-md-4">
                            <h3>{{ $currency->sign }}{{ number_format($settlement->amount, 2) }}</h3>
                            <p class="text-muted">{{ __('Amount') }}</p>
                        </div>
                        <div class="col-md-4">
                            <h5>{{ $settlement->payment_method ?? '-' }}</h5>
                            <p class="text-muted">{{ __('Payment Method') }}</p>
                        </div>
                    </div>

                    @if($settlement->reference_number)
                    <div class="alert alert-info mt-3">
                        <strong>{{ __('Reference') }}:</strong> {{ $settlement->reference_number }}
                    </div>
                    @endif

                    @if($settlement->notes)
                    <hr>
                    <p><strong>{{ __('Notes') }}:</strong></p>
                    <p class="text-muted">{{ $settlement->notes }}</p>
                    @endif
                </div>
            </div>

            {{-- Transactions --}}
            @if($settlement->transactions->count() > 0)
            <div class="card">
                <div class="card-header">
                    <strong>{{ __('Related Transactions') }}</strong>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Type') }}</th>
                                    <th class="text-end">{{ __('Amount') }}</th>
                                    <th class="text-end">{{ __('Balance Before') }}</th>
                                    <th class="text-end">{{ __('Balance After') }}</th>
                                    <th>{{ __('Date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($settlement->transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->getTypeLabel() }}</td>
                                    <td class="text-end">{{ $currency->sign }}{{ number_format($transaction->amount, 2) }}</td>
                                    <td class="text-end">{{ $currency->sign }}{{ number_format($transaction->balance_before, 2) }}</td>
                                    <td class="text-end">{{ $currency->sign }}{{ number_format($transaction->balance_after, 2) }}</td>
                                    <td>{{ $transaction->created_at->format('d M Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-4">
            {{-- Actions --}}
            <div class="card">
                <div class="card-header">
                    <strong>{{ __('Actions') }}</strong>
                </div>
                <div class="card-body">
                    @if($settlement->isPending())
                        <form action="{{ route('operator.settlement.courier.process', $settlement->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-check me-2"></i> {{ __('Process Settlement') }}
                            </button>
                        </form>

                        <form action="{{ route('operator.settlement.courier.cancel', $settlement->id) }}" method="POST"
                              onsubmit="return confirm('{{ __('Are you sure?') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="fas fa-times me-2"></i> {{ __('Cancel Settlement') }}
                            </button>
                        </form>
                    @endif

                    @if($settlement->isCompleted())
                        <div class="alert alert-success text-center mb-0">
                            <i class="fas fa-check-circle fa-3x mb-2"></i>
                            <h5>{{ __('Settlement Completed') }}</h5>
                            <p class="mb-0">{{ $settlement->processed_at->format('d M Y H:i') }}</p>
                        </div>
                    @endif

                    @if($settlement->isCancelled())
                        <div class="alert alert-danger text-center mb-0">
                            <i class="fas fa-times-circle fa-3x mb-2"></i>
                            <h5>{{ __('Settlement Cancelled') }}</h5>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
