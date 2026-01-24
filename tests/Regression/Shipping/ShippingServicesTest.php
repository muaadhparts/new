<?php

namespace Tests\Regression\Shipping;

use Tests\TestCase;
use App\Services\ShipmentTrackingService;
use App\Services\ShippingCalculatorService;
use App\Services\ShippingQuoteService;
use App\Services\TrackingViewService;
use App\Services\TryotoService;
use App\Services\TryotoLocationService;
use App\Services\CustomerLocationService;
use App\Domain\Shipping\Services\ShipmentTrackingService as DomainShipmentTrackingService;
use App\Domain\Shipping\Services\ShippingCalculatorService as DomainShippingCalculatorService;
use App\Domain\Shipping\Services\ShippingQuoteService as DomainShippingQuoteService;
use App\Domain\Shipping\Services\TrackingViewService as DomainTrackingViewService;
use App\Domain\Shipping\Services\TryotoService as DomainTryotoService;
use App\Domain\Shipping\Services\TryotoLocationService as DomainTryotoLocationService;
use App\Domain\Shipping\Services\CustomerLocationService as DomainCustomerLocationService;

/**
 * Regression Tests for Shipping Domain Services
 *
 * Phase 8: Services Migration
 *
 * This test ensures backward compatibility after moving services
 * from App\Services to App\Domain\Shipping\Services.
 */
class ShippingServicesTest extends TestCase
{
    // =========================================================================
    // BACKWARD COMPATIBILITY TESTS
    // =========================================================================

    /** @test */
    public function old_shipment_tracking_service_extends_domain_service()
    {
        $service = new ShipmentTrackingService();
        $this->assertInstanceOf(DomainShipmentTrackingService::class, $service);
    }

    /** @test */
    public function old_shipping_calculator_service_extends_domain_service()
    {
        $this->assertTrue(class_exists(ShippingCalculatorService::class));
        $this->assertTrue(is_subclass_of(ShippingCalculatorService::class, DomainShippingCalculatorService::class));
    }

    /** @test */
    public function old_shipping_quote_service_extends_domain_service()
    {
        $this->assertTrue(class_exists(ShippingQuoteService::class));
        $this->assertTrue(is_subclass_of(ShippingQuoteService::class, DomainShippingQuoteService::class));
    }

    /** @test */
    public function old_tracking_view_service_extends_domain_service()
    {
        $service = new TrackingViewService();
        $this->assertInstanceOf(DomainTrackingViewService::class, $service);
    }

    /** @test */
    public function old_tryoto_service_extends_domain_service()
    {
        $this->assertTrue(class_exists(TryotoService::class));
        $this->assertTrue(is_subclass_of(TryotoService::class, DomainTryotoService::class));
    }

    /** @test */
    public function old_tryoto_location_service_extends_domain_service()
    {
        $service = new TryotoLocationService();
        $this->assertInstanceOf(DomainTryotoLocationService::class, $service);
    }

    /** @test */
    public function old_customer_location_service_extends_domain_service()
    {
        $service = new CustomerLocationService();
        $this->assertInstanceOf(DomainCustomerLocationService::class, $service);
    }

    // =========================================================================
    // SHIPMENT TRACKING SERVICE TESTS
    // =========================================================================

    /** @test */
    public function shipment_tracking_service_has_create_methods()
    {
        $service = new ShipmentTrackingService();

        $this->assertTrue(method_exists($service, 'createTrackingRecord'));
        $this->assertTrue(method_exists($service, 'createApiShipment'));
        $this->assertTrue(method_exists($service, 'createManualShipment'));
    }

    /** @test */
    public function shipment_tracking_service_has_update_methods()
    {
        $service = new ShipmentTrackingService();

        $this->assertTrue(method_exists($service, 'updateFromApi'));
        $this->assertTrue(method_exists($service, 'updateManually'));
    }

    /** @test */
    public function shipment_tracking_service_has_status_methods()
    {
        $service = new ShipmentTrackingService();

        $this->assertTrue(method_exists($service, 'cancelShipment'));
        $this->assertTrue(method_exists($service, 'getCurrentStatus'));
        $this->assertTrue(method_exists($service, 'getTrackingHistory'));
        $this->assertTrue(method_exists($service, 'getShipmentInfo'));
    }

    /** @test */
    public function shipment_tracking_service_has_stats_methods()
    {
        $service = new ShipmentTrackingService();

        $this->assertTrue(method_exists($service, 'getMerchantStats'));
        $this->assertTrue(method_exists($service, 'getOperatorStats'));
    }

    // =========================================================================
    // SHIPPING CALCULATOR SERVICE TESTS
    // =========================================================================

    /** @test */
    public function shipping_calculator_service_has_weight_methods()
    {
        $this->assertTrue(method_exists(DomainShippingCalculatorService::class, 'calculateVolumetricWeight'));
        $this->assertTrue(method_exists(DomainShippingCalculatorService::class, 'calculateChargeableWeight'));
    }

    /** @test */
    public function shipping_calculator_service_has_city_methods()
    {
        $this->assertTrue(method_exists(DomainShippingCalculatorService::class, 'getBranchCity'));
        $this->assertTrue(method_exists(DomainShippingCalculatorService::class, 'getMerchantCity'));
        $this->assertTrue(method_exists(DomainShippingCalculatorService::class, 'getCustomerCity'));
    }

    /** @test */
    public function shipping_calculator_service_has_request_methods()
    {
        $this->assertTrue(method_exists(DomainShippingCalculatorService::class, 'prepareShippingRequest'));
        $this->assertTrue(method_exists(DomainShippingCalculatorService::class, 'validateMerchantShippingData'));
        $this->assertTrue(method_exists(DomainShippingCalculatorService::class, 'calculatePackageDimensions'));
    }

    /** @test */
    public function shipping_calculator_volumetric_weight_calculation()
    {
        // Standard formula: (L × W × H) / 5000
        $result = DomainShippingCalculatorService::calculateVolumetricWeight(30, 20, 10);
        $this->assertEquals(1.2, $result);

        // Null dimensions
        $result = DomainShippingCalculatorService::calculateVolumetricWeight(null, 20, 10);
        $this->assertNull($result);
    }

    /** @test */
    public function shipping_calculator_chargeable_weight_calculation()
    {
        // Returns higher of actual vs volumetric
        $result = DomainShippingCalculatorService::calculateChargeableWeight(2.0, 1.5);
        $this->assertEquals(2.0, $result);

        $result = DomainShippingCalculatorService::calculateChargeableWeight(1.0, 2.5);
        $this->assertEquals(2.5, $result);
    }

    // =========================================================================
    // TRACKING VIEW SERVICE TESTS
    // =========================================================================

    /** @test */
    public function tracking_view_service_has_view_methods()
    {
        $service = new TrackingViewService();

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
        $this->assertTrue(method_exists(DomainTryotoService::class, 'getToken'));
        $this->assertTrue(method_exists(DomainTryotoService::class, 'getCacheKey'));
        $this->assertTrue(method_exists(DomainTryotoService::class, 'clearCachedToken'));
    }

    /** @test */
    public function tryoto_service_has_api_methods()
    {
        $this->assertTrue(method_exists(DomainTryotoService::class, 'makeApiRequest'));
        $this->assertTrue(method_exists(DomainTryotoService::class, 'getDeliveryOptions'));
        $this->assertTrue(method_exists(DomainTryotoService::class, 'createShipment'));
        $this->assertTrue(method_exists(DomainTryotoService::class, 'trackShipment'));
        $this->assertTrue(method_exists(DomainTryotoService::class, 'cancelShipment'));
    }

    /** @test */
    public function tryoto_service_has_warehouse_methods()
    {
        $this->assertTrue(method_exists(DomainTryotoService::class, 'getWarehouses'));
        $this->assertTrue(method_exists(DomainTryotoService::class, 'createWarehouse'));
    }

    /** @test */
    public function tryoto_service_has_utility_methods()
    {
        $this->assertTrue(method_exists(DomainTryotoService::class, 'resolveCityName'));
        $this->assertTrue(method_exists(DomainTryotoService::class, 'verifyCitySupport'));
        $this->assertTrue(method_exists(DomainTryotoService::class, 'getOrderDetails'));
        $this->assertTrue(method_exists(DomainTryotoService::class, 'checkConfiguration'));
    }

    /** @test */
    public function tryoto_service_has_statistics_methods()
    {
        $this->assertTrue(method_exists(DomainTryotoService::class, 'getMerchantShipments'));
        $this->assertTrue(method_exists(DomainTryotoService::class, 'getMerchantStatistics'));
        $this->assertTrue(method_exists(DomainTryotoService::class, 'getAdminStatistics'));
    }

    /** @test */
    public function tryoto_service_has_merchant_context_method()
    {
        $this->assertTrue(method_exists(DomainTryotoService::class, 'forMerchant'));
    }

    // =========================================================================
    // TRYOTO LOCATION SERVICE TESTS
    // =========================================================================

    /** @test */
    public function tryoto_location_service_has_resolve_methods()
    {
        $service = new TryotoLocationService();

        $this->assertTrue(method_exists($service, 'resolveMapCity'));
        $this->assertTrue(method_exists($service, 'resolveByCoordinatesOnly'));
    }

    /** @test */
    public function tryoto_location_service_has_verify_methods()
    {
        $service = new TryotoLocationService();

        $this->assertTrue(method_exists($service, 'verifyCitySupport'));
    }

    /** @test */
    public function tryoto_location_service_has_query_methods()
    {
        $service = new TryotoLocationService();

        $this->assertTrue(method_exists($service, 'getSyncStats'));
        $this->assertTrue(method_exists($service, 'getSupportedCountries'));
        $this->assertTrue(method_exists($service, 'getSupportedCities'));
        $this->assertTrue(method_exists($service, 'searchCities'));
    }

    // =========================================================================
    // CUSTOMER LOCATION SERVICE TESTS
    // =========================================================================

    /** @test */
    public function customer_location_service_has_city_methods()
    {
        $service = new CustomerLocationService();

        $this->assertTrue(method_exists($service, 'hasCity'));
        $this->assertTrue(method_exists($service, 'getCityId'));
        $this->assertTrue(method_exists($service, 'getCityName'));
    }

    /** @test */
    public function customer_location_service_has_set_methods()
    {
        $service = new CustomerLocationService();

        $this->assertTrue(method_exists($service, 'setManually'));
        $this->assertTrue(method_exists($service, 'setFromGeolocation'));
        $this->assertTrue(method_exists($service, 'clear'));
    }

    // =========================================================================
    // CONTAINER RESOLUTION TESTS
    // =========================================================================

    /** @test */
    public function shipment_tracking_service_can_be_resolved_from_container()
    {
        $service = app(ShipmentTrackingService::class);
        $this->assertInstanceOf(DomainShipmentTrackingService::class, $service);
    }

    /** @test */
    public function tracking_view_service_can_be_resolved_from_container()
    {
        $service = app(TrackingViewService::class);
        $this->assertInstanceOf(DomainTrackingViewService::class, $service);
    }

    /** @test */
    public function tryoto_location_service_can_be_resolved_from_container()
    {
        $service = app(TryotoLocationService::class);
        $this->assertInstanceOf(DomainTryotoLocationService::class, $service);
    }

    /** @test */
    public function customer_location_service_can_be_resolved_from_container()
    {
        $service = app(CustomerLocationService::class);
        $this->assertInstanceOf(DomainCustomerLocationService::class, $service);
    }

    // =========================================================================
    // DOMAIN SERVICES DIRECT RESOLUTION
    // =========================================================================

    /** @test */
    public function domain_shipment_tracking_service_can_be_resolved()
    {
        $service = app(DomainShipmentTrackingService::class);
        $this->assertInstanceOf(DomainShipmentTrackingService::class, $service);
    }

    /** @test */
    public function domain_tracking_view_service_can_be_resolved()
    {
        $service = app(DomainTrackingViewService::class);
        $this->assertInstanceOf(DomainTrackingViewService::class, $service);
    }

    /** @test */
    public function domain_tryoto_location_service_can_be_resolved()
    {
        $service = app(DomainTryotoLocationService::class);
        $this->assertInstanceOf(DomainTryotoLocationService::class, $service);
    }

    /** @test */
    public function domain_customer_location_service_can_be_resolved()
    {
        $service = app(DomainCustomerLocationService::class);
        $this->assertInstanceOf(DomainCustomerLocationService::class, $service);
    }
}
