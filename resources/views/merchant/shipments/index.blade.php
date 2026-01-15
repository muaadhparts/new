@extends('layouts.merchant')

@section('content')
<div class="gs-merchant-outlet">
    <!-- Breadcrumb -->
    <div class="gs-merchant-breadcrumb has-mb">
        <div class="gs-topup-name d-flex align-items-center gap-4">
            <h4 class="text-capitalize">@lang('Shipments Management')</h4>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center">
                    <i class="fas fa-shipping-fast fa-2x mb-2"></i>
                    <h3 class="mb-0">{{ $stats['total'] }}</h3>
                    <small>@lang('Total Shipments')</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <h3 class="mb-0">{{ $stats['delivered'] }}</h3>
                    <small>@lang('Delivered')</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body text-center">
                    <i class="fas fa-truck fa-2x mb-2"></i>
                    <h3 class="mb-0">{{ $stats['in_transit'] }}</h3>
                    <small>@lang('In Transit')</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white h-100">
                <div class="card-body text-center">
                    <i class="fas fa-motorcycle fa-2x mb-2"></i>
                    <h3 class="mb-0">{{ $stats['out_for_delivery'] }}</h3>
                    <small>@lang('Out for Delivery')</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white h-100">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                    <h3 class="mb-0">{{ $stats['failed'] }}</h3>
                    <small>@lang('Failed')</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white h-100">
                <div class="card-body text-center">
                    <i class="fas fa-undo fa-2x mb-2"></i>
                    <h3 class="mb-0">{{ $stats['returned'] }}</h3>
                    <small>@lang('Returned')</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Rate -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>@lang('Success Rate')</span>
                        <span class="fw-bold text-success">{{ $stats['success_rate'] }}%</span>
                    </div>
                    <div class="progress" style="height: 10px;">
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
            <form action="{{ route('merchant.shipments.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">@lang('Status')</label>
                    <select name="status" class="form-select">
                        <option value="">@lang('All Statuses')</option>
                        <option value="created" {{ $status == 'created' ? 'selected' : '' }}>@lang('Created')</option>
                        <option value="picked_up" {{ $status == 'picked_up' ? 'selected' : '' }}>@lang('Picked Up')</option>
                        <option value="in_transit" {{ $status == 'in_transit' ? 'selected' : '' }}>@lang('In Transit')</option>
                        <option value="out_for_delivery" {{ $status == 'out_for_delivery' ? 'selected' : '' }}>@lang('Out for Delivery')</option>
                        <option value="delivered" {{ $status == 'delivered' ? 'selected' : '' }}>@lang('Delivered')</option>
                        <option value="failed" {{ $status == 'failed' ? 'selected' : '' }}>@lang('Failed')</option>
                        <option value="returned" {{ $status == 'returned' ? 'selected' : '' }}>@lang('Returned')</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> @lang('Filter')
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('merchant.shipments.export', request()->all()) }}" class="btn btn-outline-success w-100">
                        <i class="fas fa-download me-1"></i> @lang('Export')
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Shipments Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>@lang('Tracking Number')</th>
                            <th>@lang('Purchase')</th>
                            <th>@lang('Customer')</th>
                            <th>@lang('Company')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Date')</th>
                            <th>@lang('Actions')</th>
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
                                    <a href="{{ route('merchant.shipments.show', $shipment->tracking_number) }}"
                                       class="fw-bold text-primary">
                                        {{ $shipment->tracking_number }}
                                    </a>
                                </td>
                                <td>
                                    @if($shipment->purchase)
                                        <a href="{{ route('merchant-purchase-show', $shipment->purchase->purchase_number) }}">
                                            {{ $shipment->purchase->purchase_number }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>{{ $shipment->purchase->customer_name ?? 'N/A' }}</td>
                                <td>{{ $shipment->company_name }}</td>
                                <td>
                                    <span class="badge bg-{{ $color }}">
                                        {{ $shipment->status_ar }}
                                    </span>
                                </td>
                                <td>{{ $shipment->status_date?->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('merchant.shipments.show', $shipment->tracking_number) }}"
                                           class="btn btn-outline-primary" name="@lang('View')">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('merchant.shipments.refresh', $shipment->tracking_number) }}"
                                           class="btn btn-outline-info" name="@lang('Refresh')">
                                            <i class="fas fa-sync-alt"></i>
                                        </a>
                                        @if(!in_array($shipment->status, ['delivered', 'cancelled', 'returned']))
                                            <button type="button" class="btn btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#cancelModal{{ $shipment->tracking_number }}"
                                                    name="@lang('Cancel')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @endif
                                    </div>

                                    <!-- Cancel Modal -->
                                    <div class="modal fade" id="cancelModal{{ $shipment->tracking_number }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('merchant.shipments.cancel', $shipment->tracking_number) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-name">@lang('Cancel Shipment')</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>@lang('Are you sure you want to cancel this shipment?')</p>
                                                        <p><strong>@lang('Tracking'): </strong>{{ $shipment->tracking_number }}</p>
                                                        <div class="mb-3">
                                                            <label class="form-label">@lang('Reason')</label>
                                                            <textarea name="reason" class="form-control" rows="2"
                                                                      placeholder="@lang('Enter cancellation reason')"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                            @lang('Close')
                                                        </button>
                                                        <button type="submit" class="btn btn-danger">
                                                            @lang('Cancel Shipment')
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
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">@lang('No shipments found')</p>
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
@endsection
