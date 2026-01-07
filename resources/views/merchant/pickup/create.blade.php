@extends('layouts.merchant')
@section('content')
    <div class="gs-merchant-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-merchant-breadcrumb has-mb">
                <div class=" d-flex align-items-center gap-4">
                    <a href="{{route("merchant-pickup-point-index")}}"class="back-btn">
                        <i class="fa-solid fa-arrow-left-long"></i>
                    </a>
                    <h4>@lang('Create Pickup Point')</h4>
                </div>

            <ul class="breadcrumb-menu">
                <li>
                    <a href="{{ route('merchant.dashboard') }}" class="text-capitalize">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" class="home-icon-merchant-panel-breadcrumb">
                            <path
                                d="M9 21V13.6C9 13.0399 9 12.7599 9.109 12.546C9.20487 12.3578 9.35785 12.2049 9.54601 12.109C9.75993 12 10.04 12 10.6 12H13.4C13.9601 12 14.2401 12 14.454 12.109C14.6422 12.2049 14.7951 12.3578 14.891 12.546C15 12.7599 15 13.0399 15 13.6V21M2 9.5L11.04 2.72C11.3843 2.46181 11.5564 2.33271 11.7454 2.28294C11.9123 2.23902 12.0877 2.23902 12.2546 2.28295C12.4436 2.33271 12.6157 2.46181 12.96 2.72L22 9.5M4 8V17.8C4 18.9201 4 19.4802 4.21799 19.908C4.40974 20.2843 4.7157 20.5903 5.09202 20.782C5.51985 21 6.0799 21 7.2 21H16.8C17.9201 21 18.4802 21 18.908 20.782C19.2843 20.5903 19.5903 20.2843 19.782 19.908C20 19.4802 20 18.9201 20 17.8V8L13.92 3.44C13.2315 2.92361 12.8872 2.66542 12.5091 2.56589C12.1754 2.47804 11.8246 2.47804 11.4909 2.56589C11.1128 2.66542 10.7685 2.92361 10.08 3.44L4 8Z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="{{route("merchant.dashboard")}}" class="text-capitalize">
                        @lang('Dashboard')
                    </a>
                </li>
                <li>
                    <a href="javascript:;" class="text-capitalize"> @lang('Create Pickup Point') </a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->

        <!-- Edit Profile area start  -->
        <div class="merchant-edit-profile-section-wrapper">
            <div class="gs-edit-profile-section">

                <form class="edit-profile-area" action="{{ route('merchant-pickup-point-create') }}" method="POST">
                    @csrf
                    <div class="row">

                        <div class="form-group col-md-6 mb-3">
                            <label for="country_id">@lang('Country') <span class="text-danger">*</span></label>
                            <select name="country_id" id="country_id" class="form-control" required>
                                <option value="">@lang('Select Country')</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}">{{ $country->country_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-6 mb-3">
                            <label for="city_id">@lang('City') <span class="text-danger">*</span></label>
                            <select name="city_id" id="city_id" class="form-control" required>
                                <option value="">@lang('Select Country First')</option>
                            </select>
                            @error('city_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group col-12 mb-3">
                            <label for="location">@lang('Location / Address') <span class="text-danger">*</span></label>
                            <input type="text" id="location" class="form-control" placeholder="@lang('Enter detailed address')" value="{{ old('location') }}"
                                name="location" required>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2"
                                onclick="openMapPicker({ addressField: '#location', latField: '#latitude', lngField: '#longitude' })">
                                <i class="fas fa-map-marker-alt me-1"></i> @lang('Select on Map')
                            </button>
                            @error('location')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Hidden fields for coordinates --}}
                        <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
                        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">

                        <div class="col-12 col-sm-12">
                            <button class="template-btn btn-forms" type="submit">
                                @lang('Save')
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- Edit Profile area end  -->
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var countrySelect = document.getElementById('country_id');
    var citySelect = document.getElementById('city_id');
    var ajaxUrl = "{{ route('merchant-pickup-get-cities') }}";

    if (!countrySelect || !citySelect) {
        console.error('Country or City select not found');
        return;
    }

    countrySelect.addEventListener('change', function() {
        var countryId = this.value;

        if (!countryId) {
            citySelect.innerHTML = '<option value="">-- {{ __("Select Country First") }} --</option>';
            return;
        }

        // Show loading
        citySelect.innerHTML = '<option value="">{{ __("Loading...") }}</option>';

        // AJAX request using jQuery (more compatible)
        $.ajax({
            url: ajaxUrl,
            type: 'GET',
            data: { country_id: countryId },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    citySelect.innerHTML = data.cities;
                } else {
                    citySelect.innerHTML = '<option value="">-- {{ __("No cities found") }} --</option>';
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                citySelect.innerHTML = '<option value="">-- {{ __("Error loading cities") }} --</option>';
            }
        });
    });
});
</script>
@endpush
