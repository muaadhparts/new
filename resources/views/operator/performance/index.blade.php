@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Performance Monitoring') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="javascript:;">{{ __('Performance') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    @if(isset($error))
    <div class="alert alert-danger">
        <strong>{{ __('Error') }}:</strong> {{ $error }}
        <br><small>{{ __('Make sure Telescope is properly installed and migrations are run.') }}</small>
    </div>
    @else

    {{-- Period Selector --}}
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row align-items-center">
                        <div class="col-auto">
                            <label class="form-label mb-0">{{ __('Analysis Period') }}:</label>
                        </div>
                        <div class="col-auto">
                            <select name="days" class="form-control" onchange="this.form.submit()">
                                <option value="1" {{ $days == 1 ? 'selected' : '' }}>{{ __('Last 24 Hours') }}</option>
                                <option value="7" {{ $days == 7 ? 'selected' : '' }}>{{ __('Last 7 Days') }}</option>
                                <option value="14" {{ $days == 14 ? 'selected' : '' }}>{{ __('Last 14 Days') }}</option>
                                <option value="30" {{ $days == 30 ? 'selected' : '' }}>{{ __('Last 30 Days') }}</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <a href="{{ route('operator-performance-report') }}?days={{ $days }}" class="btn btn-primary">
                                <i class="fa fa-download"></i> {{ __('Download Report') }}
                            </a>
                        </div>
                        <div class="col-auto">
                            <a href="{{ url('/telescope') }}" target="_blank" class="btn btn-info">
                                <i class="fa fa-external-link"></i> {{ __('Open Telescope') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row row-cards-one mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="mycard bg1">
                <div class="left">
                    <h5 class="title">{{ __('Total Queries') }}</h5>
                    <span class="number">{{ number_format($summary['total_queries']) }}</span>
                    <small>{{ __('Slow') }}: {{ $summary['slow_queries'] }} ({{ $summary['slow_queries_percentage'] }}%)</small>
                </div>
                <div class="right d-flex align-self-center">
                    <div class="icon">
                        <i class="icofont-database"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="mycard bg2">
                <div class="left">
                    <h5 class="title">{{ __('Total Requests') }}</h5>
                    <span class="number">{{ number_format($summary['total_requests']) }}</span>
                    <small>{{ __('Slow') }}: {{ $summary['slow_requests'] }} ({{ $summary['slow_requests_percentage'] }}%)</small>
                </div>
                <div class="right d-flex align-self-center">
                    <div class="icon">
                        <i class="icofont-globe"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="mycard bg3">
                <div class="left">
                    <h5 class="title">{{ __('Avg Query Time') }}</h5>
                    <span class="number">{{ $summary['avg_query_time_ms'] }} ms</span>
                    <small>{{ __('Threshold') }}: {{ $summary['thresholds']['slow_query'] }}ms</small>
                </div>
                <div class="right d-flex align-self-center">
                    <div class="icon">
                        <i class="icofont-clock-time"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="mycard {{ $summary['exceptions_count'] > 0 ? 'bg-danger' : 'bg4' }}">
                <div class="left">
                    <h5 class="title">{{ __('Exceptions') }}</h5>
                    <span class="number">{{ number_format($summary['exceptions_count']) }}</span>
                    <small>{{ __('In last') }} {{ $days }} {{ __('days') }}</small>
                </div>
                <div class="right d-flex align-self-center">
                    <div class="icon">
                        <i class="icofont-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Slow Queries --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Slow Queries') }} (>{{ $summary['thresholds']['slow_query'] }}ms)</h5>
                    <a href="{{ route('operator-performance-slow-queries') }}?days={{ $days }}" class="btn btn-sm btn-outline-primary">{{ __('View All') }}</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>{{ __('Query') }}</th>
                                    <th width="80">{{ __('Time') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($slowQueries as $query)
                                <tr class="{{ $query['is_very_slow'] ? 'table-danger' : '' }}">
                                    <td>
                                        <code class="small" style="word-break: break-all;">
                                            {{ \Illuminate\Support\Str::limit($query['sql'], 100) }}
                                        </code>
                                    </td>
                                    <td>
                                        <span class="badge {{ $query['is_very_slow'] ? 'bg-danger' : 'bg-warning' }}">
                                            {{ $query['time'] }}ms
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">{{ __('No slow queries found') }}</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Slow Requests --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Slow Requests') }} (>{{ $summary['thresholds']['slow_request'] }}ms)</h5>
                    <a href="{{ route('operator-performance-slow-requests') }}?days={{ $days }}" class="btn btn-sm btn-outline-primary">{{ __('View All') }}</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>{{ __('Endpoint') }}</th>
                                    <th width="80">{{ __('Duration') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($slowRequests as $request)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">{{ $request['method'] }}</span>
                                        <code class="small">{{ \Illuminate\Support\Str::limit($request['uri'], 50) }}</code>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">{{ $request['duration'] }}ms</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">{{ __('No slow requests found') }}</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Slowest Endpoints --}}
    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Slowest Endpoints (Average)') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Method') }}</th>
                                    <th>{{ __('URI') }}</th>
                                    <th>{{ __('Requests') }}</th>
                                    <th>{{ __('Avg Duration') }}</th>
                                    <th>{{ __('Max Duration') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($slowestEndpoints as $endpoint)
                                <tr>
                                    <td><span class="badge bg-info">{{ $endpoint['method'] }}</span></td>
                                    <td><code>{{ $endpoint['uri'] }}</code></td>
                                    <td>{{ number_format($endpoint['request_count']) }}</td>
                                    <td>
                                        <span class="badge {{ $endpoint['avg_duration'] > 1000 ? 'bg-danger' : ($endpoint['avg_duration'] > 500 ? 'bg-warning' : 'bg-success') }}">
                                            {{ $endpoint['avg_duration'] }}ms
                                        </span>
                                    </td>
                                    <td>{{ $endpoint['max_duration'] }}ms</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">{{ __('No data available') }}</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @endif
</div>
@endsection
