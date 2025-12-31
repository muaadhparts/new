<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\DiscountCode;
use App\Models\Currency;
use App\Models\Shipping;
use App\Models\Package;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * ============================================================================
 * CHECKOUT PRICE SERVICE - CENTRALIZED PRICE CALCULATION
 * ============================================================================
 *
 * This service provides a SINGLE SOURCE OF TRUTH for all checkout price calculations.
 * All checkout steps MUST use this service to ensure consistent pricing.
 *
 * Price Structure:
 * ================
 * products_total      = Sum of all product prices (NEVER changes)
 * discount_amount     = Discount amount from discount code
 * subtotal            = products_total - discount_amount
 * tax_amount          = subtotal * tax_rate / 100
 * shipping_cost       = Selected shipping price (after free_above check)
 * packing_cost        = Selected packaging price
 * grand_total         = subtotal + tax_amount + shipping_cost + packing_cost
 *
 * Currency Conversion:
 * ====================
 * All prices stored/calculated in SAR (base currency)
 * Conversion happens ONCE here before passing to views
 * Views only format (number_format + sign), never convert
 *
 * Data Flow:
 * ==========
 * Step1: products_total, tax_rate, tax_amount
 * Step2: + shipping_cost, packing_cost, grand_total
 * Step3: Read-only display from step2 session
 *
 * ============================================================================
 */
class CheckoutPriceService
{
    protected $currency;
    protected $currencyValue;
    protected $currencySign;
    protected $currencyFormat;
    protected $vendorId;
    protected $isVendorCheckout;

    public function __construct()
    {
        $this->loadCurrency();
        $this->loadSettings();
    }

    /**
     * Load currency from session or default
     */
    protected function loadCurrency(): void
    {
        if (Session::has('currency')) {
            $this->currency = Currency::find(Session::get('currency'));
        }

        if (!$this->currency) {
            $this->currency = Currency::where('is_default', 1)->first();
        }

        $this->currencyValue = $this->currency->value ?? 1;
        $this->currencySign = $this->currency->sign ?? 'SAR';
    }

    /**
     * Load general settings for currency format
     */
    protected function loadSettings(): void
    {
        $gs = cache()->remember('muaadhsettings', now()->addDay(), function () {
            return DB::table('muaadhsettings')->first();
        });
        $this->currencyFormat = $gs->currency_format ?? 0;
    }

    // ========================================================================
    // CURRENCY CONVERSION METHODS
    // ========================================================================

    /**
     * Get current currency object
     */
    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    /**
     * Get currency value (conversion rate)
     */
    public function getCurrencyValue(): float
    {
        return (float) $this->currencyValue;
    }

    /**
     * Get currency sign
     */
    public function getCurrencySign(): string
    {
        return $this->currencySign;
    }

    /**
     * Get currency format (0 = sign before, 1 = sign after)
     */
    public function getCurrencyFormat(): int
    {
        return (int) $this->currencyFormat;
    }

    /**
     * Convert price from SAR to selected currency
     */
    public function convert(float $amount): float
    {
        return round($amount * $this->currencyValue, 2);
    }

    /**
     * Format price with currency sign (no conversion - expects already converted value)
     */
    public function formatPrice(float $amount): string
    {
        $formatted = number_format($amount, 2);
        return $this->currencyFormat == 0
            ? $this->currencySign . $formatted
            : $formatted . $this->currencySign;
    }

    /**
     * Convert AND format price in one call
     */
    public function convertAndFormat(float $amount): string
    {
        return $this->formatPrice($this->convert($amount));
    }

    /**
     * Set vendor context for vendor-specific checkout
     */
    public function forVendor($vendorId)
    {
        $this->vendorId = $vendorId;
        $this->isVendorCheckout = true;
        return $this;
    }

    /**
     * Calculate products total for a vendor
     * This is the RAW total without any discounts
     */
    public function calculateProductsTotal($vendorId = null)
    {
        $cart = Session::get('cart');
        if (!$cart || empty($cart->items)) {
            return 0;
        }

        $total = 0;
        foreach ($cart->items as $item) {
            $itemVendorId = $this->getItemVendorId($item);

            // If vendor specified, only count that vendor's products
            if ($vendorId !== null && $itemVendorId != $vendorId) {
                continue;
            }

            $total += (float)($item['price'] ?? 0);
        }

        return round($total, 2);
    }

    /**
     * Get discount code amount
     */
    public function getDiscountAmount($vendorId = null)
    {
        if ($vendorId) {
            return (float)Session::get('discount_code_vendor_' . $vendorId, 0);
        }
        return (float)Session::get('discount_code', 0);
    }

    /**
     * Get discount code data
     */
    public function getDiscountCodeData($vendorId = null)
    {
        if ($vendorId) {
            return [
                'amount' => (float)Session::get('discount_code_vendor_' . $vendorId, 0),
                'code' => Session::get('discount_code_value_vendor_' . $vendorId, ''),
                'id' => Session::get('discount_code_id_vendor_' . $vendorId),
                'percentage' => Session::get('discount_percentage_vendor_' . $vendorId, ''),
            ];
        }
        return [
            'amount' => (float)Session::get('discount_code', 0),
            'code' => Session::get('discount_code_value', ''),
            'id' => Session::get('discount_code_id'),
            'percentage' => Session::get('discount_percentage', ''),
        ];
    }

    /**
     * Calculate tax amount
     * Tax is calculated on subtotal (products - discount)
     */
    public function calculateTax($subtotal, $taxRate)
    {
        if ($taxRate <= 0) {
            return 0;
        }
        return round(($subtotal * $taxRate) / 100, 2);
    }

    /**
     * Calculate shipping cost with free_above logic
     */
    public function calculateShippingCost($shippingId, $productsTotal)
    {
        if (!$shippingId) {
            return [
                'cost' => 0,
                'original_cost' => 0,
                'is_free' => false,
                'free_discount' => 0,
                'company' => '',
            ];
        }

        $shipping = Shipping::find($shippingId);
        if (!$shipping) {
            return [
                'cost' => 0,
                'original_cost' => 0,
                'is_free' => false,
                'free_discount' => 0,
                'company' => '',
            ];
        }

        $originalCost = (float)$shipping->price;
        $freeAbove = (float)($shipping->free_above ?? 0);
        $isFree = $freeAbove > 0 && $productsTotal >= $freeAbove;

        return [
            'cost' => $isFree ? 0 : $originalCost,
            'original_cost' => $originalCost,
            'is_free' => $isFree,
            'free_discount' => $isFree ? $originalCost : 0,
            'company' => $shipping->title,
        ];
    }

    /**
     * Calculate packing cost
     */
    public function calculatePackingCost($packingId)
    {
        if (!$packingId) {
            return [
                'cost' => 0,
                'company' => '',
            ];
        }

        $package = Package::find($packingId);
        if (!$package) {
            return [
                'cost' => 0,
                'company' => '',
            ];
        }

        return [
            'cost' => (float)$package->price,
            'company' => $package->title,
        ];
    }

    /**
     * ========================================================================
     * MASTER CALCULATION - Step 1
     * ========================================================================
     * Returns all data needed for Step 1 display and session storage
     */
    public function calculateStep1($vendorId, $taxRate = 0, $taxLocation = '')
    {
        // 1. Products Total (RAW - never changes)
        $productsTotal = $this->calculateProductsTotal($vendorId);

        // 2. Discount Code
        $discountData = $this->getDiscountCodeData($vendorId);
        $discountAmount = $discountData['amount'];

        // 3. Subtotal (products - discount)
        $subtotal = $productsTotal - $discountAmount;

        // 4. Tax (on subtotal)
        $taxAmount = $this->calculateTax($subtotal, $taxRate);

        // 5. Total with tax (for display)
        $totalWithTax = $subtotal + $taxAmount;

        return [
            // Core values
            'products_total' => $productsTotal,        // RAW products total
            'discount_amount' => $discountAmount,      // Discount code amount
            'discount_code' => $discountData['code'],
            'discount_code_id' => $discountData['id'],
            'discount_percentage' => $discountData['percentage'],
            'subtotal' => $subtotal,                   // After discount
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'tax_location' => $taxLocation,
            'total_with_tax' => $totalWithTax,         // Subtotal + Tax

            // For backward compatibility
            'vendor_subtotal' => $productsTotal,
        ];
    }

    /**
     * ========================================================================
     * MASTER CALCULATION - Step 2
     * ========================================================================
     * Returns all data needed for Step 2 display and session storage
     */
    public function calculateStep2($vendorId, $step1Data, $shippingData, $packingId)
    {
        // Get step1 values
        $productsTotal = $step1Data['products_total'] ?? 0;
        $discountAmount = $step1Data['discount_amount'] ?? 0;
        $subtotal = $step1Data['subtotal'] ?? ($productsTotal - $discountAmount);
        $taxRate = $step1Data['tax_rate'] ?? 0;
        $taxAmount = $step1Data['tax_amount'] ?? 0;
        $taxLocation = $step1Data['tax_location'] ?? '';

        // Calculate shipping
        $shippingResult = $this->processShippingData($shippingData, $productsTotal, $vendorId);

        // Calculate packing
        $packingResult = $this->calculatePackingCost($packingId);

        // Grand Total = subtotal + tax + shipping + packing
        $grandTotal = $subtotal + $taxAmount + $shippingResult['cost'] + $packingResult['cost'];

        // Subtotal before discount (for discount code operations in step3)
        $subtotalBeforeDiscount = $productsTotal + $taxAmount + $shippingResult['cost'] + $packingResult['cost'];

        return [
            // From Step 1
            'products_total' => $productsTotal,
            'discount_amount' => $discountAmount,
            'discount_code' => $step1Data['discount_code'] ?? '',
            'discount_code_id' => $step1Data['discount_code_id'] ?? null,
            'discount_percentage' => $step1Data['discount_percentage'] ?? '',
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'tax_location' => $taxLocation,

            // Shipping
            'shipping_cost' => $shippingResult['cost'],
            'original_shipping_cost' => $shippingResult['original_cost'],
            'is_free_shipping' => $shippingResult['is_free'],
            'free_shipping_discount' => $shippingResult['free_discount'],
            'shipping_company' => $shippingResult['company'],

            // Packing
            'packing_cost' => $packingResult['cost'],
            'packing_company' => $packingResult['company'],

            // Totals
            'subtotal_before_discount' => $subtotalBeforeDiscount,  // For discount recalculation
            'grand_total' => $grandTotal,                           // Final amount
            'total' => $grandTotal,                                 // Backward compatibility
            'final_total' => $grandTotal,                           // Alias
        ];
    }

    /**
     * Process shipping data (handles both array and single ID formats)
     */
    protected function processShippingData($shippingData, $productsTotal, $vendorId)
    {
        $totalCost = 0;
        $totalOriginal = 0;
        $isFree = false;
        $freeDiscount = 0;
        $companies = [];

        // Handle array format: shipping[vendor_id] = shipping_id or tryoto format
        if (is_array($shippingData)) {
            foreach ($shippingData as $vid => $val) {
                if (is_string($val) && strpos($val, '#') !== false) {
                    // Tryoto format: id#company#price#...
                    $parts = explode('#', $val);
                    $company = $parts[1] ?? '';
                    $price = (float)($parts[2] ?? 0);
                    $totalOriginal += $price;

                    // Check free_above for Tryoto
                    $vendorTryoto = Shipping::where('user_id', $vid)
                        ->where('provider', 'tryoto')
                        ->first();
                    $freeAbove = $vendorTryoto ? (float)$vendorTryoto->free_above : 0;

                    if ($freeAbove > 0 && $productsTotal >= $freeAbove) {
                        $isFree = true;
                        $companies[] = $company . ' (' . __('Free Shipping') . ')';
                    } else {
                        $totalCost += $price;
                        if ($company) $companies[] = $company;
                    }
                } else {
                    // Regular shipping ID
                    $result = $this->calculateShippingCost((int)$val, $productsTotal);
                    $totalOriginal += $result['original_cost'];
                    $totalCost += $result['cost'];
                    if ($result['is_free']) $isFree = true;
                    if ($result['company']) $companies[] = $result['company'];
                }
            }
        } elseif (is_numeric($shippingData)) {
            // Single shipping ID
            $result = $this->calculateShippingCost((int)$shippingData, $productsTotal);
            $totalOriginal = $result['original_cost'];
            $totalCost = $result['cost'];
            $isFree = $result['is_free'];
            $companies[] = $result['company'];
        }

        $freeDiscount = $totalOriginal - $totalCost;

        return [
            'cost' => round($totalCost, 2),
            'original_cost' => round($totalOriginal, 2),
            'is_free' => $isFree,
            'free_discount' => round($freeDiscount, 2),
            'company' => implode(' + ', array_filter($companies)),
        ];
    }

    /**
     * Extract vendor ID from cart item
     */
    protected function getItemVendorId($item)
    {
        if (isset($item['user_id'])) {
            return (int)$item['user_id'];
        }
        if (isset($item['item'])) {
            $itemData = $item['item'];
            if (is_object($itemData)) {
                return (int)($itemData->user_id ?? $itemData->vendor_user_id ?? 0);
            }
            if (is_array($itemData)) {
                return (int)($itemData['user_id'] ?? $itemData['vendor_user_id'] ?? 0);
            }
        }
        return 0;
    }

    /**
     * Get price breakdown for display (used by checkout-price-summary component)
     */
    public function getPriceBreakdown($step, $step1Data = null, $step2Data = null, $vendorId = null)
    {
        $productsTotal = 0;
        $discountAmount = 0;
        $discountCode = '';
        $discountPercentage = '';
        $taxRate = 0;
        $taxAmount = 0;
        $taxLocation = '';
        $shippingCost = 0;
        $originalShippingCost = 0;
        $isFreeShipping = false;
        $freeShippingDiscount = 0;
        $shippingCompany = '';
        $packingCost = 0;
        $packingCompany = '';
        $grandTotal = 0;
        $subtotalBeforeDiscount = 0;

        if ($step == 1) {
            // Step 1: Only products and tax (calculated dynamically)
            $productsTotal = $this->calculateProductsTotal($vendorId);
            $discountData = $this->getDiscountCodeData($vendorId);
            $discountAmount = $discountData['amount'];
            $discountCode = $discountData['code'];
            $discountPercentage = $discountData['percentage'];
            $grandTotal = $productsTotal - $discountAmount;
            $subtotalBeforeDiscount = $productsTotal;

        } elseif ($step == 2) {
            // Step 2: Products + Tax from step1, shipping/packing dynamic
            if ($step1Data) {
                $productsTotal = $step1Data->products_total ?? 0;
                $taxRate = $step1Data->tax_rate ?? 0;
                $taxAmount = $step1Data->tax_amount ?? 0;
                $taxLocation = $step1Data->tax_location ?? '';
            }
            $discountData = $this->getDiscountCodeData($vendorId);
            $discountAmount = $discountData['amount'];
            $discountCode = $discountData['code'];
            $discountPercentage = $discountData['percentage'];

            $subtotal = $productsTotal - $discountAmount;
            $grandTotal = $subtotal + $taxAmount; // Shipping/packing added via JS
            $subtotalBeforeDiscount = $productsTotal + $taxAmount;

        } elseif ($step == 3) {
            // Step 3: Everything from step2 session (read-only)
            if ($step2Data) {
                $productsTotal = $step2Data->products_total ?? ($step1Data->products_total ?? 0);
                $discountAmount = $step2Data->discount_amount ?? 0;
                $discountCode = $step2Data->discount_code ?? '';
                $discountPercentage = $step2Data->discount_percentage ?? '';
                $taxRate = $step2Data->tax_rate ?? 0;
                $taxAmount = $step2Data->tax_amount ?? 0;
                $taxLocation = $step2Data->tax_location ?? '';
                $shippingCost = $step2Data->shipping_cost ?? 0;
                $originalShippingCost = $step2Data->original_shipping_cost ?? $shippingCost;
                $isFreeShipping = $step2Data->is_free_shipping ?? false;
                $freeShippingDiscount = $step2Data->free_shipping_discount ?? 0;
                $shippingCompany = $step2Data->shipping_company ?? '';
                $packingCost = $step2Data->packing_cost ?? 0;
                $packingCompany = $step2Data->packing_company ?? '';
                $grandTotal = $step2Data->grand_total ?? $step2Data->total ?? 0;
                $subtotalBeforeDiscount = $step2Data->subtotal_before_discount ?? 0;
            }
        }

        return [
            'products_total' => round($productsTotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'discount_code' => $discountCode,
            'discount_percentage' => $discountPercentage,
            'has_discount' => $discountAmount > 0,
            'tax_rate' => $taxRate,
            'tax_amount' => round($taxAmount, 2),
            'tax_location' => $taxLocation,
            'shipping_cost' => round($shippingCost, 2),
            'original_shipping_cost' => round($originalShippingCost, 2),
            'is_free_shipping' => $isFreeShipping,
            'free_shipping_discount' => round($freeShippingDiscount, 2),
            'shipping_company' => $shippingCompany,
            'packing_cost' => round($packingCost, 2),
            'packing_company' => $packingCompany,
            'grand_total' => round($grandTotal, 2),
            'subtotal_before_discount' => round($subtotalBeforeDiscount, 2),
        ];
    }

    /**
     * ========================================================================
     * GET CONVERTED PRICE BREAKDOWN - FOR VIEWS
     * ========================================================================
     *
     * Returns price breakdown with ALL monetary values converted to selected currency.
     * This is the SINGLE POINT of currency conversion for checkout views.
     *
     * Views should ONLY format (number_format + sign), NEVER convert.
     */
    public function getConvertedPriceBreakdown($step, $step1Data = null, $step2Data = null, $vendorId = null): array
    {
        // Get raw SAR values
        $raw = $this->getPriceBreakdown($step, $step1Data, $step2Data, $vendorId);

        // Convert all monetary values
        return [
            // Converted values (for display)
            'products_total' => $this->convert($raw['products_total']),
            'discount_amount' => $this->convert($raw['discount_amount']),
            'tax_amount' => $this->convert($raw['tax_amount']),
            'shipping_cost' => $this->convert($raw['shipping_cost']),
            'original_shipping_cost' => $this->convert($raw['original_shipping_cost']),
            'free_shipping_discount' => $this->convert($raw['free_shipping_discount']),
            'packing_cost' => $this->convert($raw['packing_cost']),
            'grand_total' => $this->convert($raw['grand_total']),
            'subtotal_before_discount' => $this->convert($raw['subtotal_before_discount']),

            // Non-monetary values (pass through)
            'discount_code' => $raw['discount_code'],
            'discount_percentage' => $raw['discount_percentage'],
            'has_discount' => $raw['has_discount'],
            'tax_rate' => $raw['tax_rate'],
            'tax_location' => $raw['tax_location'],
            'is_free_shipping' => $raw['is_free_shipping'],
            'shipping_company' => $raw['shipping_company'],
            'packing_company' => $raw['packing_company'],

            // Currency info (for JS formatting)
            'currency_sign' => $this->currencySign,
            'currency_format' => $this->currencyFormat,
            'currency_value' => $this->currencyValue,
        ];
    }

    /**
     * Get formatted prices ready for display (converted + formatted)
     */
    public function getFormattedPriceBreakdown($step, $step1Data = null, $step2Data = null, $vendorId = null): array
    {
        $converted = $this->getConvertedPriceBreakdown($step, $step1Data, $step2Data, $vendorId);

        return [
            // Formatted strings (ready for display)
            'products_total_formatted' => $this->formatPrice($converted['products_total']),
            'discount_amount_formatted' => $this->formatPrice($converted['discount_amount']),
            'tax_amount_formatted' => $this->formatPrice($converted['tax_amount']),
            'shipping_cost_formatted' => $this->formatPrice($converted['shipping_cost']),
            'original_shipping_cost_formatted' => $this->formatPrice($converted['original_shipping_cost']),
            'free_shipping_discount_formatted' => $this->formatPrice($converted['free_shipping_discount']),
            'packing_cost_formatted' => $this->formatPrice($converted['packing_cost']),
            'grand_total_formatted' => $this->formatPrice($converted['grand_total']),

            // Include raw converted values for JS calculations
            ...$converted,
        ];
    }
}
