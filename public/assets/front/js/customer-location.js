/**
 * Customer Location Service - Frontend JavaScript
 *
 * Handles browser geolocation, manual city selection, and session storage.
 * Independent from Checkout/Cart.
 *
 * Usage:
 * - CustomerLocation.init() - Initialize and check for existing location
 * - CustomerLocation.requestLocation() - Request location (geo first, then manual)
 * - CustomerLocation.showCitySelector() - Show manual city selector modal
 * - CustomerLocation.hasCity() - Check if city is set
 * - CustomerLocation.getCityId() - Get current city_id
 */
const CustomerLocation = (function() {
    'use strict';

    const config = {
        apiBase: '/api/customer-location',
        geolocationTimeout: 10000,
        modalId: 'customerLocationModal',
    };

    let currentCity = null;
    let isInitialized = false;
    let citiesCache = null;
    let onChangeCallbacks = [];

    /**
     * Initialize the location service
     */
    async function init() {
        if (isInitialized) return currentCity;

        try {
            const response = await fetch(config.apiBase + '/status', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken()
                }
            });
            const data = await response.json();

            if (data.has_location) {
                currentCity = {
                    city_id: data.city_id,
                    city_name: data.city_name
                };
                updateDisplay();
            }

            isInitialized = true;
            return currentCity;
        } catch (error) {
            console.error('CustomerLocation: Failed to initialize', error);
            isInitialized = true;
            return null;
        }
    }

    /**
     * Request location - tries geolocation first, falls back to manual
     */
    async function requestLocation(forceManual = false) {
        console.log('CustomerLocation: requestLocation called, forceManual:', forceManual);

        // If forced manual or no geolocation support, show selector
        if (forceManual || !navigator.geolocation) {
            console.log('CustomerLocation: Showing city selector (manual/no geo)');
            return showCitySelector();
        }

        // Try geolocation with short timeout
        return new Promise((resolve) => {
            console.log('CustomerLocation: Requesting geolocation...');

            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    console.log('CustomerLocation: Got coordinates', position.coords.latitude, position.coords.longitude);
                    try {
                        const city = await setFromGeolocation(
                            position.coords.latitude,
                            position.coords.longitude
                        );
                        console.log('CustomerLocation: City resolved:', city);
                        resolve(city);
                    } catch (error) {
                        console.warn('CustomerLocation: Could not resolve city from coords', error);
                        resolve(await showCitySelector());
                    }
                },
                async (error) => {
                    console.log('CustomerLocation: Geolocation denied/failed:', error.message);
                    resolve(await showCitySelector());
                },
                {
                    enableHighAccuracy: false,
                    timeout: 5000,  // 5 seconds max
                    maximumAge: 300000  // Cache for 5 minutes
                }
            );
        });
    }

    /**
     * Set location from geolocation coordinates
     */
    async function setFromGeolocation(lat, lng) {
        const response = await fetch(config.apiBase + '/geolocation', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken()
            },
            body: JSON.stringify({ lat, lng })
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Failed to set location');
        }

        currentCity = { city_id: data.city_id, city_name: data.city_name };
        updateDisplay();
        triggerCallbacks();

        return currentCity;
    }

    /**
     * Set location manually by city ID
     */
    async function setManually(cityId) {
        const response = await fetch(config.apiBase + '/manual', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken()
            },
            body: JSON.stringify({ city_id: cityId })
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Failed to set location');
        }

        currentCity = { city_id: data.city_id, city_name: data.city_name };
        updateDisplay();
        triggerCallbacks();
        closeModal();

        return currentCity;
    }

    /**
     * Show manual city selector modal
     */
    async function showCitySelector() {
        console.log('CustomerLocation: showCitySelector called');
        return new Promise(async (resolve) => {
            if (!citiesCache) {
                console.log('CustomerLocation: Loading cities from API...');
                try {
                    const response = await fetch(config.apiBase + '/cities', {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCSRFToken() }
                    });
                    const data = await response.json();
                    citiesCache = data.cities || [];
                    console.log('CustomerLocation: Loaded', citiesCache.length, 'cities');
                } catch (error) {
                    console.error('CustomerLocation: Failed to load cities', error);
                    resolve(null);
                    return;
                }
            }

            const modal = createModal(citiesCache, async (cityId) => {
                if (cityId) {
                    try {
                        resolve(await setManually(cityId));
                    } catch (error) {
                        console.error('CustomerLocation: Failed to set city', error);
                        resolve(null);
                    }
                } else {
                    resolve(null);
                }
            });

            console.log('CustomerLocation: Appending modal to body');
            document.body.appendChild(modal);
            setTimeout(() => {
                modal.classList.add('show');
                console.log('CustomerLocation: Modal show class added');
            }, 10);
        });
    }

    /**
     * Create the city selector modal
     */
    function createModal(cities, onSelect) {
        const existing = document.getElementById(config.modalId);
        if (existing) existing.remove();

        const isRtl = document.documentElement.dir === 'rtl' || document.documentElement.lang === 'ar';
        const t = {
            title: isRtl ? 'اختر مدينتك' : 'Select Your City',
            search: isRtl ? 'ابحث عن مدينتك...' : 'Search for your city...',
            cancel: isRtl ? 'إلغاء' : 'Cancel',
            noResults: isRtl ? 'لم يتم العثور على نتائج' : 'No results found',
        };

        const modal = document.createElement('div');
        modal.id = config.modalId;
        modal.className = 'customer-location-modal';
        modal.innerHTML = `
            <div class="customer-location-modal__backdrop"></div>
            <div class="customer-location-modal__content">
                <div class="customer-location-modal__header">
                    <h3 class="customer-location-modal__title">
                        <i class="fas fa-map-marker-alt"></i> ${t.title}
                    </h3>
                    <button type="button" class="customer-location-modal__close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="customer-location-modal__body">
                    <div class="customer-location-modal__search">
                        <input type="text" class="customer-location-modal__search-input" placeholder="${t.search}">
                        <i class="fas fa-search customer-location-modal__search-icon"></i>
                    </div>
                    <div class="customer-location-modal__cities">
                        ${cities.map(c => `
                            <button type="button" class="customer-location-modal__city-item" data-city-id="${c.id}">
                                <i class="fas fa-map-pin"></i> ${c.name}
                            </button>
                        `).join('')}
                    </div>
                    <div class="customer-location-modal__no-results" style="display:none">${t.noResults}</div>
                </div>
                <div class="customer-location-modal__footer">
                    <button type="button" class="btn btn-secondary customer-location-modal__cancel">${t.cancel}</button>
                </div>
            </div>
        `;

        // Event handlers
        const close = () => {
            modal.classList.remove('show');
            setTimeout(() => modal.remove(), 300);
            onSelect(null);
        };

        modal.querySelector('.customer-location-modal__backdrop').addEventListener('click', close);
        modal.querySelector('.customer-location-modal__close').addEventListener('click', close);
        modal.querySelector('.customer-location-modal__cancel').addEventListener('click', close);

        modal.querySelector('.customer-location-modal__cities').addEventListener('click', (e) => {
            const item = e.target.closest('.customer-location-modal__city-item');
            if (item) onSelect(parseInt(item.dataset.cityId));
        });

        const searchInput = modal.querySelector('.customer-location-modal__search-input');
        const citiesContainer = modal.querySelector('.customer-location-modal__cities');
        const noResults = modal.querySelector('.customer-location-modal__no-results');

        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            const items = citiesContainer.querySelectorAll('.customer-location-modal__city-item');
            let visible = 0;

            items.forEach(item => {
                const matches = !query || item.textContent.toLowerCase().includes(query);
                item.style.display = matches ? '' : 'none';
                if (matches) visible++;
            });

            noResults.style.display = visible === 0 ? '' : 'none';
        });

        setTimeout(() => searchInput.focus(), 100);

        return modal;
    }

    function closeModal() {
        const modal = document.getElementById(config.modalId);
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => modal.remove(), 300);
        }
    }

    function updateDisplay() {
        document.querySelectorAll('[data-location-display]').forEach(el => {
            el.textContent = currentCity ? currentCity.city_name : (el.dataset.locationPlaceholder || '');
            el.classList.toggle('has-location', !!currentCity);
        });

        document.querySelectorAll('[data-location-trigger]').forEach(btn => {
            btn.classList.toggle('has-location', !!currentCity);
        });

        document.dispatchEvent(new CustomEvent('customer-location-changed', { detail: currentCity }));
    }

    function onLocationChange(callback) {
        onChangeCallbacks.push(callback);
    }

    function triggerCallbacks() {
        onChangeCallbacks.forEach(cb => {
            try { cb(currentCity); } catch (e) { console.error(e); }
        });
    }

    function hasCity() {
        return currentCity !== null && currentCity.city_id;
    }

    function getCityId() {
        return currentCity ? currentCity.city_id : null;
    }

    function getCityName() {
        return currentCity ? currentCity.city_name : null;
    }

    function getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    // Public API
    return {
        init,
        requestLocation,
        showCitySelector,
        setManually,
        hasCity,
        getCityId,
        getCityName,
        onLocationChange,
    };
})();

// Auto-initialize (handle both before and after DOMContentLoaded)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => CustomerLocation.init());
} else {
    CustomerLocation.init();
}
