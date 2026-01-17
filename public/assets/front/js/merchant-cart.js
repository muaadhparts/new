/**
 * MerchantCart - Cart Page JavaScript
 *
 * For cart page operations (update, remove, etc.)
 * Works with MerchantCartController (Merchant-Scoped API)
 *
 * API Endpoints:
 * POST /merchant-cart/add      { merchant_item_id, qty?, size?, color? }
 * POST /merchant-cart/update   { merchant_id, key, qty }
 * POST /merchant-cart/increase { merchant_id, key }
 * POST /merchant-cart/decrease { merchant_id, key }
 * POST /merchant-cart/remove   { merchant_id, key }
 * POST /merchant-cart/clear-merchant { merchant_id }
 * POST /merchant-cart/clear
 * GET  /merchant-cart/summary?merchant_id=X
 * GET  /merchant-cart/count
 */
const MerchantCart = (function() {
    'use strict';

    // Configuration
    let config = {
        endpoints: {
            add: '/merchant-cart/add',
            update: '/merchant-cart/update',
            increase: '/merchant-cart/increase',
            decrease: '/merchant-cart/decrease',
            remove: '/merchant-cart/remove',
            clearMerchant: '/merchant-cart/clear-merchant',
            clear: '/merchant-cart/clear',
            summary: '/merchant-cart/summary',
            count: '/merchant-cart/count',
        },
        csrfToken: '',
        debug: false,
    };

    // Cart state
    let state = {
        loading: false,
        lastResponse: null,
    };

    /**
     * Initialize cart module
     */
    function init(options = {}) {
        if (options.endpoints) {
            config.endpoints = { ...config.endpoints, ...options.endpoints };
        }
        if (options.csrfToken) {
            config.csrfToken = options.csrfToken;
        }
        if (options.debug !== undefined) {
            config.debug = options.debug;
        }

        bindEvents();
        log('Cart initialized', config);
    }

    /**
     * Bind DOM event listeners
     */
    function bindEvents() {
        document.addEventListener('click', function(e) {
            // Increase button
            if (e.target.closest('[data-action="increase"]')) {
                e.preventDefault();
                const btn = e.target.closest('[data-action="increase"]');
                const key = btn.dataset.cartKey;
                const merchantId = getMerchantIdFromElement(btn);
                if (key && merchantId) {
                    increase(merchantId, key);
                }
            }

            // Decrease button
            if (e.target.closest('[data-action="decrease"]')) {
                e.preventDefault();
                const btn = e.target.closest('[data-action="decrease"]');
                const key = btn.dataset.cartKey;
                const merchantId = getMerchantIdFromElement(btn);
                if (key && merchantId) {
                    decrease(merchantId, key);
                }
            }

            // Remove button
            if (e.target.closest('[data-action="remove"]')) {
                e.preventDefault();
                const btn = e.target.closest('[data-action="remove"]');
                const key = btn.dataset.cartKey;
                const merchantId = getMerchantIdFromElement(btn);

                if (key && merchantId && confirm(getTranslation('confirmRemove', 'Remove this item from cart?'))) {
                    remove(merchantId, key);
                }
            }

            // Clear merchant button
            if (e.target.closest('[data-action="clear-merchant"]')) {
                e.preventDefault();
                const btn = e.target.closest('[data-action="clear-merchant"]');
                const merchantId = parseInt(btn.dataset.merchantId, 10);

                if (merchantId && confirm(getTranslation('confirmClearMerchant', 'Remove all items from this merchant?'))) {
                    clearMerchant(merchantId);
                }
            }

            // Clear all button
            if (e.target.closest('[data-action="clear-all"]')) {
                e.preventDefault();
                if (confirm(getTranslation('confirmClearAll', 'Remove all items from cart?'))) {
                    clearAll();
                }
            }
        });

        // Quantity input change
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('m-cart__qty-input')) {
                const input = e.target;
                const key = input.dataset.cartKey;
                const merchantId = getMerchantIdFromElement(input);
                const qty = parseInt(input.value, 10);

                if (qty && qty > 0 && key && merchantId) {
                    update(merchantId, key, qty);
                }
            }
        });
    }

    /**
     * Get merchant ID from element or its parent
     */
    function getMerchantIdFromElement(el) {
        // Check element itself
        if (el.dataset.merchantId) {
            return parseInt(el.dataset.merchantId, 10);
        }

        // Check parent row
        const row = el.closest('[data-merchant-id]');
        if (row) {
            return parseInt(row.dataset.merchantId, 10);
        }

        // Check parent group
        const group = el.closest('.m-cart__merchant-group');
        if (group && group.dataset.merchantId) {
            return parseInt(group.dataset.merchantId, 10);
        }

        return null;
    }

    /**
     * Add item to cart
     */
    async function add(merchantItemId, options = {}) {
        if (state.loading) return;

        const data = {
            merchant_item_id: merchantItemId,
            qty: options.qty || 1,
        };

        if (options.size) data.size = options.size;
        if (options.color) data.color = options.color;

        const response = await request('add', data);

        if (response && response.success) {
            showNotification('success', response.message);
            updateHeaderCount(response.header_count);
            dispatchEvent('cart:added', response);
        } else {
            showNotification('error', response?.message || getTranslation('error', 'An error occurred'));
        }

        return response;
    }

    /**
     * Update item quantity
     */
    async function update(merchantId, cartKey, qty) {
        if (state.loading) return;

        const response = await request('update', {
            merchant_id: merchantId,
            key: cartKey,
            qty: qty
        });

        if (response && response.success) {
            updateMerchantUI(merchantId, response);
            updateHeaderCount(response.header_count);
            dispatchEvent('cart:updated', response);
        } else {
            showNotification('error', response?.message || getTranslation('error', 'An error occurred'));
        }

        return response;
    }

    /**
     * Increase item quantity
     */
    async function increase(merchantId, cartKey) {
        if (state.loading) return;

        const response = await request('increase', {
            merchant_id: merchantId,
            key: cartKey
        });

        if (response && response.success) {
            updateMerchantUI(merchantId, response);
            updateHeaderCount(response.header_count);
            dispatchEvent('cart:updated', response);
        } else {
            showNotification('error', response?.message || getTranslation('error', 'An error occurred'));
        }

        return response;
    }

    /**
     * Decrease item quantity
     */
    async function decrease(merchantId, cartKey) {
        if (state.loading) return;

        const response = await request('decrease', {
            merchant_id: merchantId,
            key: cartKey
        });

        if (response && response.success) {
            updateMerchantUI(merchantId, response);
            updateHeaderCount(response.header_count);
            dispatchEvent('cart:updated', response);
        } else {
            showNotification('error', response?.message || getTranslation('error', 'An error occurred'));
        }

        return response;
    }

    /**
     * Remove item from cart
     */
    async function remove(merchantId, cartKey) {
        if (state.loading) return;

        const response = await request('remove', {
            merchant_id: merchantId,
            key: cartKey
        });

        if (response && response.success) {
            // Remove item row from DOM
            removeItemFromDOM(cartKey, merchantId);
            updateHeaderCount(response.header_count);
            showNotification('success', response.message);
            dispatchEvent('cart:removed', response);

            // Check if cart is empty
            if (response.header_count === 0) {
                setTimeout(() => location.reload(), 500);
            }
        } else {
            showNotification('error', response?.message || getTranslation('error', 'An error occurred'));
        }

        return response;
    }

    /**
     * Clear merchant items
     */
    async function clearMerchant(merchantId) {
        if (state.loading) return;

        const response = await request('clearMerchant', {
            merchant_id: merchantId
        });

        if (response && response.success) {
            // Remove merchant group from DOM
            const group = document.querySelector(`.m-cart__merchant-group[data-merchant-id="${merchantId}"]`);
            if (group) {
                group.style.transition = 'all 0.3s ease';
                group.style.opacity = '0';
                setTimeout(() => group.remove(), 300);
            }

            updateHeaderCount(response.header_count);
            showNotification('success', response.message);
            dispatchEvent('cart:merchantCleared', response);

            // Check if cart is empty
            if (response.header_count === 0) {
                setTimeout(() => location.reload(), 500);
            }
        } else {
            showNotification('error', response?.message || getTranslation('error', 'An error occurred'));
        }

        return response;
    }

    /**
     * Clear all cart
     */
    async function clearAll() {
        if (state.loading) return;

        const response = await request('clear', {});

        if (response && response.success) {
            showNotification('success', response.message);
            dispatchEvent('cart:cleared', response);
            setTimeout(() => location.reload(), 500);
        } else {
            showNotification('error', response?.message || getTranslation('error', 'An error occurred'));
        }

        return response;
    }

    /**
     * Get merchant summary
     */
    async function getSummary(merchantId) {
        return await request('summary', { merchant_id: merchantId }, 'GET');
    }

    /**
     * Get cart count
     */
    async function getCount() {
        return await request('count', {}, 'GET');
    }

    /**
     * Make API request
     */
    async function request(action, data = {}, method = 'POST') {
        state.loading = true;
        showLoading(true);

        try {
            let url = config.endpoints[action];
            if (!url) {
                throw new Error(`Unknown action: ${action}`);
            }

            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            };

            if (method === 'POST') {
                options.body = JSON.stringify(data);
            } else if (method === 'GET' && Object.keys(data).length) {
                url += '?' + new URLSearchParams(data).toString();
            }

            log(`Request: ${method} ${url}`, data);

            const response = await fetch(url, options);
            const result = await response.json();

            log(`Response:`, result);

            state.lastResponse = result;
            return result;

        } catch (error) {
            console.error('Cart request error:', error);
            return { success: false, message: getTranslation('networkError', 'Network error. Please try again.') };

        } finally {
            state.loading = false;
            showLoading(false);
        }
    }

    /**
     * Update header cart count
     */
    function updateHeaderCount(count) {
        const countElements = document.querySelectorAll('.cart-count, .m-header__cart-count, #cart-count');
        countElements.forEach(el => {
            el.textContent = count || 0;
            if (count === 0) {
                el.style.display = 'none';
            } else {
                el.style.display = '';
            }
        });
    }

    /**
     * Update merchant section UI
     */
    function updateMerchantUI(merchantId, response) {
        const data = response.data;
        if (!data || !data.items) return;

        // Update each item in this merchant's section
        Object.entries(data.items).forEach(([key, item]) => {
            const row = document.querySelector(`[data-cart-key="${key}"]`);
            if (!row) return;

            // Update quantity input
            const qtyInput = row.querySelector('.m-cart__qty-input');
            if (qtyInput) {
                qtyInput.value = item.qty;
            }

            // Update total price
            const totalEl = row.querySelector('.m-cart__item-total-value');
            if (totalEl) {
                totalEl.textContent = formatPrice(item.total_price);
            }

            // Update button states
            const decreaseBtn = row.querySelector('[data-action="decrease"]');
            if (decreaseBtn) {
                decreaseBtn.disabled = item.qty <= item.min_qty;
            }

            const increaseBtn = row.querySelector('[data-action="increase"]');
            if (increaseBtn) {
                increaseBtn.disabled = !item.preordered && item.stock > 0 && item.qty >= item.stock;
            }
        });

        // Update merchant totals
        const group = document.querySelector(`.m-cart__merchant-group[data-merchant-id="${merchantId}"]`);
        if (group && data.totals) {
            const subtotalEl = group.querySelector('.m-cart__merchant-subtotal-value');
            if (subtotalEl) {
                subtotalEl.textContent = formatPrice(data.totals.total);
            }

            const qtyEl = group.querySelector('.m-cart__merchant-qty');
            if (qtyEl) {
                qtyEl.textContent = data.totals.qty;
            }
        }
    }

    /**
     * Remove item from DOM
     */
    function removeItemFromDOM(cartKey, merchantId) {
        const row = document.querySelector(`[data-cart-key="${cartKey}"]`);
        if (!row) return;

        row.style.transition = 'all 0.3s ease';
        row.style.opacity = '0';
        row.style.transform = 'translateX(-20px)';

        setTimeout(() => {
            row.remove();

            // Check if merchant group is empty
            const group = document.querySelector(`.m-cart__merchant-group[data-merchant-id="${merchantId}"]`);
            if (group) {
                const remainingItems = group.querySelectorAll('.m-cart__item');
                if (remainingItems.length === 0) {
                    group.remove();
                }
            }
        }, 300);
    }

    /**
     * Format price
     */
    function formatPrice(price) {
        if (typeof window.formatCurrency === 'function') {
            return window.formatCurrency(price);
        }
        return parseFloat(price).toFixed(2);
    }

    /**
     * Show/hide loading state
     */
    function showLoading(show) {
        document.body.classList.toggle('cart-loading', show);
        const buttons = document.querySelectorAll('.m-cart__qty-btn, .m-cart__remove-btn');
        buttons.forEach(btn => {
            btn.disabled = show;
        });
    }

    /**
     * Show notification
     */
    function showNotification(type, message) {
        if (typeof toastr !== 'undefined') {
            toastr[type === 'success' ? 'success' : 'error'](message);
            return;
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type === 'success' ? 'success' : 'error',
                title: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });
        }
    }

    /**
     * Dispatch custom event
     */
    function dispatchEvent(name, detail) {
        document.dispatchEvent(new CustomEvent(name, { detail }));
    }

    /**
     * Get translation
     */
    function getTranslation(key, fallback) {
        if (typeof window.translations !== 'undefined' && window.translations[key]) {
            return window.translations[key];
        }
        return fallback;
    }

    /**
     * Debug logging
     */
    function log(...args) {
        if (config.debug) {
            console.log('[MerchantCart]', ...args);
        }
    }

    // Public API
    return {
        init,
        add,
        update,
        increase,
        decrease,
        remove,
        clearMerchant,
        clearAll,
        getSummary,
        getCount,
        isLoading: () => state.loading,
    };
})();

// CSS for loading state
(function() {
    const style = document.createElement('style');
    style.textContent = `
        .cart-loading .m-cart__item {
            opacity: 0.6;
            pointer-events: none;
        }
    `;
    document.head.appendChild(style);
})();

// Export
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MerchantCart;
}
