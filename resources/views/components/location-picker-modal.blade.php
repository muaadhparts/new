{{--
    ============================================================================
    LOCATION PICKER MODAL - Standalone Reusable Component
    ============================================================================

    Usage:
    1. Include in any blade file:
       @include('components.location-picker-modal')

    2. Add a button to open:
       <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#locationPickerModal">
           <i class="fas fa-map-marker-alt"></i> Select Location
       </button>

    3. Listen for location selection:
       document.addEventListener('locationSelected', function(e) {
           console.log('Selected location:', e.detail);
           // e.detail contains:
           // - coordinates: { latitude, longitude }
           // - country: { id, name, name_ar }
           // - state: { id, name, name_ar }
           // - city: { id, name, name_ar }
           // - address: { en, ar }
       });

    4. Or use callback:
       window.onLocationSelected = function(data) {
           // Handle selected location
       };

    5. Auto-fill form fields (optional):
       Set data attributes on input elements:
       <input name="address" data-location-field="address">
       <input name="latitude" data-location-field="latitude">
       <input name="longitude" data-location-field="longitude">
       <input name="country" data-location-field="country_name">
       <input name="state" data-location-field="state_name">
       <input name="city" data-location-field="city_name">
       <input name="country_id" data-location-field="country_id">
       <input name="state_id" data-location-field="state_id">
       <input name="city_id" data-location-field="city_id">
--}}

<div class="modal fade" id="locationPickerModal" tabindex="-1" aria-labelledby="locationPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content location-picker-modal">
            {{-- Header --}}
            <div class="modal-header location-picker-header">
                <h5 class="modal-name" id="locationPickerModalLabel">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    {{ __('Select Location') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body p-0">
                {{-- Alert Container --}}
                <div id="lp-alert-container"></div>

                {{-- Map Container --}}
                <div class="location-picker-map-wrapper">
                    {{-- Search Box --}}
                    <div class="location-picker-search">
                        <i class="fas fa-search"></i>
                        <input type="text"
                               id="lp-search-input"
                               class="location-picker-search-input"
                               placeholder="{{ __('Search for a location...') }}"
                               autocomplete="off">
                    </div>

                    {{-- Map --}}
                    <div id="lp-map" class="location-picker-map"></div>

                    {{-- Loading Overlay --}}
                    <div id="lp-loading" class="location-picker-loading">
                        <div class="location-picker-spinner"></div>
                    </div>

                    {{-- Zoom Hint --}}
                    <div id="lp-zoom-hint" class="location-picker-zoom-hint">
                        {{ __('Use mouse wheel or pinch to zoom') }}
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="location-picker-actions">
                    <button type="button" id="lp-current-location" class="location-picker-btn location-picker-btn-secondary">
                        <i class="fas fa-crosshairs"></i>
                        {{ __('Current Location') }}
                    </button>
                    <button type="button" id="lp-reset" class="location-picker-btn location-picker-btn-outline">
                        <i class="fas fa-undo"></i>
                        {{ __('Reset') }}
                    </button>
                </div>

                {{-- Location Info Card --}}
                <div id="lp-info-card" class="location-picker-info-card" style="display: none;">
                    <div class="location-picker-info-header">
                        <i class="fas fa-info-circle"></i>
                        <span>{{ __('Selected Location') }}</span>
                    </div>

                    <div class="location-picker-info-grid">
                        {{-- Country --}}
                        <div class="location-picker-info-item">
                            <span class="location-picker-info-label">{{ __('Country') }}</span>
                            <span id="lp-country" class="location-picker-info-value">-</span>
                        </div>

                        {{-- State --}}
                        <div class="location-picker-info-item">
                            <span class="location-picker-info-label">{{ __('State/Province') }}</span>
                            <span id="lp-state" class="location-picker-info-value">-</span>
                        </div>

                        {{-- City --}}
                        <div class="location-picker-info-item">
                            <span class="location-picker-info-label">{{ __('City') }}</span>
                            <span id="lp-city" class="location-picker-info-value">-</span>
                        </div>

                        {{-- Coordinates --}}
                        <div class="location-picker-info-item">
                            <span class="location-picker-info-label">{{ __('Coordinates') }}</span>
                            <span id="lp-coords" class="location-picker-info-value location-picker-coords">-</span>
                        </div>
                    </div>

                    {{-- Full Address --}}
                    <div class="location-picker-address">
                        <span class="location-picker-info-label">{{ __('Full Address') }}</span>
                        <p id="lp-address" class="location-picker-address-text">-</p>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer location-picker-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    {{ __('Cancel') }}
                </button>
                <button type="button" id="lp-confirm" class="btn btn-primary" disabled>
                    <i class="fas fa-check me-1"></i>
                    {{ __('Confirm Location') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Sync Progress Overlay --}}
<div id="lp-sync-overlay" class="location-picker-sync-overlay" style="display: none;">
    <div class="location-picker-sync-card">
        <div class="location-picker-sync-spinner"></div>
        <h5 class="location-picker-sync-name">{{ __('Loading Location Data') }}</h5>
        <p id="lp-sync-country" class="location-picker-sync-subname"></p>
        <div class="location-picker-sync-progress">
            <div id="lp-sync-bar" class="location-picker-sync-bar"></div>
        </div>
        <p id="lp-sync-message" class="location-picker-sync-message">{{ __('Please wait...') }}</p>
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
/**
 * Location Picker - Standalone Module
 * Reusable location selection component
 */
(function() {
    'use strict';

    // Prevent duplicate initialization
    if (window.LocationPicker) return;

    const LocationPicker = {
        // State
        map: null,
        marker: null,
        searchBox: null,
        selectedData: null,
        isLoading: false,

        // Config
        config: {
            defaultCenter: { lat: 24.7136, lng: 46.6753 }, // Riyadh
            defaultZoom: 12,
            debounceDelay: 400
        },

        // DOM Elements
        elements: {},

        /**
         * Initialize the location picker
         */
        init: function() {
            this.cacheElements();
            this.bindEvents();
        },

        /**
         * Cache DOM elements
         */
        cacheElements: function() {
            this.elements = {
                modal: document.getElementById('locationPickerModal'),
                map: document.getElementById('lp-map'),
                searchInput: document.getElementById('lp-search-input'),
                loading: document.getElementById('lp-loading'),
                zoomHint: document.getElementById('lp-zoom-hint'),
                alertContainer: document.getElementById('lp-alert-container'),
                infoCard: document.getElementById('lp-info-card'),
                country: document.getElementById('lp-country'),
                state: document.getElementById('lp-state'),
                city: document.getElementById('lp-city'),
                coords: document.getElementById('lp-coords'),
                address: document.getElementById('lp-address'),
                confirmBtn: document.getElementById('lp-confirm'),
                resetBtn: document.getElementById('lp-reset'),
                currentLocationBtn: document.getElementById('lp-current-location'),
                syncOverlay: document.getElementById('lp-sync-overlay'),
                syncCountry: document.getElementById('lp-sync-country'),
                syncBar: document.getElementById('lp-sync-bar'),
                syncMessage: document.getElementById('lp-sync-message')
            };
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            const self = this;

            // Modal shown
            if (this.elements.modal) {
                this.elements.modal.addEventListener('shown.bs.modal', function() {
                    self.onModalShown();
                });

                this.elements.modal.addEventListener('hidden.bs.modal', function() {
                    self.onModalHidden();
                });
            }

            // Buttons
            if (this.elements.confirmBtn) {
                this.elements.confirmBtn.addEventListener('click', function() {
                    self.confirmSelection();
                });
            }

            if (this.elements.resetBtn) {
                this.elements.resetBtn.addEventListener('click', function() {
                    self.reset();
                });
            }

            if (this.elements.currentLocationBtn) {
                this.elements.currentLocationBtn.addEventListener('click', function() {
                    self.getCurrentLocation();
                });
            }
        },

        /**
         * Wait for Google Maps to load
         */
        waitForGoogleMaps: function(callback) {
            if (typeof google !== 'undefined' && google.maps) {
                callback();
            } else {
                setTimeout(() => this.waitForGoogleMaps(callback), 100);
            }
        },

        /**
         * Modal shown handler
         */
        onModalShown: function() {
            const self = this;

            if (!this.map) {
                this.waitForGoogleMaps(function() {
                    self.initMap();
                });
            } else {
                google.maps.event.trigger(this.map, 'resize');
            }
        },

        /**
         * Modal hidden handler
         */
        onModalHidden: function() {
            this.hideZoomHint();
        },

        /**
         * Initialize Google Map
         */
        initMap: function() {
            const self = this;

            // Create map with gesture handling enabled
            this.map = new google.maps.Map(this.elements.map, {
                center: this.config.defaultCenter,
                zoom: this.config.defaultZoom,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
                zoomControl: true,
                gestureHandling: 'greedy', // Allow zoom without Ctrl
                styles: this.getMapStyles()
            });

            // Create marker
            this.marker = new google.maps.Marker({
                map: this.map,
                draggable: true,
                animation: google.maps.Animation.DROP,
                icon: {
                    url: 'data:image/svg+xml,' + encodeURIComponent(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="50" viewBox="0 0 40 50">
                            <path d="M20 0C8.954 0 0 8.954 0 20c0 15 20 30 20 30s20-15 20-30C40 8.954 31.046 0 20 0z" fill="var(--theme-primary, #006c35)"/>
                            <circle cx="20" cy="18" r="8" fill="white"/>
                        </svg>
                    `),
                    scaledSize: new google.maps.Size(40, 50),
                    anchor: new google.maps.Point(20, 50)
                }
            });

            // Setup search
            this.searchBox = new google.maps.places.SearchBox(this.elements.searchInput);

            // Bias search to map viewport
            this.map.addListener('bounds_changed', function() {
                self.searchBox.setBounds(self.map.getBounds());
            });

            // Search selection
            this.searchBox.addListener('places_changed', function() {
                self.onPlaceSelected();
            });

            // Map click
            this.map.addListener('click', function(e) {
                self.setMarkerPosition(e.latLng);
            });

            // Marker drag
            this.marker.addListener('dragend', function() {
                self.onMarkerDragEnd();
            });

            // Show zoom hint briefly
            this.showZoomHint();
        },

        /**
         * Get map styles for theme
         */
        getMapStyles: function() {
            return [
                {
                    featureType: 'poi',
                    elementType: 'labels',
                    stylers: [{ visibility: 'off' }]
                }
            ];
        },

        /**
         * Show zoom hint
         */
        showZoomHint: function() {
            const hint = this.elements.zoomHint;
            if (hint) {
                hint.classList.add('visible');
                setTimeout(() => {
                    hint.classList.remove('visible');
                }, 3000);
            }
        },

        /**
         * Hide zoom hint
         */
        hideZoomHint: function() {
            const hint = this.elements.zoomHint;
            if (hint) {
                hint.classList.remove('visible');
            }
        },

        /**
         * Place selected from search
         */
        onPlaceSelected: function() {
            const places = this.searchBox.getPlaces();
            if (!places || places.length === 0) return;

            const place = places[0];
            if (!place.geometry || !place.geometry.location) return;

            this.map.setCenter(place.geometry.location);
            this.map.setZoom(15);
            this.setMarkerPosition(place.geometry.location);
        },

        /**
         * Set marker position and geocode
         */
        setMarkerPosition: function(latLng) {
            this.marker.setPosition(latLng);
            this.marker.setVisible(true);
            this.reverseGeocode(latLng.lat(), latLng.lng());
        },

        /**
         * Marker drag end handler
         */
        onMarkerDragEnd: function() {
            const position = this.marker.getPosition();
            this.reverseGeocode(position.lat(), position.lng());
        },

        /**
         * Get current location
         */
        getCurrentLocation: function() {
            const self = this;

            if (!navigator.geolocation) {
                this.showAlert('{{ __("Browser does not support location service") }}', 'error');
                return;
            }

            this.showLoading(true);

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const pos = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };

                    self.map.setCenter(pos);
                    self.map.setZoom(15);
                    self.setMarkerPosition(new google.maps.LatLng(pos.lat, pos.lng));
                },
                function(error) {
                    self.showLoading(false);
                    self.showAlert('{{ __("Failed to get your current location") }}', 'error');
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        },

        /**
         * Reverse geocode coordinates
         */
        reverseGeocode: async function(lat, lng) {
            this.showLoading(true);
            this.clearAlert();

            try {
                const response = await fetch('/geocoding/reverse', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ latitude: lat, longitude: lng })
                });

                const result = await response.json();

                if (result.success && result.needs_sync) {
                    // Country needs sync
                    this.showLoading(false);
                    await this.syncCountry(result, lat, lng);
                    return;
                }

                if (result.success) {
                    this.selectedData = result.data;
                    this.displayLocationInfo(result.data);
                    this.elements.confirmBtn.disabled = false;
                    this.showAlert('{{ __("Location selected successfully") }}', 'success');
                } else {
                    this.showAlert(result.message || '{{ __("Failed to get location information") }}', 'error');
                }
            } catch (error) {
                this.showAlert('{{ __("Server connection error") }}', 'error');
            } finally {
                this.showLoading(false);
            }
        },

        /**
         * Sync country data
         */
        syncCountry: async function(syncData, lat, lng) {
            const self = this;

            this.showSyncProgress(syncData.country_name_ar || syncData.country_name);

            try {
                const response = await fetch('/geocoding/sync-country', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        country_name: syncData.country_name,
                        country_code: syncData.country_code
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.updateSyncProgress(100, result.message);

                    setTimeout(function() {
                        self.hideSyncProgress();
                        self.reverseGeocode(lat, lng);
                    }, 1000);
                } else {
                    this.updateSyncProgress(0, result.message || '{{ __("Sync failed") }}');

                    setTimeout(function() {
                        self.hideSyncProgress();
                        self.showAlert(result.message || '{{ __("Failed to load country data") }}', 'error');
                    }, 2000);
                }
            } catch (error) {
                this.hideSyncProgress();
                this.showAlert('{{ __("Server connection error") }}', 'error');
            }
        },

        /**
         * Display location info
         */
        displayLocationInfo: function(data) {
            // Determine language
            const isArabic = document.documentElement.lang === 'ar';

            // Country
            this.elements.country.textContent = isArabic
                ? (data.country?.name_ar || data.country?.name || '-')
                : (data.country?.name || '-');

            // State
            this.elements.state.textContent = isArabic
                ? (data.state?.name_ar || data.state?.name || '-')
                : (data.state?.name || '-');

            // City
            this.elements.city.textContent = isArabic
                ? (data.city?.name_ar || data.city?.name || '-')
                : (data.city?.name || '-');

            // Coordinates
            const lat = data.coordinates?.latitude?.toFixed(6) || '-';
            const lng = data.coordinates?.longitude?.toFixed(6) || '-';
            this.elements.coords.textContent = `${lat}, ${lng}`;

            // Address
            this.elements.address.textContent = isArabic
                ? (data.address?.ar || data.address?.en || '-')
                : (data.address?.en || data.address?.ar || '-');

            // Show info card
            this.elements.infoCard.style.display = 'block';
        },

        /**
         * Confirm selection
         */
        confirmSelection: function() {
            if (!this.selectedData) return;

            // Auto-fill form fields
            this.autoFillFormFields();

            // Dispatch custom event
            const event = new CustomEvent('locationSelected', {
                detail: this.selectedData,
                bubbles: true
            });
            document.dispatchEvent(event);

            // Call callback if exists
            if (typeof window.onLocationSelected === 'function') {
                window.onLocationSelected(this.selectedData);
            }

            // Close modal
            const modal = bootstrap.Modal.getInstance(this.elements.modal);
            if (modal) {
                modal.hide();
            }
        },

        /**
         * Auto-fill form fields with data-location-field attributes
         */
        autoFillFormFields: function() {
            const data = this.selectedData;
            if (!data) return;

            const isArabic = document.documentElement.lang === 'ar';

            // Mapping of field names to data paths
            const fieldMapping = {
                'latitude': data.coordinates?.latitude,
                'longitude': data.coordinates?.longitude,
                'address': isArabic ? (data.address?.ar || data.address?.en) : (data.address?.en || data.address?.ar),
                'address_ar': data.address?.ar,
                'address_en': data.address?.en,
                'country_id': data.country?.id,
                'country_name': isArabic ? (data.country?.name_ar || data.country?.name) : data.country?.name,
                'country_name_ar': data.country?.name_ar,
                'country_name_en': data.country?.name,
                'state_id': data.state?.id,
                'state_name': isArabic ? (data.state?.name_ar || data.state?.name) : data.state?.name,
                'state_name_ar': data.state?.name_ar,
                'state_name_en': data.state?.name,
                'city_id': data.city?.id,
                'city_name': isArabic ? (data.city?.name_ar || data.city?.name) : data.city?.name,
                'city_name_ar': data.city?.name_ar,
                'city_name_en': data.city?.name
            };

            // Fill all fields with data-location-field attribute
            Object.keys(fieldMapping).forEach(function(fieldName) {
                const value = fieldMapping[fieldName];
                if (value !== undefined && value !== null) {
                    const fields = document.querySelectorAll('[data-location-field="' + fieldName + '"]');
                    fields.forEach(function(field) {
                        field.value = value;
                        // Trigger change event for any listeners
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                }
            });
        },

        /**
         * Reset selection
         */
        reset: function() {
            this.marker.setVisible(false);
            this.selectedData = null;
            this.elements.confirmBtn.disabled = true;
            this.elements.infoCard.style.display = 'none';
            this.elements.searchInput.value = '';
            this.map.setCenter(this.config.defaultCenter);
            this.map.setZoom(this.config.defaultZoom);
            this.clearAlert();
        },

        /**
         * Show/hide loading
         */
        showLoading: function(show) {
            this.isLoading = show;
            this.elements.loading.classList.toggle('visible', show);
        },

        /**
         * Show alert
         */
        showAlert: function(message, type) {
            const alertClass = type === 'success' ? 'location-picker-alert-success' : 'location-picker-alert-error';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

            this.elements.alertContainer.innerHTML = `
                <div class="location-picker-alert ${alertClass}">
                    <i class="fas ${icon}"></i>
                    <span>${message}</span>
                </div>
            `;
        },

        /**
         * Clear alert
         */
        clearAlert: function() {
            this.elements.alertContainer.innerHTML = '';
        },

        /**
         * Show sync progress
         */
        showSyncProgress: function(countryName) {
            this.elements.syncCountry.textContent = countryName;
            this.elements.syncBar.style.width = '0%';
            this.elements.syncOverlay.style.display = 'flex';

            // Animate progress
            let progress = 0;
            const self = this;

            this.syncInterval = setInterval(function() {
                if (progress < 90) {
                    progress += Math.random() * 15;
                    if (progress > 90) progress = 90;
                    self.updateSyncProgress(progress);
                }
            }, 500);
        },

        /**
         * Update sync progress
         */
        updateSyncProgress: function(percent, message) {
            this.elements.syncBar.style.width = percent + '%';
            if (message) {
                this.elements.syncMessage.textContent = message;
            }
        },

        /**
         * Hide sync progress
         */
        hideSyncProgress: function() {
            if (this.syncInterval) {
                clearInterval(this.syncInterval);
            }
            this.elements.syncOverlay.style.display = 'none';
        }
    };

    // Export to window
    window.LocationPicker = LocationPicker;

    // Auto-init when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            LocationPicker.init();
        });
    } else {
        LocationPicker.init();
    }
})();
</script>
@endpush
