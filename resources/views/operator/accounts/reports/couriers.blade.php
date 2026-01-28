@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Couriers Financial Report') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.accounts.index') }}">{{ __('Accounts') }}</a></li>
                    <li><a href="javascript:;">{{ __('Couriers Report') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('operator.accounts.reports.couriers') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">{{ __('From Date') }}</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('To Date') }}</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                    <a href="{{ route('operator.accounts.reports.couriers') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Totals Summary - Pre-computed in controller (DATA_FLOW_POLICY) --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Fees Earned') }}</h6>
                    <h3 class="mb-0">{{ $reportDisplay['totals']['fees_earned_formatted'] }}</h3>
                    <small>{{ __('Delivery fees') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h6>{{ __('COD Collected') }}</h6>
                    <h3 class="mb-0">{{ $reportDisplay['totals']['cod_collected_formatted'] }}</h3>
                    <small>{{ __('Already collected') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h6>{{ __('COD Pending') }}</h6>
                    <h3 class="mb-0">{{ $reportDisplay['totals']['cod_pending_formatted'] }}</h3>
                    <small>{{ __('Not yet collected') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h6>{{ __('Owes to Platform') }}</h6>
                    <h3 class="mb-0">{{ $reportDisplay['totals']['owes_to_platform_formatted'] }}</h3>
                    <small>{{ __('Pending settlement') }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Couriers Table --}}
    <div class="card">
        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-motorcycle me-2"></i>{{ __('All Couriers') }}</strong>
            <span class="badge bg-dark text-white">{{ $reportDisplay['couriers']->count() }} {{ __('Couriers') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Courier') }}</th>
                            <th class="text-end">{{ __('Fees Earned') }}</th>
                            <th class="text-end">{{ __('COD Collected') }}</th>
                            <th class="text-end">{{ __('COD Pending') }}</th>
                            <th class="text-end">{{ __('Settlements Made') }}</th>
                            <th class="text-end">{{ __('Owes to Platform') }}</th>
                            <th class="text-center">{{ __('Deliveries') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportDisplay['couriers'] as $data)
                        <tr>
                            <td>
                                <strong>{{ $data['courier']->name }}</strong>
                                <br><small class="text-muted">{{ $data['courier']->code }}</small>
                            </td>
                            <td class="text-end text-success fw-bold">{{ $data['fees_earned_formatted'] }}</td>
                            <td class="text-end text-info">{{ $data['cod_collected_formatted'] }}</td>
                            <td class="text-end {{ $data['cod_pending'] > 0 ? 'text-warning fw-bold' : '' }}">
                                {{ $data['cod_pending_formatted'] }}
                            </td>
                            <td class="text-end text-muted">{{ $data['settlements_made_formatted'] }}</td>
                            <td class="text-end {{ $data['owes_to_platform'] > 0 ? 'text-danger fw-bold' : 'text-success' }}">
                                {{ $data['owes_to_platform_formatted'] }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $data['delivery_count'] }}</span>
                            </td>
                            <td>
                                <a href="{{ route('operator.accounts.party.statement', $data['courier']->id) }}"
                                   class="btn btn-sm btn-outline-primary" name="{{ __('View Statement') }}">
                                    <i class="fas fa-file-invoice"></i>
                                </a>
                                @if($data['owes_to_platform'] > 0)
                                <a href="{{ route('operator.accounts.settlements.create', ['party_id' => $data['courier']->id]) }}"
                                   class="btn btn-sm btn-outline-success" name="{{ __('Record Settlement') }}">
                                    <i class="fas fa-money-bill"></i>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                {{ __('No couriers found') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($reportDisplay['couriers']->count() > 0)
                    <tfoot class="table-dark">
                        <tr class="fw-bold">
                            <th>{{ __('Total') }}</th>
                            <th class="text-end">{{ $reportDisplay['totals']['fees_earned_formatted'] }}</th>
                            <th class="text-end">{{ $reportDisplay['totals']['cod_collected_formatted'] }}</th>
                            <th class="text-end">{{ $reportDisplay['totals']['cod_pending_formatted'] }}</th>
                            <th class="text-end">{{ $reportDisplay['totals']['settlements_made_formatted'] }}</th>
                            <th class="text-end">{{ $reportDisplay['totals']['owes_to_platform_formatted'] }}</th>
                            <th class="text-center">{{ $reportDisplay['totals']['delivery_count'] }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Back Button --}}
    <div class="mt-3">
        <a href="{{ route('operator.accounts.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> {{ __('Back to Accounts') }}
        </a>
    </div>
</div>
@endsection
