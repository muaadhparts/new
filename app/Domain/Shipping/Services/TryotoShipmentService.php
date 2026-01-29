<?php

namespace App\Domain\Shipping\Services;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Shipping\Models\ShipmentTracking;
use App\Services\TryotoService;
use App\Helpers\PriceHelper;
use Illuminate\Support\Facades\Log;

/**
 * TryotoShipmentService
 * 
 * Handles all Tryoto API integration logic
 * 
 * This is the MOST COMPLEX service in the delivery system
 * 
 * Responsibilities:
 * - Get shipping options from Tryoto API
 * - Send shipments to Tryoto
 * - Update existing shipments
 * - Cancel shipments
 * - Print shipping labels
 * - Handle complex error scenarios
 * - Resolve cities and dimensions
 */
class TryotoShipmentService
{
    protected TryotoService $tryotoService;
    protected CityResolutionService $cityService;
    protected ShipmentCalculationService $calculationService;
    protected ShipmentTrackingService $trackingService;

    public function __construct(
        CityResolutionService $cityService,
        ShipmentCalculationService $calculationService,
        ShipmentTrackingService $trackingService
    ) {
        $this->cityService = $cityService;
        $this->calculationService = $calculationService;
        $this->trackingService = $trackingService;
    }

    /**
     * Get shipping options from Tryoto API
     * 
     * This is the MOST COMPLEX method (250+ lines in original controller)
     * 
     * @param int $purchaseId
     * @param int $merchantId
     * @param array|null $customDimensions Optional custom dimensions for re-search
     * @return array
     */
    public function getShippingOptions(
        int $purchaseId,
        int $merchantId,
        ?array $customDimensions = null
    ): array {
        try {
            $purchase = Purchase::find($purchaseId);

            if (!$purchase) {
                return [
                    'success' => false,
                    'error' => __('Purchase not found'),
                    'error_code' => 'PURCHASE_NOT_FOUND'
                ];
            }

            // Resolve merchant city from merchant_branches
            $originCity = $this->cityService->resolveMerchantCity($merchantId);

            if (!$originCity) {
                Log::warning('Merchant city not configured in merchant_branches', [
                    'merchant_id' => $merchantId,
                ]);
                return [
                    'success' => false,
                    'error' => __('Please configure your warehouse location in merchant settings'),
                    'error_code' => 'MERCHANT_CITY_MISSING',
                    'show_settings_link' => true
                ];
            }

            // Resolve customer city from purchase
            $destinationCity = $this->cityService->resolveCustomerCity($purchase, $merchantId);

            if (!$destinationCity) {
                Log::warning('Customer city not found in purchase', [
                    'purchase_id' => $purchase->id,
                ]);
                return [
                    'success' => false,
                    'error' => __('Customer city not specified in purchase'),
                    'error_code' => 'CUSTOMER_CITY_MISSING'
                ];
            }

            // Use custom dimensions or calculate from cart
            if ($customDimensions && isset($customDimensions['weight']) && $customDimensions['weight'] > 0) {
                $dimensions = $customDimensions;
                $weight = $dimensions['weight'];
            } else {
                $dimensions = $this->calculationService->calculatePurchaseDimensions($purchase);
                $weight = $dimensions['weight'];
            }

            // Calculate COD amount if payment is COD
            $codAmount = in_array($purchase->method, ['cod', 'Cash On Delivery'])
                ? (float)$purchase->pay_amount
                : 0;

            // Get customer's shipping choice
            $customerChoice = $purchase->customer_shipping_choice[$merchantId] ?? null;

            Log::debug('Getting Tryoto shipping options', [
                'purchase_id' => $purchase->id,
                'merchant_id' => $merchantId,
                'origin' => $originCity,
                'destination' => $destinationCity,
                'weight' => $weight,
                'dimensions' => $dimensions,
                'customer_choice' => $customerChoice
            ]);

            // Use merchant-specific Tryoto credentials
            $this->tryotoService = (new TryotoService())->forMerchant($merchantId);

            // Check Tryoto configuration
            $config = $this->checkConfiguration($merchantId);
            if (!$config['configured']) {
                Log::error('Tryoto not configured for merchant', $config);
                return [
                    'success' => false,
                    'error' => __('Smart Shipping is temporarily unavailable'),
                    'error_code' => 'TRYOTO_NOT_CONFIGURED',
                    'details' => $config['issues']
                ];
            }

            // Get delivery options from Tryoto API
            $result = $this->tryotoService->getDeliveryOptions(
                $originCity,
                $destinationCity,
                $weight,
                $codAmount,
                $dimensions
            );

            $options = $result['success'] ? ($result['options'] ?? []) : [];
            $usedNearestCity = false;
            $originalCustomerCity = $destinationCity;

            // If original city failed, try nearest supported city
            if (empty($options) && $purchase->customer_latitude && $purchase->customer_longitude) {
                $nearestCity = $this->cityService->findNearestSupportedCity($purchase);

                if ($nearestCity && $nearestCity !== $destinationCity) {
                    Log::debug('Trying nearest city', [
                        'purchase_id' => $purchase->id,
                        'original_city' => $destinationCity,
                        'nearest_city' => $nearestCity
                    ]);

                    $retryResult = $this->tryotoService->getDeliveryOptions(
                        $originCity,
                        $nearestCity,
                        $weight,
                        $codAmount,
                        $dimensions
                    );

                    if ($retryResult['success'] && !empty($retryResult['options'])) {
                        $result = $retryResult;
                        $options = $result['options'];
                        $usedNearestCity = true;
                        $destinationCity = $nearestCity;
                    }
                }
            }

            if (!$result['success'] && empty($options)) {
                $errorCode = $result['error_code'] ?? 'UNKNOWN';
                $userFriendlyError = $this->getShippingErrorMessage($errorCode, $result['error'] ?? '');

                Log::warning('Tryoto API failed', [
                    'purchase_id' => $purchase->id,
                    'origin' => $originCity,
                    'destination' => $destinationCity,
                    'error' => $result['error'] ?? 'No options',
                    'error_code' => $errorCode
                ]);

                return [
                    'success' => false,
                    'error' => $userFriendlyError,
                    'error_code' => $errorCode,
                    'technical_error' => $result['error'] ?? null
                ];
            }

            if (empty($options)) {
                return [
                    'success' => false,
                    'error' => __('No shipping options available for this route'),
                    'error_code' => 'NO_OPTIONS',
                    'origin' => $originCity,
                    'destination' => $destinationCity
                ];
            }

            // Format options for display
            $formattedOptions = $this->formatShippingOptions($options);

            return [
                'success' => true,
                'options' => $options,
                'formatted_options' => $formattedOptions,
                'options_count' => count($options),
                'origin' => $originCity,
                'destination' => $destinationCity,
                'original_city' => $originalCustomerCity,
                'used_nearest_city' => $usedNearestCity,
                'dimensions' => $dimensions,
                'customer_choice' => $customerChoice,
            ];

        } catch (\Exception $e) {
            Log::error('Exception in getShippingOptions', [
                'purchase_id' => $purchaseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => __('Shipping service temporarily unavailable. Please try again later.'),
                'error_code' => 'EXCEPTION'
            ];
        }
    }

    /**
     * Send shipment to Tryoto
     * 
     * @param int $purchaseId
     * @param array $shipmentData
     * @param int $merchantId
     * @param int $merchantBranchId
     * @return ShipmentTracking
     * @throws \Exception
     */
    public function sendShipment(
        int $purchaseId,
        array $shipmentData,
        int $merchantId,
        int $merchantBranchId
    ): ShipmentTracking {
        $purchase = Purchase::findOrFail($purchaseId);

        // Verify purchase belongs to merchant
        $merchantOrder = $purchase->merchantPurchases()
            ->where('user_id', $merchantId)
            ->first();

        if (!$merchantOrder) {
            throw new \Exception(__('This purchase does not belong to you'));
        }

        // Check for existing shipment
        $existingShipment = ShipmentTracking::getLatestForPurchase($purchaseId, $merchantId);

        if ($existingShipment && !$existingShipment->is_final) {
            throw new \Exception(__('A shipment already exists for this purchase. Tracking: ') . $existingShipment->tracking_number);
        }

        // Use merchant-specific Tryoto credentials
        $this->tryotoService = (new TryotoService())->forMerchant($merchantId);

        // Send to Tryoto API
        $result = $this->tryotoService->createShipment($shipmentData);

        if (!$result['success']) {
            throw new \Exception($result['error'] ?? __('Failed to create shipment'));
        }

        // Create shipment tracking record
        $shipment = $this->trackingService->createTryotoShipment(
            purchaseId: $purchaseId,
            merchantId: $merchantId,
            trackingNumber: $result['tracking_number'],
            deliveryOptionId: $shipmentData['delivery_option_id'],
            companyName: $shipmentData['company_name'],
            price: $shipmentData['price'],
            merchantBranchId: $merchantBranchId
        );

        // Update merchant purchase status
        $merchantOrder->status = 'processing';
        $merchantOrder->save();

        // Add tracking entry
        $purchase->tracks()->create([
            'name' => __('Shipment Created'),
            'text' => __('Shipment created with Tryoto. Tracking: :tracking', [
                'tracking' => $result['tracking_number']
            ])
        ]);

        Log::info('Tryoto shipment created', [
            'purchase_id' => $purchaseId,
            'merchant_id' => $merchantId,
            'tracking_number' => $result['tracking_number'],
            'shipment_id' => $shipment->id
        ]);

        return $shipment;
    }

    /**
     * Update existing Tryoto shipment
     * 
     * @param int $purchaseId
     * @param array $updateData
     * @param int $merchantId
     * @return ShipmentTracking
     * @throws \Exception
     */
    public function updateShipment(
        int $purchaseId,
        array $updateData,
        int $merchantId
    ): ShipmentTracking {
        $shipment = ShipmentTracking::getLatestForPurchase($purchaseId, $merchantId);

        if (!$shipment) {
            throw new \Exception(__('No shipment found for this purchase'));
        }

        // Use merchant-specific Tryoto credentials
        $this->tryotoService = (new TryotoService())->forMerchant($merchantId);

        // Update via Tryoto API
        $result = $this->tryotoService->updateShipment($shipment->tracking_number, $updateData);

        if (!$result['success']) {
            throw new \Exception($result['error'] ?? __('Failed to update shipment'));
        }

        // Update local record
        $shipment->update($updateData);

        Log::info('Tryoto shipment updated', [
            'purchase_id' => $purchaseId,
            'merchant_id' => $merchantId,
            'tracking_number' => $shipment->tracking_number
        ]);

        return $shipment;
    }

    /**
     * Cancel Tryoto shipment
     * 
     * @param int $purchaseId
     * @param int $merchantId
     * @return bool
     * @throws \Exception
     */
    public function cancelShipment(int $purchaseId, int $merchantId): bool
    {
        $shipment = ShipmentTracking::getLatestForPurchase($purchaseId, $merchantId);

        if (!$shipment) {
            throw new \Exception(__('No shipment found for this purchase'));
        }

        // Use merchant-specific Tryoto credentials
        $this->tryotoService = (new TryotoService())->forMerchant($merchantId);

        // Cancel via Tryoto API
        $result = $this->tryotoService->cancelShipment($shipment->tracking_number);

        if (!$result['success']) {
            throw new \Exception($result['error'] ?? __('Failed to cancel shipment'));
        }

        // Mark as cancelled
        $shipment->status = 'cancelled';
        $shipment->is_final = true;
        $shipment->save();

        Log::info('Tryoto shipment cancelled', [
            'purchase_id' => $purchaseId,
            'merchant_id' => $merchantId,
            'tracking_number' => $shipment->tracking_number
        ]);

        return true;
    }

    /**
     * Print shipping label
     * 
     * @param int $purchaseId
     * @param int $merchantId
     * @return string PDF URL
     * @throws \Exception
     */
    public function printLabel(int $purchaseId, int $merchantId): string
    {
        $shipment = ShipmentTracking::getLatestForPurchase($purchaseId, $merchantId);

        if (!$shipment) {
            throw new \Exception(__('No shipment found for this purchase'));
        }

        // Use merchant-specific Tryoto credentials
        $this->tryotoService = (new TryotoService())->forMerchant($merchantId);

        // Get label from Tryoto API
        $result = $this->tryotoService->getShippingLabel($shipment->tracking_number);

        if (!$result['success']) {
            throw new \Exception($result['error'] ?? __('Failed to get shipping label'));
        }

        return $result['label_url'];
    }

    /**
     * Check Tryoto configuration for merchant
     * 
     * @param int $merchantId
     * @return array
     */
    public function checkConfiguration(int $merchantId): array
    {
        $this->tryotoService = (new TryotoService())->forMerchant($merchantId);
        return $this->tryotoService->checkConfiguration($merchantId);
    }

    /**
     * Format shipping options for display
     * 
     * @param array $options
     * @return string HTML
     */
    private function formatShippingOptions(array $options): string
    {
        $html = '<option value="">' . __('Select Shipping Company') . '</option>';

        foreach ($options as $option) {
            $price = $option['price'] ?? 0;
            $company = $option['company'] ?? 'Unknown';
            $deliveryOptionId = $option['deliveryOptionId'] ?? '';
            $estimatedDays = $option['estimatedDeliveryDays'] ?? '';
            $logo = $option['logo'] ?? '';
            $serviceType = $option['serviceType'] ?? '';

            $displayPrice = PriceHelper::showAdminCurrencyPrice($price);
            $label = $company . ' - ' . $displayPrice;
            if ($estimatedDays) {
                $label .= ' (' . $estimatedDays . ' ' . __('days') . ')';
            }

            $html .= '<option value="' . $deliveryOptionId . '"
                        data-company="' . htmlspecialchars($company) . '"
                        data-price="' . $price . '"
                        data-display-price="' . $displayPrice . '"
                        data-days="' . $estimatedDays . '"
                        data-logo="' . htmlspecialchars($logo) . '"
                        data-service-type="' . htmlspecialchars($serviceType) . '">' . $label . '</option>';
        }

        return $html;
    }

    /**
     * Get user-friendly error message
     * 
     * @param string $errorCode
     * @param string $technicalError
     * @return string
     */
    private function getShippingErrorMessage(string $errorCode, string $technicalError): string
    {
        $messages = [
            'CITY_NOT_SUPPORTED' => __('Shipping is not available to this city yet'),
            'INVALID_DIMENSIONS' => __('Invalid package dimensions. Please check weight and size'),
            'API_ERROR' => __('Shipping service temporarily unavailable'),
            'AUTHENTICATION_FAILED' => __('Shipping service configuration error'),
            'NO_OPTIONS' => __('No shipping options available for this route'),
        ];

        return $messages[$errorCode] ?? __('Shipping service error: ') . $technicalError;
    }
}
