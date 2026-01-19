/**
 * MerchantCart - Cart Page JavaScript
 *
 * For cart page operations (update, remove, etc.)
 * Works with MerchantCartController (Branch-Scoped API)
 *
 * API Endpoints:
 * POST /merchant-cart/add      { merchant_item_id, qty?, size?, color? }
 * POST /merchant-cart/update   { branch_id, key, qty }
 * POST /merchant-cart/increase { branch_id, key }
 * POST /merchant-cart/decrease { branch_id, key }
 * POST /merchant-cart/remove   { branch_id, key }
 * POST /merchant-cart/clear-branch { branch_id }
 * POST /merchant-cart/clear
 * GET  /merchant-cart/summary?branch_id=X
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
            clearBranch: '/merchant-cart/clear-branch',
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
                const branchId = getBranchIdFromElement(btn);
                if (key && branchId) {
                    increase(branchId, key);
                }
            }

            // Decrease button
            if (e.target.closest('[data-action="decrease"]')) {
                e.preventDefault();
                const btn = e.target.closest('[data-action="decrease"]');
                const key = btn.dataset.cartKey;
                const branchId = getBranchIdFromElement(btn);
                if (key && branchId) {
                    decrease(branchId, key);
                }
            }

            // Remove button (silent - no confirmation)
            if (e.target.closest('[data-action="remove"]')) {
                e.preventDefault();
                const btn = e.target.closest('[data-action="remove"]');
                const key = btn.dataset.cartKey;
                const branchId = getBranchIdFromElement(btn);

                if (key && branchId) {
                    remove(branchId, key, true); // silent = true
                }
            }

            // Clear branch button (silent - no confirmation)
            if (e.target.closest('[data-action="clear-branch"]')) {
                e.preventDefault();
                const btn = e.target.closest('[data-action="clear-branch"]');
                const branchId = parseInt(btn.dataset.branchId, 10);

                if (branchId) {
                    clearBranch(branchId, true); // silent = true
                }
            }

            // Clear all button (silent - no confirmation)
            if (e.target.closest('[data-action="clear-all"]')) {
                e.preventDefault();
                clearAll(true); // silent = true
            }
        });

        // Quantity input change
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('m-cart__qty-input')) {
                const input = e.target;
                const key = input.dataset.cartKey;
                const branchId = getBranchIdFromElement(input);
                const qty = parseInt(input.value, 10);

                if (qty && qty > 0 && key && branchId) {
                    update(branchId, key, qty);
                }
            }
        });
    }

    /**
     * Get branch ID from element or its parent
     */
    function getBranchIdFromElement(el) {
        // Check element itself
        if (el.dataset.branchId) {
            return parseInt(el.dataset.branchId, 10);
        }

        // Check parent row
        const row = el.closest('[data-branch-id]');
        if (row) {
            return parseInt(row.dataset.branchId, 10);
        }

        // Check parent group/section
        const section = el.closest('.m-cart__branch-section');
        if (section && section.dataset.branchId) {
            return parseInt(section.dataset.branchId, 10);
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
    async function update(branchId, cartKey, qty) {
        if (state.loading) return;

        const response = await request('update', {
            branch_id: branchId,
            key: cartKey,
            qty: qty
        });

        if (response && response.success) {
            updateBranchUI(branchId, response);
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
    async function increase(branchId, cartKey) {
        if (state.loading) return;

        const response = await request('increase', {
            branch_id: branchId,
            key: cartKey
        });

        if (response && response.success) {
            updateBranchUI(branchId, response);
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
    async function decrease(branchId, cartKey) {
        if (state.loading) return;

        const response = await request('decrease', {
            branch_id: branchId,
            key: cartKey
        });

        if (response && response.success) {
            updateBranchUI(branchId, response);
            updateHeaderCount(response.header_count);
            dispatchEvent('cart:updated', response);
        } else {
            showNotification('error', response?.message || getTranslation('error', 'An error occurred'));
        }

        return response;
    }

    /**
     * Remove item from cart
     * @param {boolean} silent - Skip notifications and reload
     */
    async function remove(branchId, cartKey, silent = false) {
        if (state.loading) return;

        const response = await request('remove', {
            branch_id: branchId,
            key: cartKey
        });

        if (response && response.success) {
            // Remove item row from DOM
            removeItemFromDOM(cartKey, branchId);
            updateHeaderCount(response.header_count);
            dispatchEvent('cart:removed', response);

            // Show empty state if cart is empty (no reload)
            if (response.header_count === 0) {
                showEmptyState();
            }
        } else if (!silent) {
            showNotification('error', response?.message || getTranslation('error', 'An error occurred'));
        }

        return response;
    }

    /**
     * Clear branch items
     * @param {boolean} silent - Skip notifications and reload
     */
    async function clearBranch(branchId, silent = false) {
        if (state.loading) return;

        const response = await request('clearBranch', {
            branch_id: branchId
        });

        if (response && response.success) {
            // Remove branch section from DOM
            const section = document.querySelector(`.m-cart__branch-section[data-branch-id="${branchId}"]`);
            if (section) {
                section.style.transition = 'all 0.3s ease';
                section.style.opacity = '0';
                setTimeout(() => {
                    section.remove();
                    // Remove separator if exists
                    const separator = document.querySelector('.m-cart__branch-separator');
                    if (separator) separator.remove();
                }, 300);
            }

            updateHeaderCount(response.header_count);
            dispatchEvent('cart:branchCleared', response);

            // Show empty state if cart is empty (no reload)
            if (response.header_count === 0) {
                showEmptyState();
            }
        } else if (!silent) {
            showNotification('error', response?.message || getTranslation('error', 'An error occurred'));
        }

        return response;
    }

    /**
     * Clear all cart
     * @param {boolean} silent - Skip notifications and reload
     */
    async function clearAll(silent = false) {
        if (state.loading) return;

        const response = await request('clear', {});

        if (response && response.success) {
            updateHeaderCount(0);
            dispatchEvent('cart:cleared', response);
            showEmptyState();
        } else if (!silent) {
            showNotification('error', response?.message || getTranslation('error', 'An error occurred'));
        }

        return response;
    }

    /**
     * Get branch summary
     */
    async function getSummary(branchId) {
        return await request('summary', { branch_id: branchId }, 'GET');
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
     * Update branch section UI
     */
    function updateBranchUI(branchId, response) {
        const data = response.data;
        if (!data || !data.items) return;

        // Update each item in this branch's section
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

        // Update branch totals
        const section = document.querySelector(`.m-cart__branch-section[data-branch-id="${branchId}"]`);
        if (section && data.totals) {
            const subtotalEl = section.querySelector('.m-cart__summary-value--total');
            if (subtotalEl) {
                subtotalEl.textContent = formatPrice(data.totals.total);
            }

            const qtyEl = section.querySelector('.count-value');
            if (qtyEl) {
                qtyEl.textContent = data.totals.qty;
            }
        }
    }

    /**
     * Remove item from DOM
     */
    function removeItemFromDOM(cartKey, branchId) {
        const row = document.querySelector(`[data-cart-key="${cartKey}"]`);
        if (!row) return;

        row.style.transition = 'all 0.3s ease';
        row.style.opacity = '0';
        row.style.transform = 'translateX(-20px)';

        setTimeout(() => {
            row.remove();

            // Check if branch section is empty
            const section = document.querySelector(`.m-cart__branch-section[data-branch-id="${branchId}"]`);
            if (section) {
                const remainingItems = section.querySelectorAll('.m-cart__item');
                if (remainingItems.length === 0) {
                    section.style.transition = 'all 0.3s ease';
                    section.style.opacity = '0';
                    setTimeout(() => {
                        section.remove();
                        // Remove separator if exists
                        const separator = document.querySelector('.m-cart__branch-separator');
                        if (separator) separator.remove();
                    }, 300);
                }
            }
        }, 300);
    }

    /**
     * Show empty cart state (no reload)
     */
    function showEmptyState() {
        const container = document.querySelector('.gs-cart-section .container');
        if (!container) return;

        // Remove all branch sections
        container.querySelectorAll('.m-cart__branch-section').forEach(el => el.remove());
        container.querySelectorAll('.m-cart__branch-separator').forEach(el => el.remove());
        container.querySelectorAll('.text-center.mt-4').forEach(el => el.remove());

        // Insert empty state HTML
        container.innerHTML = `
            <div class="m-cart__empty">
                <div class="m-cart__empty-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3>${getTranslation('emptyCartTitle', 'Your cart is empty')}</h3>
                <p>${getTranslation('emptyCartText', 'Looks like you haven\'t added anything to your cart yet.')}</p>
                <a href="/" class="m-btn m-btn--primary">
                    <i class="fas fa-arrow-left me-2"></i>
                    ${getTranslation('continueShopping', 'Continue Shopping')}
                </a>
            </div>
        `;
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
        clearBranch,
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
