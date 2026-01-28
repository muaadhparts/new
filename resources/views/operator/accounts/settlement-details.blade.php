@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Settlement Details') }}: {{ $batch->batch_ref }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.accounts.index') }}">{{ __('Accounts') }}</a></li>
                    <li><a href="{{ route('operator.accounts.settlements') }}">{{ __('Settlements') }}</a></li>
                    <li><a href="javascript:;">{{ $batch->batch_ref }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    <div class="row">
        <div class="col-md-8">
            {{-- Settlement Info --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-money-check me-2"></i>{{ __('Settlement Information') }}</span>
                    <span class="badge bg-{{ $batch->getStatusColor() }} fs-6">{{ $batch->getStatusNameAr() }}</span>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <td class="text-muted">{{ __('Reference') }}:</td>
                                    <td><code class="fs-5">{{ $batch->batch_ref }}</code></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">{{ __('Created Date') }}:</td>
                                    <td>{{ $batch->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                                @if($batch->settlement_date)
                                <tr>
                                    <td class="text-muted">{{ __('Settlement Date') }}:</td>
                                    <td>{{ $batch->settlement_date->format('Y-m-d') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <td class="text-muted">{{ __('Amount') }}:</td>
                                    <td><span class="fs-4 fw-bold text-success">{{ $batch->getFormattedAmount() }}</span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">{{ __('Payment Method') }}:</td>
                                    <td>{{ $batch->getPaymentMethodNameAr() }}</td>
                                </tr>
                                @if($batch->payment_reference)
                                <tr>
                                    <td class="text-muted">{{ __('Payment Reference') }}:</td>
                                    <td>{{ $batch->payment_reference }}</td>
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
                                        <i class="{{ $batch->fromParty->getIcon() }} me-2"></i>
                                        {{ $batch->fromParty->name }}
                                    </h5>
                                    <small class="text-muted">{{ $batch->fromParty->getTypeNameAr() }}</small>
                                    <br>
                                    <a href="{{ route('operator.accounts.party.statement', $batch->fromParty) }}" class="btn btn-sm btn-outline-primary mt-2">
                                        {{ __('View Account') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-center justify-content-center">
                            <div class="text-center">
                                <i class="fas fa-arrow-right fa-2x text-success"></i>
                                <br>
                                <small class="text-success">{{ $batch->getFormattedAmount() }}</small>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <small class="text-muted">{{ __('To') }}</small>
                                    <h5>
                                        <i class="{{ $batch->toParty->getIcon() }} me-2"></i>
                                        {{ $batch->toParty->name }}
                                    </h5>
                                    <small class="text-muted">{{ $batch->toParty->getTypeNameAr() }}</small>
                                    <br>
                                    <a href="{{ route('operator.accounts.party.statement', $batch->toParty) }}" class="btn btn-sm btn-outline-primary mt-2">
                                        {{ __('View Account') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Notes --}}
                    @if($batch->notes)
                    <div class="mt-4">
                        <h6>{{ __('Notes') }}</h6>
                        <p class="mb-0">{!! nl2br(e($batch->notes)) !!}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Related Ledger Entries --}}
            @if($batch->ledgerEntries && $batch->ledgerEntries->count() > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-list me-2"></i>{{ __('Related Transactions') }}
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Reference') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th class="text-end">{{ __('Amount') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($batch->ledgerEntries as $entry)
                                <tr>
                                    <td>
                                        <a href="{{ route('operator.accounts.transaction', $entry) }}">
                                            {{ $entry->transaction_ref }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $entry->getTypeNameAr() }}</span>
                                    </td>
                                    <td class="text-end">{{ $currency->formatAmount($entry->amount) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $entry->getStatusColor() }}">{{ $entry->getStatusNameAr() }}</span>
                                    </td>
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
            {{-- Status Actions --}}
            @if(!$batch->isCompleted() && !$batch->isCancelled())
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-cogs me-2"></i>{{ __('Actions') }}
                </div>
                <div class="card-body">
                    @if($batch->isDraft())
                        <p class="text-muted mb-3">{{ __('This settlement is in draft status.') }}</p>
                        <button class="btn btn-warning w-100 mb-2" disabled>
                            <i class="fas fa-paper-plane me-1"></i> {{ __('Submit for Approval') }}
                        </button>
                    @elseif($batch->isPending())
                        <p class="text-muted mb-3">{{ __('This settlement is pending approval.') }}</p>
                        <button class="btn btn-success w-100 mb-2" disabled>
                            <i class="fas fa-check me-1"></i> {{ __('Approve') }}
                        </button>
                    @endif
                    <button class="btn btn-outline-danger w-100" disabled>
                        <i class="fas fa-times me-1"></i> {{ __('Cancel') }}
                    </button>
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
                            <td>{{ $batch->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                        @if($batch->createdByUser)
                        <tr>
                            <td class="text-muted">{{ __('Created By') }}:</td>
                            <td>{{ $batch->createdByUser->name }}</td>
                        </tr>
                        @endif
                        @if($batch->approvedByUser)
                        <tr>
                            <td class="text-muted">{{ __('Approved By') }}:</td>
                            <td>{{ $batch->approvedByUser->name }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-muted">{{ __('Last Updated') }}:</td>
                            <td>{{ $batch->updated_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Back Button --}}
    <div class="mt-3">
        <a href="{{ route('operator.accounts.settlements') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> {{ __('Back to Settlements') }}
        </a>
    </div>
</div>
@endsection
