<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\DiscountCode;
use App\Models\MonetaryUnit;
use App\Models\Shipping;
use App\Models\Package;
use App\Models\MerchantCommission;
use App\Models\MerchantTaxSetting;
use App\Models\CourierServiceArea;
use App\Models\MerchantLocation;
use App\Services\MonetaryUnitService;
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
 * catalog_items_total = Sum of all catalogItem prices (NEVER changes)
 * discount_amount     = Discount amount from discount code
 * subtotal            = catalog_items_total - discount_amount
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
 * Step1: catalog_items_total, tax_rate, tax_amount
 * Step2: + shipping_cost, packing_cost, grand_total
 * Step3: Read-only display from step2 session
 *
 * ============================================================================
 */
class CheckoutPriceService
{
    protected $monetaryUnit;
    protected $currencyValue;
    protected $currencySign;
    protected $currencyFormat;
    protected $merchantId;
    protected $isMerchantCheckout;

    public function __construct()
    {
        $this->loadMonetaryUnit();
        $this->loadSettings();
    }

    /**
     * Load currency from MonetaryUnitService (SINGLE SOURCE OF TRUTH)
     */
    protected function loadMonetaryUnit(): void
    {
        // Use centralized MonetaryUnitService
        $service = app(MonetaryUnitService::class);

        $this->monetaryUnit = $service->getCurrent();
        $this->currencyValue = $service->getValue();
        $this->currencySign = $service->getSign();
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
    public function getMonetaryUnit(): ?MonetaryUnit
    {
        return $this->monetaryUnit;
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
     * Set merchant context for merchant-specific checkout
     */
    public function forMerchant($merchantId)
    {
        $this->merchantId = $merchantId;
        $this->isMerchantCheckout = true;
        return $this;
    }

    /**
     * Calculate catalogItems total for a merchant
     * This is the RAW total without any discounts
     */
    public function calculateCatalogItemsTotal($merchantId = null)
    {
        $cart = Session::get('cart');
        if (!$cart || empty($cart->items)) {
            return 0;
        }

        $total = 0;
        foreach ($cart->items as $item) {
            $itemMerchantId = $this->getItemMerchantId($item);

            // If merchant specified, only count that merchant's catalogItems
            if ($merchantId !== null && $itemMerchantId != $merchantId) {
                continue;
            }

            $total += (float)($item['price'] ?? 0);
        }

        return round($total, 2);
    }

    /**
     * Get discount code amount
     */
    public function getDiscountAmount($merchantId = null)
    {
        if ($merchantId) {
            return (float)Session::get('discount_code_merchant_' . $merchantId, 0);
        }
        return (float)Session::get('discount_code', 0);
    }

    /**
     * Get discount code data
     */
    public function getDiscountCodeData($merchantId = null)
    {
        if ($merchantId) {
            return [
                'amount' => (float)Session::get('discount_code_merchant_' . $merchantId, 0),
                'code' => Session::get('discount_code_value_merchant_' . $merchantId, ''),
                'id' => Session::get('discount_code_id_merchant_' . $merchantId),
                'percentage' => Session::get('discount_percentage_merchant_' . $merchantId, ''),
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
     * Tax is calculated on subtotal (catalogItems - discount)
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
    public function calculateShippingCost($shippingId, $catalogItemsTotal)
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
        $isFree = $freeAbove > 0 && $catalogItemsTotal >= $freeAbove;

        return [
            'cost' => $isFree ? 0 : $originalCost,
            'original_cost' => $originalCost,
            'is_free' => $isFree,
            'free_discount' => $isFree ? $originalCost : 0,
            'company' => $shipping->name,
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
            'company' => $package->name,
        ];
    }

    /**
     * ========================================================================
     * MASTER CALCULATION - Step 1
     * ========================================================================
     * Returns all data needed for Step 1 display and session storage
     */
    public function calculateStep1($merchantId, $taxRate = 0, $taxLocation = '')
    {
        // 1. CatalogItems Total (RAW - never changes)
        $catalogItemsTotal = $this->calculateCatalogItemsTotal($merchantId);

        // 2. Discount Code
        $discountData = $this->getDiscountCodeData($merchantId);
        $discountAmount = $discountData['amount'];

        // 3. Subtotal (catalogItems - discount)
        $subtotal = $catalogItemsTotal - $discountAmount;

        // 4. Tax (on subtotal)
        $taxAmount = $this->calculateTax($subtotal, $taxRate);

        // 5. Total with tax (for display)
        $totalWithTax = $subtotal + $taxAmount;

        return [
            // Core values
            'catalog_items_total' => $catalogItemsTotal,  // RAW catalogItems total
            'discount_amount' => $discountAmount,         // Discount code amount
            'discount_code' => $discountData['code'],
            'discount_code_id' => $discountData['id'],
            'discount_percentage' => $discountData['percentage'],
            'subtotal' => $subtotal,                      // After discount
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'tax_location' => $taxLocation,
            'total_with_tax' => $totalWithTax,            // Subtotal + Tax

            // For backward compatibility
            'merchant_subtotal' => $catalogItemsTotal,
        ];
    }

    /**
     * ========================================================================
     * MASTER CALCULATION - Step 2
     * ========================================================================
     * Returns all data needed for Step 2 display and session storage
     */
    public function calculateStep2($merchantId, $step1Data, $shippingData, $packingId)
    {
        // Get step1 values
        $catalogItemsTotal = $step1Data['catalog_items_total'] ?? 0;
        $discountAmount = $step1Data['discount_amount'] ?? 0;
        $subtotal = $step1Data['subtotal'] ?? ($catalogItemsTotal - $discountAmount);
        $taxRate = $step1Data['tax_rate'] ?? 0;
        $taxAmount = $step1Data['tax_amount'] ?? 0;
        $taxLocation = $step1Data['tax_location'] ?? '';

        // Calculate shipping
        $shippingResult = $this->processShippingData($shippingData, $catalogItemsTotal, $merchantId);

        // Calculate packing
        $packingResult = $this->calculatePackingCost($packingId);

        // Grand Total = subtotal + tax + shipping + packing
        $grandTotal = $subtotal + $taxAmount + $shippingResult['cost'] + $packingResult['cost'];

        // Subtotal before discount (for discount code operations in step3)
        $subtotalBeforeDiscount = $catalogItemsTotal + $taxAmount + $shippingResult['cost'] + $packingResult['cost'];

        return [
            // From Step 1
            'catalog_items_total' => $catalogItemsTotal,
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
    protected function processShippingData($shippingData, $catalogItemsTotal, $merchantId)
    {
        $totalCost = 0;
        $totalOriginal = 0;
        $isFree = false;
        $freeDiscount = 0;
        $companies = [];

        // Handle array format: shipping[merchant_id] = shipping_id or tryoto format
        if (is_array($shippingData)) {
            foreach ($shippingData as $mid => $val) {
                if (is_string($val) && strpos($val, '#') !== false) {
                    // Tryoto format: id#company#price#...
                    $parts = explode('#', $val);
                    $company = $parts[1] ?? '';
                    $price = (float)($parts[2] ?? 0);
                    $totalOriginal += $price;

                    // Check free_above for Tryoto
                    $merchantTryoto = Shipping::where('user_id', $mid)
                        ->where('provider', 'tryoto')
                        ->first();
                    $freeAbove = $merchantTryoto ? (float)$merchantTryoto->free_above : 0;

                    if ($freeAbove > 0 && $catalogItemsTotal >= $freeAbove) {
                        $isFree = true;
                        $companies[] = $company . ' (' . __('Free Shipping') . ')';
                    } else {
                        $totalCost += $price;
                        if ($company) $companies[] = $company;
                    }
                } else {
                    // Regular shipping ID
                    $result = $this->calculateShippingCost((int)$val, $catalogItemsTotal);
                    $totalOriginal += $result['original_cost'];
                    $totalCost += $result['cost'];
                    if ($result['is_free']) $isFree = true;
                    if ($result['company']) $companies[] = $result['company'];
                }
            }
        } elseif (is_numeric($shippingData)) {
            // Single shipping ID
            $result = $this->calculateShippingCost((int)$shippingData, $catalogItemsTotal);
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
     * Extract merchant ID from cart item
     */
    protected function getItemMerchantId($item)
    {
        if (isset($item['user_id'])) {
            return (int)$item['user_id'];
        }
        if (isset($item['item'])) {
            $itemData = $item['item'];
            if (is_object($itemData)) {
                return (int)($itemData->user_id ?? $itemData->merchant_user_id ?? 0);
            }
            if (is_array($itemData)) {
                return (int)($itemData['user_id'] ?? $itemData['merchant_user_id'] ?? 0);
            }
        }
        return 0;
    }

    /**
     * Get price breakdown for display (used by checkout-price-summary component)
     */
    public function getPriceBreakdown($step, $step1Data = null, $step2Data = null, $merchantId = null)
    {
        $catalogItemsTotal = 0;
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
        // Courier delivery fields
        $deliveryType = 'shipping';
        $courierId = null;
        $courierName = '';
        $courierFee = 0;

        if ($step == 1) {
            // Step 1: Only catalogItems and tax (calculated dynamically)
            $catalogItemsTotal = $this->calculateCatalogItemsTotal($merchantId);
            $discountData = $this->getDiscountCodeData($merchantId);
            $discountAmount = $discountData['amount'];
            $discountCode = $discountData['code'];
            $discountPercentage = $discountData['percentage'];
            $grandTotal = $catalogItemsTotal - $discountAmount;
            $subtotalBeforeDiscount = $catalogItemsTotal;

        } elseif ($step == 2) {
            // Step 2: CatalogItems + Tax from step1, shipping/packing dynamic
            if ($step1Data) {
                $catalogItemsTotal = $step1Data->catalog_items_total ?? 0;
                $taxRate = $step1Data->tax_rate ?? 0;
                $taxAmount = $step1Data->tax_amount ?? 0;
                $taxLocation = $step1Data->tax_location ?? '';
            }
            $discountData = $this->getDiscountCodeData($merchantId);
            $discountAmount = $discountData['amount'];
            $discountCode = $discountData['code'];
            $discountPercentage = $discountData['percentage'];

            $subtotal = $catalogItemsTotal - $discountAmount;
            $grandTotal = $subtotal + $taxAmount; // Shipping/packing added via JS
            $subtotalBeforeDiscount = $catalogItemsTotal + $taxAmount;

        } elseif ($step == 3) {
            // Step 3: Everything from step2 session (read-only)
            if ($step2Data) {
                $catalogItemsTotal = $step2Data->catalog_items_total ?? ($step1Data->catalog_items_total ?? 0);
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
                // Courier delivery fields
                $deliveryType = $step2Data->delivery_type ?? 'shipping';
                $courierId = $step2Data->courier_id ?? null;
                $courierName = $step2Data->courier_name ?? '';
                $courierFee = $step2Data->courier_fee ?? 0;
            }
        }

        return [
            'catalog_items_total' => round($catalogItemsTotal, 2),
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
            // Courier delivery fields
            'delivery_type' => $deliveryType,
            'courier_id' => $courierId,
            'courier_name' => $courierName,
            'courier_fee' => round($courierFee, 2),
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
    public function getConvertedPriceBreakdown($step, $step1Data = null, $step2Data = null, $merchantId = null): array
    {
        // Get raw SAR values
        $raw = $this->getPriceBreakdown($step, $step1Data, $step2Data, $merchantId);

        // Convert all monetary values
        return [
            // Converted values (for display)
            'catalog_items_total' => $this->convert($raw['catalog_items_total']),
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

            // Courier delivery fields
            'delivery_type' => $raw['delivery_type'],
            'courier_id' => $raw['courier_id'],
            'courier_name' => $raw['courier_name'],
            'courier_fee' => $this->convert($raw['courier_fee']),

            // Currency info (for JS formatting)
            'currency_sign' => $this->currencySign,
            'currency_format' => $this->currencyFormat,
            'currency_value' => $this->currencyValue,
        ];
    }

    /**
     * Get formatted prices ready for display (converted + formatted)
     */
    public function getFormattedPriceBreakdown($step, $step1Data = null, $step2Data = null, $merchantId = null): array
    {
        $converted = $this->getConvertedPriceBreakdown($step, $step1Data, $step2Data, $merchantId);

        return [
            // Formatted strings (ready for display)
            'catalog_items_total_formatted' => $this->formatPrice($converted['catalog_items_total']),
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

    // ========================================================================
    // MERCHANT COMMISSION & TAX METHODS
    // ========================================================================

    /**
     * Calculate merchant commission for a given amount
     * Commission goes to the platform
     */
    public function calculateMerchantCommission(int $merchantId, float $amount): array
    {
        $commission = MerchantCommission::where('user_id', $merchantId)->first();

        if (!$commission || !$commission->is_active) {
            return [
                'commission_amount' => 0,
                'commission_type' => null,
                'commission_rate' => 0,
                'commission_fixed' => 0,
            ];
        }

        $commissionAmount = $commission->calculateCommission($amount);

        return [
            'commission_amount' => round($commissionAmount, 2),
            'commission_type' => $commission->commission_type,
            'commission_rate' => (float)$commission->commission_rate,
            'commission_fixed' => (float)$commission->commission_fixed,
        ];
    }

    /**
     * Calculate merchant-specific tax
     * Each merchant can have their own tax settings
     */
    public function calculateMerchantTax(int $merchantId, float $amount): array
    {
        $taxSetting = MerchantTaxSetting::where('user_id', $merchantId)->first();

        if (!$taxSetting || !$taxSetting->is_active) {
            return [
                'tax_amount' => 0,
                'tax_rate' => 0,
                'tax_name' => '',
                'tax_number' => '',
            ];
        }

        $taxAmount = $taxSetting->calculateTax($amount);

        return [
            'tax_amount' => round($taxAmount, 2),
            'tax_rate' => (float)$taxSetting->tax_rate,
            'tax_name' => $taxSetting->tax_name ?? 'VAT',
            'tax_number' => $taxSetting->tax_number ?? '',
        ];
    }

    /**
     * Get merchant tax setting
     */
    public function getMerchantTaxSetting(int $merchantId): ?MerchantTaxSetting
    {
        return MerchantTaxSetting::where('user_id', $merchantId)->first();
    }

    /**
     * Get merchant commission setting
     */
    public function getMerchantCommissionSetting(int $merchantId): ?MerchantCommission
    {
        return MerchantCommission::where('user_id', $merchantId)->first();
    }

    // ========================================================================
    // COURIER / DELIVERY METHODS
    // ========================================================================

    /**
     * Check if courier delivery is available for a city
     * Checks if merchant has a warehouse location in that city AND couriers serve that city
     */
    public function canDeliverToCity(int $merchantId, int $customerCityId): bool
    {
        // Check if merchant has a warehouse location in customer's city
        $merchantHasLocationInCity = MerchantLocation::where('user_id', $merchantId)
            ->where('city_id', $customerCityId)
            ->where('status', 1)
            ->exists();

        if (!$merchantHasLocationInCity) {
            return false;
        }

        // Check if there are couriers serving this city
        $couriersAvailable = CourierServiceArea::where('city_id', $customerCityId)
            ->whereHas('courier', function ($query) {
                $query->where('status', 1);
            })
            ->exists();

        return $couriersAvailable;
    }

    /**
     * Get available couriers for a city with their prices
     */
    public function getAvailableCouriersForCity(int $cityId): array
    {
        $serviceAreas = CourierServiceArea::where('city_id', $cityId)
            ->with('courier')
            ->whereHas('courier', function ($query) {
                $query->where('status', 1);
            })
            ->get();

        $couriers = [];
        foreach ($serviceAreas as $area) {
            $couriers[] = [
                'courier_id' => $area->courier_id,
                'courier_name' => $area->courier->name,
                'delivery_fee' => (float)$area->price,
                'service_area_id' => $area->id,
            ];
        }

        return $couriers;
    }

    /**
     * Get courier delivery fee for a specific city
     */
    public function getCourierDeliveryFee(int $courierId, int $cityId): float
    {
        $serviceArea = CourierServiceArea::where('courier_id', $courierId)
            ->where('city_id', $cityId)
            ->first();

        return $serviceArea ? (float)$serviceArea->price : 0;
    }

    /**
     * Get merchant warehouse locations in a specific city
     */
    public function getMerchantLocationsInCity(int $merchantId, int $cityId): \Illuminate\Database\Eloquent\Collection
    {
        return MerchantLocation::where('user_id', $merchantId)
            ->where('city_id', $cityId)
            ->where('status', 1)
            ->get();
    }

    /**
     * Get all merchant warehouse locations
     */
    public function getMerchantLocations(int $merchantId): \Illuminate\Database\Eloquent\Collection
    {
        return MerchantLocation::where('user_id', $merchantId)
            ->where('status', 1)
            ->get();
    }

    // ========================================================================
    // MERCHANT PURCHASE BREAKDOWN
    // ========================================================================

    /**
     * Calculate complete merchant purchase details
     * This is used to populate the merchant_purchases table
     */
    public function calculateMerchantPurchaseDetails(int $merchantId, array $cartItems, array $options = []): array
    {
        // 1. Calculate items total for this merchant
        $itemsTotal = 0;
        foreach ($cartItems as $item) {
            $itemMerchantId = $this->getItemMerchantId($item);
            if ((int)$itemMerchantId === $merchantId) {
                $itemsTotal += (float)($item['price'] ?? 0);
            }
        }
        $itemsTotal = round($itemsTotal, 2);

        // 2. Calculate commission (platform's cut)
        $commissionData = $this->calculateMerchantCommission($merchantId, $itemsTotal);
        $commissionAmount = $commissionData['commission_amount'];

        // 3. Calculate tax (merchant-specific)
        $taxData = $this->calculateMerchantTax($merchantId, $itemsTotal);
        $taxAmount = $taxData['tax_amount'];

        // 4. Get shipping cost from options
        $shippingCost = (float)($options['shipping_cost'] ?? 0);
        $shippingType = $options['shipping_type'] ?? 'platform';
        $shippingId = $options['shipping_id'] ?? null;

        // 5. Get packing cost from options
        $packingCost = (float)($options['packing_cost'] ?? 0);

        // 6. Get courier info from options
        $courierId = $options['courier_id'] ?? null;
        $courierFee = (float)($options['courier_fee'] ?? 0);
        $merchantLocationId = $options['merchant_location_id'] ?? null;

        // 7. Determine payment type and money receiver
        $paymentType = $options['payment_type'] ?? 'platform';
        $paymentGatewayId = $options['payment_gateway_id'] ?? null;
        $moneyReceivedBy = $options['money_received_by'] ?? 'platform';

        // 8. Calculate net amount (what merchant gets after commission)
        $netAmount = $itemsTotal - $commissionAmount;

        return [
            // Basic amounts
            'items_total' => $itemsTotal,
            'price' => $itemsTotal,

            // Commission
            'commission_amount' => $commissionAmount,
            'commission_type' => $commissionData['commission_type'],
            'commission_rate' => $commissionData['commission_rate'],
            'commission_fixed' => $commissionData['commission_fixed'],

            // Tax
            'tax_amount' => $taxAmount,
            'tax_rate' => $taxData['tax_rate'],
            'tax_name' => $taxData['tax_name'],
            'tax_number' => $taxData['tax_number'],

            // Shipping
            'shipping_cost' => $shippingCost,
            'shipping_type' => $shippingType,
            'shipping_id' => $shippingId,

            // Packing
            'packing_cost' => $packingCost,

            // Courier
            'courier_id' => $courierId,
            'courier_fee' => $courierFee,
            'merchant_location_id' => $merchantLocationId,

            // Payment
            'payment_type' => $paymentType,
            'payment_gateway_id' => $paymentGatewayId,
            'money_received_by' => $moneyReceivedBy,

            // Net amount (merchant's earnings after commission)
            'net_amount' => round($netAmount, 2),

            // Grand total for this merchant (what customer pays for this merchant's items)
            'merchant_total' => round($itemsTotal + $taxAmount + $shippingCost + $packingCost + $courierFee, 2),
        ];
    }

    /**
     * Determine who receives the money based on payment gateway
     */
    public function determineMoneyReceiver(int $merchantId, ?int $paymentGatewayId): string
    {
        if (!$paymentGatewayId) {
            return 'platform';
        }

        $gateway = \App\Models\MerchantPayment::find($paymentGatewayId);
        if (!$gateway) {
            return 'platform';
        }

        // If gateway belongs to the merchant, merchant receives the money
        if ($gateway->user_id > 0 && $gateway->user_id == $merchantId) {
            return 'merchant';
        }

        return 'platform';
    }

    /**
     * Determine payment type based on payment gateway ownership
     */
    public function determinePaymentType(int $merchantId, ?int $paymentGatewayId): string
    {
        return $this->determineMoneyReceiver($merchantId, $paymentGatewayId) === 'merchant' ? 'merchant' : 'platform';
    }

    /**
     * Determine shipping type
     */
    public function determineShippingType(int $merchantId, ?int $shippingId, ?int $courierId): string
    {
        // If courier is selected, it's courier delivery
        if ($courierId) {
            return 'courier';
        }

        // If no shipping selected, default to platform shipping
        if (!$shippingId) {
            return 'platform';
        }

        $shipping = Shipping::find($shippingId);
        if (!$shipping) {
            return 'platform';
        }

        // If shipping belongs to merchant, it's merchant shipping
        if ($shipping->user_id > 0 && $shipping->user_id == $merchantId) {
            return 'merchant';
        }

        return 'platform';
    }
}
