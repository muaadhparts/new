@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Repeated Queries (N+1 Detection)') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator-performance') }}">{{ __('Performance') }}</a></li>
                    <li><a href="javascript:;">{{ __('Repeated Queries') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Period Selector --}}
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row align-items-center">
                        <div class="col-auto">
                            <label class="form-label mb-0">{{ __('Period') }}:</label>
                        </div>
                        <div class="col-auto">
                            <select name="days" class="form-control" onchange="this.form.submit()">
                                <option value="1" {{ $days == 1 ? 'selected' : '' }}>{{ __('Last 24 Hours') }}</option>
                                <option value="7" {{ $days == 7 ? 'selected' : '' }}>{{ __('Last 7 Days') }}</option>
                                <option value="14" {{ $days == 14 ? 'selected' : '' }}>{{ __('Last 14 Days') }}</option>
                                <option value="30" {{ $days == 30 ? 'selected' : '' }}>{{ __('Last 30 Days') }}</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i>
        {{ __('High repetition counts may indicate N+1 query problems. Consider using eager loading (with()) to optimize.') }}
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Most Repeated Queries') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th width="80">{{ __('Count') }}</th>
                                    <th>{{ __('SQL Query') }}</th>
                                    <th width="100">{{ __('Avg Time') }}</th>
                                    <th width="100">{{ __('Max Time') }}</th>
                                    <th width="100">{{ __('Total Time') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($repeatedQueries as $query)
                                <tr class="{{ $query['count'] > 100 ? 'table-danger' : ($query['count'] > 50 ? 'table-warning' : '') }}">
                                    <td>
                                        <span class="badge {{ $query['count'] > 100 ? 'bg-danger' : ($query['count'] > 50 ? 'bg-warning' : 'bg-secondary') }}">
                                            {{ number_format($query['count']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <pre class="mb-0" style="white-space: pre-wrap; font-size: 11px; max-height: 100px; overflow-y: auto;">{{ $query['sql'] }}</pre>
                                    </td>
                                    <td>{{ $query['avg_time'] }}ms</td>
                                    <td>{{ $query['max_time'] }}ms</td>
                                    <td>
                                        <strong>{{ number_format($query['total_time']) }}ms</strong>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        {{ __('No query data available') }}
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
