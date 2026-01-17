/**
 * m-cart-add.js
 * ==============
 * Handler for .m-cart-add buttons
 *
 * Class: .m-cart-add (ONLY)
 * Required: data-merchant-item-id
 * Optional: data-qty-input, data-size, data-color, data-redirect
 *
 * API: POST /merchant-cart/add
 */
(function() {
    'use strict';

    // ============================================
    // CONFIGURATION
    // ============================================
    const CONFIG = {
        endpoints: {
            add: '/merchant-cart/add',
            count: '/merchant-cart/count',
        },
        selectors: {
            // Component wrapper
            wrapper: '.m-cart-button',
            // Add button - SINGLE pattern only
            addButton: '.m-cart-add',
            // Size selector
            sizeButton: '.m-cart-button__size',
            // Color selector
            colorButton: '.m-cart-button__color',
            // Qty input
            qtyInput: '.m-cart-button__qty-input',
            // Qty buttons
            qtyIncrease: '[data-action="increase"]',
            qtyDecrease: '[data-action="decrease"]',
            // Loading state
            loading: '.m-cart-button__loading',
            // Header cart count
            cartCount: '#cart-count',
        },
        classes: {
            active: 'active',
            disabled: 'disabled',
            loading: 'is-loading',
        }
    };

    // ============================================
    // STATE
    // ============================================
    let isProcessing = false;

    // ============================================
    // CSRF TOKEN
    // ============================================
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // ============================================
    // API REQUEST
    // ============================================
    async function apiRequest(endpoint, data) {
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Cart API Error:', error);
            return { success: false, message: error.message };
        }
    }

    // ============================================
    // ADD TO CART
    // ============================================
    async function addToCart(merchantItemId, options = {}) {
        if (isProcessing || !merchantItemId) return;

        isProcessing = true;

        const payload = {
            merchant_item_id: merchantItemId,
            qty: options.qty || 1,
        };

        if (options.size) payload.size = options.size;
        if (options.color) payload.color = options.color;

        const result = await apiRequest(CONFIG.endpoints.add, payload);

        isProcessing = false;

        if (result.success) {
            // Update header cart count (new API uses header_count)
            updateCartCount(result.header_count ?? result.data?.totals?.qty ?? 0);

            // Show success notification
            showNotification('success', result.message || 'Added to cart');

            // Dispatch custom event
            document.dispatchEvent(new CustomEvent('cart:added', {
                detail: { merchantItemId, result }
            }));
        } else {
            showNotification('error', result.message || 'Failed to add to cart');
        }

        return result;
    }

    // ============================================
    // UPDATE CART COUNT
    // ============================================
    function updateCartCount(count) {
        const countElements = document.querySelectorAll(CONFIG.selectors.cartCount);
        countElements.forEach(el => {
            el.textContent = count;
            // Add animation
            el.classList.add('cart-count-updated');
            setTimeout(() => el.classList.remove('cart-count-updated'), 300);
        });
    }

    // ============================================
    // GET DATA FROM WRAPPER
    // ============================================
    function getWrapperData(wrapper) {
        return {
            merchantItemId: parseInt(wrapper.dataset.mpId, 10),
            stock: parseInt(wrapper.dataset.stock || 0, 10),
            preordered: wrapper.dataset.preordered === '1',
            minQty: parseInt(wrapper.dataset.minQty || 1, 10),
            maxQty: parseInt(wrapper.dataset.maxQty || 9999, 10),
            sizes: JSON.parse(wrapper.dataset.sizes || '[]'),
            sizeQtys: JSON.parse(wrapper.dataset.sizeQtys || '[]'),
            colors: JSON.parse(wrapper.dataset.colors || '[]'),
        };
    }

    // ============================================
    // GET SELECTED OPTIONS
    // ============================================
    function getSelectedOptions(wrapper) {
        const options = {
            qty: 1,
            size: null,
            color: null,
        };

        // Get qty
        const qtyInput = wrapper.querySelector(CONFIG.selectors.qtyInput);
        if (qtyInput) {
            options.qty = parseInt(qtyInput.value, 10) || 1;
        }

        // Get selected size
        const activeSize = wrapper.querySelector(`${CONFIG.selectors.sizeButton}.${CONFIG.classes.active}`);
        if (activeSize) {
            options.size = activeSize.dataset.size;
        }

        // Get selected color
        const activeColor = wrapper.querySelector(`${CONFIG.selectors.colorButton}.${CONFIG.classes.active}`);
        if (activeColor) {
            options.color = activeColor.dataset.color;
        }

        return options;
    }

    // ============================================
    // SHOW NOTIFICATION
    // ============================================
    function showNotification(type, message) {
        // Try toastr first
        if (typeof toastr !== 'undefined') {
            toastr[type === 'success' ? 'success' : 'error'](message);
            return;
        }

        // Try SweetAlert
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type,
                title: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });
            return;
        }

        // Fallback to alert
        if (type === 'error') {
            alert(message);
        }
    }

    // ============================================
    // SET LOADING STATE
    // ============================================
    function setLoading(wrapper, loading) {
        const btn = wrapper.querySelector(CONFIG.selectors.addButton);
        const loadingEl = wrapper.querySelector(CONFIG.selectors.loading);

        if (btn) {
            btn.disabled = loading;
            btn.style.display = loading ? 'none' : '';
        }

        if (loadingEl) {
            loadingEl.style.display = loading ? 'flex' : 'none';
        }

        wrapper.classList.toggle(CONFIG.classes.loading, loading);
    }

    // ============================================
    // HANDLE SIZE SELECTION
    // ============================================
    function handleSizeSelect(wrapper, sizeBtn) {
        const data = getWrapperData(wrapper);
        const selectedSize = sizeBtn.dataset.size;
        const sizeIdx = data.sizes.indexOf(selectedSize);
        const sizeStock = data.sizeQtys[sizeIdx] || 0;

        // Update active state
        wrapper.querySelectorAll(CONFIG.selectors.sizeButton).forEach(btn => {
            btn.classList.remove(CONFIG.classes.active);
        });
        sizeBtn.classList.add(CONFIG.classes.active);

        // Update qty max based on size stock
        const qtyInput = wrapper.querySelector(CONFIG.selectors.qtyInput);
        if (qtyInput && !data.preordered) {
            qtyInput.dataset.max = sizeStock;
            if (parseInt(qtyInput.value, 10) > sizeStock) {
                qtyInput.value = Math.max(1, sizeStock);
            }
        }
    }

    // ============================================
    // HANDLE COLOR SELECTION
    // ============================================
    function handleColorSelect(wrapper, colorBtn) {
        wrapper.querySelectorAll(CONFIG.selectors.colorButton).forEach(btn => {
            btn.classList.remove(CONFIG.classes.active);
        });
        colorBtn.classList.add(CONFIG.classes.active);
    }

    // ============================================
    // HANDLE QTY CHANGE
    // ============================================
    function handleQtyChange(wrapper, action) {
        const qtyInput = wrapper.querySelector(CONFIG.selectors.qtyInput);
        if (!qtyInput) return;

        const current = parseInt(qtyInput.value, 10) || 1;
        const min = parseInt(qtyInput.dataset.min || 1, 10);
        const max = parseInt(qtyInput.dataset.max || 9999, 10);

        let newQty = current;
        if (action === 'increase') {
            newQty = Math.min(max, current + 1);
        } else if (action === 'decrease') {
            newQty = Math.max(min, current - 1);
        }

        qtyInput.value = newQty;

        // Update button states
        const decreaseBtn = wrapper.querySelector(CONFIG.selectors.qtyDecrease);
        const increaseBtn = wrapper.querySelector(CONFIG.selectors.qtyIncrease);

        if (decreaseBtn) decreaseBtn.disabled = newQty <= min;
        if (increaseBtn) increaseBtn.disabled = newQty >= max;
    }

    // ============================================
    // EVENT DELEGATION
    // ============================================
    document.addEventListener('click', async function(e) {
        // Handle Add to Cart button
        const addBtn = e.target.closest(CONFIG.selectors.addButton);
        if (addBtn) {
            e.preventDefault();

            // Find wrapper (parent component)
            const wrapper = addBtn.closest(CONFIG.selectors.wrapper);

            if (wrapper) {
                // Component mode - get data from wrapper
                const data = getWrapperData(wrapper);
                const options = getSelectedOptions(wrapper);

                if (data.merchantItemId) {
                    setLoading(wrapper, true);
                    await addToCart(data.merchantItemId, options);
                    setLoading(wrapper, false);
                }
            } else {
                // Simple button mode - data on button itself (m-cart-add pattern)
                const merchantItemId = parseInt(addBtn.dataset.merchantItemId, 10);
                if (merchantItemId) {
                    // Get qty from external input if specified
                    let qty = parseInt(addBtn.dataset.qty || 1, 10);
                    const qtyInputSelector = addBtn.dataset.qtyInput;
                    if (qtyInputSelector) {
                        const externalInput = document.querySelector(qtyInputSelector);
                        if (externalInput) {
                            qty = parseInt(externalInput.value, 10) || 1;
                        }
                    }

                    // Get size from active selector or data attribute
                    let size = addBtn.dataset.size || null;
                    const sizeSelector = document.querySelector('.size-option.active, .m-size-btn.active, [name="size"]:checked');
                    if (sizeSelector) {
                        size = sizeSelector.dataset.size || sizeSelector.value || size;
                    }

                    // Get color from active selector or data attribute
                    let color = addBtn.dataset.color || null;
                    const colorSelector = document.querySelector('.color-option.active, .m-color-btn.active, [name="color"]:checked');
                    if (colorSelector) {
                        color = colorSelector.dataset.color || colorSelector.value || color;
                    }

                    addBtn.disabled = true;
                    const result = await addToCart(merchantItemId, { qty, size, color });
                    addBtn.disabled = false;

                    // Handle redirect if specified (Buy Now)
                    if (result.success && addBtn.dataset.redirect) {
                        window.location.href = addBtn.dataset.redirect;
                    }
                }
            }

            return;
        }

        // Handle Size selection
        const sizeBtn = e.target.closest(CONFIG.selectors.sizeButton);
        if (sizeBtn && !sizeBtn.disabled) {
            e.preventDefault();
            const wrapper = sizeBtn.closest(CONFIG.selectors.wrapper);
            if (wrapper) handleSizeSelect(wrapper, sizeBtn);
            return;
        }

        // Handle Color selection
        const colorBtn = e.target.closest(CONFIG.selectors.colorButton);
        if (colorBtn) {
            e.preventDefault();
            const wrapper = colorBtn.closest(CONFIG.selectors.wrapper);
            if (wrapper) handleColorSelect(wrapper, colorBtn);
            return;
        }

        // Handle Qty increase
        const increaseBtn = e.target.closest(CONFIG.selectors.qtyIncrease);
        if (increaseBtn) {
            e.preventDefault();
            const wrapper = increaseBtn.closest(CONFIG.selectors.wrapper);
            if (wrapper) handleQtyChange(wrapper, 'increase');
            return;
        }

        // Handle Qty decrease
        const decreaseBtn = e.target.closest(CONFIG.selectors.qtyDecrease);
        if (decreaseBtn) {
            e.preventDefault();
            const wrapper = decreaseBtn.closest(CONFIG.selectors.wrapper);
            if (wrapper) handleQtyChange(wrapper, 'decrease');
            return;
        }
    });

    // ============================================
    // GLOBAL API
    // ============================================
    window.MerchantCartGlobal = {
        add: addToCart,
        updateCount: updateCartCount,
    };

    console.log('[CartGlobal] Initialized');
})();
