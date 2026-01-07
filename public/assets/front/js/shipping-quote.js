/**
 * Shipping Quote Service - Frontend JavaScript
 *
 * Shows shipping cost quotes without creating shipments.
 * Works with CustomerLocation.js for location context.
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
     * Get quick shipping estimate (cheapest option)
     */
    async function getQuickEstimate(merchantUserId, weight = 0.5) {
        const cityId = CustomerLocation.getCityId();
        const cacheKey = `${merchantUserId}:${weight}:${cityId || 'none'}`;

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
                    weight: weight,
                    city_id: cityId
                })
            });

            const data = await response.json();

            if (data.success) {
                quoteCache.set(cacheKey, { data, timestamp: Date.now() });
            }

            return data;
        } catch (error) {
            return { success: false, message: 'حدث خطأ في الاتصال' };
        }
    }

    /**
     * Get full catalogItem quote with all options
     */
    async function getProductQuote(merchantUserId, weight = 0.5, catalogItemId = null) {
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
                    weight,
                    catalog_item_id: catalogItemId,
                    city_id: CustomerLocation.getCityId()
                })
            });

            return await response.json();
        } catch (error) {
            return { success: false, message: 'حدث خطأ في الاتصال' };
        }
    }

    /**
     * Show shipping quote modal with all options
     */
    async function showQuoteModal(merchantUserId, weight = 0.5, catalogItemName = '') {
        // Check if location is set first
        if (!CustomerLocation.hasCity()) {
            const city = await CustomerLocation.requestLocation();
            if (!city) return; // User cancelled
        }

        // Create and show modal with loading state
        const modal = createModal(catalogItemName);
        document.body.appendChild(modal);
        setTimeout(() => modal.classList.add('show'), 10);

        // Fetch quote
        const result = await getProductQuote(merchantUserId, weight);

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
     * Initialize shipping quote buttons on the page
     */
    function initButtons() {
        const buttons = document.querySelectorAll('[data-shipping-quote]');

        buttons.forEach(button => {
            if (button.dataset.shippingQuoteInit) return;
            button.dataset.shippingQuoteInit = 'true';

            button.addEventListener('click', async function(e) {
                e.preventDefault();
                e.stopPropagation();

                const merchantUserId = parseInt(this.dataset.merchantUserId);
                const weight = parseFloat(this.dataset.weight || 0.5);
                const productName = this.dataset.catalogItemName || this.dataset.productName || '';
                const isRtl = document.documentElement.dir === 'rtl' || document.documentElement.lang === 'ar';

                if (!merchantUserId) return;

                // Show loading state
                const originalContent = this.innerHTML;
                this.classList.add('m-shipping-quote-btn--loading');
                this.innerHTML = `<i class="fas fa-spinner fa-spin"></i> <span>${isRtl ? 'جاري التحميل...' : 'Loading...'}</span>`;

                // Check location first
                if (!CustomerLocation.hasCity()) {
                    const city = await CustomerLocation.requestLocation();
                    if (!city) {
                        this.classList.remove('m-shipping-quote-btn--loading');
                        this.innerHTML = originalContent;
                        return;
                    }
                }

                // Get quick estimate
                const result = await getQuickEstimate(merchantUserId, weight);

                this.classList.remove('m-shipping-quote-btn--loading');

                if (result.success && result.price) {
                    // Show inline result with price
                    this.innerHTML = `
                        <i class="fas fa-truck m-shipping-quote-btn__icon"></i>
                        <span class="m-shipping-quote-btn__price">${result.formatted_price}</span>
                    `;
                    this.classList.add('has-price');

                    // Click again to show full modal with all options
                    this.onclick = () => showQuoteModal(merchantUserId, weight, productName);
                } else {
                    // Show error or no service message
                    const errorMsg = isRtl ? 'غير متاح' : 'N/A';
                    this.innerHTML = `<i class="fas fa-times"></i> <span>${errorMsg}</span>`;
                    this.classList.add('m-shipping-quote-btn--error');

                    // Reset after 3 seconds
                    setTimeout(() => {
                        this.innerHTML = originalContent;
                        this.classList.remove('m-shipping-quote-btn--error');
                    }, 3000);
                }
            });
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
            btn.click();
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
