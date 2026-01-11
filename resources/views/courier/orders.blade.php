@extends('layouts.front')
@section('css')
    <link rel="stylesheet" href="{{ asset('assets/front/css/datatables.css') }}">
@endsection
@section('content')
    <div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="d-flex">
                <!-- sidebar -->
                @include('includes.courier.sidebar')
                <!-- main content -->
                <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                    <!-- page title -->
                    <div class="ud-page-title-box">
                        <h3 class="ud-page-title">@lang('My Deliveries')</h3>
                    </div>

                    {{-- ✅ Order Status Tabs (NEW WORKFLOW) --}}
                    <ul class="nav nav-tabs mb-4" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ !$type || $type == 'all' ? 'active' : '' }}"
                               href="{{ route('courier-purchases') }}">
                                <i class="fas fa-list"></i> @lang('All Active')
                                @php
                                    $activeCount = \App\Models\DeliveryCourier::where('courier_id', auth('courier')->id())
                                        ->whereIn('status', [
                                            \App\Models\DeliveryCourier::STATUS_PENDING_APPROVAL,
                                            \App\Models\DeliveryCourier::STATUS_APPROVED,
                                            \App\Models\DeliveryCourier::STATUS_READY_FOR_PICKUP,
                                            \App\Models\DeliveryCourier::STATUS_PICKED_UP,
                                        ])
                                        ->count();
                                @endphp
                                @if($activeCount > 0)
                                    <span class="badge bg-primary ms-1">{{ $activeCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $type == 'pending' ? 'active' : '' }}"
                               href="{{ route('courier-purchases', ['type' => 'pending']) }}">
                                <i class="fas fa-clock"></i> @lang('Pending Approval')
                                @php
                                    $pendingCount = \App\Models\DeliveryCourier::where('courier_id', auth('courier')->id())
                                        ->where('status', \App\Models\DeliveryCourier::STATUS_PENDING_APPROVAL)
                                        ->count();
                                @endphp
                                @if($pendingCount > 0)
                                    <span class="badge bg-warning text-dark ms-1">{{ $pendingCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $type == 'in_progress' ? 'active' : '' }}"
                               href="{{ route('courier-purchases', ['type' => 'in_progress']) }}">
                                <i class="fas fa-truck"></i> @lang('In Progress')
                                @php
                                    $progressCount = \App\Models\DeliveryCourier::where('courier_id', auth('courier')->id())
                                        ->whereIn('status', [
                                            \App\Models\DeliveryCourier::STATUS_APPROVED,
                                            \App\Models\DeliveryCourier::STATUS_READY_FOR_PICKUP,
                                            \App\Models\DeliveryCourier::STATUS_PICKED_UP,
                                        ])
                                        ->count();
                                @endphp
                                @if($progressCount > 0)
                                    <span class="badge bg-info ms-1">{{ $progressCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $type == 'completed' ? 'active' : '' }}"
                               href="{{ route('courier-purchases', ['type' => 'completed']) }}">
                                <i class="fas fa-check-circle"></i> @lang('Completed')
                            </a>
                        </li>
                    </ul>

                    {{-- ✅ Info Alert based on tab --}}
                    @if($type == 'pending')
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-bell me-2"></i>
                            <strong>@lang('New Delivery Requests!')</strong>
                            @lang('These orders need your approval. Accept to start the delivery process.')
                        </div>
                    @elseif($type == 'in_progress')
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-truck me-2"></i>
                            @lang('These orders are in progress. Pick up from merchant or deliver to customer.')
                        </div>
                    @elseif(!$type || $type == 'all')
                        <div class="alert alert-primary mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            @lang('All your active deliveries. New requests need approval, then wait for merchant, then deliver.')
                        </div>
                    @endif

                    <div class="user-table table-responsive position-relative">
                        <table class="gs-data-table w-100">
                            <thead>
                                <tr>
                                    <th>@lang('Purchase #')</th>
                                    <th>@lang('Merchant')</th>
                                    <th>@lang('Customer')</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchases as $delivery)
                                    <tr>
                                        {{-- Purchase Number --}}
                                        <td data-label="@lang('Purchase #')">
                                            <strong>{{ $delivery->purchase->purchase_number ?? 'N/A' }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $delivery->created_at?->format('Y-m-d H:i') }}</small>
                                        </td>

                                        {{-- Merchant --}}
                                        <td data-label="@lang('Merchant')">
                                            <i class="fas fa-store me-1"></i>
                                            <strong>{{ $delivery->merchant->shop_name ?? $delivery->merchant->name ?? 'N/A' }}</strong>
                                            @if($delivery->merchantLocation && $delivery->merchantLocation->location)
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    {{ Str::limit($delivery->merchantLocation->location, 25) }}
                                                </small>
                                            @endif
                                            @if($delivery->merchant?->shop_phone)
                                                <br>
                                                <small><i class="fas fa-phone"></i> {{ $delivery->merchant->shop_phone }}</small>
                                            @endif
                                        </td>

                                        {{-- Customer --}}
                                        <td data-label="@lang('Customer')">
                                            <i class="fas fa-user me-1"></i>
                                            <strong>{{ $delivery->purchase->customer_name ?? 'N/A' }}</strong>
                                            <br>
                                            <small><i class="fas fa-phone"></i> {{ $delivery->purchase->customer_phone ?? 'N/A' }}</small>
                                            <br>
                                            <small><i class="fas fa-city"></i> {{ $delivery->purchase->customer_city ?? 'N/A' }}</small>
                                        </td>

                                        {{-- Amount --}}
                                        <td data-label="@lang('Amount')">
                                            <strong class="text-success">
                                                @lang('Fee'): {{ \PriceHelper::showAdminCurrencyPrice($delivery->delivery_fee ?? 0) }}
                                            </strong>
                                            @if($delivery->isCod())
                                                <br>
                                                <span class="badge bg-warning text-dark">COD</span>
                                                <br>
                                                <small>@lang('Collect'): {{ \PriceHelper::showAdminCurrencyPrice($delivery->purchase_amount ?? 0) }}</small>
                                            @else
                                                <br>
                                                <span class="badge bg-info">
                                                    <i class="fas fa-credit-card"></i> @lang('Paid Online')
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Status (NEW WORKFLOW) --}}
                                        <td data-label="@lang('Status')">
                                            @if($delivery->isPendingApproval())
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-clock"></i> @lang('Needs Approval')
                                                </span>
                                                <br><small class="text-warning">@lang('Approve or Reject')</small>
                                            @elseif($delivery->isApproved())
                                                <span class="badge bg-info">
                                                    <i class="fas fa-box-open"></i> @lang('Merchant Preparing')
                                                </span>
                                                <br><small class="text-muted">@lang('Wait for merchant')</small>
                                            @elseif($delivery->isReadyForPickup())
                                                <span class="badge bg-success">
                                                    <i class="fas fa-box"></i> @lang('Ready for Pickup')
                                                </span>
                                                <br><small class="text-success">@lang('Go to merchant')</small>
                                            @elseif($delivery->isPickedUp())
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-truck"></i> @lang('On The Way')
                                                </span>
                                                <br><small class="text-primary">@lang('Deliver now!')</small>
                                            @elseif($delivery->isDelivered())
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-double"></i> @lang('Delivered')
                                                </span>
                                                @if($delivery->delivered_at)
                                                    <br><small class="text-muted">{{ $delivery->delivered_at->format('Y-m-d H:i') }}</small>
                                                @endif
                                            @elseif($delivery->isConfirmed())
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle"></i> @lang('Confirmed')
                                                </span>
                                            @elseif($delivery->isRejected())
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-times"></i> @lang('Rejected')
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">{{ $delivery->status_label }}</span>
                                            @endif
                                        </td>

                                        {{-- Actions (NEW WORKFLOW) --}}
                                        <td data-label="@lang('Actions')">
                                            @if($delivery->isPendingApproval())
                                                {{-- STEP 1: Courier Approve/Reject --}}
                                                <div class="d-flex flex-column gap-1">
                                                    <a href="{{ route('courier-purchase-delivery-accept', $delivery->id) }}"
                                                       class="btn btn-sm btn-success">
                                                        <i class="fas fa-check"></i> @lang('Approve')
                                                    </a>
                                                    <a href="{{ route('courier-purchase-delivery-reject', $delivery->id) }}"
                                                       class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-times"></i> @lang('Reject')
                                                    </a>
                                                </div>
                                            @elseif($delivery->isApproved())
                                                {{-- STEP 2: Waiting for merchant to prepare --}}
                                                <span class="text-muted">
                                                    <i class="fas fa-hourglass-half"></i> @lang('Merchant Preparing')
                                                </span>
                                            @elseif($delivery->isReadyForPickup())
                                                {{-- STEP 3: Waiting for merchant to hand over --}}
                                                <span class="text-info">
                                                    <i class="fas fa-store"></i> @lang('Pick up from merchant')
                                                </span>
                                            @elseif($delivery->isPickedUp())
                                                {{-- STEP 4: Courier delivers to customer --}}
                                                <a href="{{ route('courier-purchase-delivery-complete', $delivery->id) }}"
                                                   class="btn btn-sm btn-success">
                                                    <i class="fas fa-check-double"></i> @lang('Mark Delivered')
                                                </a>
                                            @elseif($delivery->isCompleted())
                                                <span class="text-success">
                                                    <i class="fas fa-check-circle"></i> @lang('Completed')
                                                </span>
                                            @elseif($delivery->isRejected())
                                                <span class="text-danger">
                                                    <i class="fas fa-ban"></i> @lang('Rejected')
                                                </span>
                                            @endif
                                            <br>
                                            <a href="{{ route('courier-purchase-details', $delivery->id) }}" class="btn btn-sm btn-secondary mt-1">
                                                <i class="fas fa-eye"></i> @lang('Details')
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <br>
                                            @lang('No deliveries found')
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $purchases->appends(['type' => $type])->links('includes.frontend.pagination') }}
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="{{ asset('assets/front/js/dataTables.min.js') }}" defer></script>
    <script src="{{ asset('assets/front/js/user.js') }}" defer></script>
@endsection
