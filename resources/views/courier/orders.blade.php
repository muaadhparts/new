@extends('layouts.courier')
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
                    <!-- page name -->
                    <div class="ud-page-name-box">
                        <h3 class="ud-page-name">@lang('My Deliveries')</h3>
                    </div>

                    {{-- ✅ Order Status Tabs (NEW WORKFLOW) --}}
                    {{-- استخدام counts محملة مسبقاً من الـ Controller --}}
                    <ul class="nav nav-tabs mb-4" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ !$type || $type == 'all' ? 'active' : '' }}"
                               href="{{ route('courier-purchases') }}">
                                <i class="fas fa-list"></i> @lang('All Active')
                                @if(($tabCounts['active'] ?? 0) > 0)
                                    <span class="badge bg-primary ms-1">{{ $tabCounts['active'] }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $type == 'pending' ? 'active' : '' }}"
                               href="{{ route('courier-purchases', ['type' => 'pending']) }}">
                                <i class="fas fa-clock"></i> @lang('Pending Approval')
                                @if(($tabCounts['pending'] ?? 0) > 0)
                                    <span class="badge bg-warning text-dark ms-1">{{ $tabCounts['pending'] }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $type == 'in_progress' ? 'active' : '' }}"
                               href="{{ route('courier-purchases', ['type' => 'in_progress']) }}">
                                <i class="fas fa-truck"></i> @lang('In Progress')
                                @if(($tabCounts['in_progress'] ?? 0) > 0)
                                    <span class="badge bg-info ms-1">{{ $tabCounts['in_progress'] }}</span>
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
                                            <strong>{{ $delivery->purchase_number }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $delivery->created_at_formatted }}</small>
                                        </td>

                                        {{-- Merchant --}}
                                        <td data-label="@lang('Merchant')">
                                            <i class="fas fa-store me-1"></i>
                                            <strong>{{ $delivery->merchant_name }}</strong>
                                            @if($delivery->branch_location)
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    {{ $delivery->branch_location }}
                                                </small>
                                            @endif
                                            @if($delivery->merchant_phone)
                                                <br>
                                                <small><i class="fas fa-phone"></i> {{ $delivery->merchant_phone }}</small>
                                            @endif
                                        </td>

                                        {{-- Customer --}}
                                        <td data-label="@lang('Customer')">
                                            <i class="fas fa-user me-1"></i>
                                            <strong>{{ $delivery->customer_name }}</strong>
                                            <br>
                                            <small><i class="fas fa-phone"></i> {{ $delivery->customer_phone }}</small>
                                            <br>
                                            <small><i class="fas fa-city"></i> {{ $delivery->customer_city }}</small>
                                        </td>

                                        {{-- Amount --}}
                                        <td data-label="@lang('Amount')">
                                            <strong class="text-success">
                                                @lang('Fee'): {{ $delivery->delivery_fee_formatted }}
                                            </strong>
                                            @if($delivery->is_cod)
                                                <br>
                                                <span class="badge bg-warning text-dark">COD</span>
                                                <br>
                                                <small>@lang('Collect'): {{ $delivery->purchase_amount_formatted }}</small>
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
                                                @if($delivery->delivered_at_formatted)
                                                    <br><small class="text-muted">{{ $delivery->delivered_at_formatted }}</small>
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
