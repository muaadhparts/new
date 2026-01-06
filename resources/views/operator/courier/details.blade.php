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
            <a href="{{ route('operator-courier-create-settlement', $courier->id) }}" class="btn btn-success">
                <i class="fas fa-dollar-sign"></i> {{ __('Create Settlement') }}
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

            <!-- Recent Transactions -->
            <div class="col-lg-8 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('Recent Transactions') }}</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('Type') }}</th>
                                        <th>{{ __('Amount') }}</th>
                                        <th>{{ __('Balance After') }}</th>
                                        <th>{{ __('Date') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($transactions as $transaction)
                                    <tr>
                                        <td>
                                            @switch($transaction->type)
                                                @case('cod_collected')
                                                    <span class="badge bg-warning">{{ __('COD') }}</span>
                                                    @break
                                                @case('fee_earned')
                                                    <span class="badge bg-success">{{ __('Fee') }}</span>
                                                    @break
                                                @case('settlement_paid')
                                                    <span class="badge bg-info">{{ __('Paid') }}</span>
                                                    @break
                                                @case('settlement_received')
                                                    <span class="badge bg-primary">{{ __('Received') }}</span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td>
                                            @if($transaction->type == 'cod_collected')
                                                <span class="text-danger">-{{ $currency->sign }}{{ number_format($transaction->amount, 2) }}</span>
                                            @else
                                                <span class="text-success">+{{ $currency->sign }}{{ number_format($transaction->amount, 2) }}</span>
                                            @endif
                                        </td>
                                        <td class="{{ $transaction->balance_after >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $currency->sign }}{{ number_format($transaction->balance_after, 2) }}
                                        </td>
                                        <td>{{ $transaction->created_at->format('d-m-Y H:i') }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">{{ __('No transactions yet') }}</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Settlements -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ __('Recent Settlements') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Amount') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Payment Method') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($settlements as $settlement)
                            <tr>
                                <td>
                                    @if($settlement->type == 'pay_to_courier')
                                        <span class="badge bg-success">{{ __('Paid to Courier') }}</span>
                                    @else
                                        <span class="badge bg-warning">{{ __('Received') }}</span>
                                    @endif
                                </td>
                                <td><strong>{{ $currency->sign }}{{ number_format($settlement->amount, 2) }}</strong></td>
                                <td>
                                    @switch($settlement->status)
                                        @case('pending')
                                            <span class="badge bg-warning">{{ __('Pending') }}</span>
                                            @break
                                        @case('completed')
                                            <span class="badge bg-success">{{ __('Completed') }}</span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge bg-danger">{{ __('Cancelled') }}</span>
                                            @break
                                    @endswitch
                                </td>
                                <td>{{ $settlement->payment_method ?? '-' }}</td>
                                <td>{{ $settlement->created_at->format('d-m-Y') }}</td>
                                <td>
                                    @if($settlement->status == 'pending')
                                        <form action="{{ route('operator-settlement-process', $settlement->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="{{ __('Approve') }}">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">{{ __('No settlements yet') }}</td>
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
