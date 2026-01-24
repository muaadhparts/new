<?php

namespace Tests\Regression\Shipping;

use Tests\TestCase;
use App\Models\Shipping;
use App\Models\ShipmentTracking;
use App\Models\DeliveryCourier;
use App\Models\CourierServiceArea;
use App\Models\City;
use App\Models\Country;
use App\Domain\Shipping\Models\Shipping as DomainShipping;
use App\Domain\Shipping\Models\ShipmentTracking as DomainShipmentTracking;
use App\Domain\Shipping\Models\DeliveryCourier as DomainDeliveryCourier;
use App\Domain\Shipping\Models\CourierServiceArea as DomainCourierServiceArea;
use App\Domain\Shipping\Models\City as DomainCity;
use App\Domain\Shipping\Models\Country as DomainCountry;

/**
 * Regression tests for Shipping Domain models
 *
 * These tests verify that the refactored Domain models maintain
 * backward compatibility with the original App\Models classes.
 */
class ShippingModelsTest extends TestCase
{
    // ==========================================
    // Shipping Tests
    // ==========================================

    public function test_shipping_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(Shipping::class, DomainShipping::class),
            'Shipping should extend Domain Shipping'
        );
    }

    public function test_shipping_table_name(): void
    {
        $model = new Shipping();
        $this->assertEquals('shippings', $model->getTable());
    }

    public function test_shipping_has_required_scopes(): void
    {
        $model = new Shipping();

        $this->assertTrue(method_exists($model, 'scopeForMerchant'), 'Should have forMerchant scope');
        $this->assertTrue(method_exists($model, 'scopePlatformOnly'), 'Should have platformOnly scope');
        $this->assertTrue(method_exists($model, 'scopeActive'), 'Should have active scope');
    }

    public function test_shipping_has_ownership_methods(): void
    {
        $model = new Shipping();

        $this->assertTrue(method_exists($model, 'isPlatformOwned'), 'Should have isPlatformOwned method');
        $this->assertTrue(method_exists($model, 'isMerchantOwned'), 'Should have isMerchantOwned method');
        $this->assertTrue(method_exists($model, 'isEnabledForMerchant'), 'Should have isEnabledForMerchant method');
    }

    // ==========================================
    // ShipmentTracking Tests
    // ==========================================

    public function test_shipment_tracking_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(ShipmentTracking::class, DomainShipmentTracking::class),
            'ShipmentTracking should extend Domain ShipmentTracking'
        );
    }

    public function test_shipment_tracking_table_name(): void
    {
        $model = new ShipmentTracking();
        $this->assertEquals('shipment_trackings', $model->getTable());
    }

    public function test_shipment_tracking_has_required_relations(): void
    {
        $model = new ShipmentTracking();

        $this->assertTrue(method_exists($model, 'purchase'), 'Should have purchase relation');
        $this->assertTrue(method_exists($model, 'merchant'), 'Should have merchant relation');
        $this->assertTrue(method_exists($model, 'shipping'), 'Should have shipping relation');
    }

    public function test_shipment_tracking_has_required_scopes(): void
    {
        $model = new ShipmentTracking();

        $this->assertTrue(method_exists($model, 'scopeForPurchase'), 'Should have forPurchase scope');
        $this->assertTrue(method_exists($model, 'scopeForMerchant'), 'Should have forMerchant scope');
        $this->assertTrue(method_exists($model, 'scopeByTracking'), 'Should have byTracking scope');
        $this->assertTrue(method_exists($model, 'scopeLatestFirst'), 'Should have latestFirst scope');
        $this->assertTrue(method_exists($model, 'scopeActive'), 'Should have active scope');
        $this->assertTrue(method_exists($model, 'scopeCompleted'), 'Should have completed scope');
    }

    public function test_shipment_tracking_has_status_constants(): void
    {
        $this->assertEquals('created', ShipmentTracking::STATUS_CREATED);
        $this->assertEquals('delivered', ShipmentTracking::STATUS_DELIVERED);
        $this->assertEquals('returned', ShipmentTracking::STATUS_RETURNED);
        $this->assertIsArray(ShipmentTracking::FINAL_STATUSES);
    }

    public function test_shipment_tracking_has_static_methods(): void
    {
        $this->assertTrue(method_exists(ShipmentTracking::class, 'getStatusTranslation'));
        $this->assertTrue(method_exists(ShipmentTracking::class, 'getAllStatuses'));
        $this->assertTrue(method_exists(ShipmentTracking::class, 'canTransition'));
        $this->assertTrue(method_exists(ShipmentTracking::class, 'getLatestForPurchase'));
    }

    // ==========================================
    // DeliveryCourier Tests
    // ==========================================

    public function test_delivery_courier_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(DeliveryCourier::class, DomainDeliveryCourier::class),
            'DeliveryCourier should extend Domain DeliveryCourier'
        );
    }

    public function test_delivery_courier_table_name(): void
    {
        $model = new DeliveryCourier();
        $this->assertEquals('delivery_couriers', $model->getTable());
    }

    public function test_delivery_courier_has_required_relations(): void
    {
        $model = new DeliveryCourier();

        $this->assertTrue(method_exists($model, 'courier'), 'Should have courier relation');
        $this->assertTrue(method_exists($model, 'purchase'), 'Should have purchase relation');
        $this->assertTrue(method_exists($model, 'merchant'), 'Should have merchant relation');
        $this->assertTrue(method_exists($model, 'merchantBranch'), 'Should have merchantBranch relation');
        $this->assertTrue(method_exists($model, 'servicearea'), 'Should have servicearea relation');
    }

    public function test_delivery_courier_has_status_methods(): void
    {
        $model = new DeliveryCourier();

        $this->assertTrue(method_exists($model, 'isPendingApproval'), 'Should have isPendingApproval method');
        $this->assertTrue(method_exists($model, 'isApproved'), 'Should have isApproved method');
        $this->assertTrue(method_exists($model, 'isDelivered'), 'Should have isDelivered method');
        $this->assertTrue(method_exists($model, 'isCompleted'), 'Should have isCompleted method');
        $this->assertTrue(method_exists($model, 'isCod'), 'Should have isCod method');
    }

    public function test_delivery_courier_has_transition_methods(): void
    {
        $model = new DeliveryCourier();

        $this->assertTrue(method_exists($model, 'approve'), 'Should have approve method');
        $this->assertTrue(method_exists($model, 'reject'), 'Should have reject method');
        $this->assertTrue(method_exists($model, 'markReadyForPickup'), 'Should have markReadyForPickup method');
        $this->assertTrue(method_exists($model, 'markAsDelivered'), 'Should have markAsDelivered method');
        $this->assertTrue(method_exists($model, 'canTransitionTo'), 'Should have canTransitionTo method');
    }

    public function test_delivery_courier_has_status_constants(): void
    {
        $this->assertEquals('pending_approval', DeliveryCourier::STATUS_PENDING_APPROVAL);
        $this->assertEquals('delivered', DeliveryCourier::STATUS_DELIVERED);
        $this->assertEquals('cod', DeliveryCourier::PAYMENT_COD);
        $this->assertIsArray(DeliveryCourier::STATUS_TRANSITIONS);
    }

    // ==========================================
    // CourierServiceArea Tests
    // ==========================================

    public function test_courier_service_area_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(CourierServiceArea::class, DomainCourierServiceArea::class),
            'CourierServiceArea should extend Domain CourierServiceArea'
        );
    }

    public function test_courier_service_area_table_name(): void
    {
        $model = new CourierServiceArea();
        $this->assertEquals('courier_service_areas', $model->getTable());
    }

    public function test_courier_service_area_has_required_relations(): void
    {
        $model = new CourierServiceArea();

        $this->assertTrue(method_exists($model, 'city'), 'Should have city relation');
        $this->assertTrue(method_exists($model, 'courier'), 'Should have courier relation');
    }

    public function test_courier_service_area_has_geo_scopes(): void
    {
        $model = new CourierServiceArea();

        $this->assertTrue(method_exists($model, 'scopeWithinRadius'), 'Should have withinRadius scope');
        $this->assertTrue(method_exists($model, 'scopeWithActiveCourier'), 'Should have withActiveCourier scope');
        $this->assertTrue(method_exists($model, 'scopeActive'), 'Should have active scope');
    }

    public function test_courier_service_area_has_helper_methods(): void
    {
        $model = new CourierServiceArea();

        $this->assertTrue(method_exists($model, 'isLocationCovered'), 'Should have isLocationCovered method');
        $this->assertTrue(method_exists($model, 'calculateDistance'), 'Should have calculateDistance method');
    }

    // ==========================================
    // City Tests
    // ==========================================

    public function test_city_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(City::class, DomainCity::class),
            'City should extend Domain City'
        );
    }

    public function test_city_table_name(): void
    {
        $model = new City();
        $this->assertEquals('cities', $model->getTable());
    }

    public function test_city_has_required_relations(): void
    {
        $model = new City();

        $this->assertTrue(method_exists($model, 'country'), 'Should have country relation');
        $this->assertTrue(method_exists($model, 'courierServiceAreas'), 'Should have courierServiceAreas relation');
    }

    public function test_city_has_required_scopes(): void
    {
        $model = new City();

        $this->assertTrue(method_exists($model, 'scopeActive'), 'Should have active scope');
        $this->assertTrue(method_exists($model, 'scopeTryotoSupported'), 'Should have tryotoSupported scope');
        $this->assertTrue(method_exists($model, 'scopeByCountry'), 'Should have byCountry scope');
    }

    // ==========================================
    // Country Tests
    // ==========================================

    public function test_country_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(Country::class, DomainCountry::class),
            'Country should extend Domain Country'
        );
    }

    public function test_country_table_name(): void
    {
        $model = new Country();
        $this->assertEquals('countries', $model->getTable());
    }

    public function test_country_has_required_relations(): void
    {
        $model = new Country();

        $this->assertTrue(method_exists($model, 'cities'), 'Should have cities relation');
    }

    public function test_country_has_required_scopes(): void
    {
        $model = new Country();

        $this->assertTrue(method_exists($model, 'scopeActive'), 'Should have active scope');
        $this->assertTrue(method_exists($model, 'scopeSynced'), 'Should have synced scope');
        $this->assertTrue(method_exists($model, 'scopeByCode'), 'Should have byCode scope');
    }

    public function test_country_has_helper_methods(): void
    {
        $model = new Country();

        $this->assertTrue(method_exists($model, 'getLocalizedName'), 'Should have getLocalizedName method');
        $this->assertTrue(method_exists($model, 'hasTax'), 'Should have hasTax method');
        $this->assertTrue(method_exists($model, 'calculateTax'), 'Should have calculateTax method');
    }

    // ==========================================
    // Relation Type Tests
    // ==========================================

    public function test_city_country_relation_type(): void
    {
        $model = new City();
        $relation = $model->country();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relation,
            'country should be a BelongsTo relation'
        );
    }

    public function test_country_cities_relation_type(): void
    {
        $model = new Country();
        $relation = $model->cities();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $relation,
            'cities should be a HasMany relation'
        );
    }

    public function test_courier_service_area_city_relation_type(): void
    {
        $model = new CourierServiceArea();
        $relation = $model->city();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relation,
            'city should be a BelongsTo relation'
        );
    }

    // ==========================================
    // Scope Query Tests
    // ==========================================

    public function test_shipping_scopes_work(): void
    {
        $merchantId = 1;
        $sql = Shipping::forMerchant($merchantId)->toSql();
        $this->assertStringContainsString('status', $sql);
    }

    public function test_shipment_tracking_scopes_work(): void
    {
        $sql = ShipmentTracking::active()->toSql();
        $this->assertStringContainsString('status', $sql);
    }

    public function test_delivery_courier_scopes_work(): void
    {
        $sql = DeliveryCourier::active()->toSql();
        $this->assertStringContainsString('status', $sql);

        $sql2 = DeliveryCourier::cod()->toSql();
        $this->assertStringContainsString('payment_method', $sql2);
    }
}
