<script>
/**
 * Google Maps Location Picker for Merchant Checkout
 */

// Check if Google Maps is available
const googleMapsAvailable = typeof google !== 'undefined' && typeof google.maps !== 'undefined';

// Map Variables
let map, marker, geocoder, autocomplete;
let selectedLat = null;
let selectedLng = null;
let selectedAddress = '';

// Config
const currentLocale = '{{ app()->getLocale() == "ar" ? "ar" : "en" }}';
const defaultCenter = { lat: 24.7136, lng: 46.6753 }; // Saudi Arabia
const defaultZoom = 6;

// Modal Events
$('#mapModal').on('show.bs.modal', function() {
    const savedLat = parseFloat($('#latitude').val());
    const savedLng = parseFloat($('#longitude').val());

    if (savedLat && savedLng && !isNaN(savedLat) && !isNaN(savedLng)) {
        selectedLat = savedLat;
        selectedLng = savedLng;
        selectedAddress = $('#selected-location-info').attr('data-address') || '';
    } else {
        selectedLat = null;
        selectedLng = null;
        selectedAddress = '';
    }

    $('#map-search-input').val('');
});

$('#mapModal').on('shown.bs.modal', function() {
    if (!googleMapsAvailable) {
        $('#coords-display').html('<span class="text-danger">@lang("Map service unavailable")</span>');
        return;
    }

    if (!map) {
        initMap();
    } else {
        google.maps.event.trigger(map, 'resize');
    }

    if (selectedLat && selectedLng) {
        showLocationOnMap(selectedLat, selectedLng, selectedAddress);
    } else {
        showDefaultView();
    }
});

function initMap() {
    if (!googleMapsAvailable) return;

    geocoder = new google.maps.Geocoder();

    map = new google.maps.Map(document.getElementById('map'), {
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
    const input = document.getElementById('map-search-input');
    if (input) {
        autocomplete = new google.maps.places.Autocomplete(input, {
            types: ['geocode', 'establishment'],
            fields: ['geometry', 'formatted_address']
        });

        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            if (place.geometry && place.geometry.location) {
                const lat = place.geometry.location.lat();
                const lng = place.geometry.location.lng();
                map.setCenter({ lat, lng });
                map.setZoom(15);
                setLocation(lat, lng, place.formatted_address);
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
    document.getElementById('my-location-btn').addEventListener('click', getMyLocation);
    document.getElementById('confirm-location-btn').addEventListener('click', confirmLocation);

    $('#coords-display').html('@lang("Click on map or search to select location")');
}

function showDefaultView() {
    if (marker) marker.setVisible(false);
    map.setCenter(defaultCenter);
    map.setZoom(defaultZoom);
    $('#confirm-location-btn').prop('disabled', true);
    $('#coords-display').html('@lang("Click on map or search to select location")');
}

function showLocationOnMap(lat, lng, address) {
    map.setCenter({ lat, lng });
    map.setZoom(15);
    marker.setPosition({ lat, lng });
    marker.setVisible(true);
    updateDisplay(lat, lng, address);
    $('#confirm-location-btn').prop('disabled', false);
}

function updateDisplay(lat, lng, address) {
    let html = '';
    if (address) {
        html = '<div class="mb-1">' + address + '</div>';
        html += '<small class="text-muted">(' + lat.toFixed(6) + ', ' + lng.toFixed(6) + ')</small>';
    } else {
        html = lat.toFixed(6) + ', ' + lng.toFixed(6);
    }
    $('#coords-display').html(html);
}

function setLocation(lat, lng, address) {
    selectedLat = lat;
    selectedLng = lng;
    selectedAddress = address || '';

    marker.setPosition({ lat, lng });
    marker.setVisible(true);
    updateDisplay(lat, lng, address);
    $('#confirm-location-btn').prop('disabled', false);
}

function getAddressAndSetLocation(lat, lng) {
    $('#coords-display').html('<i class="fas fa-spinner fa-spin"></i> @lang("Loading...")');

    geocoder.geocode({
        location: { lat, lng },
        language: currentLocale
    }, function(results, status) {
        const address = (status === 'OK' && results[0]) ? results[0].formatted_address : null;
        setLocation(lat, lng, address);
    });
}

function getMyLocation() {
    if (!navigator.geolocation) {
        $('#coords-display').html('<span class="text-warning">@lang("Browser does not support geolocation")</span>');
        return;
    }

    const btn = $('#my-location-btn');
    const originalText = btn.html();
    btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
    $('#coords-display').html('<i class="fas fa-spinner fa-spin"></i> @lang("Detecting location...")');

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
            $('#coords-display').html('<span class="text-warning">@lang("Could not get location")</span>');
        },
        { enableHighAccuracy: false, timeout: 30000, maximumAge: 60000 }
    );
}

function confirmLocation() {
    if (!selectedLat || !selectedLng) return;

    const btn = $('#confirm-location-btn');
    const originalText = btn.html();
    btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

    $.ajax({
        url: '/geocoding/tax-from-coordinates',
        method: 'POST',
        data: {
            latitude: selectedLat,
            longitude: selectedLng,
            merchant_id: merchantId,
            locale: currentLocale,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            // Save to hidden fields
            $('#latitude').val(selectedLat);
            $('#longitude').val(selectedLng);

            if (response.country_name) $('#customer_country').val(response.country_name);
            if (response.city_name) $('#customer_city').val(response.city_name);
            if (response.state_name) $('#customer_state').val(response.state_name);
            if (response.formatted_address) {
                $('#customer_address').val(response.formatted_address);
            }
            if (response.postal_code) {
                $('#customer_zip').val(response.postal_code);
            }

            // Save IDs for tax calculation
            if (response.country_id) $('#country_id').val(response.country_id);
            if (response.state_id) $('#state_id').val(response.state_id);
            if (response.city_id) $('#city_id').val(response.city_id);

            // Update UI
            let displayAddress = selectedAddress;
            if (response.geocoding_success && response.formatted_address) {
                displayAddress = response.formatted_address;
            }

            $('#open-map-btn').removeClass('m-btn--primary').addClass('m-btn--success');
            $('#open-map-btn').html('<i class="fas fa-check-circle me-2"></i> @lang("Location Selected")');

            $('#selected-location-info').removeClass('d-none').attr('data-address', displayAddress);
            $('#location-text').html(displayAddress || (selectedLat.toFixed(6) + ', ' + selectedLng.toFixed(6)));

            // Update tax display directly from geocoding response
            if (response.tax_rate > 0) {
                $('#tax-rate').text(response.tax_rate);
                const taxFormatted = '{{ $curr->sign ?? "" }}' + parseFloat(response.tax_amount || 0).toFixed(2);
                $('#summary-tax').text(taxFormatted);

                // Calculate new total
                const subtotal = parseFloat('{{ $cart["total_price"] ?? 0 }}');
                const taxAmount = parseFloat(response.tax_amount || 0);
                const total = subtotal + taxAmount;
                $('#summary-total').text('{{ $curr->sign ?? "" }}' + total.toFixed(2));

                $('#tax-row').removeClass('d-none');
            } else {
                $('#tax-row').addClass('d-none');
                $('#summary-total').text('{{ $curr->sign ?? "" }}' + parseFloat('{{ $cart["total_price"] ?? 0 }}').toFixed(2));
            }

            // Trigger event for any additional listeners
            $(document).trigger('locationSelected', [{
                country_id: response.country_id || 0,
                state_id: response.state_id || 0,
                city_id: response.city_id || 0,
                tax_rate: response.tax_rate || 0,
                tax_amount: response.tax_amount || 0
            }]);

            $('#mapModal').modal('hide');
            btn.html(originalText).prop('disabled', false);
        },
        error: function() {
            // Still save coordinates even if API fails
            $('#latitude').val(selectedLat);
            $('#longitude').val(selectedLng);

            $('#open-map-btn').removeClass('m-btn--primary').addClass('m-btn--success');
            $('#open-map-btn').html('<i class="fas fa-check-circle me-2"></i> @lang("Location Selected")');

            $('#selected-location-info').removeClass('d-none');
            $('#location-text').html(selectedLat.toFixed(6) + ', ' + selectedLng.toFixed(6));

            $('#mapModal').modal('hide');
            btn.html(originalText).prop('disabled', false);
        }
    });
}
</script>
