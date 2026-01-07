{{--
    Global Google Maps Location Picker Modal
    =========================================
    Include ONCE in layout (already done in layouts/front.blade.php)

    Usage from any page:
    <button type="button" onclick="openMapPicker({
        addressField: '#address',
        zipField: '#zip',
        latField: '#latitude',
        lngField: '#longitude',
        callback: function(data) { console.log(data); }
    })">
        <i class="fas fa-map-marker-alt"></i> Select on Map
    </button>
--}}

@if(!empty($googleMapsApiKey))
{{-- Map Modal --}}
<div class="modal fade" id="globalMapModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: var(--radius-lg, 12px); overflow: hidden;">
            <div class="modal-header" style="background: var(--action-primary); color: var(--text-on-primary, #fff); border: none;">
                <h5 class="modal-title">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    @lang('Select your location')
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                {{-- Search Box --}}
                <div class="p-3" style="background: var(--bg-secondary); border-bottom: 1px solid var(--border-default);">
                    <div class="input-group">
                        <span class="input-group-text" style="background: var(--bg-primary); border-color: var(--border-default);">
                            <i class="fas fa-search" style="color: var(--text-muted);"></i>
                        </span>
                        <input type="text" id="global-map-search-input" class="form-control"
                               style="border-color: var(--border-default);"
                               placeholder="@lang('Search for a location...')" autocomplete="off">
                    </div>
                </div>

                {{-- Map Container --}}
                <div id="global-map" style="height: 350px; width: 100%;"></div>

                {{-- Location Display --}}
                <div class="p-3" style="background: var(--bg-secondary); border-top: 1px solid var(--border-default);">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="flex-grow-1">
                            <small class="d-block mb-1" style="color: var(--text-muted);">@lang('Selected Location'):</small>
                            <div id="global-coords-display" class="fw-bold" style="word-break: break-word; color: var(--text-primary);">
                                @lang('Click on map or search to select location')
                            </div>
                        </div>
                        <button type="button" class="m-btn m-btn--secondary m-btn--sm flex-shrink-0" id="global-my-location-btn">
                            <i class="fas fa-crosshairs"></i> @lang('My Location')
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background: var(--bg-primary); border-top: 1px solid var(--border-default);">
                <button type="button" class="m-btn m-btn--secondary" data-bs-dismiss="modal">@lang('Close')</button>
                <button type="button" class="m-btn m-btn--primary" id="global-confirm-location-btn" disabled>
                    <i class="fas fa-check me-1"></i> @lang('Confirm Location')
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
/* Google Places Autocomplete z-index fix */
.pac-container {
    z-index: 10000 !important;
}

/* Modal - No transparency */
#globalMapModal .modal-content {
    background: var(--bg-primary, #fff) !important;
    opacity: 1 !important;
}
</style>
@endpush

@push('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsApiKey }}&libraries=places&language={{ app()->getLocale() == 'ar' ? 'ar' : 'en' }}"></script>
<script>
/**
 * Global Map Picker - Reusable across all pages
 */
(function() {
    // State
    let map, marker, geocoder, autocomplete;
    let selectedLat = null, selectedLng = null, selectedAddress = '';
    let targetFields = {};
    let isInitialized = false;

    // Config
    const currentLocale = '{{ app()->getLocale() == "ar" ? "ar" : "en" }}';
    const defaultCenter = { lat: 24.7136, lng: 46.6753 };
    const defaultZoom = 6;

    // =====================================================
    // Global Function - Call from anywhere
    // =====================================================
    window.openMapPicker = function(options = {}) {
        targetFields = {
            addressField: options.addressField || null,
            zipField: options.zipField || null,
            latField: options.latField || null,
            lngField: options.lngField || null,
            callback: options.callback || null
        };

        // Reset state
        selectedLat = null;
        selectedLng = null;
        selectedAddress = '';

        $('#globalMapModal').modal('show');
    };

    // =====================================================
    // Modal Events
    // =====================================================
    $('#globalMapModal').on('shown.bs.modal', function() {
        if (!isInitialized) {
            initMap();
            isInitialized = true;
        } else {
            google.maps.event.trigger(map, 'resize');
            showDefaultView();
        }
    });

    // =====================================================
    // Map Functions
    // =====================================================
    function initMap() {
        if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
            $('#global-coords-display').html('<span class="text-danger">@lang("Map service unavailable")</span>');
            return;
        }

        geocoder = new google.maps.Geocoder();

        map = new google.maps.Map(document.getElementById('global-map'), {
            center: defaultCenter,
            zoom: defaultZoom,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true
        });

        marker = new google.maps.Marker({
            map: map,
            draggable: true,
            visible: false
        });

        // Search autocomplete
        const input = document.getElementById('global-map-search-input');
        if (input) {
            autocomplete = new google.maps.places.Autocomplete(input, {
                types: ['geocode', 'establishment'],
                fields: ['geometry', 'formatted_address', 'address_components']
            });

            autocomplete.addListener('place_changed', function() {
                const place = autocomplete.getPlace();
                if (place.geometry && place.geometry.location) {
                    const lat = place.geometry.location.lat();
                    const lng = place.geometry.location.lng();
                    map.setCenter({ lat, lng });
                    map.setZoom(15);
                    setLocation(lat, lng, place.formatted_address, place.address_components);
                }
            });
        }

        // Click on map
        map.addListener('click', function(e) {
            getAddressAndSetLocation(e.latLng.lat(), e.latLng.lng());
        });

        // Drag marker
        marker.addListener('dragend', function() {
            const pos = marker.getPosition();
            getAddressAndSetLocation(pos.lat(), pos.lng());
        });

        // Buttons
        document.getElementById('global-my-location-btn').addEventListener('click', getMyLocation);
        document.getElementById('global-confirm-location-btn').addEventListener('click', confirmLocation);

        showDefaultView();
    }

    function showDefaultView() {
        if (marker) marker.setVisible(false);
        if (map) {
            map.setCenter(defaultCenter);
            map.setZoom(defaultZoom);
        }
        $('#global-confirm-location-btn').prop('disabled', true);
        $('#global-coords-display').html('@lang("Click on map or search to select location")');
        $('#global-map-search-input').val('');
    }

    function setLocation(lat, lng, address, addressComponents) {
        selectedLat = lat;
        selectedLng = lng;
        selectedAddress = address || '';

        // Extract postal code from address components
        if (addressComponents) {
            for (let comp of addressComponents) {
                if (comp.types.includes('postal_code')) {
                    selectedAddress = { text: address, postalCode: comp.long_name };
                    break;
                }
            }
        }

        marker.setPosition({ lat, lng });
        marker.setVisible(true);
        updateDisplay(lat, lng, typeof selectedAddress === 'object' ? selectedAddress.text : address);
        $('#global-confirm-location-btn').prop('disabled', false);
    }

    function updateDisplay(lat, lng, address) {
        let html = '';
        if (address) {
            html = '<div class="mb-1">' + address + '</div>';
            html += '<small class="text-muted">(' + lat.toFixed(6) + ', ' + lng.toFixed(6) + ')</small>';
        } else {
            html = lat.toFixed(6) + ', ' + lng.toFixed(6);
        }
        $('#global-coords-display').html(html);
    }

    function getAddressAndSetLocation(lat, lng) {
        $('#global-coords-display').html('<i class="fas fa-spinner fa-spin"></i> @lang("Loading address...")');

        geocoder.geocode({
            location: { lat, lng },
            language: currentLocale
        }, function(results, status) {
            if (status === 'OK' && results[0]) {
                setLocation(lat, lng, results[0].formatted_address, results[0].address_components);
            } else {
                setLocation(lat, lng, null, null);
            }
        });
    }

    function getMyLocation() {
        if (!navigator.geolocation) {
            $('#global-coords-display').html('<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> @lang("Browser does not support geolocation")</span>');
            return;
        }

        const btn = $('#global-my-location-btn');
        const originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
        $('#global-coords-display').html('<i class="fas fa-spinner fa-spin"></i> @lang("Detecting your location...")');

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                map.setCenter({ lat, lng });
                map.setZoom(15);
                getAddressAndSetLocation(lat, lng);

                btn.html(originalText).prop('disabled', false);
            },
            function(error) {
                btn.html(originalText).prop('disabled', false);

                let msg = '@lang("Could not get your location")';
                if (error.code === error.PERMISSION_DENIED) {
                    msg = '@lang("Location permission denied")';
                }

                $('#global-coords-display').html(
                    '<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> ' + msg + '</span>'
                );
            },
            { enableHighAccuracy: false, timeout: 30000, maximumAge: 60000 }
        );
    }

    // =====================================================
    // Confirm Location
    // =====================================================
    function confirmLocation() {
        if (!selectedLat || !selectedLng) return;

        const addressText = typeof selectedAddress === 'object' ? selectedAddress.text : selectedAddress;
        const postalCode = typeof selectedAddress === 'object' ? selectedAddress.postalCode : null;

        // Fill target fields
        if (targetFields.addressField) {
            $(targetFields.addressField).val(addressText || '');
        }
        if (targetFields.zipField && postalCode) {
            $(targetFields.zipField).val(postalCode);
        }
        if (targetFields.latField) {
            $(targetFields.latField).val(selectedLat);
        }
        if (targetFields.lngField) {
            $(targetFields.lngField).val(selectedLng);
        }

        // Execute callback
        if (typeof targetFields.callback === 'function') {
            targetFields.callback({
                lat: selectedLat,
                lng: selectedLng,
                address: addressText,
                postalCode: postalCode
            });
        }

        // Close modal
        $('#globalMapModal').modal('hide');

        // Success message
        if (typeof toastr !== 'undefined') {
            toastr.success('@lang("Location selected successfully")');
        }
    }
})();
</script>
@endpush
@endif
