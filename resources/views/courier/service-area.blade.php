@extends('layouts.courier')

@section('content')
<div class="gs-user-panel-review wow-replaced">
    <div class="container">
        <div class="d-flex">
            @include('includes.courier.sidebar')

            <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                {{-- Page Header --}}
                <div class="ud-page-name-box d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <h3 class="ud-page-name">@lang('Service Areas')</h3>
                    <a class="template-btn md-btn black-btn" href="{{ route('courier-service-area-create') }}">
                        <i class="fas fa-plus me-1"></i> @lang('Add New')
                    </a>
                </div>

                {{-- Table --}}
                <div class="user-table table-responsive">
                    <table class="gs-data-table w-100">
                        <thead>
                            <tr>
                                <th>@lang('Country')</th>
                                <th>@lang('City')</th>
                                <th>@lang('Radius') (KM)</th>
                                <th>@lang('Delivery Cost')</th>
                                <th>@lang('Coordinates')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Actions')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($service_area as $area)
                                <tr>
                                    {{-- Country --}}
                                    <td data-label="@lang('Country')">
                                        {{ $area->country_name }}
                                    </td>

                                    {{-- City --}}
                                    <td data-label="@lang('City')">
                                        <strong>{{ $area->city_name }}</strong>
                                    </td>

                                    {{-- Radius --}}
                                    <td data-label="@lang('Radius')">
                                        <span class="badge bg-info text-white">
                                            {{ $area->radius_display }} @lang('KM')
                                        </span>
                                    </td>

                                    {{-- Price --}}
                                    <td data-label="@lang('Delivery Cost')">
                                        <strong class="text-success">
                                            {{ $area->price_formatted }}
                                        </strong>
                                    </td>

                                    {{-- Coordinates --}}
                                    <td data-label="@lang('Coordinates')">
                                        @if($area->coordinates_display)
                                            <small class="text-muted">
                                                {{ $area->coordinates_display }}
                                            </small>
                                        @else
                                            <span class="text-warning">-</span>
                                        @endif
                                    </td>

                                    {{-- Status Toggle --}}
                                    <td data-label="@lang('Status')">
                                        <a href="{{ route('courier-service-area-toggle-status', $area->id) }}"
                                           class="status-toggle"
                                           name="{{ $area->status == 1 ? __('Click to deactivate') : __('Click to activate') }}">
                                            @if($area->status == 1)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle me-1"></i>@lang('Active')
                                                </span>
                                            @else
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-times-circle me-1"></i>@lang('Inactive')
                                                </span>
                                            @endif
                                        </a>
                                    </td>

                                    {{-- Actions --}}
                                    <td data-label="@lang('Actions')">
                                        <div class="table-icon-btns-wrapper">
                                            {{-- Edit --}}
                                            <a href="{{ route('courier-service-area-edit', $area->id) }}"
                                               class="view-btn edit-btn" name="@lang('Edit')">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            {{-- Delete --}}
                                            <a href="{{ route('courier-service-area-delete', $area->id) }}"
                                               class="view-btn delete-btn"
                                               name="@lang('Delete')"
                                               onclick="return confirm('@lang('Are you sure you want to delete this service area?')')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-map-marker-alt fa-2x mb-2"></i>
                                            <p>@lang('No service areas found.')</p>
                                            <a href="{{ route('courier-service-area-create') }}" class="btn btn-primary btn-sm">
                                                <i class="fas fa-plus me-1"></i> @lang('Add Your First Service Area')
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($service_area->hasPages())
                    <div class="mt-3">
                        {{ $service_area->links('includes.frontend.pagination') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.status-toggle {
    text-decoration: none;
    cursor: pointer;
    transition: opacity 0.2s;
}
.status-toggle:hover {
    opacity: 0.8;
}
.table-icon-btns-wrapper {
    display: flex;
    gap: 8px;
}
.view-btn {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    color: #fff;
    text-decoration: none;
}
.edit-btn {
    background: var(--action-primary, #007bff);
}
.delete-btn {
    background: var(--action-danger, #dc3545);
}
.view-btn:hover {
    opacity: 0.9;
    color: #fff;
}
</style>
@endpush
