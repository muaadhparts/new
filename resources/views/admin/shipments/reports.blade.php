@extends('layouts.admin')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">
                    <a href="{{ route('admin.shipments.index') }}" class="btn btn-secondary btn-sm mr-2">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    {{ __('Shipping Reports') }}
                </h4>
                <ul class="links">
                    <li><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('admin.shipments.index') }}">{{ __('Shipments') }}</a></li>
                    <li><a href="javascript:;">{{ __('Reports') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.shipments.reports') }}" method="GET" class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label">{{ __('Period') }}</label>
                    <select name="period" class="form-control">
                        <option value="today" {{ $period == 'today' ? 'selected' : '' }}>{{ __('Today') }}</option>
                        <option value="week" {{ $period == 'week' ? 'selected' : '' }}>{{ __('This Week') }}</option>
                        <option value="month" {{ $period == 'month' ? 'selected' : '' }}>{{ __('This Month') }}</option>
                        <option value="quarter" {{ $period == 'quarter' ? 'selected' : '' }}>{{ __('This Quarter') }}</option>
                        <option value="year" {{ $period == 'year' ? 'selected' : '' }}>{{ __('This Year') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Vendor') }}</label>
                    <select name="vendor_id" class="form-control">
                        <option value="">{{ __('All Vendors') }}</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ $vendorId == $vendor->id ? 'selected' : '' }}>
                                {{ $vendor->shop_name ?? $vendor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter mr-1"></i> {{ __('Apply') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center py-4">
                    <i class="fas fa-shipping-fast fa-3x mb-3"></i>
                    <h2 class="mb-0">{{ $total }}</h2>
                    <p class="mb-0">{{ __('Total Shipments') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center py-4">
                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                    <h2 class="mb-0">{{ $statusDistribution['delivered'] }}</h2>
                    <p class="mb-0">{{ __('Delivered') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center py-4">
                    <i class="fas fa-times-circle fa-3x mb-3"></i>
                    <h2 class="mb-0">{{ $statusDistribution['failed'] + $statusDistribution['returned'] }}</h2>
                    <p class="mb-0">{{ __('Failed/Returned') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center py-4">
                    <i class="fas fa-percentage fa-3x mb-3"></i>
                    <h2 class="mb-0">{{ $successRate }}%</h2>
                    <p class="mb-0">{{ __('Success Rate') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Status Distribution Chart -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie mr-2"></i>{{ __('Status Distribution') }}</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Daily Trend Chart -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line mr-2"></i>{{ __('Daily Shipments') }}</h5>
                </div>
                <div class="card-body">
                    <canvas id="dailyChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top Vendors -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-store mr-2"></i>{{ __('Top Vendors by Shipments') }}</h5>
                </div>
                <div class="card-body">
                    @if($topVendors->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('Vendor') }}</th>
                                        <th class="text-right">{{ __('Shipments') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topVendors as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $item->vendor->shop_name ?? $item->vendor->name ?? 'N/A' }}</td>
                                            <td class="text-right">
                                                <span class="badge badge-primary">{{ $item->total }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">{{ __('No data available') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Companies Performance -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-truck mr-2"></i>{{ __('Shipping Companies Performance') }}</h5>
                </div>
                <div class="card-body">
                    @if(count($companiesPerformance) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>{{ __('Company') }}</th>
                                        <th class="text-center">{{ __('Delivered') }}</th>
                                        <th class="text-center">{{ __('Failed') }}</th>
                                        <th class="text-center">{{ __('Success %') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($companiesPerformance as $company => $statuses)
                                        @php
                                            $delivered = $statuses->where('status', 'delivered')->first()->count ?? 0;
                                            $failed = $statuses->where('status', 'failed')->first()->count ?? 0;
                                            $returned = $statuses->where('status', 'returned')->first()->count ?? 0;
                                            $companyTotal = $statuses->sum('count');
                                            $companySuccess = $companyTotal > 0 ? round(($delivered / $companyTotal) * 100) : 0;
                                        @endphp
                                        <tr>
                                            <td>{{ $company ?? 'Unknown' }}</td>
                                            <td class="text-center">
                                                <span class="badge badge-success">{{ $delivered }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-danger">{{ $failed + $returned }}</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-{{ $companySuccess >= 80 ? 'success' : ($companySuccess >= 60 ? 'warning' : 'danger') }}"
                                                         role="progressbar" style="width: {{ $companySuccess }}%">
                                                        {{ $companySuccess }}%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">{{ __('No data available') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Statistics -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-list-alt mr-2"></i>{{ __('Detailed Status Breakdown') }}</h5>
        </div>
        <div class="card-body">
            <div class="row">
                @php
                    $statusLabels = [
                        'created' => ['label' => __('Created'), 'color' => 'info', 'icon' => 'fa-box'],
                        'picked_up' => ['label' => __('Picked Up'), 'color' => 'primary', 'icon' => 'fa-truck-loading'],
                        'in_transit' => ['label' => __('In Transit'), 'color' => 'warning', 'icon' => 'fa-truck'],
                        'out_for_delivery' => ['label' => __('Out for Delivery'), 'color' => 'warning', 'icon' => 'fa-motorcycle'],
                        'delivered' => ['label' => __('Delivered'), 'color' => 'success', 'icon' => 'fa-check-circle'],
                        'failed' => ['label' => __('Failed'), 'color' => 'danger', 'icon' => 'fa-exclamation-circle'],
                        'returned' => ['label' => __('Returned'), 'color' => 'secondary', 'icon' => 'fa-undo'],
                        'cancelled' => ['label' => __('Cancelled'), 'color' => 'dark', 'icon' => 'fa-times-circle'],
                    ];
                @endphp
                @foreach($statusLabels as $status => $info)
                    <div class="col-md-3 mb-3">
                        <div class="d-flex align-items-center p-3 border rounded">
                            <div class="bg-{{ $info['color'] }} text-white rounded-circle p-2 mr-3 d-flex align-items-center justify-content-center"
                                 style="width: 45px; height: 45px;">
                                <i class="fas {{ $info['icon'] }}"></i>
                            </div>
                            <div>
                                <h4 class="mb-0">{{ $overallStats[$status] ?? 0 }}</h4>
                                <small class="text-muted">{{ $info['label'] }}</small>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Status Distribution Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['{{ __("Delivered") }}', '{{ __("In Transit") }}', '{{ __("Failed") }}', '{{ __("Returned") }}', '{{ __("Cancelled") }}'],
        datasets: [{
            data: [{{ $statusDistribution['delivered'] }}, {{ $statusDistribution['in_transit'] }}, {{ $statusDistribution['failed'] }}, {{ $statusDistribution['returned'] }}, {{ $statusDistribution['cancelled'] }}],
            backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#6c757d', '#343a40'],
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Daily Shipments Chart
const dailyCtx = document.getElementById('dailyChart').getContext('2d');
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($dailyShipments->pluck('date')->toArray()) !!},
        datasets: [{
            label: '{{ __("Shipments") }}',
            data: {!! json_encode($dailyShipments->pluck('count')->toArray()) !!},
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
@endpush
@endsection
