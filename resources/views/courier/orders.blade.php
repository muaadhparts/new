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
                        <h3 class="ud-page-title">{{ __('My Purchases') }}</h3>
                    </div>

                    {{-- ✅ Order Status Tabs --}}
                    <ul class="nav nav-tabs mb-4" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ !request()->input('type') ? 'active' : '' }}"
                               href="{{ route('courier-purchases') }}">
                                <i class="fas fa-box-open"></i> @lang('Ready for Courier Collection')
                                @php
                                    $readyCount = \App\Models\DeliveryCourier::where('courier_id', auth('courier')->id())
                                        ->whereIn('status', ['ready_for_courier_collection', 'accepted'])
                                        ->count();
                                @endphp
                                @if($readyCount > 0)
                                    <span class="badge bg-success ms-1">{{ $readyCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->input('type') == 'pending' ? 'active' : '' }}"
                               href="{{ route('courier-purchases', ['type' => 'pending']) }}">
                                <i class="fas fa-clock"></i> @lang('Pending')
                                @php
                                    $pendingCount = \App\Models\DeliveryCourier::where('courier_id', auth('courier')->id())
                                        ->where('status', 'pending')
                                        ->count();
                                @endphp
                                @if($pendingCount > 0)
                                    <span class="badge bg-warning text-dark ms-1">{{ $pendingCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->input('type') == 'complete' ? 'active' : '' }}"
                               href="{{ route('courier-purchases', ['type' => 'complete']) }}">
                                <i class="fas fa-check-circle"></i> @lang('Completed')
                            </a>
                        </li>
                    </ul>

                    {{-- ✅ Info Alert based on tab --}}
                    @if(!request()->input('type'))
                        <div class="alert alert-success mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            @lang('These orders are ready for courier collection from merchants. Accept and deliver them to earn!')
                        </div>
                    @elseif(request()->input('type') == 'pending')
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-clock me-2"></i>
                            @lang('These orders are assigned to you but merchants haven\'t marked them ready yet.')
                        </div>
                    @endif

                    <div class="user-table table-responsive position-relative">

                        <table class="gs-data-table w-100">
                            <thead>
                                <tr>
                                    <th>{{ __('#Purchase') }}</th>
                                    <th>{{ __('Merchant') }}</th>
                                    <th>{{ __('Delivery Area') }}</th>
                                    <th>{{ __('Merchant Location') }}</th>
                                    <th>{{ __('Delivery Fee') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchases as $delivery)
                                    <tr>
                                        {{-- Purchase Number --}}
                                        <td data-label="{{ __('#Purchase') }}">
                                            <strong>{{ $delivery->purchase->purchase_number ?? 'N/A' }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $delivery->purchase->created_at?->format('Y-m-d') }}</small>
                                        </td>

                                        {{-- Merchant --}}
                                        <td data-label="{{ __('Merchant') }}">
                                            <strong>{{ $delivery->merchant->shop_name ?? $delivery->merchant->name ?? 'N/A' }}</strong>
                                            @if($delivery->merchant?->shop_phone)
                                                <br>
                                                <small><i class="fas fa-phone"></i> {{ $delivery->merchant->shop_phone }}</small>
                                            @endif
                                        </td>

                                        {{-- Delivery Area (Customer) --}}
                                        <td data-label="{{ __('Delivery Area') }}">
                                            <strong>{{ $delivery->purchase->customer_name ?? 'N/A' }}</strong>
                                            <br>
                                            <small><i class="fas fa-phone"></i> {{ $delivery->purchase->customer_phone ?? 'N/A' }}</small>
                                            <br>
                                            <small><i class="fas fa-city"></i> {{ $delivery->purchase->customer_city ?? 'N/A' }}</small>
                                            <br>
                                            <small title="{{ $delivery->purchase->customer_address }}">
                                                <i class="fas fa-map-marker-alt"></i> {{ Str::limit($delivery->purchase->customer_address ?? '', 30) }}
                                            </small>
                                        </td>

                                        {{-- Warehouse Location --}}
                                        <td data-label="{{ __('Warehouse Location') }}">
                                            {{ $delivery->merchantLocation->location ?? 'N/A' }}
                                        </td>

                                        {{-- Delivery Fee --}}
                                        <td data-label="{{ __('Delivery Fee') }}">
                                            <strong class="text-success">
                                                {{ \PriceHelper::showAdminCurrencyPrice($delivery->delivery_fee ?? 0) }}
                                            </strong>
                                            @if($delivery->payment_method === 'cod')
                                                <br>
                                                <span class="badge bg-warning text-dark">COD</span>
                                                <br>
                                                <small>@lang('Collect'): {{ \PriceHelper::showAdminCurrencyPrice($delivery->purchase_amount ?? 0) }}</small>
                                            @endif
                                        </td>

                                        {{-- Status --}}
                                        <td data-label="{{ __('Status') }}">
                                            @if($delivery->status == 'pending')
                                                <span class="badge bg-warning text-dark">@lang('Pending Merchant')</span>
                                            @elseif($delivery->status == 'ready_for_courier_collection')
                                                <span class="badge bg-success">@lang('Ready for Courier Collection')</span>
                                            @elseif($delivery->status == 'accepted')
                                                <span class="badge bg-primary">@lang('Accepted')</span>
                                            @elseif($delivery->status == 'delivered')
                                                <span class="badge bg-info">@lang('Delivered')</span>
                                            @elseif($delivery->status == 'rejected')
                                                <span class="badge bg-danger">@lang('Rejected')</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucwords($delivery->status) }}</span>
                                            @endif
                                        </td>

                                        {{-- Actions --}}
                                        <td data-label="{{ __('Actions') }}">
                                            @if($delivery->status == 'ready_for_courier_collection')
                                                <a href="{{ route('courier-purchase-delivery-accept', $delivery->id) }}"
                                                   class="btn btn-sm btn-success mb-1">
                                                    <i class="fas fa-check"></i> @lang('Accept')
                                                </a>
                                                <br>
                                                <a href="{{ route('courier-purchase-delivery-reject', $delivery->id) }}"
                                                   class="btn btn-sm btn-danger mb-1">
                                                    <i class="fas fa-times"></i> @lang('Reject')
                                                </a>
                                            @elseif($delivery->status == 'accepted')
                                                <a href="{{ route('courier-purchase-delivery-complete', $delivery->id) }}"
                                                   class="btn btn-sm btn-primary mb-1">
                                                    <i class="fas fa-check-double"></i> @lang('Mark Delivered')
                                                </a>
                                            @elseif($delivery->status == 'pending')
                                                <span class="text-muted">
                                                    <i class="fas fa-clock"></i> @lang('Waiting for merchant')
                                                </span>
                                            @endif
                                            <br>
                                            <a href="{{ route('courier-purchase-details', $delivery->id) }}" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-eye"></i> @lang('View')
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <br>
                                            {{ __('No purchases found') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $purchases->links('includes.frontend.pagination') }}
                </div>
            </div>
        </div>
    </div>
    <!-- user dashboard wrapper end -->
@endsection
@section('script')
    <script src="{{ asset('assets/front/js/dataTables.min.js') }}" defer></script>
    <script src="{{ asset('assets/front/js/user.js') }}" defer></script>
@endsection
