/**
 * MerchantCart - Unified Cart JavaScript
 *
 * Single JS file for all cart operations.
 * Works with MerchantCartController endpoints.
 *
 * Usage:
 * MerchantCart.init({ endpoints, csrfToken });
 * MerchantCart.add(merchantItemId, options);
 * MerchantCart.update(cartKey, qty);
 * MerchantCart.remove(cartKey);
 * MerchantCart.clear();
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
        // Merge options
        if (options.endpoints) {
            config.endpoints = { ...config.endpoints, ...options.endpoints };
        }
        if (options.csrfToken) {
            config.csrfToken = options.csrfToken;
        }
        if (options.debug !== undefined) {
            config.debug = options.debug;
        }

        // Bind event listeners
        bindEvents();

        log('Cart initialized', config);
    }

    /**
     * Bind DOM event listeners
     */
    function bindEvents() {
        document.addEventListener('click', function(e) {
            // Quantity buttons
            if (e.target.closest('[data-action="increase"]')) {
                e.preventDefault();
                const btn = e.target.closest('[data-action="increase"]');
                const key = btn.dataset.cartKey;
                increase(key);
            }

            if (e.target.closest('[data-action="decrease"]')) {
                e.preventDefault();
                const btn = e.target.closest('[data-action="decrease"]');
                const key = btn.dataset.cartKey;
                decrease(key);
            }

            // Remove button
            if (e.target.closest('[data-action="remove"]')) {
                e.preventDefault();
                const btn = e.target.closest('[data-action="remove"]');
                const key = btn.dataset.cartKey;

                if (confirm(getTranslation('confirmRemove', 'Remove this item from cart?'))) {
                    remove(key);
                }
            }

            // Add to cart button
            if (e.target.closest('[data-action="add-to-cart"]')) {
                e.preventDefault();
                const btn = e.target.closest('[data-action="add-to-cart"]');
                const merchantItemId = parseInt(btn.dataset.merchantItemId, 10);

                if (merchantItemId) {
                    const options = {
                        qty: parseInt(btn.dataset.qty || 1, 10),
                        size: btn.dataset.size || null,
                        color: btn.dataset.color || null,
                    };

                    add(merchantItemId, options);
                }
            }

            // Clear cart button
            if (e.target.closest('#clear-cart-btn')) {
                e.preventDefault();
                if (confirm(getTranslation('confirmClear', 'Clear all items from cart?'))) {
                    clear();
                }
            }
        });

        // Quantity input change
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('m-cart__qty-input')) {
                const input = e.target;
                const key = input.dataset.cartKey;
                const qty = parseInt(input.value, 10);

                if (qty && qty > 0) {
                    update(key, qty);
                }
            }
        });
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
        if (options.keys) data.keys = options.keys;
        if (options.values) data.values = options.values;

        const response = await request('add', data);

        if (response && response.success) {
            showNotification('success', response.message);
            updateCartUI(response);
            dispatchEvent('cart:added', response);
        } else {
            showNotification('error', response?.message || getTranslation('error', 'An error occurred'));
        }

        return response;
    }

    /**
     * Update item quantity
     */
    async function update(cartKey, qty) {
        if (state.loading) return;

        const response = await request('update', { key: cartKey, qty });

        if (response && response.success) {
            updateCartUI(response);
            updateItemRow(cartKey, response);
            dispatchEvent('cart:updated', response);
        } else {
            showNotification('error', response?.message || getTranslation('error', 'An error occurred'));
            // Revert input
            const input = document.querySelector(`[data-cart-key="${cartKey}"].m-cart__qty-input`);
            if (input && response.cart) {
                const item = response.cart.items?.[cartKey];
                if (item) {
                    input.value = item.qty;
                }
            }
        }

        return response;
    }

    /**
     * Increase item quantity
     */
    async function increase(cartKey) {
        if (state.loading) return;

        const response = await request('increase', { key: cartKey });

        if (response && response.success) {
            updateCartUI(response);
            updateItemRow(cartKey, response);
            dispatchEvent('cart:updated', response);
        } else {
            showNotification('error', response?.message || getTranslation('error', 'An error occurred'));
        }

        return response;
    }

    /**
     * Decrease item quantity
     */
    async function decrease(cartKey) {
        if (state.loading) return;

        const response = await request('decrease', { key: cartKey });

        if (response && response.success) {
            updateCartUI(response);
            updateItemRow(cartKey, response);
            dispatchEvent('cart:updated', response);
        } else {
            showNotification('error', response?.message || getTranslation('error', 'An error occurred'));
        }

        return response;
    }

    /**
     * Remove item from cart
     */
    async function remove(cartKey) {
        if (state.loading) return;

        const response = await request('remove', { key: cartKey });

        if (response && response.success) {
            // Remove item row from DOM
            const row = document.querySelector(`[data-cart-key="${cartKey}"]`);
            if (row) {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';

                setTimeout(() => {
                    row.remove();

                    // Check if merchant group is empty
                    const merchantId = row.dataset.merchantId;
                    const merchantGroup = document.querySelector(`[data-merchant-id="${merchantId}"]`);
                    if (merchantGroup) {
                        const remainingItems = merchantGroup.querySelectorAll('.m-cart__item');
                        if (remainingItems.length === 0) {
                            merchantGroup.remove();
                        }
                    }

                    // Check if cart is empty
                    if (response.item_count === 0) {
                        location.reload();
                    }
                }, 300);
            }

            updateCartUI(response);
            showNotification('success', response.message);
            dispatchEvent('cart:removed', response);
        } else {
            showNotification('error', response?.message || getTranslation('error', 'An error occurred'));
        }

        return response;
    }

    /**
     * Clear cart
     */
    async function clear() {
        if (state.loading) return;

        const response = await request('clear', {});

        if (response && response.success) {
            showNotification('success', response.message);
            dispatchEvent('cart:cleared', response);

            // Reload page to show empty state
            setTimeout(() => {
                location.reload();
            }, 500);
        } else {
            showNotification('error', response?.message || getTranslation('error', 'An error occurred'));
        }

        return response;
    }

    /**
     * Get cart summary
     */
    async function getSummary(merchantId = null) {
        const params = merchantId ? { merchant_id: merchantId } : {};
        return await request('summary', params, 'GET');
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
            const url = config.endpoints[action];
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
     * Update cart UI elements
     */
    function updateCartUI(response) {
        const totals = response.totals || {};

        // Update header cart count
        const countBadge = document.querySelector('.cart-count, .m-header__cart-count, #cart-count');
        if (countBadge) {
            countBadge.textContent = response.item_count || 0;

            // Hide if empty
            if (response.item_count === 0) {
                countBadge.style.display = 'none';
            } else {
                countBadge.style.display = '';
            }
        }

        // Update summary totals
        const subtotalEl = document.querySelector('#cart-subtotal');
        if (subtotalEl && totals.subtotal_formatted) {
            subtotalEl.textContent = totals.subtotal_formatted;
        }

        const totalEl = document.querySelector('#cart-total');
        if (totalEl && totals.total_formatted) {
            totalEl.textContent = totals.total_formatted;
        }
    }

    /**
     * Update specific item row
     */
    function updateItemRow(cartKey, response) {
        const cart = response.cart;
        if (!cart || !cart.items) return;

        const item = cart.items[cartKey];
        if (!item) return;

        const row = document.querySelector(`[data-cart-key="${cartKey}"]`);
        if (!row) return;

        // Update quantity input
        const qtyInput = row.querySelector('.m-cart__qty-input');
        if (qtyInput) {
            qtyInput.value = item.qty;
        }

        // Update total
        const totalEl = row.querySelector('.m-cart__item-total-value');
        if (totalEl) {
            // Use converted and formatted price from response
            const formattedTotal = formatPrice(item.total_price);
            totalEl.textContent = formattedTotal;
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

        // Update merchant subtotal
        const merchantId = row.dataset.merchantId;
        if (merchantId && cart.by_merchant && cart.by_merchant[merchantId]) {
            const merchantSubtotal = cart.by_merchant[merchantId].subtotal;
            const subtotalEl = document.querySelector(`[data-merchant-id="${merchantId}"] .merchant-subtotal-value`);
            if (subtotalEl) {
                subtotalEl.textContent = formatPrice(merchantSubtotal);
            }

            // Update merchant item count
            const countEl = document.querySelector(`[data-merchant-id="${merchantId}"] .count-value`);
            if (countEl) {
                countEl.textContent = cart.by_merchant[merchantId].qty;
            }
        }
    }

    /**
     * Format price (uses global currency formatter if available)
     */
    function formatPrice(price) {
        // Check if there's a global price formatter
        if (typeof window.formatCurrency === 'function') {
            return window.formatCurrency(price);
        }

        // Fallback: basic formatting
        return price.toFixed(2);
    }

    /**
     * Show/hide loading state
     */
    function showLoading(show) {
        document.body.classList.toggle('cart-loading', show);

        // Disable all cart buttons during loading
        const buttons = document.querySelectorAll('.m-cart__qty-btn, .m-cart__remove-btn, [data-action="add-to-cart"]');
        buttons.forEach(btn => {
            btn.disabled = show;
        });
    }

    /**
     * Show notification
     */
    function showNotification(type, message) {
        // Check for toastr
        if (typeof toastr !== 'undefined') {
            toastr[type === 'success' ? 'success' : 'error'](message);
            return;
        }

        // Check for SweetAlert
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type === 'success' ? 'success' : 'error',
                title: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });
            return;
        }

        // Fallback: create custom notification
        const notification = document.createElement('div');
        notification.className = `m-notification m-notification--${type}`;
        notification.innerHTML = `
            <span class="m-notification__message">${message}</span>
            <button class="m-notification__close">&times;</button>
        `;

        document.body.appendChild(notification);

        // Auto-hide
        setTimeout(() => {
            notification.classList.add('m-notification--hide');
            setTimeout(() => notification.remove(), 300);
        }, 3000);

        // Close button
        notification.querySelector('.m-notification__close').addEventListener('click', () => {
            notification.remove();
        });
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
        clear,
        getSummary,
        getCount,
        isLoading: () => state.loading,
        getLastResponse: () => state.lastResponse,
    };
})();

// Add notification styles
(function() {
    const style = document.createElement('style');
    style.textContent = `
        .m-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideIn 0.3s ease;
            max-width: 400px;
        }

        .m-notification--success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .m-notification--error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .m-notification__close {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            opacity: 0.5;
            transition: opacity 0.2s;
        }

        .m-notification__close:hover {
            opacity: 1;
        }

        .m-notification--hide {
            animation: slideOut 0.3s ease forwards;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        [dir="rtl"] .m-notification {
            right: auto;
            left: 20px;
        }

        [dir="rtl"] @keyframes slideIn {
            from {
                transform: translateX(-100%);
            }
            to {
                transform: translateX(0);
            }
        }

        [dir="rtl"] @keyframes slideOut {
            to {
                transform: translateX(-100%);
            }
        }

        .cart-loading .m-cart__item {
            opacity: 0.6;
            pointer-events: none;
        }
    `;
    document.head.appendChild(style);
})();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MerchantCart;
}
