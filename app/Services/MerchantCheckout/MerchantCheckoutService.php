<?php

namespace App\Services\MerchantCheckout;

use App\Models\User;
use App\Models\Country;
use App\Models\State;
use App\Models\Shipping;
use App\Models\Package;
use App\Models\MerchantPayment;
use App\Models\CourierServiceArea;
use App\Models\MerchantBranch;
use App\Services\Cart\MerchantCartManager;
use Illuminate\Support\Facades\Auth;

/**
 * Main Merchant Checkout Service
 *
 * Orchestrates the entire checkout flow - single source of truth
 */
class MerchantCheckoutService
{
    protected MerchantCartManager $cartService;
    protected MerchantSessionManager $sessionManager;
    protected MerchantPriceCalculator $priceCalculator;

    public function __construct(
        MerchantCartManager $cartService,
        MerchantSessionManager $sessionManager,
        MerchantPriceCalculator $priceCalculator
    ) {
        $this->cartService = $cartService;
        $this->sessionManager = $sessionManager;
        $this->priceCalculator = $priceCalculator;
    }

    /**
     * Initialize checkout for merchant - get address page data
     */
    public function initializeAddressStep(int $merchantId): array
    {
        // Validate merchant has items
        if (!$this->cartService->hasMerchantItems($merchantId)) {
            return [
                'success' => false,
                'error' => 'no_items',
                'message' => __('No items found for this merchant'),
            ];
        }

        $merchant = User::find($merchantId);
        if (!$merchant || (int)$merchant->is_merchant !== 2) {
            return [
                'success' => false,
                'error' => 'invalid_merchant',
                'message' => __('Invalid merchant'),
            ];
        }

        $user = Auth::user();
        $cartSummary = $this->cartService->getMerchantCartSummary($merchantId);
        $savedAddress = $this->sessionManager->getAddressData($merchantId);
        $locationDraft = $this->sessionManager->getLocationDraft($merchantId);

        // Get customer defaults
        $customerData = $this->getCustomerDefaults($user, $savedAddress, $locationDraft);

        // Get countries for dropdown
        $countries = Country::where('status', 1)->orderBy('country_name')->get();

        return [
            'success' => true,
            'data' => [
                'merchant' => [
                    'id' => $merchant->id,
                    'name' => $merchant->shop_name ?? $merchant->name,
                    'name_ar' => $merchant->shop_name_ar ?? $merchant->shop_name ?? $merchant->name,
                ],
                'cart' => $cartSummary,
                'customer' => $customerData,
                'countries' => $countries->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->country_name,
                    'code' => $c->country_code ?? $c->country_name,
                ]),
                'saved_address' => $savedAddress,
            ],
        ];
    }

    /**
     * Process address step submission
     */
    public function processAddressStep(int $merchantId, array $input): array
    {
        // Get user info
        $user = Auth::user();

        // Build address data
        $addressData = [
            'customer_name' => $user->name ?? $input['customer_name'] ?? '',
            'customer_email' => $user->email ?? $input['customer_email'] ?? '',
            'customer_phone' => $user->phone ?? $input['customer_phone'] ?? '',
            'customer_address' => $input['customer_address'] ?? '',
            'customer_city' => $input['customer_city'] ?? '',
            'customer_state' => $input['customer_state'] ?? '',
            'customer_zip' => $input['customer_zip'] ?? '',
            'customer_country' => $input['customer_country'] ?? '',
            'country_id' => (int)($input['country_id'] ?? 0),
            'state_id' => (int)($input['state_id'] ?? 0),
            'city_id' => (int)($input['city_id'] ?? 0),
            'latitude' => $input['latitude'] ?? null,
            'longitude' => $input['longitude'] ?? null,
        ];

        // Calculate tax for this location
        $taxInfo = $this->priceCalculator->getTaxRateForLocation(
            $addressData['country_id'],
            $addressData['state_id']
        );

        $addressData['tax_rate'] = $taxInfo['tax_rate'];
        $addressData['tax_location'] = $taxInfo['tax_location'];

        // Calculate tax amount
        $cartSummary = $this->cartService->getMerchantCartSummary($merchantId);
        $taxAmount = $this->priceCalculator->calculateTax(
            $cartSummary['total_price'],
            $taxInfo['tax_rate']
        );
        $addressData['tax_amount'] = $taxAmount;

        // Save to session
        $this->sessionManager->saveAddressData($merchantId, $addressData);

        return [
            'success' => true,
            'data' => $addressData,
            'next_step' => 'shipping',
            'redirect' => route('merchant.checkout.shipping', $merchantId),
        ];
    }

    /**
     * Initialize shipping step
     */
    public function initializeShippingStep(int $merchantId): array
    {
        // Check address is completed
        $addressData = $this->sessionManager->getAddressData($merchantId);
        if (!$addressData) {
            return [
                'success' => false,
                'error' => 'address_required',
                'message' => __('Please complete address step first'),
                'redirect' => route('merchant.checkout.address', $merchantId),
            ];
        }

        $merchant = User::find($merchantId);
        $cartSummary = $this->cartService->getMerchantCartSummary($merchantId);

        // Get shipping options for this merchant
        $shippingOptions = $this->getMerchantShippingOptions($merchantId, $cartSummary['total_price']);

        // Get packaging options
        $packagingOptions = $this->getMerchantPackagingOptions($merchantId);

        // Get courier options if available
        $courierOptions = $this->getCourierOptions($merchantId, $addressData);

        // Get saved shipping selection
        $savedShipping = $this->sessionManager->getShippingData($merchantId);

        // Calculate totals preview
        $totals = $this->priceCalculator->calculateTotals($cartSummary['items'], [
            'tax_rate' => $addressData['tax_rate'],
            'shipping_cost' => $savedShipping['shipping_cost'] ?? 0,
            'packing_cost' => $savedShipping['packing_cost'] ?? 0,
            'courier_fee' => $savedShipping['courier_fee'] ?? 0,
        ]);

        return [
            'success' => true,
            'data' => [
                'merchant' => [
                    'id' => $merchant->id,
                    'name' => $merchant->shop_name ?? $merchant->name,
                ],
                'address' => $addressData,
                'cart' => $cartSummary,
                'shipping_options' => $shippingOptions,
                'packaging_options' => $packagingOptions,
                'courier_options' => $courierOptions,
                'saved_shipping' => $savedShipping,
                'totals' => $totals,
            ],
        ];
    }

    /**
     * Process shipping step submission
     */
    public function processShippingStep(int $merchantId, array $input): array
    {
        $addressData = $this->sessionManager->getAddressData($merchantId);
        if (!$addressData) {
            return [
                'success' => false,
                'error' => 'address_required',
                'redirect' => route('merchant.checkout.address', $merchantId),
            ];
        }

        $cartSummary = $this->cartService->getMerchantCartSummary($merchantId);
        $deliveryType = $input['delivery_type'] ?? 'shipping';

        $shippingData = [
            'delivery_type' => $deliveryType,
            'items_total' => $cartSummary['total_price'],
        ];

        if ($deliveryType === 'local_courier') {
            // ✅ STRICT: merchant_branch_id is REQUIRED for courier delivery - NO fallback
            $merchantBranchId = (int)($input['merchant_branch_id'] ?? 0);
            if ($merchantBranchId <= 0) {
                return [
                    'success' => false,
                    'error' => 'branch_required',
                    'message' => __('Please select a pickup branch for courier delivery.'),
                ];
            }

            // Courier delivery - use data from frontend (already matched by coordinates)
            $courierId = (int)($input['courier_id'] ?? 0);
            $courierFee = (float)($input['courier_fee'] ?? 0);
            $serviceAreaId = (int)($input['service_area_id'] ?? 0);
            $courierName = $input['courier_name'] ?? null;

            // Fallback: If courier_fee is 0 but we have service_area_id, lookup from database
            if ($courierFee <= 0 && $serviceAreaId > 0) {
                $serviceArea = CourierServiceArea::find($serviceAreaId);
                if ($serviceArea) {
                    $courierFee = (float)$serviceArea->price;
                    if (empty($courierName)) {
                        $courierName = $serviceArea->courier->shop_name ?? $serviceArea->courier->name ?? 'Courier';
                    }
                }
            }

            // Fallback: If still no name, lookup from courier user
            if (empty($courierName) && $courierId > 0) {
                $courier = \App\Models\User::find($courierId);
                $courierName = $courier ? ($courier->shop_name ?? $courier->name ?? 'Courier') : 'Courier';
            }

            $shippingData = array_merge($shippingData, [
                'courier_id' => $courierId,
                'courier_name' => $courierName ?: 'Courier',
                'courier_fee' => $courierFee,
                'service_area_id' => $serviceAreaId,
                'merchant_branch_id' => $merchantBranchId,
                'shipping_id' => 0,
                'shipping_provider' => null,
                'shipping_name' => null,
                'shipping_cost' => 0,
                'original_shipping_cost' => 0,
                'is_free_shipping' => false,
                // ✅ المندوب يتبع المنصة دائماً → المبلغ للمنصة
                'is_platform_provided' => true,
                'owner_user_id' => 0,
            ]);
        } else {
            // Regular shipping
            $shippingProvider = $input['shipping_provider'] ?? 'manual';

            // Check if this is an API provider (Tryoto, etc.)
            if ($this->isApiProvider($shippingProvider)) {
                // For API providers, use values from frontend directly
                $shippingCost = (float)($input['shipping_cost'] ?? 0);
                $originalCost = (float)($input['shipping_original_cost'] ?? $shippingCost);
                $isFree = ($input['shipping_is_free'] ?? '0') === '1';
                $shippingName = $input['shipping_name'] ?? ucfirst($shippingProvider);

                // ✅ API shipping ownership - lookup from database using shipping_id
                $shippingId = $input['shipping_id'] ?? '';
                $isPlatformProvided = false;
                $ownerUserId = $merchantId;

                if (is_numeric($shippingId) && (int)$shippingId > 0) {
                    $shippingRecord = Shipping::find((int)$shippingId);
                    if ($shippingRecord) {
                        $isPlatformProvided = (int)$shippingRecord->user_id === 0;
                        $ownerUserId = $isPlatformProvided ? 0 : (int)$shippingRecord->user_id;
                    }
                }

                $shippingData = array_merge($shippingData, [
                    'shipping_id' => $shippingId,
                    'shipping_provider' => $shippingProvider,
                    'shipping_name' => $shippingName,
                    'shipping_cost' => $isFree ? 0 : $shippingCost,
                    'original_shipping_cost' => $originalCost,
                    'is_free_shipping' => $isFree,
                    'courier_id' => 0,
                    'courier_name' => null,
                    'courier_fee' => 0,
                    // ✅ للحسابات المالية
                    'is_platform_provided' => $isPlatformProvided,
                    'owner_user_id' => $ownerUserId,
                ]);
            } else {
                // For database shipping methods
                $shippingId = (int)($input['shipping_id'] ?? 0);
                $shippingInfo = $this->priceCalculator->calculateShippingCost(
                    $shippingId,
                    $cartSummary['total_price']
                );
                // Use name from form if provided, otherwise from database
                $shippingName = !empty($input['shipping_name']) ? $input['shipping_name'] : $shippingInfo['shipping_name'];

                // ✅ Database shipping ownership
                $isPlatformProvided = false;
                $ownerUserId = $merchantId;

                if ($shippingId > 0) {
                    $shippingRecord = Shipping::find($shippingId);
                    if ($shippingRecord) {
                        $isPlatformProvided = (int)$shippingRecord->user_id === 0;
                        $ownerUserId = $isPlatformProvided ? 0 : (int)$shippingRecord->user_id;
                    }
                }

                $shippingData = array_merge($shippingData, [
                    'shipping_id' => $shippingInfo['shipping_id'],
                    'shipping_provider' => $shippingProvider,
                    'shipping_name' => $shippingName,
                    'shipping_cost' => $shippingInfo['shipping_cost'],
                    'original_shipping_cost' => $shippingInfo['original_cost'],
                    'is_free_shipping' => $shippingInfo['is_free'],
                    'courier_id' => 0,
                    'courier_name' => null,
                    'courier_fee' => 0,
                    // ✅ للحسابات المالية
                    'is_platform_provided' => $isPlatformProvided,
                    'owner_user_id' => $ownerUserId,
                ]);
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        // PACKAGING: Supports operator pattern (user_id=0, operator=merchantId)
        // - user_id = 0 + operator = merchantId → Platform provides for merchant
        // - user_id = merchantId → Merchant's own packaging
        // ═══════════════════════════════════════════════════════════════════
        $packingId = (int)($input['packing_id'] ?? 0);
        $packingName = $input['packing_name'] ?? null;
        $packingCost = 0;
        $packingIsPlatformProvided = false;
        $packingOwnerUserId = $merchantId;

        if ($packingId > 0) {
            $packingInfo = $this->priceCalculator->calculatePackingCost($packingId);
            $packingName = !empty($packingName) ? $packingName : $packingInfo['packing_name'];
            $packingCost = $packingInfo['packing_cost'];

            // Get owner from database - check for operator pattern
            $packingRecord = Package::find($packingId);
            if ($packingRecord) {
                // Platform-provided = user_id is 0
                $packingIsPlatformProvided = (int)$packingRecord->user_id === 0;
                $packingOwnerUserId = $packingIsPlatformProvided ? 0 : (int)$packingRecord->user_id;
            }
        }

        $shippingData = array_merge($shippingData, [
            'packing_id' => $packingId,
            'packing_name' => $packingName,
            'packing_cost' => $packingCost,
            // ✅ للحسابات المالية
            'packing_is_platform_provided' => $packingIsPlatformProvided,
            'packing_owner_user_id' => $packingOwnerUserId,
        ]);

        // Get discount if any
        $discountData = $this->sessionManager->getDiscountData($merchantId);
        $discountAmount = $discountData['amount'] ?? 0;

        // Calculate final totals
        $totals = $this->priceCalculator->calculateTotals($cartSummary['items'], [
            'discount_amount' => $discountAmount,
            'tax_rate' => $addressData['tax_rate'],
            'shipping_cost' => $shippingData['shipping_cost'],
            'packing_cost' => $shippingData['packing_cost'],
            'courier_fee' => $shippingData['courier_fee'],
        ]);

        $shippingData = array_merge($shippingData, [
            'discount_amount' => $discountAmount,
            'tax_rate' => $addressData['tax_rate'],
            'tax_amount' => $totals['tax_amount'],
            'grand_total' => $totals['grand_total'],
        ]);

        // Save to session
        $this->sessionManager->saveShippingData($merchantId, $shippingData);

        return [
            'success' => true,
            'data' => $shippingData,
            'totals' => $totals,
            'next_step' => 'payment',
            'redirect' => route('merchant.checkout.payment', $merchantId),
        ];
    }

    /**
     * Initialize payment step
     */
    public function initializePaymentStep(int $merchantId): array
    {
        // Check previous steps
        $addressData = $this->sessionManager->getAddressData($merchantId);
        $shippingData = $this->sessionManager->getShippingData($merchantId);

        if (!$addressData) {
            return [
                'success' => false,
                'error' => 'address_required',
                'redirect' => route('merchant.checkout.address', $merchantId),
            ];
        }

        if (!$shippingData) {
            return [
                'success' => false,
                'error' => 'shipping_required',
                'redirect' => route('merchant.checkout.shipping', $merchantId),
            ];
        }

        $merchant = User::find($merchantId);
        $cartSummary = $this->cartService->getMerchantCartSummary($merchantId);

        // Get payment methods for this merchant ONLY
        $paymentMethods = $this->getMerchantPaymentMethods($merchantId);

        if (empty($paymentMethods)) {
            return [
                'success' => false,
                'error' => 'no_payment_methods',
                'message' => __('This merchant has no payment methods configured'),
            ];
        }

        // Final totals
        $discountData = $this->sessionManager->getDiscountData($merchantId);
        $totals = $this->priceCalculator->calculateTotals($cartSummary['items'], [
            'discount_amount' => $discountData['amount'] ?? 0,
            'tax_rate' => $addressData['tax_rate'],
            'shipping_cost' => $shippingData['shipping_cost'],
            'packing_cost' => $shippingData['packing_cost'],
            'courier_fee' => $shippingData['courier_fee'],
        ]);

        return [
            'success' => true,
            'data' => [
                'merchant' => [
                    'id' => $merchant->id,
                    'name' => $merchant->shop_name ?? $merchant->name,
                ],
                'address' => $addressData,
                'shipping' => $shippingData,
                'cart' => $cartSummary,
                'payment_methods' => $paymentMethods,
                'totals' => $totals,
                'discount' => $discountData,
            ],
        ];
    }

    /**
     * Get merchant shipping options grouped by provider
     *
     * Includes:
     * 1. Merchant's own shipping (user_id = merchantId)
     * 2. Platform-provided shipping for this merchant (user_id = 0, operator = merchantId)
     */
    protected function getMerchantShippingOptions(int $merchantId, float $itemsTotal): array
    {
        $shipping = Shipping::where(function ($q) use ($merchantId) {
                // التاجر يملك هذا الشحن
                $q->where('user_id', $merchantId)
                  // أو المنصة وفرت هذا الشحن للتاجر
                  ->orWhere(function ($q2) use ($merchantId) {
                      $q2->where('user_id', 0)
                         ->where('operator', $merchantId);
                  });
            })
            ->where('status', 1)
            ->orderBy('provider')
            ->orderBy('price')
            ->get();

        // Group by provider
        $grouped = [];
        foreach ($shipping as $s) {
            $provider = $s->provider ?? 'manual';
            $integrationType = $s->integration_type ?? 'manual';
            $freeAbove = (float)$s->free_above;

            // ✅ Free shipping if subtotal >= free_above threshold
            // مثال: إذا free_above = 2000 والفاتورة = 2500 → الشحن مجاني
            $isFree = $freeAbove > 0 && $itemsTotal >= $freeAbove;

            // ✅ Use integration_type to determine if API provider
            $isApiProvider = $integrationType === 'api';

            // ✅ تحديد ملكية الشحن (للحسابات المالية)
            // user_id > 0 → التاجر يملك الشحن → المبلغ للتاجر
            // user_id = 0 → المنصة وفرت الشحن → المبلغ للمنصة
            $isPlatformProvided = (int)$s->user_id === 0;
            $ownerUserId = $isPlatformProvided ? 0 : (int)$s->user_id;

            if (!isset($grouped[$provider])) {
                $grouped[$provider] = [
                    'provider' => $provider,
                    'label' => $this->getProviderLabel($provider),
                    'icon' => $this->getProviderIcon($provider),
                    'is_api' => $isApiProvider,
                    'methods' => [],
                ];
            }

            // For non-API providers, add methods with free shipping logic
            // API providers (like Tryoto) handle this in the API response
            if (!$isApiProvider) {
                $grouped[$provider]['methods'][] = [
                    'id' => $s->id,
                    'name' => $s->name,
                    'subname' => $s->subname,
                    'price' => round((float)$s->price, 2),
                    'original_price' => round((float)$s->price, 2),
                    'chargeable_price' => $isFree ? 0 : round((float)$s->price, 2),
                    'free_above' => $freeAbove,
                    'is_free' => $isFree,
                    // ✅ للحسابات المالية
                    'is_platform_provided' => $isPlatformProvided,
                    'owner_user_id' => $ownerUserId,
                ];
            }
        }

        return array_values($grouped);
    }

    /**
     * Get provider label for display
     * Dynamic - uses provider name from database
     */
    protected function getProviderLabel(string $provider): string
    {
        // Use the provider name with first letter uppercase
        return ucfirst($provider);
    }

    /**
     * Get provider icon - default icon for all
     */
    protected function getProviderIcon(string $provider): string
    {
        // Default icon for all providers
        return 'fas fa-truck';
    }

    /**
     * Check if provider uses external API
     * Only tryoto fetches prices from external API
     */
    protected function isApiProvider(string $provider): bool
    {
        return $provider === 'tryoto';
    }

    /**
     * Get merchant packaging options
     *
     * OPERATOR PATTERN:
     * - user_id = merchantId → Merchant's own packaging
     * - user_id = 0, operator = merchantId → Platform provides for merchant
     */
    protected function getMerchantPackagingOptions(int $merchantId): array
    {
        $packages = Package::where(function ($q) use ($merchantId) {
                // Merchant's own packaging
                $q->where('user_id', $merchantId)
                  // OR Platform-provided packaging for this merchant
                  ->orWhere(function ($q2) use ($merchantId) {
                      $q2->where('user_id', 0)
                         ->where('operator', $merchantId);
                  });
            })
            ->orderBy('price')
            ->get();

        return $packages->map(function ($p) {
            // Platform-provided = user_id is 0
            $isPlatformProvided = (int)$p->user_id === 0;
            $ownerUserId = $isPlatformProvided ? 0 : (int)$p->user_id;

            return [
                'id' => $p->id,
                'name' => $p->name,
                'subname' => $p->subname,
                'price' => round((float)$p->price, 2),
                // ✅ للحسابات المالية
                'is_platform_provided' => $isPlatformProvided,
                'owner_user_id' => $ownerUserId,
            ];
        })->toArray();
    }

    /**
     * Get courier options for address
     *
     * Couriers are shown if:
     * 1. Customer's location is within courier's service radius
     * 2. Merchant has a location within courier's service radius
     * 3. Courier is active
     *
     * Uses Haversine formula to calculate distance between coordinates
     */
    protected function getCourierOptions(int $merchantId, array $addressData): array
    {
        $customerLat = (float)($addressData['latitude'] ?? 0);
        $customerLng = (float)($addressData['longitude'] ?? 0);

        \Log::debug('getCourierOptions: Checking couriers', [
            'merchant_id' => $merchantId,
            'customer_lat' => $customerLat,
            'customer_lng' => $customerLng,
        ]);

        if (!$customerLat || !$customerLng) {
            \Log::debug('getCourierOptions: No customer coordinates');
            return [];
        }

        // Step 1: Find merchant's locations (warehouses)
        $merchantBranches = MerchantBranch::where('user_id', $merchantId)
            ->where('status', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        if ($merchantBranches->isEmpty()) {
            \Log::debug('getCourierOptions: Merchant has no locations with coordinates');
            return [];
        }

        // Step 2: Find courier service areas where customer is within radius
        $availableCouriers = [];

        $serviceAreas = CourierServiceArea::where('status', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereHas('courier', function ($q) {
                $q->where('status', 1);
            })
            ->with(['courier', 'city'])
            ->get();

        foreach ($serviceAreas as $sa) {
            $courierLat = (float)$sa->latitude;
            $courierLng = (float)$sa->longitude;
            $serviceRadius = (int)($sa->service_radius_km ?? 20);

            // Check if customer is within courier's service radius
            $distanceToCustomer = $this->haversineDistance($courierLat, $courierLng, $customerLat, $customerLng);

            if ($distanceToCustomer > $serviceRadius) {
                continue; // Customer is too far from this courier
            }

            // Check if any merchant location is within courier's service radius
            $nearestMerchantBranch = null;
            $minDistanceToMerchant = PHP_FLOAT_MAX;

            foreach ($merchantBranches as $ml) {
                $distanceToMerchant = $this->haversineDistance(
                    $courierLat, $courierLng,
                    (float)$ml->latitude, (float)$ml->longitude
                );

                if ($distanceToMerchant <= $serviceRadius && $distanceToMerchant < $minDistanceToMerchant) {
                    $minDistanceToMerchant = $distanceToMerchant;
                    $nearestMerchantBranch = $ml;
                }
            }

            if (!$nearestMerchantBranch) {
                continue; // No merchant location within courier's service area
            }

            // Both customer and merchant are within courier's service radius
            // Use shop_name for display, fallback to name
            $courierDisplayName = $sa->courier->shop_name ?? $sa->courier->name ?? 'Courier';

            $availableCouriers[] = [
                'courier_id' => $sa->courier_id,
                'courier_name' => $courierDisplayName,
                'courier_phone' => $sa->courier->phone ?? '',
                'courier_photo' => $sa->courier->photo ?? null,
                'delivery_fee' => round((float)$sa->price, 2),
                'service_area_id' => $sa->id,
                'city_name' => $sa->city->name ?? '',
                'merchant_branch_id' => $nearestMerchantBranch->id,
                'distance_to_customer' => round($distanceToCustomer, 1),
                // ✅ المندوب يتبع المنصة دائماً → المبلغ للمنصة
                'is_platform_provided' => true,
                'owner_user_id' => 0,
            ];

            \Log::debug('getCourierOptions: Courier available', [
                'courier_id' => $sa->courier_id,
                'courier_name' => $sa->courier->name,
                'distance_to_customer_km' => round($distanceToCustomer, 1),
                'distance_to_merchant_km' => round($minDistanceToMerchant, 1),
                'service_radius_km' => $serviceRadius,
            ]);
        }

        // Sort by distance to customer (nearest first)
        usort($availableCouriers, fn($a, $b) => $a['distance_to_customer'] <=> $b['distance_to_customer']);

        \Log::debug('getCourierOptions: Found couriers', ['count' => count($availableCouriers)]);

        return $availableCouriers;
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     *
     * @param float $lat1 Latitude of point 1
     * @param float $lng1 Longitude of point 1
     * @param float $lat2 Latitude of point 2
     * @param float $lng2 Longitude of point 2
     * @return float Distance in kilometers
     */
    protected function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2 +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get merchant payment methods
     *
     * Includes:
     * 1. Merchant's own payment methods (user_id = merchantId)
     * 2. Platform-provided payment methods for this merchant (user_id = 0, operator = merchantId)
     *
     * Financial flow:
     * - Merchant's payment → money goes to merchant
     * - Platform's payment → money goes to platform (settled later)
     */
    protected function getMerchantPaymentMethods(int $merchantId): array
    {
        $payments = MerchantPayment::where(function ($q) use ($merchantId) {
                // التاجر يملك هذه البوابة
                $q->where('user_id', $merchantId)
                  // أو المنصة وفرت هذه البوابة للتاجر
                  ->orWhere(function ($q2) use ($merchantId) {
                      $q2->where('user_id', 0)
                         ->where('operator', $merchantId);
                  });
            })
            ->where('checkout', 1)
            ->where('status', 1)
            ->get();

        return $payments->map(function ($p) {
            // ✅ تحديد ملكية بوابة الدفع (للحسابات المالية)
            $isPlatformProvided = (int)$p->user_id === 0;
            $ownerUserId = $isPlatformProvided ? 0 : (int)$p->user_id;

            return [
                'id' => $p->id,
                'keyword' => $p->keyword,
                'name' => $p->name,
                'subname' => $p->subname,
                'type' => $p->type,
                'show_form' => $p->showForm(),
                // ✅ للحسابات المالية
                'is_platform_provided' => $isPlatformProvided,
                'owner_user_id' => $ownerUserId,
            ];
        })->toArray();
    }

    /**
     * Get customer defaults
     */
    protected function getCustomerDefaults(?User $user, ?array $savedAddress, ?array $locationDraft): array
    {
        return [
            'name' => $savedAddress['customer_name'] ?? $user->name ?? '',
            'email' => $savedAddress['customer_email'] ?? $user->email ?? '',
            'phone' => $savedAddress['customer_phone'] ?? $user->phone ?? '',
            'address' => $savedAddress['customer_address'] ?? $locationDraft['address'] ?? $user->address ?? '',
            'city' => $savedAddress['customer_city'] ?? $locationDraft['city'] ?? $user->city ?? '',
            'state' => $savedAddress['customer_state'] ?? '',
            'zip' => $savedAddress['customer_zip'] ?? $user->zip ?? '',
            'country_id' => $savedAddress['country_id'] ?? $locationDraft['country_id'] ?? 0,
            'latitude' => $savedAddress['latitude'] ?? $locationDraft['latitude'] ?? null,
            'longitude' => $savedAddress['longitude'] ?? $locationDraft['longitude'] ?? null,
        ];
    }

    /**
     * Get services (for dependency injection)
     */
    public function getCartService(): MerchantCartManager
    {
        return $this->cartService;
    }

    public function getSessionManager(): MerchantSessionManager
    {
        return $this->sessionManager;
    }

    public function getPriceCalculator(): MerchantPriceCalculator
    {
        return $this->priceCalculator;
    }
}
