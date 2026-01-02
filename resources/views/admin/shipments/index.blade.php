@extends('layouts.admin')

@section('content')
<input type="hidden" id="headerdata" value="{{ __('SHIPMENTS') }}">

<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Shipments Management') }}</h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Shipments') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body text-center py-3">
                    <i class="fas fa-shipping-fast fa-2x mb-2"></i>
                    <h3 class="mb-0">{{ $stats['total'] }}</h3>
                    <small>{{ __('Total') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center py-3">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <h3 class="mb-0">{{ $stats['delivered'] }}</h3>
                    <small>{{ __('Delivered') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning">
                <div class="card-body text-center py-3">
                    <i class="fas fa-truck fa-2x mb-2"></i>
                    <h3 class="mb-0">{{ $stats['in_transit'] }}</h3>
                    <small>{{ __('In Transit') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body text-center py-3">
                    <i class="fas fa-motorcycle fa-2x mb-2"></i>
                    <h3 class="mb-0">{{ $stats['out_for_delivery'] }}</h3>
                    <small>{{ __('Out for Delivery') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body text-center py-3">
                    <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                    <h3 class="mb-0">{{ $stats['failed'] }}</h3>
                    <small>{{ __('Failed') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center py-3">
                    <i class="fas fa-undo fa-2x mb-2"></i>
                    <h3 class="mb-0">{{ $stats['returned'] }}</h3>
                    <small>{{ __('Returned') }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Rate -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>{{ __('Success Rate') }}</span>
                        <span class="font-weight-bold text-success">{{ $stats['success_rate'] }}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" role="progressbar"
                             style="width: {{ $stats['success_rate'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.shipments.index') }}" method="GET" class="row align-items-end">
                <div class="col-md-2">
                    <label class="form-label">{{ __('Status') }}</label>
                    <select name="status" class="form-control">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="created" {{ $status == 'created' ? 'selected' : '' }}>{{ __('Created') }}</option>
                        <option value="picked_up" {{ $status == 'picked_up' ? 'selected' : '' }}>{{ __('Picked Up') }}</option>
                        <option value="in_transit" {{ $status == 'in_transit' ? 'selected' : '' }}>{{ __('In Transit') }}</option>
                        <option value="out_for_delivery" {{ $status == 'out_for_delivery' ? 'selected' : '' }}>{{ __('Out for Delivery') }}</option>
                        <option value="delivered" {{ $status == 'delivered' ? 'selected' : '' }}>{{ __('Delivered') }}</option>
                        <option value="failed" {{ $status == 'failed' ? 'selected' : '' }}>{{ __('Failed') }}</option>
                        <option value="returned" {{ $status == 'returned' ? 'selected' : '' }}>{{ __('Returned') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Merchant') }}</label>
                    <select name="merchant_id" class="form-control">
                        <option value="">{{ __('All Merchants') }}</option>
                        @foreach($merchants as $vendor)
                            <option value="{{ $vendor->id }}" {{ $merchantId == $vendor->id ? 'selected' : '' }}>
                                {{ $vendor->shop_name ?? $vendor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('From Date') }}</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('To Date') }}</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Search') }}</label>
                    <input type="text" name="search" class="form-control" placeholder="{{ __('Tracking/Order #') }}" value="{{ $search }}">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> {{ __('Filter') }}
                    </button>
                    <a href="{{ route('admin.shipments.export', request()->all()) }}" class="btn btn-outline-success">
                        <i class="fas fa-download"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Shipments Table -->
    <div class="product-area">
        <div class="row">
            <div class="col-lg-12">
                <div class="mr-table allproduct">
                    @include('alerts.admin.form-success')
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('Tracking Number') }}</th>
                                    <th>{{ __('Order') }}</th>
                                    <th>{{ __('Vendor') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Company') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($shipments as $shipment)
                                    @php
                                        $statusColors = [
                                            'created' => 'info',
                                            'picked_up' => 'primary',
                                            'in_transit' => 'warning',
                                            'out_for_delivery' => 'warning',
                                            'delivered' => 'success',
                                            'failed' => 'danger',
                                            'returned' => 'secondary',
                                            'cancelled' => 'dark',
                                        ];
                                        $color = $statusColors[$shipment->status] ?? 'info';
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.shipments.show', $shipment->tracking_number) }}"
                                               class="font-weight-bold text-primary">
                                                {{ $shipment->tracking_number }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($shipment->order)
                                                <a href="{{ route('admin-purchase-show', $shipment->order->id) }}">
                                                    {{ $shipment->order->purchase_number }}
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>{{ $shipment->vendor->shop_name ?? 'N/A' }}</td>
                                        <td>{{ $shipment->order->customer_name ?? 'N/A' }}</td>
                                        <td>{{ $shipment->company_name }}</td>
                                        <td>
                                            <span class="badge badge-{{ $color }}">
                                                {{ $shipment->status_ar }}
                                            </span>
                                        </td>
                                        <td>{{ $shipment->status_date?->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.shipments.show', $shipment->tracking_number) }}"
                                                   class="btn btn-outline-primary" title="{{ __('View') }}">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.shipments.refresh', $shipment->tracking_number) }}"
                                                   class="btn btn-outline-info" title="{{ __('Refresh') }}">
                                                    <i class="fas fa-sync-alt"></i>
                                                </a>
                                                @if(!in_array($shipment->status, ['delivered', 'cancelled', 'returned']))
                                                    <button type="button" class="btn btn-outline-danger"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#cancelModal{{ $shipment->tracking_number }}"
                                                            title="{{ __('Cancel') }}">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                @endif
                                            </div>

                                            <!-- Cancel Modal -->
                                            <div class="modal fade" id="cancelModal{{ $shipment->tracking_number }}" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form action="{{ route('admin.shipments.cancel', $shipment->tracking_number) }}" method="POST">
                                                            @csrf
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">{{ __('Cancel Shipment') }}</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal">
                                                                    <span>&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>{{ __('Are you sure you want to cancel this shipment?') }}</p>
                                                                <p><strong>{{ __('Tracking') }}: </strong>{{ $shipment->tracking_number }}</p>
                                                                <div class="form-group">
                                                                    <label>{{ __('Reason') }}</label>
                                                                    <textarea name="reason" class="form-control" rows="2"
                                                                              placeholder="{{ __('Enter cancellation reason') }}"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                    {{ __('Close') }}
                                                                </button>
                                                                <button type="submit" class="btn btn-danger">
                                                                    {{ __('Cancel Shipment') }}
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">{{ __('No shipments found') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $shipments->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('admin.shipments.reports') }}" class="btn btn-info">
                        <i class="fas fa-chart-bar mr-1"></i> {{ __('View Reports') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
