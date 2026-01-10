<?php

namespace App\Services\MerchantCheckout;

use App\Models\User;
use App\Models\Country;
use App\Models\State;
use App\Models\Shipping;
use App\Models\Package;
use App\Models\MerchantPayment;
use App\Models\CourierServiceArea;
use Illuminate\Support\Facades\Auth;

/**
 * Main Merchant Checkout Service
 *
 * Orchestrates the entire checkout flow - single source of truth
 */
class MerchantCheckoutService
{
    protected MerchantCartService $cartService;
    protected MerchantSessionManager $sessionManager;
    protected MerchantPriceCalculator $priceCalculator;

    public function __construct(
        MerchantCartService $cartService,
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
            // Courier delivery
            $courierInfo = $this->priceCalculator->calculateCourierFee(
                (int)($input['courier_id'] ?? 0),
                (int)($addressData['city_id'] ?? 0)
            );
            $shippingData = array_merge($shippingData, [
                'courier_id' => $courierInfo['courier_id'],
                'courier_name' => $courierInfo['courier_name'],
                'courier_fee' => $courierInfo['courier_fee'],
                'service_area_id' => $courierInfo['service_area_id'],
                'shipping_id' => 0,
                'shipping_name' => null,
                'shipping_cost' => 0,
            ]);
        } else {
            // Regular shipping
            $shippingInfo = $this->priceCalculator->calculateShippingCost(
                (int)($input['shipping_id'] ?? 0),
                $cartSummary['total_price']
            );
            $shippingData = array_merge($shippingData, [
                'shipping_id' => $shippingInfo['shipping_id'],
                'shipping_name' => $shippingInfo['shipping_name'],
                'shipping_cost' => $shippingInfo['shipping_cost'],
                'original_shipping_cost' => $shippingInfo['original_cost'],
                'is_free_shipping' => $shippingInfo['is_free'],
                'courier_id' => 0,
                'courier_name' => null,
                'courier_fee' => 0,
            ]);
        }

        // Packaging
        $packingInfo = $this->priceCalculator->calculatePackingCost(
            (int)($input['packing_id'] ?? 0)
        );
        $shippingData = array_merge($shippingData, [
            'packing_id' => $packingInfo['packing_id'],
            'packing_name' => $packingInfo['packing_name'],
            'packing_cost' => $packingInfo['packing_cost'],
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
     */
    protected function getMerchantShippingOptions(int $merchantId, float $itemsTotal): array
    {
        $shipping = Shipping::where('user_id', $merchantId)
            ->orderBy('provider')
            ->orderBy('price')
            ->get();

        // Group by provider
        $grouped = [];
        foreach ($shipping as $s) {
            $provider = $s->provider ?? 'manual';
            $isFree = $s->free_above > 0 && $itemsTotal >= $s->free_above;

            if (!isset($grouped[$provider])) {
                $grouped[$provider] = [
                    'provider' => $provider,
                    'label' => $this->getProviderLabel($provider),
                    'icon' => $this->getProviderIcon($provider),
                    'is_api' => $this->isApiProvider($provider), // Only tryoto uses external API
                    'methods' => [],
                ];
            }

            $grouped[$provider]['methods'][] = [
                'id' => $s->id,
                'title' => $s->title,
                'subtitle' => $s->subtitle,
                'price' => round((float)$s->price, 2),
                'free_above' => (float)$s->free_above,
                'is_free' => $isFree,
                'final_price' => $isFree ? 0 : round((float)$s->price, 2),
            ];
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
     */
    protected function getMerchantPackagingOptions(int $merchantId): array
    {
        $packages = Package::where('user_id', $merchantId)
            ->orderBy('price')
            ->get();

        return $packages->map(fn($p) => [
            'id' => $p->id,
            'title' => $p->title,
            'subtitle' => $p->subtitle,
            'price' => round((float)$p->price, 2),
        ])->toArray();
    }

    /**
     * Get courier options for address
     */
    protected function getCourierOptions(int $merchantId, array $addressData): array
    {
        $cityId = $addressData['city_id'] ?? 0;
        if (!$cityId) {
            return [];
        }

        $serviceAreas = CourierServiceArea::where('city_id', $cityId)
            ->whereHas('courier', function ($q) {
                $q->where('status', 1);
            })
            ->with('courier')
            ->get();

        return $serviceAreas->map(fn($sa) => [
            'courier_id' => $sa->courier_id,
            'courier_name' => $sa->courier->name ?? 'Courier',
            'courier_phone' => $sa->courier->phone ?? '',
            'courier_photo' => $sa->courier->photo ?? null,
            'delivery_fee' => round((float)$sa->price, 2),
            'service_area_id' => $sa->id,
            'city_name' => $sa->city->name ?? '',
        ])->toArray();
    }

    /**
     * Get merchant payment methods
     */
    protected function getMerchantPaymentMethods(int $merchantId): array
    {
        $payments = MerchantPayment::where('user_id', $merchantId)
            ->where('checkout', 1)
            ->get();

        return $payments->map(fn($p) => [
            'id' => $p->id,
            'keyword' => $p->keyword,
            'title' => $p->title ?? $p->name,
            'subtitle' => $p->subtitle,
            'type' => $p->type,
            'show_form' => $p->showForm(),
        ])->toArray();
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
    public function getCartService(): MerchantCartService
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
