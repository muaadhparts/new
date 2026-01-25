<?php

namespace Tests\Regression\Shipping;

use Tests\TestCase;
use App\Domain\Shipping\Services\ShipmentTrackingService;
use App\Domain\Shipping\Services\ShippingCalculatorService;
use App\Domain\Shipping\Services\ShippingQuoteService;
use App\Domain\Shipping\Services\TrackingViewService;
use App\Domain\Shipping\Services\TryotoService;
use App\Domain\Shipping\Services\TryotoLocationService;
use App\Domain\Shipping\Services\CustomerLocationService;

/**
 * Regression Tests for Shipping Domain Services
 *
 * Tests to ensure all Shipping domain services are properly structured
 * and can be resolved from the container.
 */
class ShippingServicesTest extends TestCase
{
    // =========================================================================
    // SHIPMENT TRACKING SERVICE TESTS
    // =========================================================================

    /** @test */
    public function shipment_tracking_service_can_be_resolved()
    {
        $service = app(ShipmentTrackingService::class);
        $this->assertInstanceOf(ShipmentTrackingService::class, $service);
    }

    /** @test */
    public function shipment_tracking_service_has_create_methods()
    {
        $service = app(ShipmentTrackingService::class);

        $this->assertTrue(method_exists($service, 'createTrackingRecord'));
        $this->assertTrue(method_exists($service, 'createApiShipment'));
        $this->assertTrue(method_exists($service, 'createManualShipment'));
    }

    /** @test */
    public function shipment_tracking_service_has_update_methods()
    {
        $service = app(ShipmentTrackingService::class);

        $this->assertTrue(method_exists($service, 'updateFromApi'));
        $this->assertTrue(method_exists($service, 'updateManually'));
    }

    /** @test */
    public function shipment_tracking_service_has_status_methods()
    {
        $service = app(ShipmentTrackingService::class);

        $this->assertTrue(method_exists($service, 'cancelShipment'));
        $this->assertTrue(method_exists($service, 'getCurrentStatus'));
        $this->assertTrue(method_exists($service, 'getTrackingHistory'));
        $this->assertTrue(method_exists($service, 'getShipmentInfo'));
    }

    /** @test */
    public function shipment_tracking_service_has_stats_methods()
    {
        $service = app(ShipmentTrackingService::class);

        $this->assertTrue(method_exists($service, 'getMerchantStats'));
        $this->assertTrue(method_exists($service, 'getOperatorStats'));
    }

    // =========================================================================
    // SHIPPING CALCULATOR SERVICE TESTS
    // =========================================================================

    /** @test */
    public function shipping_calculator_service_has_weight_methods()
    {
        $this->assertTrue(method_exists(ShippingCalculatorService::class, 'calculateVolumetricWeight'));
        $this->assertTrue(method_exists(ShippingCalculatorService::class, 'calculateChargeableWeight'));
    }

    /** @test */
    public function shipping_calculator_service_has_city_methods()
    {
        $this->assertTrue(method_exists(ShippingCalculatorService::class, 'getBranchCity'));
        $this->assertTrue(method_exists(ShippingCalculatorService::class, 'getMerchantCity'));
        $this->assertTrue(method_exists(ShippingCalculatorService::class, 'getCustomerCity'));
    }

    /** @test */
    public function shipping_calculator_service_has_request_methods()
    {
        $this->assertTrue(method_exists(ShippingCalculatorService::class, 'prepareShippingRequest'));
        $this->assertTrue(method_exists(ShippingCalculatorService::class, 'validateMerchantShippingData'));
        $this->assertTrue(method_exists(ShippingCalculatorService::class, 'calculatePackageDimensions'));
    }

    /** @test */
    public function shipping_calculator_volumetric_weight_calculation()
    {
        // Standard formula: (L × W × H) / 5000
        $result = ShippingCalculatorService::calculateVolumetricWeight(30, 20, 10);
        $this->assertEquals(1.2, $result);

        // Null dimensions
        $result = ShippingCalculatorService::calculateVolumetricWeight(null, 20, 10);
        $this->assertNull($result);
    }

    /** @test */
    public function shipping_calculator_chargeable_weight_calculation()
    {
        // Returns higher of actual vs volumetric
        $result = ShippingCalculatorService::calculateChargeableWeight(2.0, 1.5);
        $this->assertEquals(2.0, $result);

        $result = ShippingCalculatorService::calculateChargeableWeight(1.0, 2.5);
        $this->assertEquals(2.5, $result);
    }

    // =========================================================================
    // TRACKING VIEW SERVICE TESTS
    // =========================================================================

    /** @test */
    public function tracking_view_service_can_be_resolved()
    {
        $service = app(TrackingViewService::class);
        $this->assertInstanceOf(TrackingViewService::class, $service);
    }

    /** @test */
    public function tracking_view_service_has_view_methods()
    {
        $service = app(TrackingViewService::class);

        $this->assertTrue(method_exists($service, 'forMerchant'));
        $this->assertTrue(method_exists($service, 'forPurchase'));
        $this->assertTrue(method_exists($service, 'forPurchasesList'));
    }

    // =========================================================================
    // TRYOTO SERVICE TESTS
    // =========================================================================

    /** @test */
    public function tryoto_service_has_token_methods()
    {
        $this->assertTrue(method_exists(TryotoService::class, 'getToken'));
        $this->assertTrue(method_exists(TryotoService::class, 'getCacheKey'));
        $this->assertTrue(method_exists(TryotoService::class, 'clearCachedToken'));
    }

    /** @test */
    public function tryoto_service_has_api_methods()
    {
        $this->assertTrue(method_exists(TryotoService::class, 'makeApiRequest'));
        $this->assertTrue(method_exists(TryotoService::class, 'getDeliveryOptions'));
        $this->assertTrue(method_exists(TryotoService::class, 'createShipment'));
        $this->assertTrue(method_exists(TryotoService::class, 'trackShipment'));
        $this->assertTrue(method_exists(TryotoService::class, 'cancelShipment'));
    }

    /** @test */
    public function tryoto_service_has_warehouse_methods()
    {
        $this->assertTrue(method_exists(TryotoService::class, 'getWarehouses'));
        $this->assertTrue(method_exists(TryotoService::class, 'createWarehouse'));
    }

    /** @test */
    public function tryoto_service_has_utility_methods()
    {
        $this->assertTrue(method_exists(TryotoService::class, 'resolveCityName'));
        $this->assertTrue(method_exists(TryotoService::class, 'verifyCitySupport'));
        $this->assertTrue(method_exists(TryotoService::class, 'getOrderDetails'));
        $this->assertTrue(method_exists(TryotoService::class, 'checkConfiguration'));
    }

    /** @test */
    public function tryoto_service_has_statistics_methods()
    {
        $this->assertTrue(method_exists(TryotoService::class, 'getMerchantShipments'));
        $this->assertTrue(method_exists(TryotoService::class, 'getMerchantStatistics'));
        $this->assertTrue(method_exists(TryotoService::class, 'getAdminStatistics'));
    }

    /** @test */
    public function tryoto_service_has_merchant_context_method()
    {
        $this->assertTrue(method_exists(TryotoService::class, 'forMerchant'));
    }

    // =========================================================================
    // TRYOTO LOCATION SERVICE TESTS
    // =========================================================================

    /** @test */
    public function tryoto_location_service_can_be_resolved()
    {
        $service = app(TryotoLocationService::class);
        $this->assertInstanceOf(TryotoLocationService::class, $service);
    }

    /** @test */
    public function tryoto_location_service_has_resolve_methods()
    {
        $service = app(TryotoLocationService::class);

        $this->assertTrue(method_exists($service, 'resolveMapCity'));
        $this->assertTrue(method_exists($service, 'resolveByCoordinatesOnly'));
    }

    /** @test */
    public function tryoto_location_service_has_verify_methods()
    {
        $service = app(TryotoLocationService::class);

        $this->assertTrue(method_exists($service, 'verifyCitySupport'));
    }

    /** @test */
    public function tryoto_location_service_has_query_methods()
    {
        $service = app(TryotoLocationService::class);

        $this->assertTrue(method_exists($service, 'getSyncStats'));
        $this->assertTrue(method_exists($service, 'getSupportedCountries'));
        $this->assertTrue(method_exists($service, 'getSupportedCities'));
        $this->assertTrue(method_exists($service, 'searchCities'));
    }

    // =========================================================================
    // CUSTOMER LOCATION SERVICE TESTS
    // =========================================================================

    /** @test */
    public function customer_location_service_can_be_resolved()
    {
        $service = app(CustomerLocationService::class);
        $this->assertInstanceOf(CustomerLocationService::class, $service);
    }

    /** @test */
    public function customer_location_service_has_city_methods()
    {
        $service = app(CustomerLocationService::class);

        $this->assertTrue(method_exists($service, 'hasCity'));
        $this->assertTrue(method_exists($service, 'getCityId'));
        $this->assertTrue(method_exists($service, 'getCityName'));
    }

    /** @test */
    public function customer_location_service_has_set_methods()
    {
        $service = app(CustomerLocationService::class);

        $this->assertTrue(method_exists($service, 'setManually'));
        $this->assertTrue(method_exists($service, 'setFromGeolocation'));
        $this->assertTrue(method_exists($service, 'clear'));
    }
}
