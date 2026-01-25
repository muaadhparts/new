/**
 * Shipping Quote Service
 *
 * حساب تكلفة الشحن - يتطلب:
 * - merchant_id: معرف التاجر
 * - branch_id: معرف الفرع
 * - weight: وزن المنتج
 * - coordinates: إحداثيات العميل من المتصفح
 */
const ShippingQuote = (function() {
    'use strict';

    const API_BASE = '/api/shipping-quote';
    const CACHE_TTL = 5 * 60 * 1000;
    const quoteCache = new Map();

    // ========================================
    // Coordinates
    // ========================================

    async function getCoordinates() {
        if (typeof CustomerLocation !== 'undefined' && CustomerLocation.hasCoordinates()) {
            return CustomerLocation.getCoordinates();
        }
        return requestBrowserGeolocation();
    }

    function requestBrowserGeolocation() {
        return new Promise((resolve) => {
            if (!navigator.geolocation) {
                resolve(null);
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => resolve({ latitude: pos.coords.latitude, longitude: pos.coords.longitude }),
                () => resolve(null),
                { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 }
            );
        });
    }

    // ========================================
    // Location Permission Modal
    // ========================================

    function showLocationModal() {
        return new Promise((resolve) => {
            const isRtl = document.documentElement.dir === 'rtl';
            const t = {
                title: isRtl ? 'تفعيل الموقع' : 'Enable Location',
                message: isRtl ? 'لحساب تكلفة الشحن بدقة، نحتاج إلى معرفة موقعك.' : 'To calculate shipping, we need your location.',
                enable: isRtl ? 'تفعيل' : 'Enable',
                cancel: isRtl ? 'إلغاء' : 'Cancel',
                error: isRtl ? 'لم نتمكن من الحصول على موقعك' : 'Could not get your location'
            };

            const modal = document.createElement('div');
            modal.className = 'customer-location-modal';
            modal.innerHTML = `
                <div class="customer-location-modal__backdrop"></div>
                <div class="customer-location-modal__content">
                    <div class="customer-location-modal__header">
                        <h3 class="customer-location-modal__title"><i class="fas fa-map-marker-alt"></i> ${t.title}</h3>
                        <button type="button" class="customer-location-modal__close"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="customer-location-modal__body">
                        <div class="text-center py-4">
                            <i class="fas fa-location-arrow fa-3x text-primary mb-3"></i>
                            <p class="location-message">${t.message}</p>
                        </div>
                    </div>
                    <div class="customer-location-modal__footer">
                        <button type="button" class="btn btn-secondary btn-cancel">${t.cancel}</button>
                        <button type="button" class="btn btn-primary btn-enable">${t.enable}</button>
                    </div>
                </div>
            `;

            const close = (result) => {
                modal.classList.remove('show');
                setTimeout(() => modal.remove(), 300);
                resolve(result);
            };

            modal.querySelector('.customer-location-modal__backdrop').onclick = () => close(null);
            modal.querySelector('.customer-location-modal__close').onclick = () => close(null);
            modal.querySelector('.btn-cancel').onclick = () => close(null);

            modal.querySelector('.btn-enable').onclick = async () => {
                const coords = await requestBrowserGeolocation();
                if (coords) {
                    fetch(API_BASE + '/store-location', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCSRFToken() },
                        body: JSON.stringify(coords)
                    }).catch(() => {});
                    close(coords);
                } else {
                    modal.querySelector('.location-message').innerHTML = `<span class="text-danger">${t.error}</span>`;
                }
            };

            document.body.appendChild(modal);
            requestAnimationFrame(() => modal.classList.add('show'));
        });
    }

    // ========================================
    // API Calls
    // ========================================

    async function getQuote(merchantId, branchId, weight, fullOptions = false) {
        if (!merchantId || !branchId || !weight || weight <= 0) {
            return { success: false, error_code: 'MISSING_DATA' };
        }

        let coords = await getCoordinates();
        if (!coords) {
            coords = await showLocationModal();
            if (!coords) {
                return { success: false, requires_location: true };
            }
        }

        const cacheKey = `${merchantId}:${branchId}:${weight}:${coords.latitude.toFixed(3)}:${coords.longitude.toFixed(3)}:${fullOptions}`;
        const cached = quoteCache.get(cacheKey);
        if (cached && Date.now() - cached.time < CACHE_TTL) {
            return cached.data;
        }

        try {
            const endpoint = fullOptions ? '/quote' : '/quick-estimate';
            const response = await fetch(API_BASE + endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCSRFToken() },
                body: JSON.stringify({
                    merchant_id: merchantId,
                    branch_id: branchId,
                    weight: weight,
                    latitude: coords.latitude,
                    longitude: coords.longitude
                })
            });
            const data = await response.json();
            if (data.success) {
                quoteCache.set(cacheKey, { data, time: Date.now() });
            }
            return data;
        } catch (e) {
            return { success: false, error_code: 'CONNECTION_ERROR' };
        }
    }

    // ========================================
    // Options Modal (عرض جميع الخيارات)
    // ========================================

    async function showOptionsModal(merchantId, branchId, weight) {
        const isRtl = document.documentElement.dir === 'rtl';
        const t = {
            title: isRtl ? 'خيارات الشحن' : 'Shipping Options',
            loading: isRtl ? 'جاري التحميل...' : 'Loading...',
            close: isRtl ? 'إغلاق' : 'Close',
            noOptions: isRtl ? 'لا توجد خيارات شحن متاحة' : 'No shipping options available',
            days: isRtl ? 'أيام' : 'days',
            error: isRtl ? 'حدث خطأ' : 'Error'
        };

        // إنشاء Modal
        const modal = document.createElement('div');
        modal.className = 'customer-location-modal';
        modal.innerHTML = `
            <div class="customer-location-modal__backdrop"></div>
            <div class="customer-location-modal__content">
                <div class="customer-location-modal__header">
                    <h3 class="customer-location-modal__title"><i class="fas fa-truck"></i> ${t.title}</h3>
                    <button type="button" class="customer-location-modal__close"><i class="fas fa-times"></i></button>
                </div>
                <div class="customer-location-modal__body" id="shippingOptionsBody">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">${t.loading}</p>
                    </div>
                </div>
                <div class="customer-location-modal__footer">
                    <button type="button" class="btn btn-secondary btn-close-modal">${t.close}</button>
                </div>
            </div>
        `;

        const close = () => {
            modal.classList.remove('show');
            setTimeout(() => modal.remove(), 300);
        };

        modal.querySelector('.customer-location-modal__backdrop').onclick = close;
        modal.querySelector('.customer-location-modal__close').onclick = close;
        modal.querySelector('.btn-close-modal').onclick = close;

        document.body.appendChild(modal);
        requestAnimationFrame(() => modal.classList.add('show'));

        // جلب البيانات
        const result = await getQuote(merchantId, branchId, weight, true);
        const body = modal.querySelector('#shippingOptionsBody');

        if (!result.success || !result.options || result.options.length === 0) {
            body.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-box-open fa-2x text-muted mb-2"></i>
                    <p class="text-muted">${result.message || t.noOptions}</p>
                </div>
            `;
            return;
        }

        // عرض الخيارات
        const optionsHtml = result.options.map(opt => `
            <div class="shipping-option d-flex justify-content-between align-items-center p-3 border-bottom">
                <div>
                    <div class="fw-bold"><i class="fas fa-truck text-primary me-2"></i>${opt.name}</div>
                    ${opt.estimated_days ? `<small class="text-muted"><i class="fas fa-clock me-1"></i>${opt.estimated_days} ${t.days}</small>` : ''}
                </div>
                <div class="fw-bold text-success">${formatPrice(opt.price)}</div>
            </div>
        `).join('');

        body.innerHTML = `
            <div class="shipping-options-list">
                ${result.origin && result.destination ? `
                    <div class="d-flex justify-content-between text-muted small mb-3 px-2">
                        <span><i class="fas fa-map-marker-alt"></i> ${result.origin}</span>
                        <span><i class="fas fa-arrow-left mx-2"></i></span>
                        <span><i class="fas fa-map-marker-alt"></i> ${result.destination}</span>
                    </div>
                ` : ''}
                ${optionsHtml}
            </div>
        `;
    }

    function formatPrice(amount) {
        const isRtl = document.documentElement.dir === 'rtl';
        return new Intl.NumberFormat(isRtl ? 'ar-SA' : 'en-SA', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount) + ' ' + (isRtl ? 'ر.س' : 'SAR');
    }

    // ========================================
    // Button Handler
    // ========================================

    async function handleClick(button) {
        const merchantId = parseInt(button.dataset.merchantId);
        const branchId = parseInt(button.dataset.branchId);
        const weight = parseFloat(button.dataset.weight);
        const isRtl = document.documentElement.dir === 'rtl';

        if (!merchantId || !branchId || !weight) return;

        // إذا كان يعرض السعر بالفعل، افتح Modal الخيارات
        if (button.dataset.hasResult === 'true') {
            showOptionsModal(merchantId, branchId, weight);
            return;
        }

        // Loading state
        const originalHTML = button.innerHTML;
        button.classList.add('is-loading');
        button.innerHTML = `<i class="fas fa-spinner fa-spin"></i>`;

        const result = await getQuote(merchantId, branchId, weight);

        button.classList.remove('is-loading');

        if (result.success && result.price) {
            button.innerHTML = `<i class="fas fa-truck"></i> <span>${result.formatted_price}</span>`;
            button.classList.add('has-result');
            button.dataset.hasResult = 'true';
        } else {
            const msg = result.requires_location
                ? (isRtl ? 'فعّل الموقع' : 'Enable location')
                : (isRtl ? 'غير متاح' : 'N/A');
            button.innerHTML = `<i class="fas fa-times"></i> <span>${msg}</span>`;
            button.classList.add('has-error');

            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('has-error');
            }, 3000);
        }
    }

    // ========================================
    // Event Delegation
    // ========================================

    function init() {
        if (document.body.dataset.shippingQuoteInit) return;
        document.body.dataset.shippingQuoteInit = '1';

        document.body.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-shipping-quote]');
            if (!btn || btn.disabled) return;
            e.preventDefault();
            handleClick(btn);
        });
    }

    function getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    // Auto-init
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    return { getQuote, showOptionsModal, init };
})();
