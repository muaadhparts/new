{{-- Google Maps Modal Component --}}
{{--
    Usage: @include('partials.google-maps-modal')
    This modal allows users to select location from Google Maps
    and auto-fill country, state, city fields
--}}

<div class="modal fade" id="mapModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h5 class="modal-title">{{ __('Select Location from Map') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="alert-container-modal" style="padding: 15px;"></div>

                <div style="padding: 20px;">
                    <div id="map-container" style="position: relative; height: 500px; border-radius: 8px; overflow: hidden; border: 2px solid #e0e0e0;">
                        <div class="map-search" style="position: absolute; top: 10px; right: 10px; left: 10px; z-index: 10;">
                            <input type="text" id="map-search-input" placeholder="{{ __('Search for an address...') }}"
                                   style="width: 100%; padding: 12px 15px; border: 2px solid #667eea; border-radius: 8px; font-size: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); background: white;">
                        </div>
                        <div id="map" style="width: 100%; height: 100%;"></div>
                        <div class="loading-overlay" id="loading-overlay-modal" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.9); display: none; align-items: center; justify-content: center; z-index: 20;">
                            <div class="spinner" style="border: 3px solid #f3f3f3; border-top: 3px solid #667eea; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite;"></div>
                        </div>
                    </div>

                    <div class="buttons-container" style="display: flex; gap: 10px; margin-top: 15px;">
                        <button class="btn btn-secondary" id="reset-btn-modal" type="button">
                            {{ __('Reset') }}
                        </button>
                        <button class="btn btn-secondary" id="current-location-btn-modal" type="button">
                            {{ __('Current Location') }}
                        </button>
                    </div>

                    <div class="location-info" id="location-info-modal" style="display: none; background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px;">
                        <h6 style="font-size: 16px; color: #333; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #667eea;">{{ __('Selected Location Information') }}</h6>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                            <div style="background: white; padding: 15px; border-radius: 6px; border-right: 3px solid #667eea;">
                                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">{{ __('Country') }} ({{ __('Arabic') }})</label>
                                <div id="country-ar-modal" style="font-size: 14px; color: #333;">-</div>
                            </div>
                            <div style="background: white; padding: 15px; border-radius: 6px; border-right: 3px solid #667eea;">
                                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">{{ __('Country') }} ({{ __('English') }})</label>
                                <div id="country-en-modal" style="font-size: 14px; color: #333;">-</div>
                            </div>
                            <div style="background: white; padding: 15px; border-radius: 6px; border-right: 3px solid #667eea;">
                                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">{{ __('State') }} ({{ __('Arabic') }})</label>
                                <div id="state-ar-modal" style="font-size: 14px; color: #333;">-</div>
                            </div>
                            <div style="background: white; padding: 15px; border-radius: 6px; border-right: 3px solid #667eea;">
                                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">{{ __('State') }} ({{ __('English') }})</label>
                                <div id="state-en-modal" style="font-size: 14px; color: #333;">-</div>
                            </div>
                            <div style="background: white; padding: 15px; border-radius: 6px; border-right: 3px solid #667eea;">
                                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">{{ __('City') }} ({{ __('Arabic') }})</label>
                                <div id="city-ar-modal" style="font-size: 14px; color: #333;">-</div>
                            </div>
                            <div style="background: white; padding: 15px; border-radius: 6px; border-right: 3px solid #667eea;">
                                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">{{ __('City') }} ({{ __('English') }})</label>
                                <div id="city-en-modal" style="font-size: 14px; color: #333;">-</div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 15px; margin-top: 15px;">
                            <div style="flex: 1; background: white; padding: 15px; border-radius: 6px; text-align: center;">
                                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">{{ __('Latitude') }}</label>
                                <div id="latitude-value-modal" style="font-size: 16px; font-weight: 600; color: #667eea;">-</div>
                            </div>
                            <div style="flex: 1; background: white; padding: 15px; border-radius: 6px; text-align: center;">
                                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">{{ __('Longitude') }}</label>
                                <div id="longitude-value-modal" style="font-size: 16px; font-weight: 600; color: #667eea;">-</div>
                            </div>
                        </div>

                        <div style="margin-top: 15px;">
                            <div style="background: white; padding: 15px; border-radius: 6px;">
                                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">{{ __('Full Address') }}</label>
                                <div id="full-address-modal" style="font-size: 14px; color: #333;">-</div>
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

@push('styles')
<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.loading-overlay.active {
    display: flex !important;
}
</style>
@endpush

@push('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places&language={{ app()->getLocale() }}" async defer></script>
<script>
// Google Maps variables for modal
let mapModal, markerModal, geocoderModal, searchBoxModal, debounceTimerModal, selectedLocationData;
const DEBOUNCE_DELAY = 400;
const DEFAULT_CENTER = { lat: 24.7136, lng: 46.6753 }; // Riyadh, Saudi Arabia

// Wait for Google Maps to load
function waitForGoogleMaps(callback) {
    if (typeof google !== 'undefined' && google.maps) {
        callback();
    } else {
        setTimeout(() => waitForGoogleMaps(callback), 100);
    }
}

// Initialize map when modal is shown
$('#mapModal').on('shown.bs.modal', function() {
    if (!mapModal) {
        waitForGoogleMaps(initializeMap);
    } else {
        google.maps.event.trigger(mapModal, 'resize');
    }
});

function initializeMap() {
    geocoderModal = new google.maps.Geocoder();

    mapModal = new google.maps.Map(document.getElementById('map'), {
        center: DEFAULT_CENTER,
        zoom: 12,
        mapTypeControl: true,
        streetViewControl: false,
        fullscreenControl: true,
    });

    markerModal = new google.maps.Marker({
        map: mapModal,
        draggable: true,
        animation: google.maps.Animation.DROP,
    });

    // Setup search box
    const searchInput = document.getElementById('map-search-input');
    searchBoxModal = new google.maps.places.SearchBox(searchInput);

    // Bias search results to map viewport
    mapModal.addListener('bounds_changed', () => {
        searchBoxModal.setBounds(mapModal.getBounds());
    });

    // Handle search selection
    searchBoxModal.addListener('places_changed', () => {
        const places = searchBoxModal.getPlaces();
        if (places.length === 0) return;

        const place = places[0];
        if (!place.geometry || !place.geometry.location) return;

        mapModal.setCenter(place.geometry.location);
        markerModal.setPosition(place.geometry.location);
        markerModal.setVisible(true);

        handleLocationChange(place.geometry.location.lat(), place.geometry.location.lng());
    });

    // Map click event
    mapModal.addListener('click', (event) => {
        markerModal.setPosition(event.latLng);
        markerModal.setVisible(true);
        handleLocationChange(event.latLng.lat(), event.latLng.lng());
    });

    // Marker drag event
    markerModal.addListener('dragend', () => {
        const position = markerModal.getPosition();
        handleLocationChange(position.lat(), position.lng());
    });

    // Button events
    document.getElementById('use-location-btn-modal').addEventListener('click', useLocation);
    document.getElementById('reset-btn-modal').addEventListener('click', resetSelection);
    document.getElementById('current-location-btn-modal').addEventListener('click', getCurrentLocationModal);
}

// Handle location change with debouncing
function handleLocationChange(lat, lng) {
    clearTimeout(debounceTimerModal);
    debounceTimerModal = setTimeout(() => {
        reverseGeocode(lat, lng);
    }, DEBOUNCE_DELAY);
}

// Reverse geocode coordinates
async function reverseGeocode(lat, lng) {
    showLoadingModal(true);

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        const headers = {
            'Content-Type': 'application/json'
        };

        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken.content;
        }

        const response = await fetch('/api/geocoding/reverse', {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({
                latitude: lat,
                longitude: lng
            })
        });

        const result = await response.json();

        if (result.success) {
            selectedLocationData = result.data;
            displayLocationInfoModal(result.data);
            document.getElementById('use-location-btn-modal').disabled = false;
            showAlertModal('{{ __("Location selected successfully") }}', 'success');
        } else {
            showAlertModal('{{ __("Failed to get location information") }}: ' + (result.error || '{{ __("Unknown error") }}'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlertModal('{{ __("Server connection error") }}', 'error');
    } finally {
        showLoadingModal(false);
    }
}

// Display location information in modal
function displayLocationInfoModal(data) {
    document.getElementById('country-ar-modal').textContent = data.country?.name_ar || '-';
    document.getElementById('country-en-modal').textContent = data.country?.name || '-';
    document.getElementById('state-ar-modal').textContent = data.state?.name_ar || '-';
    document.getElementById('state-en-modal').textContent = data.state?.name || '-';
    document.getElementById('city-ar-modal').textContent = data.city?.name_ar || '-';
    document.getElementById('city-en-modal').textContent = data.city?.name || '-';
    document.getElementById('latitude-value-modal').textContent = data.coordinates?.latitude.toFixed(6) || '-';
    document.getElementById('longitude-value-modal').textContent = data.coordinates?.longitude.toFixed(6) || '-';
    document.getElementById('full-address-modal').textContent = data.address?.ar || data.address?.en || '-';

    document.getElementById('location-info-modal').style.display = 'block';
}

// Use selected location - populate form fields
function useLocation() {
    if (!selectedLocationData) return;

    // Update hidden latitude/longitude fields
    $('[name="latitude"], #latitude').val(selectedLocationData.coordinates?.latitude || '');
    $('[name="longitude"], #longitude').val(selectedLocationData.coordinates?.longitude || '');

    // Update address field
    const fullAddress = selectedLocationData.address?.ar || selectedLocationData.address?.en || '';
    $('[name="address"], [name="customer_address"], #address').val(fullAddress);

    // Get IDs from API response
    const countryId = selectedLocationData.country?.id;
    const stateId = selectedLocationData.state?.id;
    const cityId = selectedLocationData.city?.id;

    if (!countryId) {
        if (typeof toastr !== 'undefined') {
            toastr.warning('{{ __("Country ID not found") }}');
        }
        $('#mapModal').modal('hide');
        return;
    }

    // Step 1: Find and select country by ID
    selectCountryById(countryId, stateId, cityId);
}

// Select country by ID and trigger cascade
function selectCountryById(countryId, stateId, cityId) {
    let countryFound = false;

    $('[name="country"], [name="customer_country"], #select_country, #country').find('option').each(function() {
        const optionCountryId = $(this).attr('data'); // data attribute contains country ID

        if (optionCountryId && parseInt(optionCountryId) === parseInt(countryId)) {
            $(this).prop('selected', true);
            countryFound = true;

            // Update NiceSelect display text only
            const selectElement = $(this).parent()[0];
            updateNiceSelectDisplay(selectElement.id, $(this).text());

            // Trigger change to load states via AJAX
            $(this).parent().trigger('change');

            // Wait for states to load, then select state
            if (stateId) {
                waitAndSelectState(stateId, cityId);
            } else {
                // No state, show success and close
                showFinalSuccessMessage();
            }

            return false; // break loop
        }
    });

    if (!countryFound) {
        if (typeof toastr !== 'undefined') {
            toastr.warning('{{ __("Country not found in list") }}');
        }
        $('#mapModal').modal('hide');
    }
}

// Wait for states to load via AJAX, then select by ID
function waitAndSelectState(stateId, cityId) {
    let attempts = 0;
    const maxAttempts = 50; // 5 seconds max

    const checkStatesInterval = setInterval(() => {
        attempts++;

        const stateOptions = $('[name="state"], [name="customer_state"], #show_state, #state').find('option');

        if (stateOptions.length > 0) {
            clearInterval(checkStatesInterval);

            let stateFound = false;

            stateOptions.each(function() {
                const optionValue = $(this).val();

                // Match by state ID (value contains state ID)
                if (optionValue && parseInt(optionValue) === parseInt(stateId)) {
                    $(this).prop('selected', true);
                    stateFound = true;

                    // Update NiceSelect display text only
                    const selectElement = $(this).parent()[0];
                    updateNiceSelectDisplay(selectElement.id, $(this).text());

                    // Trigger change to load cities via AJAX
                    $(this).parent().trigger('change');

                    // Wait for cities to load, then select city
                    if (cityId) {
                        waitAndSelectCity(cityId);
                    } else {
                        // No city, show success and close
                        showFinalSuccessMessage();
                    }

                    return false; // break loop
                }
            });

            if (!stateFound) {
                // State not found, but still show success
                showFinalSuccessMessage();
            }
        } else if (attempts >= maxAttempts) {
            // Timeout waiting for states
            clearInterval(checkStatesInterval);
            showFinalSuccessMessage();
        }
    }, 100); // Check every 100ms
}

// Wait for cities to load via AJAX, then select by name
// Note: City options use city_name as value (not ID) in the current system
function waitAndSelectCity(cityId) {
    let attempts = 0;
    const maxAttempts = 50; // 5 seconds max

    // Get city names from API response for matching
    const cityNameEn = selectedLocationData.city?.name || '';
    const cityNameAr = selectedLocationData.city?.name_ar || '';

    const checkCitiesInterval = setInterval(() => {
        attempts++;

        const cityOptions = $('[name="city"], [name="customer_city"], #show_city, #city').find('option');

        if (cityOptions.length > 0) {
            clearInterval(checkCitiesInterval);

            let cityFound = false;

            cityOptions.each(function() {
                const optionValue = $(this).val();
                const optionText = $(this).text().trim();

                // Match by city name (value contains city_name, not ID)
                if (optionValue && (
                    optionValue.toLowerCase() === cityNameEn.toLowerCase() ||
                    optionValue.toLowerCase() === cityNameAr.toLowerCase() ||
                    optionText.toLowerCase() === cityNameEn.toLowerCase() ||
                    optionText.toLowerCase() === cityNameAr.toLowerCase()
                )) {
                    $(this).prop('selected', true);
                    cityFound = true;

                    // Update NiceSelect display text only
                    const selectElement = $(this).parent()[0];
                    updateNiceSelectDisplay(selectElement.id, $(this).text());

                    return false; // break loop
                }
            });

            // Show final success message (whether city found or not)
            showFinalSuccessMessage();
        } else if (attempts >= maxAttempts) {
            // Timeout waiting for cities
            clearInterval(checkCitiesInterval);
            showFinalSuccessMessage();
        }
    }, 100); // Check every 100ms
}

// Update NiceSelect display text without re-initializing
function updateNiceSelectDisplay(selectId, displayText) {
    if (!selectId) return;

    const selectElement = document.getElementById(selectId);
    if (!selectElement) return;

    // Find the NiceSelect wrapper
    const niceSelectWrapper = selectElement.nextElementSibling;
    if (niceSelectWrapper && niceSelectWrapper.classList.contains('nice-select')) {
        const currentSpan = niceSelectWrapper.querySelector('.current');
        if (currentSpan) {
            currentSpan.textContent = displayText;
        }
    }
}

// Show final success message once
function showFinalSuccessMessage() {
    if (typeof toastr !== 'undefined') {
        toastr.success('{{ __("Location saved successfully! All fields have been filled automatically") }}');
    }
    $('#mapModal').modal('hide');
}

// Reset selection
function resetSelection() {
    markerModal.setVisible(false);
    selectedLocationData = null;
    document.getElementById('use-location-btn-modal').disabled = true;
    document.getElementById('location-info-modal').style.display = 'none';
    document.getElementById('map-search-input').value = '';
    mapModal.setCenter(DEFAULT_CENTER);
    mapModal.setZoom(12);
    clearAlertModal();
}

// Get current location
function getCurrentLocationModal() {
    if (navigator.geolocation) {
        showLoadingModal(true);
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const pos = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                mapModal.setCenter(pos);
                markerModal.setPosition(pos);
                markerModal.setVisible(true);
                handleLocationChange(pos.lat, pos.lng);
            },
            () => {
                showLoadingModal(false);
                showAlertModal('{{ __("Failed to get your current location") }}', 'error');
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    } else {
        showAlertModal('{{ __("Browser does not support location service") }}', 'error');
    }
}

// Show/hide loading overlay
function showLoadingModal(show) {
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
function showAlertModal(message, type) {
    const container = document.getElementById('alert-container-modal');
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    container.innerHTML = `
        <div class="alert ${alertClass}" style="margin-bottom: 0;">
            ${message}
        </div>
    `;
}

// Clear alert
function clearAlertModal() {
    const container = document.getElementById('alert-container-modal');
    if (container) {
        container.innerHTML = '';
    }
}
</script>
@endpush
