@extends('layouts.operator')
@section('styles')
<style>
    .stat-card {
        background: var(--surface-primary, #fff);
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 15px;
    }
    .stat-card h6 { color: #6c757d; font-size: 12px; margin-bottom: 5px; }
    .stat-card h4 { margin: 0; }
</style>
@endsection
@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Courier Details') }}: {{ $courier->name }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator-courier-balances') }}">{{ __('Courier Balances') }}</a></li>
                    <li><a href="#">{{ $courier->name }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="catalogItem-area">
        @include('alerts.operator.form-both')

        <!-- Summary Stats -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card {{ $report['current_balance'] >= 0 ? 'border-start border-success border-3' : 'border-start border-danger border-3' }}">
                    <h6>{{ __('Current Balance') }}</h6>
                    <h4 class="{{ $report['current_balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $currency->sign }}{{ number_format($report['current_balance'], 2) }}
                    </h4>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <h6>{{ __('COD Collected') }}</h6>
                    <h4 class="text-warning">{{ $currency->sign }}{{ number_format($report['total_collected'], 2) }}</h4>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <h6>{{ __('Fees Earned') }}</h6>
                    <h4 class="text-success">{{ $currency->sign }}{{ number_format($report['total_fees_earned'], 2) }}</h4>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <h6>{{ __('Total Deliveries') }}</h6>
                    <h4>{{ $report['deliveries_count'] }}</h4>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mb-4">
            <a href="{{ route('operator.accounts.couriers') }}" class="btn btn-success">
                <i class="fas fa-dollar-sign"></i> {{ __('Manage Settlements') }}
            </a>
            <a href="{{ route('operator-courier-unsettled', $courier->id) }}" class="btn btn-warning">
                <i class="fas fa-list"></i> {{ __('Unsettled Deliveries') }}
                <span class="badge bg-dark">{{ $report['unsettled_deliveries'] }}</span>
            </a>
            <a href="{{ route('operator-courier-show', $courier->id) }}" class="btn btn-info">
                <i class="fas fa-user"></i> {{ __('View Profile') }}
            </a>
        </div>

        <div class="row">
            <!-- Courier Info -->
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('Courier Information') }}</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless table-sm">
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
                                <td><strong>{{ __('Status') }}:</strong></td>
                                <td>
                                    @if($courier->status == 1)
                                        <span class="badge bg-success">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        <hr>

                        <h6>{{ __('Delivery Stats') }}</h6>
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td>{{ __('Completed') }}:</td>
                                <td><span class="badge bg-success">{{ $report['deliveries_completed'] }}</span></td>
                            </tr>
                            <tr>
                                <td>{{ __('Pending') }}:</td>
                                <td><span class="badge bg-warning">{{ $report['deliveries_pending'] }}</span></td>
                            </tr>
                            <tr>
                                <td>{{ __('COD Orders') }}:</td>
                                <td><span class="badge bg-info">{{ $report['cod_deliveries'] }}</span></td>
                            </tr>
                            <tr>
                                <td>{{ __('Online Orders') }}:</td>
                                <td><span class="badge bg-secondary">{{ $report['online_deliveries'] }}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Deliveries -->
            <div class="col-lg-8 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('Recent Deliveries') }}</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('Purchase') }}</th>
                                        <th>{{ __('Payment') }}</th>
                                        <th>{{ __('Amount') }}</th>
                                        <th>{{ __('Fee') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Date') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentDeliveries as $delivery)
                                    <tr>
                                        <td>
                                            @if($delivery->purchase)
                                                {{ $delivery->purchase->purchase_number }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($delivery->payment_method === 'cod')
                                                <span class="badge bg-warning">{{ __('COD') }}</span>
                                            @else
                                                <span class="badge bg-success">{{ __('Online') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $currency->sign }}{{ number_format($delivery->purchase_amount ?? 0, 2) }}</td>
                                        <td class="text-success">{{ $currency->sign }}{{ number_format($delivery->delivery_fee ?? 0, 2) }}</td>
                                        <td>
                                            @if($delivery->isDelivered() || $delivery->isConfirmed())
                                                <span class="badge bg-success">{{ __('Delivered') }}</span>
                                            @elseif($delivery->isPickedUp())
                                                <span class="badge bg-primary">{{ __('In Transit') }}</span>
                                            @elseif($delivery->isReadyForPickup())
                                                <span class="badge bg-info">{{ __('Ready') }}</span>
                                            @elseif($delivery->isPendingApproval())
                                                <span class="badge bg-warning">{{ __('Pending') }}</span>
                                            @elseif($delivery->isRejected())
                                                <span class="badge bg-danger">{{ __('Rejected') }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $delivery->status }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $delivery->created_at->format('d-m-Y H:i') }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">{{ __('No deliveries yet') }}</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settlement Info Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('Settlement Summary') }}</h5>
                <a href="{{ route('operator.accounts.couriers') }}" class="btn btn-sm btn-primary">
                    {{ __('Go to Accounting') }} <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <h6 class="text-muted">{{ __('COD Collected (Unsettled)') }}</h6>
                        <h4 class="text-danger">{{ $currency->sign }}{{ number_format($settlementCalc['cod_amount'] ?? 0, 2) }}</h4>
                    </div>
                    <div class="col-md-3">
                        <h6 class="text-muted">{{ __('Fees Earned (Online)') }}</h6>
                        <h4 class="text-success">{{ $currency->sign }}{{ number_format($settlementCalc['fees_earned_online'] ?? 0, 2) }}</h4>
                    </div>
                    <div class="col-md-3">
                        <h6 class="text-muted">{{ __('Fees Earned (COD)') }}</h6>
                        <h4 class="text-success">{{ $currency->sign }}{{ number_format($settlementCalc['fees_earned_cod'] ?? 0, 2) }}</h4>
                    </div>
                    <div class="col-md-3">
                        <h6 class="text-muted">{{ __('Net Amount') }}</h6>
                        @php $netAmount = $settlementCalc['net_amount'] ?? 0; @endphp
                        <h4 class="{{ $netAmount >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $currency->sign }}{{ number_format(abs($netAmount), 2) }}
                            <small class="d-block text-muted fs-6">
                                @if($netAmount >= 0)
                                    {{ __('(Platform owes)') }}
                                @else
                                    {{ __('(Courier owes)') }}
                                @endif
                            </small>
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
