@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Withdraw Fee Income') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="javascript:;">{{ __('Withdraw Fee Income') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @include('alerts.operator.form-both')

    {{-- Summary Cards --}}
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center">
                    <h6 class="mb-2">{{ __('Total Withdraw Fees') }}</h6>
                    <h3 class="mb-0">{{ $total }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body text-center">
                    <h6 class="mb-2">{{ __('Current Month') }}</h6>
                    <h3 class="mb-0">{{ $current_month }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center">
                    <h6 class="mb-2">{{ __('Last 30 Days') }}</h6>
                    <h3 class="mb-0">{{ $last_30_days }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Date Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('operator-withdraw-income') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('From Date') }}</label>
                        <input type="text" class="form-control discount_date" name="start_date"
                               placeholder="{{ __('From Date') }}" value="{{ $start_date }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('To Date') }}</label>
                        <input type="text" class="form-control discount_date" name="end_date"
                               placeholder="{{ __('To Date') }}" value="{{ $end_date }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                        <a href="{{ route('operator-withdraw-income') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Withdraw Transactions --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>{{ __('Withdraw Fee Transactions') }}</strong>
            <span class="badge bg-primary">{{ $withdraws->count() }} {{ __('Transactions') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="withdrawTable">
                    <thead>
                        <tr>
                            <th>{{ __('User') }}</th>
                            <th class="text-end">{{ __('Withdraw Amount') }}</th>
                            <th class="text-end">{{ __('Fee') }}</th>
                            <th>{{ __('Method') }}</th>
                            <th>{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($withdraws as $withdraw)
                        <tr>
                            <td>{{ $withdraw->user?->name ?? '-' }}</td>
                            <td class="text-end">{{ $currency->formatAmount($withdraw->amount) }}</td>
                            <td class="text-end text-success">{{ $currency->formatAmount($withdraw->fee) }}</td>
                            <td>{{ $withdraw->method ?? '-' }}</td>
                            <td>{{ $withdraw->created_at->format('d-m-Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">{{ __('No withdraw transactions found') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script type="text/javascript">
    $(".discount_date").datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'yy-mm-dd'
    });

    if ($.fn.DataTable) {
        $('#withdrawTable').DataTable({
            order: [[4, 'desc']],
            pageLength: 25
        });
    }
</script>
@endsection
