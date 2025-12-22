/**
 * Centralized Quantity Control Module
 * ====================================
 * Unified quantity +/- logic respecting stock and minimum_qty.
 * Used across: Cart, Quick Modal, Product Page, Alternatives.
 *
 * @version 1.0
 *
 * Usage:
 * ------
 * 1. Add data attributes to your +/- buttons:
 *    <button class="qty-btn qty-plus" data-stock="10" data-preordered="0">+</button>
 *    <input class="qty-input" value="1" data-min="1">
 *    <button class="qty-btn qty-minus" data-min="1">-</button>
 *
 * 2. Or use specific selectors by adding wrapper class:
 *    <div class="qty-control" data-stock="10" data-min="1" data-preordered="0">
 *      <button class="qty-plus">+</button>
 *      <input class="qty-input" value="1">
 *      <button class="qty-minus">-</button>
 *    </div>
 *
 * Supported Classes:
 * ------------------
 * Plus:  .qty-plus, .qtplus, .modal-plus, .modal-qtplus, .m-cart__qty-btn.quantity-up
 * Minus: .qty-minus, .qtminus, .modal-minus, .modal-qtminus, .m-cart__qty-btn.quantity-down
 * Input: .qty-input, .qttotal, .modal-total, .modal-qty-input, .m-cart__qty-input
 */

(function($) {
    'use strict';

    // ========================================
    // Configuration
    // ========================================
    const CONFIG = {
        selectors: {
            plus: ['.qty-plus', '.qtplus', '.modal-plus', '.modal-qtplus', '.quantity-up'],
            minus: ['.qty-minus', '.qtminus', '.modal-minus', '.modal-qtminus', '.quantity-down'],
            input: ['.qty-input', '.qttotal', '.modal-total', '.modal-qty-input', '.m-cart__qty-input', '.ill-qty', '#order-qty']
        },
        containers: ['.qty-control', '.m-cart__qty-control', '.qty', '.add-qty-wrapper', '.multiple-item-price']
    };

    // ========================================
    // Utility Functions
    // ========================================
    const Utils = {
        /**
         * Find the quantity input relative to the button
         */
        findInput($btn) {
            // 1. Try in parent container
            for (const container of CONFIG.containers) {
                const $container = $btn.closest(container);
                if ($container.length) {
                    for (const selector of CONFIG.selectors.input) {
                        const $input = $container.find(selector);
                        if ($input.length) return $input.first();
                    }
                }
            }

            // 2. Try sibling
            const $parent = $btn.parent();
            for (const selector of CONFIG.selectors.input) {
                const $input = $parent.find(selector);
                if ($input.length) return $input.first();
            }

            // 3. Try data-target attribute
            const target = $btn.data('target');
            if (target) {
                const $input = $(target);
                if ($input.length) return $input;
            }

            return null;
        },

        /**
         * Get stock limit from element or parents
         */
        getStock($element) {
            // Check element itself
            let stock = parseInt($element.data('stock'));
            if (!isNaN(stock)) return stock;

            // Check parent container
            for (const container of CONFIG.containers) {
                const $container = $element.closest(container);
                if ($container.length) {
                    stock = parseInt($container.data('stock'));
                    if (!isNaN(stock)) return stock;
                }
            }

            // Check hidden input
            const $container = $element.closest(CONFIG.containers.join(', '));
            if ($container.length) {
                const $stockInput = $container.find('.stock_val, .product-stock, [name="stock"]');
                if ($stockInput.length) {
                    stock = parseInt($stockInput.val());
                    if (!isNaN(stock)) return stock;
                }
            }

            return 0; // Default: 0 means check preordered
        },

        /**
         * Get minimum quantity
         */
        getMinQty($element) {
            // Check element itself
            let min = parseInt($element.data('min') || $element.data('min-qty') || $element.data('minQty'));
            if (!isNaN(min) && min > 0) return min;

            // Check input
            if ($element.is('input')) {
                min = parseInt($element.attr('min') || $element.data('min'));
                if (!isNaN(min) && min > 0) return min;
            }

            // Check parent container
            for (const container of CONFIG.containers) {
                const $container = $element.closest(container);
                if ($container.length) {
                    min = parseInt($container.data('min') || $container.data('min-qty'));
                    if (!isNaN(min) && min > 0) return min;

                    // Check hidden input
                    const $minInput = $container.find('.minimum_qty, [name="minimum_qty"]');
                    if ($minInput.length) {
                        min = parseInt($minInput.val());
                        if (!isNaN(min) && min > 0) return min;
                    }
                }
            }

            return 1; // Default minimum
        },

        /**
         * Check if preordered (unlimited stock)
         */
        isPreordered($element) {
            // Check element
            let preordered = $element.data('preordered');
            if (preordered === 1 || preordered === '1' || preordered === true) return true;

            // Check parent container
            for (const container of CONFIG.containers) {
                const $container = $element.closest(container);
                if ($container.length) {
                    preordered = $container.data('preordered');
                    if (preordered === 1 || preordered === '1' || preordered === true) return true;

                    // Check hidden input
                    const $preInput = $container.find('.preordered_val, [name="preordered"]');
                    if ($preInput.length && ($preInput.val() === '1' || $preInput.val() === 1)) {
                        return true;
                    }
                }
            }

            return false;
        },

        /**
         * Show toast message (disabled - silent mode)
         * To enable, uncomment the toastr line
         */
        toast(message, type = 'warning') {
            // Silent mode - no toast messages for qty controls
            // if (typeof toastr !== 'undefined') {
            //     toastr[type](message);
            // }
            console.log(`[Qty ${type}]: ${message}`);
        }
    };

    // ========================================
    // Quantity Controller
    // ========================================
    const QtyController = {
        /**
         * Increase quantity
         */
        increase($btn) {
            const $input = Utils.findInput($btn);
            if (!$input) {
                console.warn('QtyControl: Input not found');
                return false;
            }

            const current = parseInt($input.val()) || 1;
            const stock = Utils.getStock($btn);
            const preordered = Utils.isPreordered($btn);

            // Check stock limit (only if not preordered and stock > 0)
            if (!preordered && stock > 0 && current >= stock) {
                Utils.toast(window.QTY_MESSAGES?.stockLimit || 'Stock limit reached: ' + stock);
                return false;
            }

            $input.val(current + 1);
            $input.trigger('change');
            return true;
        },

        /**
         * Decrease quantity
         */
        decrease($btn) {
            const $input = Utils.findInput($btn);
            if (!$input) {
                console.warn('QtyControl: Input not found');
                return false;
            }

            const current = parseInt($input.val()) || 1;
            const minQty = Utils.getMinQty($input);

            // Check minimum
            if (current <= minQty) {
                Utils.toast(window.QTY_MESSAGES?.minLimit || 'Minimum quantity is ' + minQty);
                return false;
            }

            $input.val(current - 1);
            $input.trigger('change');
            return true;
        },

        /**
         * Validate and clamp quantity
         */
        validate($input) {
            let val = parseInt($input.val());
            const minQty = Utils.getMinQty($input);
            const stock = Utils.getStock($input);
            const preordered = Utils.isPreordered($input);

            // Ensure at least minQty
            if (isNaN(val) || val < minQty) {
                val = minQty;
            }

            // Ensure not exceeding stock (if not preordered)
            if (!preordered && stock > 0 && val > stock) {
                val = stock;
                Utils.toast(window.QTY_MESSAGES?.stockLimit || 'Stock limit: ' + stock);
            }

            $input.val(val);
            return val;
        }
    };

    // ========================================
    // Event Handlers (Delegated)
    // ========================================
    function init() {
        // Plus buttons - delegated (skip if inside .m-cart or has .no-qty-control)
        $(document).on('click', CONFIG.selectors.plus.join(', '), function(e) {
            // Skip if inside cart page (has its own handlers) or marked as no-qty-control
            if ($(this).closest('.m-cart').length || $(this).hasClass('no-qty-control')) {
                return;
            }
            e.preventDefault();
            QtyController.increase($(this));
        });

        // Minus buttons - delegated (skip if inside .m-cart or has .no-qty-control)
        $(document).on('click', CONFIG.selectors.minus.join(', '), function(e) {
            // Skip if inside cart page (has its own handlers) or marked as no-qty-control
            if ($(this).closest('.m-cart').length || $(this).hasClass('no-qty-control')) {
                return;
            }
            e.preventDefault();
            QtyController.decrease($(this));
        });

        // Input validation on blur (skip if inside .m-cart)
        $(document).on('blur', CONFIG.selectors.input.join(', '), function() {
            if ($(this).closest('.m-cart').length) {
                return;
            }
            QtyController.validate($(this));
        });

        // Prevent non-numeric input
        $(document).on('keypress', CONFIG.selectors.input.join(', '), function(e) {
            if (this.value.length === 0 && e.which === 48) {
                return false; // Prevent leading zero
            }
            if (e.which !== 8 && e.which !== 32 && isNaN(String.fromCharCode(e.which))) {
                e.preventDefault();
            }
        });

        console.log('[QtyControl] Centralized quantity control initialized');
    }

    // ========================================
    // Initialization
    // ========================================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // ========================================
    // Global API
    // ========================================
    window.QtyControl = {
        increase: function(btnOrInput) {
            return QtyController.increase($(btnOrInput));
        },
        decrease: function(btnOrInput) {
            return QtyController.decrease($(btnOrInput));
        },
        validate: function(input) {
            return QtyController.validate($(input));
        },
        findInput: function(btn) {
            return Utils.findInput($(btn));
        }
    };

})(jQuery);
