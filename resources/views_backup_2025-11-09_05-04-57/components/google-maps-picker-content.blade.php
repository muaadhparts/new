{{-- Google Maps Picker Content - Reusable inner content --}}

<div class="map-picker-section">
    <div id="map-alert-container"></div>

    <div id="map-picker-container">
        <div class="map-search-wrapper">
            <input type="text" id="map-search-input" placeholder="@lang('Search for an address...')">
        </div>
        <div id="location-map"></div>
        <div id="map-loading-overlay">
            <div class="spinner"></div>
        </div>
    </div>

    <div class="map-controls">
        <button type="button" class="btn btn-secondary" id="current-location-btn">
            <i class="fas fa-crosshairs"></i> @lang('My Current Location')
        </button>
        <button type="button" class="btn btn-secondary" id="reset-location-btn">
            <i class="fas fa-redo"></i> @lang('Reset')
        </button>
    </div>

    <div class="location-info-display" id="location-info-display">
        <h6>@lang('Selected Location Details')</h6>
        <div class="location-info-grid">
            <div class="location-info-item">
                <label>@lang('Country')</label>
                <div class="value" id="country-display">-</div>
            </div>
            <div class="location-info-item">
                <label>@lang('State/Region')</label>
                <div class="value" id="state-display">-</div>
            </div>
            <div class="location-info-item">
                <label>@lang('City')</label>
                <div class="value" id="city-display">-</div>
            </div>
            <div class="location-info-item">
                <label>@lang('Coordinates')</label>
                <div class="value" id="coordinates-display">-</div>
            </div>
            <div class="location-info-item" style="grid-column: 1 / -1;">
                <label>@lang('Address')</label>
                <div class="value" id="address-display">-</div>
            </div>
        </div>
    </div>
</div>

{{-- Hidden fields to store location data --}}
<input type="hidden" name="customer_country" id="customer_country">
<input type="hidden" name="customer_state" id="customer_state">
<input type="hidden" name="customer_city" id="customer_city">
<input type="hidden" name="latitude" id="latitude">
<input type="hidden" name="longitude" id="longitude">
<input type="hidden" name="customer_address" id="customer_address">
