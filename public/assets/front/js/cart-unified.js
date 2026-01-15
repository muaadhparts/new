/**
 * Unified Cart System
 * ===================
 * Single source of truth for all cart operations.
 * Uses merchant_item_id exclusively - NO fallbacks.
 * Maintains backward compatibility with merchant_item_id data attributes.
 *
 * @version 3.1
 */

(function() {
    'use strict';

    // ========================================
    // Configuration
    // ========================================
    const CONFIG = {
        endpoint: '/cart/unified',
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
        currency: window.SITE_CURRENCY || { sign: 'SAR', value: 1 },
        debounceMs: 300,
        selectors: {
            container: '.m-cart-button',
            addBtn: '.m-cart-button__add',
            qtyInput: '.m-cart-button__qty-input',
            qtyMinus: '.m-cart-button__qty-minus',
            qtyPlus: '.m-cart-button__qty-plus',
            sizeBtn: '.m-cart-button__size',
            colorBtn: '.m-cart-button__color',
            loading: '.m-cart-button__loading',
        }
    };

    // ========================================
    // State Management
    // ========================================
    const state = {
        processing: new Set(), // Track processing MP IDs
        lastRequest: null,
    };

    // ========================================
    // Utility Functions
    // ========================================
    const utils = {
        /**
         * Parse JSON safely
         */
        parseJson(str, fallback = []) {
            if (!str || str === '') return fallback;
            try {
                return JSON.parse(str);
            } catch (e) {
                return fallback;
            }
        },

        /**
         * Show toast notification
         */
        toast(message, type = 'success') {
            if (typeof toastr !== 'undefined') {
                toastr[type](message);
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: type === 'error' ? 'error' : 'success',
                    name: message,
                    showConfirmButton: false,
                    timer: 3000
                });
            }
        },

        /**
         * Update cart UI elements
         */
        updateCartUI(data) {
            // Update cart count badges
            document.querySelectorAll('.cart-count, .cart_count, #cart-count, .header-cart-count').forEach(el => {
                el.textContent = data.cart_count || 0;
            });

            // Update cart total
            document.querySelectorAll('.cart-total, .cart_total, #cart-total').forEach(el => {
                el.textContent = data.cart_total || '';
            });

            // Trigger custom event for other scripts
            document.dispatchEvent(new CustomEvent('cart:updated', { detail: data }));
        },

        /**
         * Format price
         */
        formatPrice(price) {
            const curr = CONFIG.currency;
            const converted = price * (curr.value || 1);
            return curr.sign + ' ' + converted.toFixed(2);
        }
    };

    // ========================================
    // Cart API
    // ========================================
    const CartAPI = {
        /**
         * Add item to cart (unified endpoint)
         * Sends merchant_item_id in payload (new naming convention)
         * Also reads from merchant_item_id for backward compatibility
         */
        async addItem(payload) {
            // Support both merchant_item_id (new) and merchant_item_id (legacy)
            const mpId = payload.merchant_item_id || payload.merchant_item_id;

            // Prevent duplicate requests
            if (state.processing.has(mpId)) {
                return { success: false, message: 'Request in progress' };
            }

            state.processing.add(mpId);

            // Convert payload to use new naming convention for API
            const apiPayload = {
                ...payload,
                merchant_item_id: mpId
            };

            try {
                const response = await fetch(CONFIG.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CONFIG.csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(apiPayload)
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || data.error || 'Failed to add item');
                }

                return { success: true, data };

            } catch (error) {
                return { success: false, message: error.message };
            } finally {
                state.processing.delete(mpId);
            }
        }
    };

    // ========================================
    // Cart Button Controller
    // ========================================
    class CartButtonController {
        constructor(container) {
            this.container = container;
            this.data = this.extractData();
            this.state = {
                qty: this.data.minQty,
                selectedSize: '',
                selectedSizeIndex: -1,
                selectedColor: '',
                sizePrice: 0,
                colorPrice: 0,
            };
            this.init();
        }

        /**
         * Extract data from DOM attributes
         */
        extractData() {
            const c = this.container;
            return {
                mpId: parseInt(c.dataset.mpId) || 0,
                // New naming: data-merchant-user-id, fallback to data-merchant-id for legacy
                merchantUserId: parseInt(c.dataset.merchantUserId) || parseInt(c.dataset.merchantId) || 0,
                catalogItemId: parseInt(c.dataset.catalogItemId) || parseInt(c.dataset.productId) || 0,
                price: parseFloat(c.dataset.price) || 0,
                stock: parseInt(c.dataset.stock) || 0,
                preordered: c.dataset.preordered === '1',
                minQty: parseInt(c.dataset.minQty) || 1,
                maxQty: parseInt(c.dataset.maxQty) || 9999,
                weight: parseFloat(c.dataset.weight) || 0,
                sizes: utils.parseJson(c.dataset.sizes, []),
                sizeQtys: utils.parseJson(c.dataset.sizeQtys, []),
                sizePrices: utils.parseJson(c.dataset.sizePrices, []),
                colors: utils.parseJson(c.dataset.colors, []),
                colorPrices: utils.parseJson(c.dataset.colorPrices, []),
                inStock: c.dataset.inStock === '1',
                productType: c.dataset.productType || 'Physical',
            };
        }

        /**
         * Initialize controller
         */
        init() {
            if (!this.data.mpId) return;

            this.bindEvents();
            this.initDefaults();
        }

        /**
         * Set initial default selections
         */
        initDefaults() {
            // Select first available size
            if (this.data.sizes.length > 0) {
                const firstAvailable = this.data.sizes.findIndex((sz, i) =>
                    this.data.sizeQtys[i] > 0 || this.data.preordered
                );
                if (firstAvailable >= 0) {
                    this.selectSize(firstAvailable);
                }
            }

            // Select first color
            if (this.data.colors.length > 0) {
                this.selectColor(0);
            }

            // Set initial qty
            this.state.qty = this.data.minQty;
            this.updateQtyDisplay();
        }

        /**
         * Bind event listeners
         */
        bindEvents() {
            // Quantity buttons
            this.container.querySelectorAll(CONFIG.selectors.qtyMinus).forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.decreaseQty();
                });
            });

            this.container.querySelectorAll(CONFIG.selectors.qtyPlus).forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.increaseQty();
                });
            });

            // Size buttons
            this.container.querySelectorAll(CONFIG.selectors.sizeBtn).forEach((btn, index) => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (!btn.disabled) {
                        this.selectSize(index);
                    }
                });
            });

            // Color buttons
            this.container.querySelectorAll(CONFIG.selectors.colorBtn).forEach((btn, index) => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.selectColor(index);
                });
            });

            // Add to cart button
            this.container.querySelectorAll(CONFIG.selectors.addBtn).forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.addToCart();
                });
            });
        }

        /**
         * Select size
         */
        selectSize(index) {
            if (index < 0 || index >= this.data.sizes.length) return;

            const sizeQty = this.data.sizeQtys[index] || 0;
            if (sizeQty <= 0 && !this.data.preordered) return;

            this.state.selectedSizeIndex = index;
            this.state.selectedSize = this.data.sizes[index];
            this.state.sizePrice = this.data.sizePrices[index] || 0;

            // Update max qty based on size stock
            const newMaxQty = this.data.preordered ? 9999 : Math.max(this.data.minQty, sizeQty);
            this.data.maxQty = newMaxQty;

            // Adjust qty if exceeds new max
            if (this.state.qty > newMaxQty) {
                this.state.qty = newMaxQty;
                this.updateQtyDisplay();
            }

            // Update UI
            this.container.querySelectorAll(CONFIG.selectors.sizeBtn).forEach((btn, i) => {
                btn.classList.toggle('active', i === index);
            });
        }

        /**
         * Select color
         */
        selectColor(index) {
            if (index < 0 || index >= this.data.colors.length) return;

            this.state.selectedColor = this.data.colors[index];
            this.state.colorPrice = this.data.colorPrices[index] || 0;

            // Update UI
            this.container.querySelectorAll(CONFIG.selectors.colorBtn).forEach((btn, i) => {
                btn.classList.toggle('active', i === index);
            });
        }

        /**
         * Increase quantity
         */
        increaseQty() {
            if (this.state.qty < this.data.maxQty) {
                this.state.qty++;
                this.updateQtyDisplay();
            }
        }

        /**
         * Decrease quantity
         */
        decreaseQty() {
            if (this.state.qty > this.data.minQty) {
                this.state.qty--;
                this.updateQtyDisplay();
            }
        }

        /**
         * Update quantity display
         */
        updateQtyDisplay() {
            this.container.querySelectorAll(CONFIG.selectors.qtyInput).forEach(input => {
                input.value = this.state.qty;
            });

            // Update button states
            this.container.querySelectorAll(CONFIG.selectors.qtyMinus).forEach(btn => {
                btn.disabled = this.state.qty <= this.data.minQty;
                btn.classList.toggle('disabled', this.state.qty <= this.data.minQty);
            });

            this.container.querySelectorAll(CONFIG.selectors.qtyPlus).forEach(btn => {
                btn.disabled = this.state.qty >= this.data.maxQty;
                btn.classList.toggle('disabled', this.state.qty >= this.data.maxQty);
            });
        }

        /**
         * Validate before submission
         */
        validate() {
            const errors = [];

            // Required: merchant_item_id
            if (!this.data.mpId) {
                errors.push('Missing merchant_item_id');
            }

            // Quantity validation
            if (this.state.qty < this.data.minQty) {
                errors.push(`Minimum quantity is ${this.data.minQty}`);
            }
            if (this.state.qty > this.data.maxQty) {
                errors.push(`Maximum quantity is ${this.data.maxQty}`);
            }

            // Size validation (if sizes exist)
            if (this.data.sizes.length > 0 && !this.state.selectedSize) {
                errors.push('Please select a size');
            }

            return errors;
        }

        /**
         * Build payload for API
         * Uses merchant_item_id (new naming convention)
         */
        buildPayload() {
            return {
                merchant_item_id: this.data.mpId,
                merchant_id: this.data.merchantUserId,
                catalog_item_id: this.data.catalogItemId,
                qty: this.state.qty,
                size: this.state.selectedSize,
                color: this.state.selectedColor,
                size_price: this.state.sizePrice,
                color_price: this.state.colorPrice,
                weight: this.data.weight,
                min_qty: this.data.minQty,
            };
        }

        /**
         * Set loading state
         */
        setLoading(loading) {
            const btn = this.container.querySelector(CONFIG.selectors.addBtn);
            const loader = this.container.querySelector(CONFIG.selectors.loading);

            if (btn) {
                btn.disabled = loading;
                btn.style.display = loading ? 'none' : '';
            }
            if (loader) {
                loader.style.display = loading ? 'flex' : 'none';
            }
        }

        /**
         * Add to cart
         */
        async addToCart() {
            // Validate
            const errors = this.validate();
            if (errors.length > 0) {
                utils.toast(errors[0], 'error');
                return;
            }

            // Build payload
            const payload = this.buildPayload();

            // Show loading
            this.setLoading(true);

            try {
                const result = await CartAPI.addItem(payload);

                if (result.success) {
                    utils.toast(result.data.success || result.data.message || 'Added to cart', 'success');
                    utils.updateCartUI(result.data);
                    this.setLoading(false);
                } else if (result.message === 'Request in progress') {
                    // Silent - keep loading state, no toast
                    // Button stays disabled until the original request completes
                } else {
                    utils.toast(result.message || 'Failed to add to cart', 'error');
                    this.setLoading(false);
                }
            } catch (error) {
                utils.toast(error.message || 'An error occurred', 'error');
                this.setLoading(false);
            }
        }
    }

    // ========================================
    // Simple Add Button Handler
    // ========================================
    const SimpleAddHandler = {
        /**
         * Handle simple add-to-cart button clicks (.m-cart-add)
         * Reads merchant_item_id (new) or merchant_item_id (legacy) for backward compatibility
         */
        handleClick(e) {
            e.preventDefault();
            const btn = e.currentTarget;

            // REQUIRED: merchant_item_id (also accepts merchant_item_id for backward compatibility)
            const mpId = btn.dataset.merchantItemId || btn.dataset.merchantProductId;
            if (!mpId) {
                utils.toast('Cannot add to cart - missing catalogItem data', 'error');
                return;
            }

            // Get merchant_user_id (new naming, fallback to merchantId for legacy)
            const merchantUserId = btn.dataset.merchantUserId || btn.dataset.merchantId || 0;

            // Get redirect URL (for Buy Now buttons)
            const redirectUrl = btn.dataset.redirect || null;

            // Get qty from linked input or default to min_qty
            let qty = parseInt(btn.dataset.minQty) || 1;
            const qtyInputSelector = btn.dataset.qtyInput;

            // Try to find qty input - prioritize local container search
            if (qtyInputSelector) {
                let qtyInput = null;

                // 1. First: Search within closest modal/catalogItem container (for quickview modals)
                const modalContainers = [
                    '.catalog-quickview',
                    '.ill-catalogItem',
                    '.modal-body',
                    '.mfp-content',
                    '.m-cart-button'
                ];

                for (const containerSelector of modalContainers) {
                    const container = btn.closest(containerSelector);
                    if (container) {
                        qtyInput = container.querySelector(qtyInputSelector);
                        if (qtyInput) break;
                    }
                }

                // 2. Fallback: Try as global selector
                if (!qtyInput) {
                    qtyInput = document.querySelector(qtyInputSelector);
                }

                // 3. Fallback: Try as ID
                if (!qtyInput) {
                    qtyInput = document.getElementById(qtyInputSelector);
                }

                if (qtyInput) {
                    qty = parseInt(qtyInput.value) || qty;
                }
            }

            // Also check parent m-cart-button container
            const cartButtonContainer = btn.closest('.m-cart-button');
            if (cartButtonContainer) {
                const containerInput = cartButtonContainer.querySelector('.m-cart-button__qty-input');
                if (containerInput) {
                    qty = parseInt(containerInput.value) || qty;
                }
            }

            // Build payload with new naming convention
            const payload = {
                merchant_item_id: parseInt(mpId),
                merchant_id: parseInt(merchantUserId) || 0,
                qty: qty,
            };

            // Show loading
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            CartAPI.addItem(payload)
                .then(result => {
                    if (result.success) {
                        utils.toast(result.data.message || result.data.success || 'Added to cart', 'success');
                        utils.updateCartUI(result.data);

                        // Redirect if specified (Buy Now functionality)
                        if (redirectUrl) {
                            window.location.href = redirectUrl;
                            return; // Don't restore button state, we're redirecting
                        }
                        // Restore button
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    } else if (result.message === 'Request in progress') {
                        // Silent - keep loading state, no toast
                        // Button stays disabled until the original request completes
                    } else {
                        utils.toast(result.message || 'Failed to add to cart', 'error');
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    }
                })
                .catch(error => {
                    utils.toast(error.message || 'An error occurred', 'error');
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                });
        },

        /**
         * Initialize simple add buttons
         */
        init() {
            document.querySelectorAll('.m-cart-add').forEach(btn => {
                // Remove existing listeners by cloning
                const newBtn = btn.cloneNode(true);
                btn.parentNode.replaceChild(newBtn, btn);
                newBtn.addEventListener('click', this.handleClick);
            });
        }
    };

    // ========================================
    // Quantity Controls for CatalogItem Detail Page
    // ========================================
    const QtyControls = {
        /**
         * Initialize quantity +/- buttons
         */
        init() {
            // Handle plus button (.qtplus, .modal-qtplus)
            document.querySelectorAll('.qtplus, .modal-qtplus').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const input = this.findQtyInput(btn);
                    if (!input) return;

                    const stock = parseInt(btn.dataset.stock || input.dataset.stock) || 9999;
                    const preordered = (btn.dataset.preordered === '1') || (input.dataset.preordered === '1');
                    const maxQty = preordered ? 9999 : stock;
                    const current = parseInt(input.value) || 1;

                    if (current < maxQty) {
                        input.value = current + 1;
                    }
                });
            });

            // Handle minus button (.qtminus, .modal-qtminus)
            document.querySelectorAll('.qtminus, .modal-qtminus').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const input = this.findQtyInput(btn);
                    if (!input) return;

                    const min = parseInt(btn.dataset.min || input.dataset.min) || 1;
                    const current = parseInt(input.value) || 1;

                    if (current > min) {
                        input.value = current - 1;
                    }
                });
            });
        },

        /**
         * Find qty input relative to button
         */
        findQtyInput(btn) {
            // 1. Try modal containers first
            const containers = ['.catalog-quickview', '.ill-catalogItem', '.modal-body', '.mfp-content', '.qty', '.add-qty-wrapper'];
            for (const sel of containers) {
                const container = btn.closest(sel);
                if (container) {
                    const input = container.querySelector('.modal-qty-input, .ill-qty, .qttotal, #purchase-qty');
                    if (input) return input;
                }
            }

            // 2. Fallback to global purchase-qty
            return document.getElementById('purchase-qty');
        }
    };

    // ========================================
    // Event Delegation for Dynamic Content
    // ========================================
    const EventDelegation = {
        initialized: false,

        /**
         * Initialize event delegation for dynamically loaded content
         * This handles modal content loaded via AJAX
         */
        init() {
            if (this.initialized) return;
            this.initialized = true;

            // Delegate .m-cart-add clicks
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.m-cart-add');
                if (btn) {
                    SimpleAddHandler.handleClick({ ...e, currentTarget: btn, preventDefault: () => e.preventDefault() });
                }
            });

            // Delegate modal qty plus clicks
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.modal-qtplus');
                if (btn) {
                    e.preventDefault();
                    this.handleQtyPlus(btn);
                }
            });

            // Delegate modal qty minus clicks
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.modal-qtminus');
                if (btn) {
                    e.preventDefault();
                    this.handleQtyMinus(btn);
                }
            });
        },

        /**
         * Handle modal qty plus button
         */
        handleQtyPlus(btn) {
            const modalContainers = ['.catalog-quickview', '.ill-catalogItem', '.modal-body', '.mfp-content'];
            let input = null;

            for (const containerSelector of modalContainers) {
                const container = btn.closest(containerSelector);
                if (container) {
                    input = container.querySelector('.modal-qty-input, .ill-qty');
                    if (input) break;
                }
            }

            if (input) {
                const max = parseInt(btn.dataset.stock || input.dataset.stock) || 9999;
                const preordered = (btn.dataset.preordered === '1') || (input.dataset.preordered === '1');
                const effectiveMax = preordered ? 9999 : max;
                const current = parseInt(input.value) || 1;
                if (current < effectiveMax) {
                    input.value = current + 1;
                }
            }
        },

        /**
         * Handle modal qty minus button
         */
        handleQtyMinus(btn) {
            const modalContainers = ['.catalog-quickview', '.ill-catalogItem', '.modal-body', '.mfp-content'];
            let input = null;

            for (const containerSelector of modalContainers) {
                const container = btn.closest(containerSelector);
                if (container) {
                    input = container.querySelector('.modal-qty-input, .ill-qty');
                    if (input) break;
                }
            }

            if (input) {
                const min = parseInt(btn.dataset.min || input.dataset.min) || 1;
                const current = parseInt(input.value) || 1;
                if (current > min) {
                    input.value = current - 1;
                }
            }
        }
    };

    // ========================================
    // Initialization
    // ========================================
    function init() {
        // Initialize new cart button components (full component with size/color/qty)
        document.querySelectorAll(CONFIG.selectors.container).forEach(container => {
            if (!container._cartController) {
                container._cartController = new CartButtonController(container);
            }
        });

        // Initialize simple add buttons (.m-cart-add) for existing elements
        SimpleAddHandler.init();

        // Initialize quantity +/- controls
        QtyControls.init();

        // Initialize event delegation for dynamic content (modals, AJAX)
        EventDelegation.init();
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Re-initialize on dynamic content (for AJAX loaded content)
    document.addEventListener('cart:reinit', init);

    // Expose API globally for external use
    window.UnifiedCart = {
        addItem: CartAPI.addItem,
        reinit: init,
        utils: utils,
    };

})();
