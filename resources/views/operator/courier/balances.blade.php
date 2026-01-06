@extends('layouts.operator')
@section('styles')
<style>
    .balance-positive { color: var(--action-success, #28a745); }
    .balance-negative { color: var(--action-danger, #dc3545); }
    .summary-box {
        background: var(--surface-primary, #fff);
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 15px;
    }
</style>
@endsection
@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Courier Balances & Settlements') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator-courier-index') }}">{{ __('Couriers') }}</a></li>
                    <li><a href="{{ route('operator-courier-balances') }}">{{ __('Balances') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="catalogItem-area">
        <!-- Summary Cards -->
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="summary-box">
                    <h6 class="text-muted">{{ __('Total Couriers') }}</h6>
                    <h3>{{ $report['total_couriers'] ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="summary-box">
                    <h6 class="text-muted">{{ __('Couriers in Debt') }}</h6>
                    <h3 class="text-danger">{{ $report['couriers_in_debt'] ?? 0 }}</h3>
                    <small class="text-muted">{{ __('Owes to platform') }}</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="summary-box">
                    <h6 class="text-muted">{{ __('Couriers with Credit') }}</h6>
                    <h3 class="text-success">{{ $report['couriers_with_credit'] ?? 0 }}</h3>
                    <small class="text-muted">{{ __('Platform owes') }}</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="summary-box">
                    <h6 class="text-muted">{{ __('Net Platform Balance') }}</h6>
                    <h3 class="{{ ($report['total_balance'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $currency->sign }}{{ number_format(abs($report['total_balance'] ?? 0), 2) }}
                    </h3>
                    <small class="text-muted">
                        @if(($report['total_balance'] ?? 0) >= 0)
                            {{ __('Platform owes to couriers') }}
                        @else
                            {{ __('Couriers owe to platform') }}
                        @endif
                    </small>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="mb-4">
            <a href="{{ route('operator-courier-settlements') }}" class="btn btn-primary">
                <i class="fas fa-list-alt"></i> {{ __('View All Settlements') }}
            </a>
        </div>

        @include('alerts.operator.form-both')

        <!-- Couriers Table -->
        <div class="mr-table allproduct">
            <div class="table-responsive">
                <table id="muaadhtable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>{{ __('#') }}</th>
                            <th>{{ __('Courier') }}</th>
                            <th>{{ __('Current Balance') }}</th>
                            <th>{{ __('COD Collected') }}</th>
                            <th>{{ __('Fees Earned') }}</th>
                            <th>{{ __('Completed') }}</th>
                            <th>{{ __('Unsettled') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['couriers'] ?? [] as $key => $courier)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>
                                <strong>{{ $courier['courier_name'] }}</strong>
                            </td>
                            <td>
                                <span class="{{ $courier['current_balance'] >= 0 ? 'balance-positive' : 'balance-negative' }}">
                                    <strong>{{ $currency->sign }}{{ number_format($courier['current_balance'], 2) }}</strong>
                                </span>
                            </td>
                            <td>{{ $currency->sign }}{{ number_format($courier['total_collected'], 2) }}</td>
                            <td class="text-success">{{ $currency->sign }}{{ number_format($courier['total_fees_earned'], 2) }}</td>
                            <td>
                                <span class="badge bg-success">{{ $courier['deliveries_completed'] }}</span>
                            </td>
                            <td>
                                @if($courier['unsettled_deliveries'] > 0)
                                    <span class="badge bg-warning">{{ $courier['unsettled_deliveries'] }}</span>
                                @else
                                    <span class="badge bg-secondary">0</span>
                                @endif
                            </td>
                            <td>
                                @if($courier['is_in_debt'])
                                    <span class="badge bg-danger">{{ __('Owes') }}</span>
                                @elseif($courier['has_credit'])
                                    <span class="badge bg-success">{{ __('Credit') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('OK') }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('operator-courier-details', $courier['courier_id']) }}"
                                   class="btn btn-sm btn-info" title="{{ __('Details') }}">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('operator-courier-create-settlement', $courier['courier_id']) }}"
                                   class="btn btn-sm btn-success" title="{{ __('Settlement') }}">
                                    <i class="fas fa-dollar-sign"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center">{{ __('No couriers found') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
    $('#muaadhtable').DataTable({
        order: [[2, 'asc']]
    });
</script>
@endsection
