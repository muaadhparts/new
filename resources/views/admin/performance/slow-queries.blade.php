@extends('layouts.admin')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Slow Queries') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('admin-performance') }}">{{ __('Performance') }}</a></li>
                    <li><a href="javascript:;">{{ __('Slow Queries') }}</a></li>
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

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Slow Queries List') }} ({{ count($slowQueries) }} {{ __('queries') }})</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th width="60">{{ __('Time') }}</th>
                                    <th>{{ __('SQL Query') }}</th>
                                    <th width="100">{{ __('Connection') }}</th>
                                    <th width="150">{{ __('Date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($slowQueries as $query)
                                <tr class="{{ $query['is_very_slow'] ? 'table-danger' : 'table-warning' }}">
                                    <td>
                                        <span class="badge {{ $query['is_very_slow'] ? 'bg-danger' : 'bg-warning' }}">
                                            {{ $query['time'] }}ms
                                        </span>
                                    </td>
                                    <td>
                                        <pre class="mb-0" style="white-space: pre-wrap; font-size: 12px; max-height: 150px; overflow-y: auto;">{{ $query['sql'] }}</pre>
                                    </td>
                                    <td>{{ $query['connection'] }}</td>
                                    <td>{{ \Carbon\Carbon::parse($query['created_at'])->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="fa fa-check-circle fa-2x text-success mb-2"></i>
                                        <br>{{ __('No slow queries found in this period') }}
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
