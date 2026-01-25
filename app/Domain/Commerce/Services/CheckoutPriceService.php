<?php

namespace App\Domain\Commerce\Services;

use App\Domain\Platform\Models\MonetaryUnit;
use App\Domain\Shipping\Models\Shipping;
use App\Domain\Merchant\Models\MerchantCommission;
use App\Domain\Merchant\Models\MerchantTaxSetting;
use App\Domain\Merchant\Models\MerchantPayment;
use App\Domain\Shipping\Models\CourierServiceArea;
use App\Domain\Merchant\Models\MerchantBranch;
use App\Domain\Platform\Services\MonetaryUnitService;
use App\Domain\Commerce\Services\Cart\MerchantCartManager;
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
 * tax_amount          = catalog_items_total * tax_rate / 100
 * shipping_cost       = Selected shipping price (after free_above check)
 * grand_total         = catalog_items_total + tax_amount + shipping_cost
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
 * Step2: + shipping_cost, grand_total
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
        $ps = platformSettings();
        $this->currencyFormat = $ps->get('currency_format', 0);
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
     * Uses new MerchantCartManager
     */
    public function calculateCatalogItemsTotal($merchantId = null)
    {
        $cartManager = app(MerchantCartManager::class);
        $cartItems = $merchantId
            ? $cartManager->getItemsForMerchant($merchantId)
            : $cartManager->getItems();

        if (empty($cartItems)) {
            return 0;
        }

        $total = 0;
        foreach ($cartItems as $key => $item) {
            // NEW CART FORMAT ONLY - No fallbacks
            if (!isset($item['total_price'])) {
                throw new \RuntimeException("Cart item '{$key}' missing required field: total_price");
            }
            $total += (float)$item['total_price'];
        }

        return round($total, 2);
    }

    /**
     * Calculate tax amount
     * Tax is calculated on catalog items total
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
     * ========================================================================
     * MASTER CALCULATION - Step 1
     * ========================================================================
     * Returns all data needed for Step 1 display and session storage
     */
    public function calculateStep1($merchantId, $taxRate = 0, $taxLocation = '')
    {
        // 1. CatalogItems Total (RAW - never changes)
        $catalogItemsTotal = $this->calculateCatalogItemsTotal($merchantId);

        // 2. Tax (on total)
        $taxAmount = $this->calculateTax($catalogItemsTotal, $taxRate);

        // 3. Total with tax (for display)
        $totalWithTax = $catalogItemsTotal + $taxAmount;

        return [
            // Core values
            'catalog_items_total' => $catalogItemsTotal,
            'subtotal' => $catalogItemsTotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'tax_location' => $taxLocation,
            'total_with_tax' => $totalWithTax,

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
    public function calculateStep2($merchantId, $step1Data, $shippingData)
    {
        // Get step1 values
        $catalogItemsTotal = $step1Data['catalog_items_total'] ?? 0;
        $subtotal = $step1Data['subtotal'] ?? $catalogItemsTotal;
        $taxRate = $step1Data['tax_rate'] ?? 0;
        $taxAmount = $step1Data['tax_amount'] ?? 0;
        $taxLocation = $step1Data['tax_location'] ?? '';

        // Calculate shipping
        $shippingResult = $this->processShippingData($shippingData, $catalogItemsTotal, $merchantId);

        // Grand Total = subtotal + tax + shipping
        $grandTotal = $subtotal + $taxAmount + $shippingResult['cost'];

        return [
            // From Step 1
            'catalog_items_total' => $catalogItemsTotal,
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

            // Totals
            'grand_total' => $grandTotal,
            'total' => $grandTotal,
            'final_total' => $grandTotal,
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

                    // Check free_above for Tryoto (merchant's own or platform-provided)
                    $merchantTryoto = Shipping::where('provider', 'tryoto')
                        ->where('status', 1)
                        ->where(function ($q) use ($mid) {
                            $q->where('user_id', $mid)
                              ->orWhere(function ($q2) use ($mid) {
                                  $q2->where('user_id', 0)
                                     ->where('operator', $mid);
                              });
                        })
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
     * Get price breakdown for display (used by checkout-price-summary component)
     */
    public function getPriceBreakdown($step, $step1Data = null, $step2Data = null, $merchantId = null)
    {
        $catalogItemsTotal = 0;
        $taxRate = 0;
        $taxAmount = 0;
        $taxLocation = '';
        $shippingCost = 0;
        $originalShippingCost = 0;
        $isFreeShipping = false;
        $freeShippingDiscount = 0;
        $shippingCompany = '';
        $grandTotal = 0;
        // Courier delivery fields
        $deliveryType = 'shipping';
        $courierId = null;
        $courierName = '';
        $courierFee = 0;

        if ($step == 1) {
            // Step 1: Only catalogItems and tax (calculated dynamically)
            $catalogItemsTotal = $this->calculateCatalogItemsTotal($merchantId);
            $grandTotal = $catalogItemsTotal;

        } elseif ($step == 2) {
            // Step 2: CatalogItems + Tax from step1, shipping dynamic
            if ($step1Data) {
                $catalogItemsTotal = $step1Data->catalog_items_total ?? 0;
                $taxRate = $step1Data->tax_rate ?? 0;
                $taxAmount = $step1Data->tax_amount ?? 0;
                $taxLocation = $step1Data->tax_location ?? '';
            }

            $grandTotal = $catalogItemsTotal + $taxAmount; // Shipping added via JS

        } elseif ($step == 3) {
            // Step 3: Everything from step2 session (read-only)
            if ($step2Data) {
                $catalogItemsTotal = $step2Data->catalog_items_total ?? ($step1Data->catalog_items_total ?? 0);
                $taxRate = $step2Data->tax_rate ?? 0;
                $taxAmount = $step2Data->tax_amount ?? 0;
                $taxLocation = $step2Data->tax_location ?? '';
                $shippingCost = $step2Data->shipping_cost ?? 0;
                $originalShippingCost = $step2Data->original_shipping_cost ?? $shippingCost;
                $isFreeShipping = $step2Data->is_free_shipping ?? false;
                $freeShippingDiscount = $step2Data->free_shipping_discount ?? 0;
                $shippingCompany = $step2Data->shipping_company ?? '';
                $grandTotal = $step2Data->grand_total ?? $step2Data->total ?? 0;
                // Courier delivery fields
                $deliveryType = $step2Data->delivery_type ?? 'shipping';
                $courierId = $step2Data->courier_id ?? null;
                $courierName = $step2Data->courier_name ?? '';
                $courierFee = $step2Data->courier_fee ?? 0;
            }
        }

        return [
            'catalog_items_total' => round($catalogItemsTotal, 2),
            'tax_rate' => $taxRate,
            'tax_amount' => round($taxAmount, 2),
            'tax_location' => $taxLocation,
            'shipping_cost' => round($shippingCost, 2),
            'original_shipping_cost' => round($originalShippingCost, 2),
            'is_free_shipping' => $isFreeShipping,
            'free_shipping_discount' => round($freeShippingDiscount, 2),
            'shipping_company' => $shippingCompany,
            'grand_total' => round($grandTotal, 2),
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
            'tax_amount' => $this->convert($raw['tax_amount']),
            'shipping_cost' => $this->convert($raw['shipping_cost']),
            'original_shipping_cost' => $this->convert($raw['original_shipping_cost']),
            'free_shipping_discount' => $this->convert($raw['free_shipping_discount']),
            'grand_total' => $this->convert($raw['grand_total']),

            // Non-monetary values (pass through)
            'tax_rate' => $raw['tax_rate'],
            'tax_location' => $raw['tax_location'],
            'is_free_shipping' => $raw['is_free_shipping'],
            'shipping_company' => $raw['shipping_company'],

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
            'tax_amount_formatted' => $this->formatPrice($converted['tax_amount']),
            'shipping_cost_formatted' => $this->formatPrice($converted['shipping_cost']),
            'original_shipping_cost_formatted' => $this->formatPrice($converted['original_shipping_cost']),
            'free_shipping_discount_formatted' => $this->formatPrice($converted['free_shipping_discount']),
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
        $merchantHasLocationInCity = MerchantBranch::where('user_id', $merchantId)
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
     * Get merchant branches in a specific city
     */
    public function getMerchantBranchesInCity(int $merchantId, int $cityId): \Illuminate\Database\Eloquent\Collection
    {
        return MerchantBranch::where('user_id', $merchantId)
            ->where('city_id', $cityId)
            ->where('status', 1)
            ->get();
    }

    /**
     * Get all merchant branches
     */
    public function getMerchantBranches(int $merchantId): \Illuminate\Database\Eloquent\Collection
    {
        return MerchantBranch::where('user_id', $merchantId)
            ->where('status', 1)
            ->get();
    }

    // ========================================================================
    // MERCHANT PURCHASE BREAKDOWN
    // ========================================================================

    /**
     * Calculate complete merchant purchase details
     * This is used to populate the merchant_purchases table
     * Uses new cart item format from MerchantCartManager
     */
    public function calculateMerchantPurchaseDetails(int $merchantId, array $cartItems, array $options = []): array
    {
        // 1. Calculate items total for this merchant
        // NEW CART FORMAT ONLY - No fallbacks
        $itemsTotal = 0;
        foreach ($cartItems as $key => $item) {
            if (!isset($item['merchant_id'])) {
                throw new \RuntimeException("Cart item '{$key}' missing required field: merchant_id");
            }
            if (!isset($item['total_price'])) {
                throw new \RuntimeException("Cart item '{$key}' missing required field: total_price");
            }

            $itemMerchantId = (int)$item['merchant_id'];
            if ($itemMerchantId === $merchantId) {
                $itemsTotal += (float)$item['total_price'];
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

        // 5. Get courier info from options
        $courierId = $options['courier_id'] ?? null;
        $courierFee = (float)($options['courier_fee'] ?? 0);
        $merchantBranchId = $options['merchant_branch_id'] ?? null;

        // 6. Determine payment type and money receiver
        $paymentType = $options['payment_type'] ?? 'platform';
        $paymentGatewayId = $options['payment_gateway_id'] ?? null;
        $moneyReceivedBy = $options['money_received_by'] ?? 'platform';

        // 7. Calculate net amount (what merchant gets after commission)
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

            // Courier
            'courier_id' => $courierId,
            'courier_fee' => $courierFee,
            'merchant_branch_id' => $merchantBranchId,

            // Payment
            'payment_type' => $paymentType,
            'payment_gateway_id' => $paymentGatewayId,
            'money_received_by' => $moneyReceivedBy,

            // Net amount (merchant's earnings after commission)
            'net_amount' => round($netAmount, 2),

            // Grand total for this merchant (what customer pays for this merchant's items)
            'merchant_total' => round($itemsTotal + $taxAmount + $shippingCost + $courierFee, 2),
        ];
    }

    /**
     * Determine who receives the money based on payment gateway
     *
     * For COD: follows shipping ownership (whoever owns shipping collects the cash)
     * For Online: follows payment gateway ownership
     *
     * @param int $merchantId
     * @param int|null $paymentGatewayId
     * @param int|null $shippingId Required for COD to determine shipping owner
     */
    public function determineMoneyReceiver(int $merchantId, ?int $paymentGatewayId, ?int $shippingId = null): string
    {
        if (!$paymentGatewayId) {
            return 'platform';
        }

        $gateway = MerchantPayment::find($paymentGatewayId);
        if (!$gateway) {
            return 'platform';
        }

        // COD special case: follows shipping ownership
        // Whoever owns the shipping company collects the cash
        if ($gateway->keyword === 'cod') {
            return $this->determineShippingType($merchantId, $shippingId, null);
        }

        // Online payment: follows payment gateway ownership
        if ($gateway->user_id > 0 && $gateway->user_id == $merchantId) {
            return 'merchant';
        }

        return 'platform';
    }

    /**
     * Determine payment type based on payment gateway ownership
     *
     * @param int $merchantId
     * @param int|null $paymentGatewayId
     * @param int|null $shippingId Required for COD to determine shipping owner
     */
    public function determinePaymentType(int $merchantId, ?int $paymentGatewayId, ?int $shippingId = null): string
    {
        return $this->determineMoneyReceiver($merchantId, $paymentGatewayId, $shippingId) === 'merchant' ? 'merchant' : 'platform';
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
