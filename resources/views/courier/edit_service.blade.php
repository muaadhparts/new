@extends('layouts.courier')

@section('content')
<div class="gs-user-panel-review wow-replaced">
    <div class="container">
        <div class="d-flex">
            @include('includes.courier.sidebar')

            <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                <div class="gs-edit-profile-section">
                    {{-- Page Header --}}
                    <div class="ud-page-name-box gap-4">
                        <a href="{{ route('courier-service-area') }}" class="back-btn">
                            <i class="fa-solid fa-arrow-left-long"></i>
                        </a>
                        <h3 class="ud-page-name">@lang('Edit Service Area')</h3>
                    </div>

                    {{-- Form --}}
                    <form id="serviceAreaForm" action="{{ route('courier-service-area-update', $service_area->id) }}" method="POST">
                        @csrf
                        <div class="edit-profile-area">
                            <div class="row">
                                <div class="col-lg-8">
                                    <div class="multi-form-wrapper gap-4">

                                        {{-- Country Field --}}
                                        <div class="single-form-wrapper">
                                            <div class="form-group">
                                                <label class="form-label fw-bold">
                                                    @lang('Country') <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-control" name="country_id" id="countrySelect" required>
                                                    <option value="">-- @lang('Select Country') --</option>
                                                    @foreach ($countries as $country)
                                                        <option value="{{ $country->id }}" {{ $selectedCountryId == $country->id ? 'selected' : '' }}>
                                                            {{ app()->getLocale() == 'ar' && $country->country_name_ar ? $country->country_name_ar : $country->country_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('country_id')
                                                    <div class="text-danger mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        {{-- City Field --}}
                                        <div class="single-form-wrapper" id="cityWrapper">
                                            <div class="form-group">
                                                <label class="form-label fw-bold">
                                                    @lang('City') <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-control" name="service_area_id" id="citySelect" required>
                                                    <option value="">-- @lang('Select City') --</option>
                                                    @foreach ($cities as $city)
                                                        <option value="{{ $city->id }}" {{ $service_area->city_id == $city->id ? 'selected' : '' }}>
                                                            {{ $city->city_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div id="cityLoading" class="text-muted mt-1" style="display: none;">
                                                    <i class="fas fa-spinner fa-spin"></i> @lang('Loading cities...')
                                                </div>
                                                <div id="cityCount" class="text-success mt-1">
                                                    <i class="fas fa-check-circle"></i> {{ $cities->count() }} @lang('cities available')
                                                </div>
                                                @error('service_area_id')
                                                    <div class="text-danger mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        {{-- Service Radius Field --}}
                                        <div class="single-form-wrapper" id="radiusWrapper">
                                            <div class="form-group">
                                                <label class="form-label fw-bold">
                                                    @lang('Service Radius') (@lang('KM')) <span class="text-danger">*</span>
                                                </label>
                                                <input type="number"
                                                       class="form-control"
                                                       name="service_radius_km"
                                                       id="radiusInput"
                                                       min="1"
                                                       max="500"
                                                       value="{{ $service_area->service_radius_km ?? 20 }}"
                                                       placeholder="@lang('Enter service radius in kilometers')"
                                                       required>
                                                <small class="text-muted">@lang('The maximum distance you can deliver from the city center')</small>
                                                @error('service_radius_km')
                                                    <div class="text-danger mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        {{-- Price Field --}}
                                        <div class="single-form-wrapper" id="priceWrapper">
                                            <div class="form-group">
                                                <label class="form-label fw-bold">
                                                    @lang('Delivery Cost') ({{ $curr->name }}) <span class="text-danger">*</span>
                                                </label>
                                                <input type="number"
                                                       class="form-control"
                                                       name="price"
                                                       id="priceInput"
                                                       step="0.01"
                                                       min="1"
                                                       value="{{ monetaryUnit()->convert($service_area->price) }}"
                                                       placeholder="@lang('Enter delivery cost')"
                                                       required>
                                                @error('price')
                                                    <div class="text-danger mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                    </div>

                                    {{-- Submit Button --}}
                                    <div class="mt-4">
                                        <button type="submit" class="template-btn btn-forms">
                                            <i class="fas fa-save me-2"></i>@lang('Update Service Area')
                                        </button>
                                        <a href="{{ route('courier-service-area') }}" class="btn btn-secondary ms-2">
                                            @lang('Cancel')
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const countrySelect = document.getElementById('countrySelect');
    const citySelect = document.getElementById('citySelect');
    const cityLoading = document.getElementById('cityLoading');
    const cityCount = document.getElementById('cityCount');

    const ajaxUrl = "{{ route('courier-get-cities') }}";
    const currentCityId = "{{ $service_area->city_id }}";

    // Country change event
    countrySelect.addEventListener('change', function() {
        const countryId = this.value;

        // Reset city select
        citySelect.innerHTML = '<option value="">-- {{ __("Select City") }} --</option>';
        citySelect.disabled = true;
        cityCount.style.display = 'none';

        if (!countryId) {
            return;
        }

        // Show loading
        cityLoading.style.display = 'block';

        // AJAX request
        fetch(ajaxUrl + '?country_id=' + countryId, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            cityLoading.style.display = 'none';

            if (data.success && data.count > 0) {
                citySelect.innerHTML = data.cities;
                citySelect.disabled = false;
                cityCount.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.count + ' {{ __("cities available") }}';
                cityCount.className = 'text-success mt-1';
                cityCount.style.display = 'block';

                // Re-select current city if exists
                if (currentCityId) {
                    const option = citySelect.querySelector('option[value="' + currentCityId + '"]');
                    if (option) {
                        option.selected = true;
                    }
                }
            } else {
                citySelect.innerHTML = '<option value="">-- {{ __("No cities found") }} --</option>';
                cityCount.innerHTML = '<i class="fas fa-exclamation-circle"></i> {{ __("No cities available") }}';
                cityCount.className = 'text-warning mt-1';
                cityCount.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            cityLoading.style.display = 'none';
            citySelect.innerHTML = '<option value="">-- {{ __("Error loading cities") }} --</option>';
            cityCount.innerHTML = '<i class="fas fa-times-circle"></i> {{ __("Failed to load cities") }}';
            cityCount.className = 'text-danger mt-1';
            cityCount.style.display = 'block';
        });
    });
});
</script>
@endpush
