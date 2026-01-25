/**
 * Shipping Quote Service - Frontend JavaScript
 *
 * Shows shipping cost quotes without creating shipments.
 * REQUIRES browser geolocation coordinates - same as checkout flow.
 *
 * Usage:
 * - ShippingQuote.showQuoteModal(merchantUserId, weight) - Show modal with options
 * - Auto-initialization for elements with data-shipping-quote attribute
 */
const ShippingQuote = (function() {
    'use strict';

    const config = {
        apiBase: '/api/shipping-quote',
        modalId: 'shippingQuoteModal',
    };

    const quoteCache = new Map();
    const CACHE_TTL = 5 * 60 * 1000; // 5 minutes

    /**
     * Get coordinates - from CustomerLocation or request from browser
     */
    async function getCoordinates() {
        // First check if CustomerLocation has coordinates
        if (typeof CustomerLocation !== 'undefined' && CustomerLocation.hasCoordinates()) {
            return CustomerLocation.getCoordinates();
        }

        // Request from browser
        return requestBrowserGeolocation();
    }

    /**
     * Request coordinates from browser geolocation
     */
    function requestBrowserGeolocation() {
        return new Promise((resolve) => {
            if (!navigator.geolocation) {
                resolve(null);
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude
                    });
                },
                (error) => {
                    console.log('Geolocation error:', error.message);
                    resolve(null);
                },
                {
                    enableHighAccuracy: false,
                    timeout: 10000,
                    maximumAge: 300000
                }
            );
        });
    }

    /**
     * Show location permission modal
     */
    function showLocationPermissionModal() {
        return new Promise((resolve) => {
            const isRtl = document.documentElement.dir === 'rtl' || document.documentElement.lang === 'ar';
            const t = {
                title: isRtl ? 'تفعيل الموقع' : 'Enable Location',
                message: isRtl
                    ? 'لحساب تكلفة الشحن بدقة، نحتاج إلى معرفة موقعك. يرجى السماح بالوصول للموقع في المتصفح.'
                    : 'To calculate accurate shipping costs, we need to know your location. Please allow location access in your browser.',
                enableBtn: isRtl ? 'تفعيل الموقع' : 'Enable Location',
                cancelBtn: isRtl ? 'إلغاء' : 'Cancel',
            };

            const modal = document.createElement('div');
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
                        <div class="shipping-quote-modal__location-request">
                            <div class="shipping-quote-modal__location-icon">
                                <i class="fas fa-location-arrow"></i>
                            </div>
                            <p class="shipping-quote-modal__location-message">${t.message}</p>
                        </div>
                    </div>
                    <div class="customer-location-modal__footer">
                        <button type="button" class="btn btn-secondary customer-location-modal__cancel">${t.cancelBtn}</button>
                        <button type="button" class="btn btn-primary shipping-quote-modal__enable-location">${t.enableBtn}</button>
                    </div>
                </div>
            `;

            const close = (result) => {
                modal.classList.remove('show');
                setTimeout(() => modal.remove(), 300);
                resolve(result);
            };

            modal.querySelector('.customer-location-modal__backdrop').addEventListener('click', () => close(null));
            modal.querySelector('.customer-location-modal__close').addEventListener('click', () => close(null));
            modal.querySelector('.customer-location-modal__cancel').addEventListener('click', () => close(null));

            modal.querySelector('.shipping-quote-modal__enable-location').addEventListener('click', async () => {
                const coords = await requestBrowserGeolocation();
                if (coords) {
                    // Store coordinates via API
                    try {
                        await fetch(config.apiBase + '/store-location', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCSRFToken()
                            },
                            body: JSON.stringify(coords)
                        });

                        // Update CustomerLocation if available
                        if (typeof CustomerLocation !== 'undefined') {
                            await CustomerLocation.setFromGeolocation(coords.latitude, coords.longitude);
                        }
                    } catch (e) {
                        // Ignore storage errors
                    }
                    close(coords);
                } else {
                    // Show error message
                    const messageEl = modal.querySelector('.shipping-quote-modal__location-message');
                    messageEl.innerHTML = isRtl
                        ? '<span style="color: var(--danger-color)">لم نتمكن من الحصول على موقعك. يرجى التحقق من إعدادات المتصفح والسماح بالوصول للموقع.</span>'
                        : '<span style="color: var(--danger-color)">Could not get your location. Please check browser settings and allow location access.</span>';
                }
            });

            document.body.appendChild(modal);
            setTimeout(() => modal.classList.add('show'), 10);
        });
    }

    /**
     * Get quick shipping estimate (cheapest option)
     * @param {number} merchantUserId - Merchant's user ID
     * @param {number} branchId - Merchant branch ID (required - NO DEFAULT)
     * @param {number} weight - Product weight in kg (required - NO DEFAULT)
     */
    async function getQuickEstimate(merchantUserId, branchId, weight) {
        // Validate required parameters - NO DEFAULTS
        if (!branchId || branchId <= 0) {
            return {
                success: false,
                error_code: 'BRANCH_REQUIRED',
                message: getTranslation('فرع التاجر غير محدد', 'Merchant branch is not set')
            };
        }

        if (!weight || weight <= 0) {
            return {
                success: false,
                error_code: 'WEIGHT_REQUIRED',
                message: getTranslation('وزن المنتج غير محدد', 'Product weight is not set')
            };
        }

        // Get coordinates
        let coords = await getCoordinates();

        if (!coords) {
            // Request location from user
            coords = await showLocationPermissionModal();
            if (!coords) {
                return {
                    success: false,
                    requires_location: true,
                    message: getTranslation('يرجى تفعيل خدمة الموقع', 'Please enable location services')
                };
            }
        }

        const cacheKey = `${merchantUserId}:${branchId}:${weight}:${coords.latitude.toFixed(4)}:${coords.longitude.toFixed(4)}`;

        const cached = quoteCache.get(cacheKey);
        if (cached && Date.now() - cached.timestamp < CACHE_TTL) {
            return cached.data;
        }

        try {
            const response = await fetch(config.apiBase + '/quick-estimate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken()
                },
                body: JSON.stringify({
                    merchant_id: merchantUserId,
                    branch_id: branchId,
                    weight: weight,
                    latitude: coords.latitude,
                    longitude: coords.longitude
                })
            });

            const data = await response.json();

            if (data.success) {
                quoteCache.set(cacheKey, { data, timestamp: Date.now() });
            }

            return data;
        } catch (error) {
            return { success: false, message: getTranslation('حدث خطأ في الاتصال', 'Connection error') };
        }
    }

    /**
     * Get full catalogItem quote with all options
     * @param {number} merchantUserId - Merchant's user ID
     * @param {number} branchId - Merchant branch ID (required - NO DEFAULT)
     * @param {number} weight - Product weight in kg (required - NO DEFAULT)
     * @param {number|null} catalogItemId - Optional catalog item ID
     */
    async function getProductQuote(merchantUserId, branchId, weight, catalogItemId = null) {
        // Validate required parameters - NO DEFAULTS
        if (!branchId || branchId <= 0) {
            return {
                success: false,
                error_code: 'BRANCH_REQUIRED',
                message: getTranslation('فرع التاجر غير محدد', 'Merchant branch is not set')
            };
        }

        if (!weight || weight <= 0) {
            return {
                success: false,
                error_code: 'WEIGHT_REQUIRED',
                message: getTranslation('وزن المنتج غير محدد', 'Product weight is not set')
            };
        }

        // Get coordinates
        let coords = await getCoordinates();

        if (!coords) {
            // Request location from user
            coords = await showLocationPermissionModal();
            if (!coords) {
                return {
                    success: false,
                    requires_location: true,
                    message: getTranslation('يرجى تفعيل خدمة الموقع', 'Please enable location services')
                };
            }
        }

        try {
            const response = await fetch(config.apiBase + '/quote', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken()
                },
                body: JSON.stringify({
                    merchant_id: merchantUserId,
                    branch_id: branchId,
                    weight,
                    catalog_item_id: catalogItemId,
                    latitude: coords.latitude,
                    longitude: coords.longitude
                })
            });

            return await response.json();
        } catch (error) {
            return { success: false, message: getTranslation('حدث خطأ في الاتصال', 'Connection error') };
        }
    }

    /**
     * Show shipping quote modal with all options
     * @param {number} merchantUserId - Merchant's user ID
     * @param {number} branchId - Merchant branch ID (required - NO DEFAULT)
     * @param {number} weight - Product weight in kg (required - NO DEFAULT)
     * @param {string} catalogItemName - Optional product name for display
     */
    async function showQuoteModal(merchantUserId, branchId, weight, catalogItemName = '') {
        // Create and show modal with loading state
        const modal = createModal(catalogItemName);
        document.body.appendChild(modal);
        setTimeout(() => modal.classList.add('show'), 10);

        // Fetch quote with branch_id
        const result = await getProductQuote(merchantUserId, branchId, weight);

        // Update modal with result
        updateModalContent(modal, result);
    }

    /**
     * Create the quote modal
     */
    function createModal(productName) {
        const existing = document.getElementById(config.modalId);
        if (existing) existing.remove();

        const isRtl = document.documentElement.dir === 'rtl' || document.documentElement.lang === 'ar';
        const t = {
            title: isRtl ? 'تكلفة الشحن' : 'Shipping Cost',
            loading: isRtl ? 'جاري حساب تكلفة الشحن...' : 'Calculating shipping...',
            close: isRtl ? 'إغلاق' : 'Close',
        };

        const modal = document.createElement('div');
        modal.id = config.modalId;
        modal.className = 'customer-location-modal';
        modal.innerHTML = `
            <div class="customer-location-modal__backdrop"></div>
            <div class="customer-location-modal__content">
                <div class="customer-location-modal__header">
                    <h3 class="customer-location-modal__title">
                        <i class="fas fa-truck"></i> ${t.title}
                    </h3>
                    <button type="button" class="customer-location-modal__close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="customer-location-modal__body" id="shippingQuoteContent">
                    <div class="shipping-quote-modal__loading">
                        <div class="shipping-quote-modal__loading-spinner"></div>
                        <p>${t.loading}</p>
                    </div>
                </div>
                <div class="customer-location-modal__footer">
                    <button type="button" class="btn btn-secondary customer-location-modal__cancel">${t.close}</button>
                </div>
            </div>
        `;

        const close = () => {
            modal.classList.remove('show');
            setTimeout(() => modal.remove(), 300);
        };

        modal.querySelector('.customer-location-modal__backdrop').addEventListener('click', close);
        modal.querySelector('.customer-location-modal__close').addEventListener('click', close);
        modal.querySelector('.customer-location-modal__cancel').addEventListener('click', close);

        return modal;
    }

    /**
     * Update modal content with quote result
     */
    function updateModalContent(modal, result) {
        const content = modal.querySelector('#shippingQuoteContent');
        const isRtl = document.documentElement.dir === 'rtl' || document.documentElement.lang === 'ar';

        if (!result.success) {
            content.innerHTML = `
                <div class="shipping-quote-modal__error">
                    <div class="shipping-quote-modal__error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <p>${result.message || (isRtl ? 'لم نتمكن من حساب تكلفة الشحن' : 'Could not calculate shipping')}</p>
                </div>
            `;
            return;
        }

        if (!result.options || result.options.length === 0) {
            content.innerHTML = `
                <div class="shipping-quote-modal__error">
                    <div class="shipping-quote-modal__error-icon"><i class="fas fa-box"></i></div>
                    <p>${isRtl ? 'لا توجد خيارات شحن متاحة' : 'No shipping options available'}</p>
                </div>
            `;
            return;
        }

        const optionsHtml = result.options.map(option => `
            <div class="shipping-quote-modal__option">
                <div class="shipping-quote-modal__option-info">
                    <div class="shipping-quote-modal__option-name">
                        <i class="fas fa-truck"></i> ${option.name}
                    </div>
                    ${option.estimated_days ? `
                        <div class="shipping-quote-modal__option-time">
                            <i class="fas fa-clock"></i>
                            ${isRtl ? 'التوصيل خلال' : 'Delivery in'} ${option.estimated_days} ${isRtl ? 'أيام' : 'days'}
                        </div>
                    ` : ''}
                </div>
                <div class="shipping-quote-modal__option-price">
                    ${formatPrice(option.price)}
                </div>
            </div>
        `).join('');

        content.innerHTML = `<div class="shipping-quote-modal__options">${optionsHtml}</div>`;
    }

    /**
     * Format price with currency
     */
    function formatPrice(amount) {
        const isRtl = document.documentElement.dir === 'rtl' || document.documentElement.lang === 'ar';
        const formatted = new Intl.NumberFormat(isRtl ? 'ar-SA' : 'en-SA', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);

        return `${formatted} ${isRtl ? 'ر.س' : 'SAR'}`;
    }

    /**
     * Get translation based on document direction
     */
    function getTranslation(ar, en) {
        const isRtl = document.documentElement.dir === 'rtl' || document.documentElement.lang === 'ar';
        return isRtl ? ar : en;
    }

    /**
     * Handle shipping quote button click
     */
    async function handleButtonClick(button) {
        const merchantId = parseInt(button.dataset.merchantId);
        const branchId = parseInt(button.dataset.branchId);
        const weight = parseFloat(button.dataset.weight);
        const productName = button.dataset.catalogItemName || '';
        const isRtl = document.documentElement.dir === 'rtl' || document.documentElement.lang === 'ar';

        console.log('[ShippingQuote] handleButtonClick', { merchantId, branchId, weight });

        // Validate required data - NO DEFAULTS
        if (!merchantId) {
            console.log('[ShippingQuote] No merchantId, returning');
            return;
        }

        if (!branchId || branchId <= 0) {
            button.innerHTML = `<i class="fas fa-exclamation-triangle"></i> <span>${isRtl ? 'الفرع غير محدد' : 'Branch not set'}</span>`;
            button.classList.add('m-shipping-quote-btn--error');
            return;
        }

        if (!weight || weight <= 0) {
            button.innerHTML = `<i class="fas fa-exclamation-triangle"></i> <span>${isRtl ? 'الوزن غير محدد' : 'Weight not set'}</span>`;
            button.classList.add('m-shipping-quote-btn--error');
            return;
        }

        // Show loading state
        const originalContent = button.innerHTML;
        button.classList.add('m-shipping-quote-btn--loading');
        button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> <span>${isRtl ? 'جاري التحميل...' : 'Loading...'}</span>`;

        // Get quick estimate with branch_id
        const result = await getQuickEstimate(merchantId, branchId, weight);

        button.classList.remove('m-shipping-quote-btn--loading');

        if (result.success && result.price) {
            // Show inline result with price
            button.innerHTML = `
                <i class="fas fa-truck m-shipping-quote-btn__icon"></i>
                <span class="m-shipping-quote-btn__price">${result.formatted_price}</span>
            `;
            button.classList.add('has-price');
            // Mark as showing price so next click opens modal
            button.dataset.shippingQuoteHasPrice = 'true';
        } else {
            // Show error or no service message
            let errorMsg;
            if (result.requires_location) {
                errorMsg = isRtl ? 'فعّل الموقع' : 'Enable location';
            } else if (result.error_code === 'BRANCH_REQUIRED') {
                errorMsg = isRtl ? 'الفرع غير محدد' : 'Branch not set';
            } else if (result.error_code === 'WEIGHT_REQUIRED') {
                errorMsg = isRtl ? 'الوزن غير محدد' : 'Weight not set';
            } else {
                errorMsg = isRtl ? 'غير متاح' : 'N/A';
            }

            button.innerHTML = `<i class="fas fa-${result.requires_location ? 'map-marker-alt' : 'times'}"></i> <span>${errorMsg}</span>`;
            button.classList.add('m-shipping-quote-btn--error');

            // Reset after 3 seconds
            setTimeout(() => {
                button.innerHTML = originalContent;
                button.classList.remove('m-shipping-quote-btn--error');
                delete button.dataset.shippingQuoteHasPrice;
            }, 3000);
        }
    }

    /**
     * Initialize shipping quote buttons using event delegation
     * This works automatically for dynamically added buttons (AJAX)
     */
    function initButtons() {
        // Only set up delegation once
        if (document.body.dataset.shippingQuoteDelegation) return;
        document.body.dataset.shippingQuoteDelegation = 'true';

        console.log('[ShippingQuote] Event delegation initialized');

        // Use event delegation on document body
        document.body.addEventListener('click', async function(e) {
            const button = e.target.closest('[data-shipping-quote]');
            if (!button) return;

            console.log('[ShippingQuote] Button HTML:', button.outerHTML);
            console.log('[ShippingQuote] Button dataset:', button.dataset);

            e.preventDefault();
            e.stopPropagation();

            // If already showing price, open modal instead
            if (button.dataset.shippingQuoteHasPrice === 'true') {
                const merchantId = parseInt(button.dataset.merchantId);
                const branchId = parseInt(button.dataset.branchId);
                const weight = parseFloat(button.dataset.weight);
                const productName = button.dataset.catalogItemName || '';
                showQuoteModal(merchantId, branchId, weight, productName);
                return;
            }

            // Otherwise, get quick estimate
            await handleButtonClick(button);
        });
    }

    /**
     * Clear quote cache
     */
    function clearCache() {
        quoteCache.clear();
    }

    function getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    // Auto-initialize (handle both before and after DOMContentLoaded)
    console.log('[ShippingQuote] Script loaded, readyState:', document.readyState);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initButtons);
    } else {
        initButtons();
    }

    // Re-initialize when location changes
    document.addEventListener('customer-location-changed', () => {
        clearCache();
        document.querySelectorAll('[data-shipping-quote].has-price').forEach(btn => {
            btn.classList.remove('has-price');
            delete btn.dataset.shippingQuoteHasPrice;
        });
    });

    // Public API
    return {
        getQuickEstimate,
        getProductQuote,
        showQuoteModal,
        initButtons,
        clearCache,
        formatPrice,
    };
})();
