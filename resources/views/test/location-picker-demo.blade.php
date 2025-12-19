@extends('layouts.front')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Location Picker Demo
                    </h4>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        هذه صفحة تجريبية لاختبار مودال اختيار الموقع المستقل.
                        <br>
                        This is a demo page to test the standalone location picker modal.
                    </p>

                    {{-- Button to open modal --}}
                    <div class="mb-4">
                        <button type="button"
                                class="btn btn-primary btn-lg"
                                data-bs-toggle="modal"
                                data-bs-target="#locationPickerModal">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            @lang('Select Location from Map')
                        </button>
                    </div>

                    <hr>

                    {{-- Form with auto-fill fields --}}
                    <h5 class="mb-3">
                        <i class="fas fa-magic me-2"></i>
                        Auto-filled Form Fields
                    </h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">@lang('Country')</label>
                            <input type="text"
                                   class="form-control"
                                   data-location-field="country_name"
                                   readonly
                                   placeholder="Will be filled automatically">
                            <input type="hidden" data-location-field="country_id" id="demo_country_id">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">@lang('State/Province')</label>
                            <input type="text"
                                   class="form-control"
                                   data-location-field="state_name"
                                   readonly
                                   placeholder="Will be filled automatically">
                            <input type="hidden" data-location-field="state_id" id="demo_state_id">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">@lang('City')</label>
                            <input type="text"
                                   class="form-control"
                                   data-location-field="city_name"
                                   readonly
                                   placeholder="Will be filled automatically">
                            <input type="hidden" data-location-field="city_id" id="demo_city_id">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">@lang('Coordinates')</label>
                            <div class="input-group">
                                <input type="text"
                                       class="form-control"
                                       data-location-field="latitude"
                                       readonly
                                       placeholder="Lat">
                                <input type="text"
                                       class="form-control"
                                       data-location-field="longitude"
                                       readonly
                                       placeholder="Lng">
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">@lang('Full Address')</label>
                            <textarea class="form-control"
                                      data-location-field="address"
                                      rows="2"
                                      readonly
                                      placeholder="Will be filled automatically"></textarea>
                        </div>
                    </div>

                    <hr class="my-4">

                    {{-- Raw JSON output --}}
                    <h5 class="mb-3">
                        <i class="fas fa-code me-2"></i>
                        Raw Location Data (JSON)
                    </h5>
                    <pre id="location-json" class="bg-dark text-light p-3 rounded" style="max-height: 300px; overflow: auto;">
{
    "message": "Select a location to see the data here"
}
                    </pre>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Include the standalone location picker modal --}}
@include('components.location-picker-modal')
@endsection

@section('script')
<script>
// Listen for location selection event
document.addEventListener('locationSelected', function(e) {
    console.log('📍 Location selected:', e.detail);

    // Display raw JSON
    document.getElementById('location-json').textContent = JSON.stringify(e.detail, null, 2);

    // Show success toast if available
    if (typeof toastr !== 'undefined') {
        toastr.success('{{ __("Location selected successfully!") }}');
    }
});

// Alternative: Use callback function
window.onLocationSelected = function(data) {
    console.log('📍 Callback received:', data);
};
</script>
@endsection
