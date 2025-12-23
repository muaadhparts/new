@extends('layouts.vendor')
@php
    $isDashboard = true;
    $isVendor = true;
@endphp
@section('content')
    <div class="gs-vendor-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-vendor-breadcrumb has-mb">
            <div class="d-flex gap-4 flex-wrap">
                <h4 class="text-capitalize">@lang('Warehouse Settings')
                </h4>
            </div>
            <ul class="breadcrumb-menu">
                <li>
                    <a href="{{ route('vendor.dashboard') }}" class="text-capitalize">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" class="home-icon-vendor-panel-breadcrumb">
                            <path
                                d="M9 21V13.6C9 13.0399 9 12.7599 9.109 12.546C9.20487 12.3578 9.35785 12.2049 9.54601 12.109C9.75993 12 10.04 12 10.6 12H13.4C13.9601 12 14.2401 12 14.454 12.109C14.6422 12.2049 14.7951 12.3578 14.891 12.546C15 12.7599 15 13.0399 15 13.6V21M2 9.5L11.04 2.72C11.3843 2.46181 11.5564 2.33271 11.7454 2.28294C11.9123 2.23902 12.0877 2.23902 12.2546 2.28295C12.4436 2.33271 12.6157 2.46181 12.96 2.72L22 9.5M4 8V17.8C4 18.9201 4 19.4802 4.21799 19.908C4.40974 20.2843 4.7157 20.5903 5.09202 20.782C5.51985 21 6.0799 21 7.2 21H16.8C17.9201 21 18.4802 21 18.908 20.782C19.2843 20.5903 19.5903 20.2843 19.782 19.908C20 19.4802 20 18.9201 20 17.8V8L13.92 3.44C13.2315 2.92361 12.8872 2.66542 12.5091 2.56589C12.1754 2.47804 11.8246 2.47804 11.4909 2.56589C11.1128 2.66542 10.7685 2.92361 10.08 3.44L4 8Z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="{{route("vendor.dashboard")}}" class="text-capitalize">
                        @lang('Dashboard')
                    </a>
                </li>
                <li>
                    <a href="javascript:;" class="text-capitalize">@lang('Warehouse Settings')  </a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->

        <!-- Warehouse Settings area start  -->
        <div class="vendor-edit-profile-section-wrapper">
            <div class="gs-edit-profile-section">

                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form class="edit-profile-area" action="{{route('vendor-warehouse-update')}}" method="POST">
                    @csrf
                    <div class="row">

                        <div class="col-md-12">
                            <p class="text-muted mb-3">
                                @lang('Set your warehouse/store origin location for accurate shipping calculations')
                            </p>
                        </div>

                        <!-- Country (readonly, from shop settings) -->
                        <div class="col-md-6 form-group">
                            <label for="country">@lang('Country')</label>
                            <input type="text" id="country" class="form-control"
                                value="{{ $user->country ?? '' }}"
                                readonly disabled>
                            <small class="text-muted">@lang('Country is set in your shop profile')</small>
                        </div>

                        <!-- State/Region -->
                        <div class="col-md-6 form-group">
                            <label for="warehouse_state">@lang('State/Region') <span class="text-danger">*</span></label>
                            <select name="warehouse_state" id="warehouse_state" class="form-control">
                                <option value="">@lang('Select State')</option>
                                @foreach($states as $state)
                                    @php
                                        $stateDisplayName = (app()->getLocale() == 'ar')
                                            ? ($state->state_ar ?: $state->state)
                                            : $state->state;
                                    @endphp
                                    <option value="{{ $state->id }}"
                                        {{ $user->warehouse_state == $state->id ? 'selected' : '' }}>
                                        {{ $stateDisplayName }}
                                    </option>
                                @endforeach
                            </select>
                            @error('warehouse_state')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- City -->
                        <div class="col-md-6 form-group">
                            <label for="warehouse_city">@lang('City') <span class="text-danger">*</span></label>
                            <select name="warehouse_city" id="warehouse_city" class="form-control">
                                <option value="">@lang('Select City')</option>
                                @foreach($cities as $city)
                                    @php
                                        $cityDisplayName = (app()->getLocale() == 'ar')
                                            ? ($city->city_name_ar ?: $city->city_name)
                                            : $city->city_name;
                                    @endphp
                                    <option value="{{ $city->city_name }}"
                                        {{ $user->warehouse_city == $city->city_name ? 'selected' : '' }}>
                                        {{ $cityDisplayName }}
                                    </option>
                                @endforeach
                            </select>
                            @error('warehouse_city')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Address -->
                        <div class="col-md-6 form-group">
                            <label for="warehouse_address">@lang('Warehouse Address')</label>
                            <textarea name="warehouse_address" id="warehouse_address"
                                class="form-control" rows="3"
                                placeholder="@lang('Full warehouse address')">{{ old('warehouse_address', $user->warehouse_address) }}</textarea>
                            @error('warehouse_address')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- GPS Coordinates (Optional) -->
                        <div class="col-md-6 form-group">
                            <label for="warehouse_lat">@lang('Latitude (Optional)')</label>
                            <input type="text" name="warehouse_lat" id="warehouse_lat"
                                class="form-control"
                                placeholder="@lang('e.g., 24.7136')"
                                value="{{ old('warehouse_lat', $user->warehouse_lat) }}">
                            @error('warehouse_lat')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label for="warehouse_lng">@lang('Longitude (Optional)')</label>
                            <input type="text" name="warehouse_lng" id="warehouse_lng"
                                class="form-control"
                                placeholder="@lang('e.g., 46.6753')"
                                value="{{ old('warehouse_lng', $user->warehouse_lng) }}">
                            @error('warehouse_lng')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Google Maps Button -->
                        <div class="col-12 form-group">
                            <button type="button" class="template-btn dark-btn" data-bs-toggle="modal" data-bs-target="#mapModal">
                                <i class="fas fa-map-marker-alt"></i> @lang('Select Location from Map')
                            </button>
                        </div>

                        <div class="col-12 col-sm-12">
                            <button class="template-btn btn-forms" type="submit">
                                @lang('Save Warehouse Settings')
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- Warehouse Settings area end  -->
    </div>

    <!-- Google Maps Modal -->
    @include('partials.google-maps-modal')

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // عند تغيير الولاية، جلب المدن
    $('#warehouse_state').on('change', function() {
        var stateId = $(this).val();
        var citySelect = $('#warehouse_city');

        citySelect.html('<option value="">@lang("Loading...")</option>');

        if (stateId) {
            $.ajax({
                url: '{{ route("vendor-warehouse-get-cities") }}',
                type: 'GET',
                data: { state_id: stateId },
                success: function(response) {
                    citySelect.html('<option value="">@lang("Select City")</option>');

                    if (response.cities && response.cities.length > 0) {
                        response.cities.forEach(function(city) {
                            // استخدام display_name من الاستجابة (معتمد على اللغة النشطة)
                            var displayName = city.display_name || city.city_name;
                            citySelect.append(
                                '<option value="' + city.city_name + '">' +
                                displayName +
                                '</option>'
                            );
                        });
                    } else {
                        citySelect.html('<option value="">@lang("No cities found")</option>');
                    }
                },
                error: function() {
                    citySelect.html('<option value="">@lang("Error loading cities")</option>');
                }
            });
        } else {
            citySelect.html('<option value="">@lang("Select City")</option>');
        }
    });
});
</script>
@endsection
