@extends('layouts.admin')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Slow Requests') }}</h4>
                <ul class="links">
                    <li><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('admin-performance') }}">{{ __('Performance') }}</a></li>
                    <li><a href="javascript:;">{{ __('Slow Requests') }}</a></li>
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
                    <h5 class="mb-0">{{ __('Slow Requests List') }} ({{ count($slowRequests) }} {{ __('requests') }})</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th width="80">{{ __('Duration') }}</th>
                                    <th width="80">{{ __('Method') }}</th>
                                    <th>{{ __('URI') }}</th>
                                    <th width="80">{{ __('Status') }}</th>
                                    <th>{{ __('Controller') }}</th>
                                    <th width="150">{{ __('Date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($slowRequests as $request)
                                <tr class="{{ $request['duration'] > 3000 ? 'table-danger' : 'table-warning' }}">
                                    <td>
                                        <span class="badge {{ $request['duration'] > 3000 ? 'bg-danger' : 'bg-warning' }}">
                                            {{ number_format($request['duration']) }}ms
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $request['method'] }}</span>
                                    </td>
                                    <td>
                                        <code>{{ $request['uri'] }}</code>
                                    </td>
                                    <td>
                                        <span class="badge {{ $request['status'] >= 400 ? 'bg-danger' : 'bg-success' }}">
                                            {{ $request['status'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ \Illuminate\Support\Str::limit($request['controller_action'], 50) }}</small>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($request['created_at'])->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fa fa-check-circle fa-2x text-success mb-2"></i>
                                        <br>{{ __('No slow requests found in this period') }}
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
