/**
 * Google Maps Location Picker Component
 * Reusable component for selecting location with reverse geocoding
 */

class GoogleMapsLocationPicker {
    constructor(config = {}) {
        this.config = {
            containerId: config.containerId || 'map-picker-container',
            mapId: config.mapId || 'location-map',
            defaultCenter: config.defaultCenter || { lat: 24.7136, lng: 46.6753 }, // Riyadh
            defaultZoom: config.defaultZoom || 12,
            onLocationSelect: config.onLocationSelect || null,
            language: config.language || 'ar',
            debounceDelay: config.debounceDelay || 400,
            showSearchBox: config.showSearchBox !== false,
            showCurrentLocationBtn: config.showCurrentLocationBtn !== false,
            apiEndpoint: config.apiEndpoint || '/geocoding/reverse',
            csrfToken: config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content
        };

        this.map = null;
        this.marker = null;
        this.searchBox = null;
        this.debounceTimer = null;
        this.selectedLocation = null;
        this.isInitialized = false;
    }

    /**
     * Initialize the map picker
     */
    async init() {
        if (this.isInitialized) return;

        const container = document.getElementById(this.config.containerId);
        if (!container) {
            console.error('Container not found:', this.config.containerId);
            return;
        }

        // Load Google Maps if not loaded
        if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
            await this.loadGoogleMaps();
        } else {
            this.initializeMap();
        }
    }

    /**
     * Load Google Maps script
     */
    loadGoogleMaps() {
        return new Promise((resolve, reject) => {
            if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
                resolve();
                return;
            }

            window.initGoogleMapsCallback = () => {
                this.initializeMap();
                resolve();
            };

            const script = document.createElement('script');
            const apiKey = document.querySelector('meta[name="google-maps-key"]')?.content || '';
            script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places&callback=initGoogleMapsCallback&language=${this.config.language}`;
            script.async = true;
            script.defer = true;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    /**
     * Initialize the map
     */
    initializeMap() {
        const mapElement = document.getElementById(this.config.mapId);
        if (!mapElement) return;

        this.map = new google.maps.Map(mapElement, {
            center: this.config.defaultCenter,
            zoom: this.config.defaultZoom,
            mapTypeControl: true,
            streetViewControl: false,
            fullscreenControl: true,
        });

        this.marker = new google.maps.Marker({
            map: this.map,
            draggable: true,
            animation: google.maps.Animation.DROP,
        });

        // Setup search box
        if (this.config.showSearchBox) {
            this.setupSearchBox();
        }

        // Setup event listeners
        this.setupEventListeners();

        this.isInitialized = true;
    }

    /**
     * Setup search box
     */
    setupSearchBox() {
        const searchInput = document.getElementById('map-search-input');
        if (!searchInput) return;

        this.searchBox = new google.maps.places.SearchBox(searchInput);

        this.map.addListener('bounds_changed', () => {
            this.searchBox.setBounds(this.map.getBounds());
        });

        this.searchBox.addListener('places_changed', () => {
            const places = this.searchBox.getPlaces();
            if (places.length === 0) return;

            const place = places[0];
            if (!place.geometry || !place.geometry.location) return;

            this.map.setCenter(place.geometry.location);
            this.marker.setPosition(place.geometry.location);
            this.marker.setVisible(true);

            this.handleLocationChange(
                place.geometry.location.lat(),
                place.geometry.location.lng()
            );
        });
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Map click
        this.map.addListener('click', (event) => {
            this.marker.setPosition(event.latLng);
            this.marker.setVisible(true);
            this.handleLocationChange(event.latLng.lat(), event.latLng.lng());
        });

        // Marker drag
        this.marker.addListener('dragend', () => {
            const position = this.marker.getPosition();
            this.handleLocationChange(position.lat(), position.lng());
        });

        // Current location button
        if (this.config.showCurrentLocationBtn) {
            const currentLocationBtn = document.getElementById('current-location-btn');
            if (currentLocationBtn) {
                currentLocationBtn.addEventListener('click', () => this.getCurrentLocation());
            }
        }
    }

    /**
     * Handle location change with debouncing
     */
    handleLocationChange(lat, lng) {
        clearTimeout(this.debounceTimer);

        this.debounceTimer = setTimeout(async () => {
            await this.reverseGeocode(lat, lng);
        }, this.config.debounceDelay);
    }

    /**
     * Reverse geocode coordinates
     */
    async reverseGeocode(lat, lng) {
        this.showLoading(true);

        try {
            const response = await fetch(this.config.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.config.csrfToken
                },
                body: JSON.stringify({
                    latitude: lat,
                    longitude: lng
                })
            });

            const result = await response.json();

            if (result.success) {
                this.selectedLocation = result.data;
                this.displayLocationInfo(result.data);

                // Call callback if provided
                if (this.config.onLocationSelect) {
                    this.config.onLocationSelect(result.data);
                }

                this.showAlert('تم تحديد الموقع بنجاح', 'success');
            } else {
                this.showAlert('فشل في الحصول على معلومات الموقع', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('حدث خطأ في الاتصال بالخادم', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Display location information
     */
    displayLocationInfo(data) {
        // Update hidden fields if they exist
        this.updateFormField('country_id', data.country?.id);
        this.updateFormField('state_id', data.state?.id);
        this.updateFormField('city_id', data.city?.id);
        this.updateFormField('latitude', data.coordinates?.latitude);
        this.updateFormField('longitude', data.coordinates?.longitude);
        this.updateFormField('address', data.address?.ar || data.address?.en);

        // Update display elements if they exist
        this.updateDisplayElement('country-display', data.country?.name_ar || data.country?.name);
        this.updateDisplayElement('state-display', data.state?.name_ar || data.state?.name);
        this.updateDisplayElement('city-display', data.city?.name_ar || data.city?.name);
        this.updateDisplayElement('address-display', data.address?.ar || data.address?.en);
        this.updateDisplayElement('coordinates-display',
            `${data.coordinates?.latitude.toFixed(6)}, ${data.coordinates?.longitude.toFixed(6)}`
        );

        // Trigger custom event
        document.dispatchEvent(new CustomEvent('locationSelected', { detail: data }));
    }

    /**
     * Update form field value
     */
    updateFormField(fieldName, value) {
        const field = document.querySelector(`[name="${fieldName}"], #${fieldName}`);
        if (field && value !== undefined) {
            field.value = value;
        }
    }

    /**
     * Update display element
     */
    updateDisplayElement(elementId, value) {
        const element = document.getElementById(elementId);
        if (element && value !== undefined) {
            element.textContent = value || '-';
        }
    }

    /**
     * Get current location
     */
    getCurrentLocation() {
        if (!navigator.geolocation) {
            this.showAlert('المتصفح لا يدعم خدمة تحديد الموقع', 'error');
            return;
        }

        this.showLoading(true);
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const pos = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                this.map.setCenter(pos);
                this.marker.setPosition(pos);
                this.marker.setVisible(true);
                this.handleLocationChange(pos.lat, pos.lng);
            },
            () => {
                this.showLoading(false);
                this.showAlert('فشل في الحصول على موقعك الحالي', 'error');
            }
        );
    }

    /**
     * Set location programmatically
     */
    setLocation(lat, lng) {
        if (!this.map || !this.marker) return;

        const pos = { lat, lng };
        this.map.setCenter(pos);
        this.marker.setPosition(pos);
        this.marker.setVisible(true);
        this.handleLocationChange(lat, lng);
    }

    /**
     * Show/hide loading overlay
     */
    showLoading(show) {
        const overlay = document.getElementById('map-loading-overlay');
        if (overlay) {
            overlay.style.display = show ? 'flex' : 'none';
        }
    }

    /**
     * Show alert message
     */
    showAlert(message, type = 'info') {
        // Check if toastr is available
        if (typeof toastr !== 'undefined') {
            toastr[type === 'error' ? 'error' : 'success'](message);
            return;
        }

        // Fallback to custom alert
        const alertContainer = document.getElementById('map-alert-container');
        if (alertContainer) {
            alertContainer.innerHTML = `
                <div class="alert alert-${type === 'error' ? 'danger' : 'success'}" role="alert">
                    ${message}
                </div>
            `;
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 3000);
        }
    }

    /**
     * Get selected location data
     */
    getSelectedLocation() {
        return this.selectedLocation;
    }

    /**
     * Reset picker
     */
    reset() {
        this.marker.setVisible(false);
        this.selectedLocation = null;
        this.map.setCenter(this.config.defaultCenter);
        this.map.setZoom(this.config.defaultZoom);

        // Clear form fields
        ['country_id', 'state_id', 'city_id', 'latitude', 'longitude', 'address'].forEach(field => {
            this.updateFormField(field, '');
        });

        // Clear search input
        const searchInput = document.getElementById('map-search-input');
        if (searchInput) searchInput.value = '';
    }

    /**
     * Destroy the picker
     */
    destroy() {
        if (this.marker) {
            this.marker.setMap(null);
        }
        if (this.map) {
            this.map = null;
        }
        this.isInitialized = false;
    }
}

// Export for use in modules or global scope
if (typeof module !== 'undefined' && module.exports) {
    module.exports = GoogleMapsLocationPicker;
} else {
    window.GoogleMapsLocationPicker = GoogleMapsLocationPicker;
}
