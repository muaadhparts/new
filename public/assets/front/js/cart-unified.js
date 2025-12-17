/**
 * Unified Cart System
 * ===================
 * Single source of truth for all cart operations.
 * Uses merchant_product_id exclusively - NO fallbacks.
 *
 * @version 3.0
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
            // Legacy selectors for backward compatibility
            legacyAdd: '.add_cart_click, .hp-add-cart, .add-to-cart-btn, .add_to_cart_button',
            legacyQtyPlus: '.hp-qtplus, .qtplus',
            legacyQtyMinus: '.hp-qtminus, .qtminus',
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
                    title: message,
                    showConfirmButton: false,
                    timer: 3000
                });
            } else {
                console.log(`[Cart ${type}]: ${message}`);
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
         */
        async addItem(payload) {
            const mpId = payload.merchant_product_id;

            // Prevent duplicate requests
            if (state.processing.has(mpId)) {
                console.warn('Cart: Request already in progress for MP:', mpId);
                return { success: false, message: 'Request in progress' };
            }

            state.processing.add(mpId);

            try {
                const response = await fetch(CONFIG.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CONFIG.csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || data.error || 'Failed to add item');
                }

                return { success: true, data };

            } catch (error) {
                console.error('Cart API Error:', error);
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
                vendorId: parseInt(c.dataset.vendorId) || 0,
                productId: parseInt(c.dataset.productId) || 0,
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
            if (!this.data.mpId) {
                console.error('CartButton: Missing merchant_product_id');
                return;
            }

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

            // Required: merchant_product_id
            if (!this.data.mpId) {
                errors.push('Missing merchant_product_id');
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
         */
        buildPayload() {
            return {
                merchant_product_id: this.data.mpId,
                vendor_id: this.data.vendorId,
                product_id: this.data.productId,
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
                } else {
                    utils.toast(result.message || 'Failed to add to cart', 'error');
                }
            } catch (error) {
                utils.toast(error.message || 'An error occurred', 'error');
            } finally {
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
         */
        handleClick(e) {
            e.preventDefault();
            const btn = e.currentTarget;

            // REQUIRED: merchant_product_id
            const mpId = btn.dataset.merchantProductId;
            if (!mpId) {
                console.error('Cart: merchant_product_id not found on button');
                utils.toast('Cannot add to cart - missing product data', 'error');
                return;
            }

            // Get vendor_id (optional but recommended)
            const vendorId = btn.dataset.vendorId || 0;

            // Get redirect URL (for Buy Now buttons)
            const redirectUrl = btn.dataset.redirect || null;

            // Get qty from linked input or default to min_qty
            let qty = parseInt(btn.dataset.minQty) || 1;
            const qtyInputSelector = btn.dataset.qtyInput;

            // Try to find qty input by selector or ID
            if (qtyInputSelector) {
                // First try as selector (e.g., ".ill-qty")
                let qtyInput = document.querySelector(qtyInputSelector);
                // Then try as ID
                if (!qtyInput) {
                    qtyInput = document.getElementById(qtyInputSelector);
                }
                if (qtyInput) {
                    qty = parseInt(qtyInput.value) || qty;
                }
            }

            // Find qty from parent m-cart-button container
            const container = btn.closest('.m-cart-button');
            if (container) {
                const containerInput = container.querySelector('.m-cart-button__qty-input');
                if (containerInput) {
                    qty = parseInt(containerInput.value) || qty;
                }
            }

            // Build payload
            const payload = {
                merchant_product_id: parseInt(mpId),
                vendor_id: parseInt(vendorId) || 0,
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
                    } else {
                        utils.toast(result.message || 'Failed to add to cart', 'error');
                    }
                })
                .catch(error => {
                    utils.toast(error.message || 'An error occurred', 'error');
                })
                .finally(() => {
                    // Only restore if not redirecting
                    if (!redirectUrl || !btn.disabled) {
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    }
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
    // Product Detail Page Handler
    // ========================================
    const ProductDetailHandler = {
        /**
         * Get selected size from radio buttons
         */
        getSelectedSize() {
            const sizeRadio = document.querySelector('input[name="size"]:checked');
            if (sizeRadio) {
                const label = document.querySelector(`label[for="${sizeRadio.id}"]`);
                return {
                    key: sizeRadio.dataset.key || '',
                    value: sizeRadio.value,
                    price: parseFloat(sizeRadio.dataset.price) || 0,
                    qty: parseInt(sizeRadio.dataset.qty) || 0,
                    text: label ? label.textContent.trim() : sizeRadio.dataset.key || ''
                };
            }
            return null;
        },

        /**
         * Get selected color from radio buttons
         */
        getSelectedColor() {
            const colorRadio = document.querySelector('input[name="colors"]:checked');
            if (colorRadio) {
                return {
                    value: colorRadio.dataset.color || colorRadio.value,
                    price: parseFloat(colorRadio.dataset.price) || 0
                };
            }
            return null;
        },

        /**
         * Get selected attributes
         */
        getSelectedAttributes() {
            const attrs = {};
            document.querySelectorAll('.cart_attr:checked').forEach(radio => {
                const key = radio.dataset.key;
                if (key) {
                    attrs[key] = {
                        value: radio.value,
                        price: parseFloat(radio.dataset.price) || 0
                    };
                }
            });
            return attrs;
        },

        /**
         * Build payload from product detail page inputs
         */
        buildPayload() {
            // Get required IDs
            const mpId = document.getElementById('merchant_product_id')?.value;
            const vendorId = document.getElementById('vendor_user_id')?.value;
            const productId = document.getElementById('product_id')?.value;

            if (!mpId) {
                throw new Error('merchant_product_id is required');
            }

            // Get quantity
            const qtyInput = document.getElementById('order-qty');
            const minQty = parseInt(document.getElementById('product_minimum_qty')?.value) || 1;
            const qty = parseInt(qtyInput?.value) || minQty;

            // Validate qty >= minQty
            if (qty < minQty) {
                throw new Error(`Minimum quantity is ${minQty}`);
            }

            // Build payload
            const payload = {
                merchant_product_id: parseInt(mpId),
                vendor_id: parseInt(vendorId) || 0,
                product_id: parseInt(productId) || 0,
                qty: qty,
            };

            // Get size (if selected)
            const size = this.getSelectedSize();
            if (size && size.key) {
                payload.size = size.key;
                payload.size_price = size.price;
            }

            // Get color (if selected)
            const color = this.getSelectedColor();
            if (color && color.value) {
                payload.color = color.value;
                payload.color_price = color.price;
            }

            // Get attributes
            const attrs = this.getSelectedAttributes();
            if (Object.keys(attrs).length > 0) {
                payload.attributes = attrs;
            }

            return payload;
        },

        /**
         * Handle Add to Cart click
         */
        async handleAddToCart(e, redirectToCart = false) {
            e.preventDefault();

            const btn = e.currentTarget;
            const originalHtml = btn.innerHTML;

            try {
                // Show loading
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (redirectToCart ? 'جارٍ...' : 'جارٍ...');
                btn.disabled = true;

                // Build payload
                const payload = this.buildPayload();

                // Send request
                const result = await CartAPI.addItem(payload);

                if (result.success) {
                    utils.toast(result.data.message || result.data.success || 'Added to cart', 'success');
                    utils.updateCartUI(result.data);

                    // Redirect to cart if Buy Now
                    if (redirectToCart) {
                        window.location.href = '/cart';
                    }
                } else {
                    utils.toast(result.message || 'Failed to add to cart', 'error');
                }

            } catch (error) {
                utils.toast(error.message || 'An error occurred', 'error');
            } finally {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        },

        /**
         * Handle quantity +/- buttons for product detail page
         */
        initQtyControls() {
            const qtyInput = document.getElementById('order-qty');
            const minQty = parseInt(document.getElementById('product_minimum_qty')?.value) || 1;
            const stockInput = document.getElementById('stock');
            const preorderedInput = document.getElementById('product_preordered');
            const isPreordered = preorderedInput?.value === '1' || preorderedInput?.dataset?.preordered === '1';

            // Handle plus button
            document.querySelectorAll('.qtplus').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (!qtyInput) return;

                    const stock = parseInt(stockInput?.value) || 9999;
                    const maxQty = isPreordered ? 9999 : stock;
                    const current = parseInt(qtyInput.value) || minQty;

                    if (current < maxQty) {
                        qtyInput.value = current + 1;
                    }
                });
            });

            // Handle minus button
            document.querySelectorAll('.qtminus').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (!qtyInput) return;

                    const current = parseInt(qtyInput.value) || minQty;
                    if (current > minQty) {
                        qtyInput.value = current - 1;
                    }
                });
            });
        },

        /**
         * Initialize product detail page handlers
         */
        init() {
            // Only initialize if we're on product detail page
            const mpIdInput = document.getElementById('merchant_product_id');
            if (!mpIdInput) return;

            // Bind Add to Cart button
            const addBtn = document.getElementById('addtodetailscart');
            if (addBtn) {
                addBtn.addEventListener('click', (e) => this.handleAddToCart(e, false));
            }

            // Bind Buy Now button
            const buyBtn = document.getElementById('addtobycard');
            if (buyBtn) {
                buyBtn.addEventListener('click', (e) => this.handleAddToCart(e, true));
            }

            // Initialize qty controls
            this.initQtyControls();

            console.log('[Cart] Product detail handler initialized');
        }
    };

    // ========================================
    // Legacy Support
    // ========================================
    const LegacySupport = {
        /**
         * Handle legacy add-to-cart clicks
         */
        handleLegacyAdd(e) {
            e.preventDefault();
            const btn = e.currentTarget;

            // Try to find merchant_product_id from various sources
            let mpId = btn.dataset.merchantProduct ||
                       btn.dataset.merchantProductId ||
                       btn.closest('[data-merchant-product-id]')?.dataset.merchantProductId ||
                       document.getElementById('merchant_product_id')?.value;

            if (!mpId) {
                console.error('Legacy cart: merchant_product_id not found');
                utils.toast('Cannot add to cart - missing product data', 'error');
                return;
            }

            // Find qty from various sources
            const qtyPrefix = btn.dataset.qtyPrefix || '';
            let qty = 1;

            if (qtyPrefix) {
                const qtyInput = document.querySelector(`[id^="qty_${qtyPrefix}"]`) ||
                                 document.querySelector(`#qty_${qtyPrefix}`) ||
                                 document.querySelector(`.hp-qty-input[id*="${qtyPrefix}"]`);
                if (qtyInput) {
                    qty = parseInt(qtyInput.value) || 1;
                }
            } else {
                const orderQty = document.getElementById('order-qty');
                if (orderQty) {
                    qty = parseInt(orderQty.value) || 1;
                }
            }

            // Find size
            let size = '';
            const sizeSelect = document.querySelector('select[name="size"]');
            const sizeActive = document.querySelector('.size-option.active, .m-cart-button__size.active');
            if (sizeSelect) {
                size = sizeSelect.value;
            } else if (sizeActive) {
                size = sizeActive.dataset.size || sizeActive.textContent.trim();
            }

            // Find color
            let color = '';
            const colorActive = document.querySelector('.color-option.active, .m-cart-button__color.active');
            if (colorActive) {
                color = colorActive.dataset.color || '';
            }

            // Get vendor_id
            let vendorId = btn.dataset.vendorId ||
                          document.getElementById('vendor_user_id')?.value ||
                          0;

            // Build payload
            const payload = {
                merchant_product_id: parseInt(mpId),
                vendor_id: parseInt(vendorId) || 0,
                qty: qty,
                size: size,
                color: color,
            };

            // Show loading on button
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            CartAPI.addItem(payload)
                .then(result => {
                    if (result.success) {
                        utils.toast(result.data.success || 'Added to cart', 'success');
                        utils.updateCartUI(result.data);
                    } else {
                        utils.toast(result.message || 'Failed to add to cart', 'error');
                    }
                })
                .catch(error => {
                    utils.toast(error.message || 'An error occurred', 'error');
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        },

        /**
         * Initialize legacy support
         */
        init() {
            // Bind to legacy add-to-cart buttons
            document.querySelectorAll(CONFIG.selectors.legacyAdd).forEach(btn => {
                // Remove old handlers by cloning
                const newBtn = btn.cloneNode(true);
                btn.parentNode.replaceChild(newBtn, btn);
                newBtn.addEventListener('click', this.handleLegacyAdd);
            });

            // Handle legacy qty buttons
            document.querySelectorAll(CONFIG.selectors.legacyQtyPlus).forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const target = btn.dataset.target;
                    const input = document.querySelector(`#qty_${target}`) ||
                                 document.getElementById('order-qty');
                    if (input) {
                        const max = parseInt(input.dataset.max || input.dataset.stock) || 9999;
                        const preordered = input.dataset.preordered === '1';
                        const effectiveMax = preordered ? 9999 : max;
                        const current = parseInt(input.value) || 1;
                        if (current < effectiveMax) {
                            input.value = current + 1;
                        }
                    }
                });
            });

            document.querySelectorAll(CONFIG.selectors.legacyQtyMinus).forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const target = btn.dataset.target;
                    const input = document.querySelector(`#qty_${target}`) ||
                                 document.getElementById('order-qty');
                    if (input) {
                        const min = parseInt(input.dataset.min || btn.dataset.min) || 1;
                        const current = parseInt(input.value) || 1;
                        if (current > min) {
                            input.value = current - 1;
                        }
                    }
                });
            });
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

        // Initialize simple add buttons (.m-cart-add)
        SimpleAddHandler.init();

        // Initialize product detail page handler
        ProductDetailHandler.init();

        // Initialize legacy support for backward compatibility
        LegacySupport.init();

        console.log('[Cart] Unified cart system initialized v3');
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
