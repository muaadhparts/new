{{-- Google Maps Modal Component --}}
{{--
    Usage: @include('partials.google-maps-modal')
    This modal allows users to select location from Google Maps
    and auto-fill country, state, city fields
--}}

<div class="modal fade" id="mapModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header map-modal-header">
                <h5 class="modal-name">{{ __('Select Location from Map') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="alert-container-modal" class="p-3"></div>

                <div class="p-4">
                    <div id="map-container" class="map-container">
                        <div class="map-search-wrapper">
                            <input type="text" id="map-search-input" class="map-search-input" placeholder="{{ __('Search for an address...') }}">
                        </div>
                        <div id="map" style="width: 100%; height: 100%;"></div>
                        <div class="map-loading-overlay" id="loading-overlay-modal">
                            <div class="map-spinner"></div>
                        </div>
                    </div>

                    <div class="map-buttons-container">
                        <button class="btn btn-secondary" id="reset-btn-modal" type="button">
                            {{ __('Reset') }}
                        </button>
                        <button class="btn btn-secondary" id="current-location-btn-modal" type="button">
                            {{ __('Current Location') }}
                        </button>
                    </div>

                    <div class="map-location-info" id="location-info-modal">
                        <h6 class="map-location-name">{{ __('Selected Location Information') }}</h6>

                        <div class="map-location-grid">
                            <div class="map-location-item">
                                <label class="map-location-label">{{ __('Country') }} ({{ __('Arabic') }})</label>
                                <div id="country-ar-modal" class="map-location-value">-</div>
                            </div>
                            <div class="map-location-item">
                                <label class="map-location-label">{{ __('Country') }} ({{ __('English') }})</label>
                                <div id="country-en-modal" class="map-location-value">-</div>
                            </div>
                            <div class="map-location-item">
                                <label class="map-location-label">{{ __('State') }} ({{ __('Arabic') }})</label>
                                <div id="state-ar-modal" class="map-location-value">-</div>
                            </div>
                            <div class="map-location-item">
                                <label class="map-location-label">{{ __('State') }} ({{ __('English') }})</label>
                                <div id="state-en-modal" class="map-location-value">-</div>
                            </div>
                            <div class="map-location-item">
                                <label class="map-location-label">{{ __('City') }} ({{ __('Arabic') }})</label>
                                <div id="city-ar-modal" class="map-location-value">-</div>
                            </div>
                            <div class="map-location-item">
                                <label class="map-location-label">{{ __('City') }} ({{ __('English') }})</label>
                                <div id="city-en-modal" class="map-location-value">-</div>
                            </div>
                        </div>

                        <div class="map-coordinates-row">
                            <div class="map-coordinate-item">
                                <label class="map-coordinate-label">{{ __('Latitude') }}</label>
                                <div id="latitude-value-modal" class="map-coordinate-value">-</div>
                            </div>
                            <div class="map-coordinate-item">
                                <label class="map-coordinate-label">{{ __('Longitude') }}</label>
                                <div id="longitude-value-modal" class="map-coordinate-value">-</div>
                            </div>
                        </div>

                        <div class="map-address-box">
                            <div class="map-address-item">
                                <label class="map-location-label">{{ __('Full Address') }}</label>
                                <div id="full-address-modal" class="map-location-value">-</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary" id="use-location-btn-modal" disabled>{{ __('Use This Location') }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
{{-- POLICY: Google Maps loads ONLY if API key exists in api_credentials table --}}
{{-- Using @once to prevent duplicate loading (DATA_FLOW_POLICY - no @php) --}}
@if(!empty($googleMapsApiKey))
    @once
    <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsApiKey }}&libraries=places&language={{ app()->getLocale() }}" async defer></script>
    @endonce
@endif

<script>
// Check if already initialized to prevent duplicate definitions
if (typeof window.GoogleMapsModalInitialized === 'undefined') {
    window.GoogleMapsModalInitialized = true;

    // Google Maps variables for modal (in window scope to avoid redeclaration)
    window.mapModal = null;
    window.markerModal = null;
    window.geocoderModal = null;
    window.searchBoxModal = null;
    window.debounceTimerModal = null;
    window.selectedLocationData = null;

    // Constants
    window.DEBOUNCE_DELAY = 400;
    window.DEFAULT_CENTER = { lat: 24.7136, lng: 46.6753 }; // Riyadh, Saudi Arabia
}

// Only define functions if not already defined
if (typeof window.waitForGoogleMaps === 'undefined') {

// Wait for Google Maps to load
window.waitForGoogleMaps = function(callback) {
    if (typeof google !== 'undefined' && google.maps) {
        callback();
    } else {
        setTimeout(() => window.waitForGoogleMaps(callback), 100);
    }
}

// Initialize map when modal is shown
$('#mapModal').on('shown.bs.modal', function() {
    if (!window.mapModal) {
        window.waitForGoogleMaps(window.initializeMap);
    } else {
        google.maps.event.trigger(window.mapModal, 'resize');
    }
});

window.initializeMap = function() {
    window.geocoderModal = new google.maps.Geocoder();

    window.mapModal = new google.maps.Map(document.getElementById('map'), {
        center: window.DEFAULT_CENTER,
        zoom: 12,
        mapTypeControl: true,
        streetViewControl: false,
        fullscreenControl: true,
    });

    window.markerModal = new google.maps.Marker({
        map: window.mapModal,
        draggable: true,
        animation: google.maps.Animation.DROP,
    });

    // Setup search box
    const searchInput = document.getElementById('map-search-input');
    window.searchBoxModal = new google.maps.places.SearchBox(searchInput);

    // Bias search results to map viewport
    window.mapModal.addListener('bounds_changed', () => {
        window.searchBoxModal.setBounds(window.mapModal.getBounds());
    });

    // Handle search selection
    window.searchBoxModal.addListener('places_changed', () => {
        const places = window.searchBoxModal.getPlaces();
        if (places.length === 0) return;

        const place = places[0];
        if (!place.geometry || !place.geometry.location) return;

        window.mapModal.setCenter(place.geometry.location);
        window.markerModal.setPosition(place.geometry.location);
        window.markerModal.setVisible(true);

        window.handleLocationChange(place.geometry.location.lat(), place.geometry.location.lng());
    });

    // Map click event
    window.mapModal.addListener('click', (event) => {
        window.markerModal.setPosition(event.latLng);
        window.markerModal.setVisible(true);
        window.handleLocationChange(event.latLng.lat(), event.latLng.lng());
    });

    // Marker drag event
    window.markerModal.addListener('dragend', () => {
        const position = window.markerModal.getPosition();
        window.handleLocationChange(position.lat(), position.lng());
    });

    // Button events
    document.getElementById('use-location-btn-modal').addEventListener('click', window.useLocation);
    document.getElementById('reset-btn-modal').addEventListener('click', window.resetSelection);
    document.getElementById('current-location-btn-modal').addEventListener('click', window.getCurrentLocationModal);
}

// Handle location change with debouncing
window.handleLocationChange = function(lat, lng) {
    clearTimeout(window.debounceTimerModal);
    window.debounceTimerModal = setTimeout(() => {
        window.reverseGeocode(lat, lng);
    }, window.DEBOUNCE_DELAY);
}

// Reverse geocode coordinates
window.reverseGeocode = async function(lat, lng) {
    window.showLoadingModal(true);

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        const headers = {
            'Content-Type': 'application/json'
        };

        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken.content;
        }

        const response = await fetch('/geocoding/reverse', {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({
                latitude: lat,
                longitude: lng
            })
        });

        const result = await response.json();

        // التحقق إذا كانت الدولة تحتاج مزامنة
        if (result.success && result.needs_sync) {
            window.showLoadingModal(false);

            // بدء عملية المزامنة مع شريط التحميل
            await window.syncCountryWithProgress(result, lat, lng);
            return;
        }

        if (result.success && !result.needs_sync) {
            window.selectedLocationData = result.data;
            window.displayLocationInfoModal(result.data);
            document.getElementById('use-location-btn-modal').disabled = false;
            window.showAlertModal('{{ __("Location selected successfully") }}', 'success');
        } else if (!result.success) {
            window.showAlertModal(result.message || '{{ __("Failed to get location information") }}', 'error');
        }
    } catch (error) {
        window.showAlertModal('{{ __("Server connection error") }}', 'error');
    } finally {
        window.showLoadingModal(false);
    }
}

// مزامنة الدولة مع شريط التحميل
window.syncCountryWithProgress = async function(syncData, lat, lng) {
    const countryName = syncData.country_name;
    const countryCode = syncData.country_code;
    const countryNameAr = syncData.country_name_ar || countryName;

    // إظهار شريط التحميل المخصص
    window.showSyncProgressModal(countryNameAr);

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        const headers = {
            'Content-Type': 'application/json'
        };

        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken.content;
        }

        // بدء المزامنة
        const syncResponse = await fetch('/geocoding/sync-country', {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({
                country_name: countryName,
                country_code: countryCode
            })
        });

        const syncResult = await syncResponse.json();

        if (syncResult.success) {
            window.updateSyncProgress(100, syncResult.message);

            // انتظار قليل ثم إعادة محاولة الـ geocode
            setTimeout(async () => {
                window.hideSyncProgressModal();
                // إعادة محاولة الـ geocode بعد المزامنة
                await window.reverseGeocode(lat, lng);
            }, 1500);
        } else {
            window.updateSyncProgress(0, syncResult.message || 'فشل في المزامنة');

            setTimeout(() => {
                window.hideSyncProgressModal();
                window.showAlertModal(syncResult.message || 'فشل في استيراد بيانات الدولة', 'error');
            }, 2000);
        }
    } catch (error) {
        window.hideSyncProgressModal();
        window.showAlertModal('{{ __("Server connection error") }}', 'error');
    }
}

// إظهار نافذة شريط التحميل للمزامنة
window.showSyncProgressModal = function(countryName) {
    // إزالة النافذة السابقة إن وجدت
    $('#syncProgressOverlay').remove();

    const html = `
        <div id="syncProgressOverlay" class="sync-progress-overlay">
            <div class="sync-progress-card">
                <div class="sync-progress-spinner"></div>
                <h4 class="sync-progress-name">{{ __('Importing Country Data') }}</h4>
                <p class="sync-progress-country">${countryName}</p>
                <div class="sync-progress-bar-bg">
                    <div id="syncProgressBar" class="sync-progress-bar"></div>
                </div>
                <p id="syncProgressMessage" class="sync-progress-message">{{ __('Starting import...') }}</p>
                <p class="sync-progress-note">{{ __('This step happens once per country') }}</p>
            </div>
        </div>
    `;

    $('body').append(html);

    // تحديث شريط التحميل بشكل تدريجي
    let progress = 0;
    window.syncProgressInterval = setInterval(() => {
        if (progress < 90) {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            window.updateSyncProgress(progress);
        }
    }, 500);
}

// تحديث شريط التحميل
window.updateSyncProgress = function(percent, message) {
    $('#syncProgressBar').css('width', percent + '%');
    if (message) {
        $('#syncProgressMessage').text(message);
    }
}

// إخفاء نافذة المزامنة
window.hideSyncProgressModal = function() {
    if (window.syncProgressInterval) {
        clearInterval(window.syncProgressInterval);
    }
    $('#syncProgressOverlay').fadeOut(300, function() {
        $(this).remove();
    });
}

// Display location information in modal
window.displayLocationInfoModal = function(data) {
    document.getElementById('country-ar-modal').textContent = data.country?.name_ar || '-';
    document.getElementById('country-en-modal').textContent = data.country?.name || '-';
    document.getElementById('state-ar-modal').textContent = data.state?.name_ar || '-';
    document.getElementById('state-en-modal').textContent = data.state?.name || '-';
    document.getElementById('city-ar-modal').textContent = data.city?.name_ar || '-';
    document.getElementById('city-en-modal').textContent = data.city?.name || '-';
    document.getElementById('latitude-value-modal').textContent = data.coordinates?.latitude.toFixed(6) || '-';
    document.getElementById('longitude-value-modal').textContent = data.coordinates?.longitude.toFixed(6) || '-';
    document.getElementById('full-address-modal').textContent = data.address?.ar || data.address?.en || '-';

    document.getElementById('location-info-modal').classList.add('active');
}

// Use selected location - populate form fields
window.useLocation = function() {
    if (!window.selectedLocationData) {
        return;
    }

    // Update hidden latitude/longitude fields (supports multiple field names)
    const lat = window.selectedLocationData.coordinates?.latitude || '';
    const lng = window.selectedLocationData.coordinates?.longitude || '';
    $('[name="latitude"], #latitude, [name="warehouse_lat"], #warehouse_lat').val(lat);
    $('[name="longitude"], #longitude, [name="warehouse_lng"], #warehouse_lng').val(lng);

    // Update address field (supports multiple field names)
    const fullAddress = window.selectedLocationData.address?.ar || window.selectedLocationData.address?.en || '';
    $('[name="address"], [name="customer_address"], #address, [name="warehouse_address"], #warehouse_address').val(fullAddress);

    // Get data from API response
    const countryId = window.selectedLocationData.country?.id;
    const countryName = window.selectedLocationData.country?.name; // English name
    const stateId = window.selectedLocationData.state?.id;
    const cityId = window.selectedLocationData.city?.id;

    if (!countryName) {
        if (typeof toastr !== 'undefined') {
            toastr.warning('{{ __("Country not found") }}');
        }
        $('#mapModal').modal('hide');
        return;
    }

    // Step 1: Find and select country by name (since value is country name)
    window.selectCountryByName(countryName, countryId, stateId, cityId);
}

// Select country by name (since value is country name, not ID)
window.selectCountryByName = function(countryName, countryId, stateId, cityId) {
    let countryFound = false;

    // Set flag to indicate this is a map selection
    window.isMapSelection = true;

    $('[name="country"], [name="customer_country"], #select_country, #country, #warehouse_country').find('option').each(function() {
        const optionValue = $(this).val(); // value is country name
        const optionText = $(this).text().trim();

        // Match by value (country name) or display text
        if (optionValue === countryName || optionText === countryName) {
            $(this).prop('selected', true);
            countryFound = true;

            const selectElement = $(this).parent()[0];
            const selectId = selectElement.id;
            const displayName = optionText;

            // Update country NiceSelect display immediately
            const countryNiceSelect = selectElement.nextElementSibling;
            if (countryNiceSelect && countryNiceSelect.classList.contains('nice-select')) {
                const currentSpan = countryNiceSelect.querySelector('.current');
                if (currentSpan) {
                    currentSpan.textContent = displayName;
                }
            }

            // Setup callback for when states are loaded
            if (stateId) {
                // Pass reinitFunction to callback
                window.onStatesLoaded = function(reinitStateNiceSelect) {
                    // Now select the state (passing the stateId and cityId)
                    window.selectStateById(stateId, cityId, reinitStateNiceSelect);

                    // Clear callback
                    window.onStatesLoaded = null;
                };
            } else {
                window.onStatesLoaded = function(reinitStateNiceSelect) {
                    // No state to select, just reinit with no value
                    if (reinitStateNiceSelect) {
                        reinitStateNiceSelect(null);
                    }

                    // Show success and close
                    window.isMapSelection = false;
                    window.showFinalSuccessMessage();
                    window.onStatesLoaded = null;
                };
            }

            // Trigger change to load states via AJAX
            $(this).parent().trigger('change');

            return false; // break loop
        }
    });

    if (!countryFound) {
        window.isMapSelection = false;
        if (typeof toastr !== 'undefined') {
            toastr.warning('{{ __("Country not found in list") }}');
        }
        $('#mapModal').modal('hide');
    }
}


// Select state by ID (called after states are loaded)
window.selectStateById = function(stateId, cityId, reinitStateNiceSelect) {
    let stateFound = false;

    // Log all available state options for debugging
    const stateSelects = $('[name="state"], [name="customer_state"], #show_state, #state, [name="warehouse_state"], #warehouse_state, [name="state_id"]');

    stateSelects.find('option').each(function(index) {
        const optionValue = $(this).val();
        const optionText = $(this).text();

        // Match by state ID (value contains state ID)
        if (optionValue && parseInt(optionValue) === parseInt(stateId)) {
            $(this).prop('selected', true);
            stateFound = true;

            const selectElement = $(this).parent()[0];
            const stateName = $(this).text();

            // Reinitialize state NiceSelect with the selected value
            if (reinitStateNiceSelect) {
                reinitStateNiceSelect(optionValue);
            }

            // Setup callback for when cities are loaded
            if (cityId) {
                // Pass reinitFunction to callback
                window.onCitiesLoaded = function(reinitCityNiceSelect) {
                    // Now select the city
                    window.selectCityById(cityId, reinitCityNiceSelect);

                    // Clear callback
                    window.onCitiesLoaded = null;
                };
            } else {
                window.onCitiesLoaded = function(reinitCityNiceSelect) {
                    // No city to select, just reinit with no value
                    if (reinitCityNiceSelect) {
                        reinitCityNiceSelect(null);
                    }

                    // Show success and close
                    window.isMapSelection = false;
                    window.showFinalSuccessMessage();
                    window.onCitiesLoaded = null;
                };
            }

            // Trigger change to load cities via AJAX
            $(this).parent().trigger('change');

            return false; // break loop
        }
    });

    if (!stateFound) {
        // State not found, reinit with no value
        if (reinitStateNiceSelect) {
            reinitStateNiceSelect(null);
        }

        // Still show success
        window.isMapSelection = false;
        window.showFinalSuccessMessage();
    }
}

// Select city by ID (called after cities are loaded)
window.selectCityById = function(cityId, reinitCityNiceSelect) {
    let cityFound = false;

    // Get city data from API response for matching
    const cityIdFromApi = window.selectedLocationData?.city?.id || cityId;
    const cityNameEn = window.selectedLocationData?.city?.name || '';
    const cityNameAr = window.selectedLocationData?.city?.name_ar || '';

    // Log all available city options for debugging
    const citySelects = $('[name="city"], [name="customer_city"], #show_city, #city, [name="warehouse_city"], #warehouse_city, [name="city_id"]');

    citySelects.find('option').each(function(index) {
        const optionValue = $(this).val();
        const optionText = $(this).text().trim();

        // Match by city ID (for user/courier profiles) OR city name (for warehouse/checkout)
        const matchById = parseInt(optionValue) === parseInt(cityIdFromApi);
        const matchByEnName = optionValue.toLowerCase() === cityNameEn.toLowerCase();
        const matchByArName = optionValue.toLowerCase() === cityNameAr.toLowerCase();
        const matchByEnText = optionText.toLowerCase() === cityNameEn.toLowerCase();
        const matchByArText = optionText.toLowerCase() === cityNameAr.toLowerCase();

        if (optionValue && (matchById || matchByEnName || matchByArName || matchByEnText || matchByArText)) {
            $(this).prop('selected', true);
            cityFound = true;

            const cityDisplayName = optionText;

            // Reinitialize city NiceSelect with the selected value
            if (reinitCityNiceSelect) {
                reinitCityNiceSelect(optionValue);
            }

            // Show success after NiceSelect is reinitialized
            setTimeout(() => {
                window.isMapSelection = false;
                window.showFinalSuccessMessage();
            }, 100);

            return false; // break loop
        }
    });

    if (!cityFound) {
        // City not found, reinit with no value
        if (reinitCityNiceSelect) {
            reinitCityNiceSelect(null);
        }

        // Still show success
        window.isMapSelection = false;
        window.showFinalSuccessMessage();
    }
}

// Show final success message once
window.showFinalSuccessMessage = function() {
    if (typeof toastr !== 'undefined') {
        toastr.success('{{ __("Location saved successfully! All fields have been filled automatically") }}');
    }
    $('#mapModal').modal('hide');
}

// Reset selection
window.resetSelection = function() {
    window.markerModal.setVisible(false);
    window.selectedLocationData = null;
    document.getElementById('use-location-btn-modal').disabled = true;
    document.getElementById('location-info-modal').classList.remove('active');
    document.getElementById('map-search-input').value = '';
    window.mapModal.setCenter(window.DEFAULT_CENTER);
    window.mapModal.setZoom(12);
    window.clearAlertModal();
}

// Get current location
window.getCurrentLocationModal = function() {
    if (navigator.geolocation) {
        window.showLoadingModal(true);
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const pos = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                window.mapModal.setCenter(pos);
                window.markerModal.setPosition(pos);
                window.markerModal.setVisible(true);
                window.handleLocationChange(pos.lat, pos.lng);
            },
            () => {
                window.showLoadingModal(false);
                window.showAlertModal('{{ __("Failed to get your current location") }}', 'error');
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    } else {
        window.showAlertModal('{{ __("Browser does not support location service") }}', 'error');
    }
}

// Show/hide loading overlay
window.showLoadingModal = function(show) {
    const overlay = document.getElementById('loading-overlay-modal');
    if (overlay) {
        if (show) {
            overlay.classList.add('active');
        } else {
            overlay.classList.remove('active');
        }
    }
}

// Show alert message
window.showAlertModal = function(message, type) {
    const container = document.getElementById('alert-container-modal');
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    container.innerHTML = `<div class="alert ${alertClass} mb-0">${message}</div>`;
}

// Clear alert
window.clearAlertModal = function() {
    const container = document.getElementById('alert-container-modal');
    if (container) {
        container.innerHTML = '';
    }
}

// Close the check for function definitions
} // end of if (typeof window.waitForGoogleMaps === 'undefined')
</script>
@endpush
