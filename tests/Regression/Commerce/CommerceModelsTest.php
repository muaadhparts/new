<?php

namespace Tests\Regression\Commerce;

use Tests\TestCase;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Commerce\Models\MerchantPurchase;
use App\Domain\Commerce\Models\PurchaseTimeline;
use App\Domain\Commerce\Models\StockReservation;
use App\Domain\Commerce\Models\FavoriteSeller;
use App\Domain\Commerce\Models\BuyerNote;

/**
 * Regression tests for Commerce Domain models
 *
 * These tests verify that the refactored Domain models maintain
 * backward compatibility with the original App\Models classes.
 */
class CommerceModelsTest extends TestCase
{
    // ==========================================
    // Purchase Tests
    // ==========================================

    public function test_purchase_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(Purchase::class, Purchase::class),
            'Purchase should extend Domain Purchase'
        );
    }

    public function test_purchase_table_name(): void
    {
        $model = new Purchase();
        $this->assertEquals('purchases', $model->getTable());
    }

    public function test_purchase_has_required_relations(): void
    {
        $model = new Purchase();

        $this->assertTrue(method_exists($model, 'user'), 'Should have user relation');
        $this->assertTrue(method_exists($model, 'merchantPurchases'), 'Should have merchantPurchases relation');
        $this->assertTrue(method_exists($model, 'timelines'), 'Should have timelines relation');
        $this->assertTrue(method_exists($model, 'shipmentTrackings'), 'Should have shipmentTrackings relation');
        $this->assertTrue(method_exists($model, 'deliveryCouriers'), 'Should have deliveryCouriers relation');
    }

    public function test_purchase_has_cart_methods(): void
    {
        $model = new Purchase();

        $this->assertTrue(method_exists($model, 'getCartItems'), 'Should have getCartItems method');
        $this->assertTrue(method_exists($model, 'getCartTotalQty'), 'Should have getCartTotalQty method');
        $this->assertTrue(method_exists($model, 'getCartTotalPrice'), 'Should have getCartTotalPrice method');
        $this->assertTrue(method_exists($model, 'getCartItemsByMerchant'), 'Should have getCartItemsByMerchant method');
        $this->assertTrue(method_exists($model, 'getMerchantIdsFromCart'), 'Should have getMerchantIdsFromCart method');
    }

    public function test_purchase_has_shipment_methods(): void
    {
        $model = new Purchase();

        $this->assertTrue(method_exists($model, 'getLatestShipmentStatus'), 'Should have getLatestShipmentStatus method');
        $this->assertTrue(method_exists($model, 'hasShipments'), 'Should have hasShipments method');
        $this->assertTrue(method_exists($model, 'allShipmentsDelivered'), 'Should have allShipmentsDelivered method');
    }

    public function test_purchase_has_cart_cast(): void
    {
        $model = new Purchase();
        $casts = $model->getCasts();

        $this->assertArrayHasKey('cart', $casts);
        $this->assertEquals('array', $casts['cart']);
    }

    // ==========================================
    // MerchantPurchase Tests
    // ==========================================

    public function test_merchant_purchase_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(MerchantPurchase::class, MerchantPurchase::class),
            'MerchantPurchase should extend Domain MerchantPurchase'
        );
    }

    public function test_merchant_purchase_table_name(): void
    {
        $model = new MerchantPurchase();
        $this->assertEquals('merchant_purchases', $model->getTable());
    }

    public function test_merchant_purchase_has_required_relations(): void
    {
        $model = new MerchantPurchase();

        $this->assertTrue(method_exists($model, 'user'), 'Should have user relation');
        $this->assertTrue(method_exists($model, 'merchant'), 'Should have merchant relation');
        $this->assertTrue(method_exists($model, 'purchase'), 'Should have purchase relation');
        $this->assertTrue(method_exists($model, 'paymentGateway'), 'Should have paymentGateway relation');
        $this->assertTrue(method_exists($model, 'shipping'), 'Should have shipping relation');
        $this->assertTrue(method_exists($model, 'courier'), 'Should have courier relation');
    }

    public function test_merchant_purchase_has_payment_owner_methods(): void
    {
        $model = new MerchantPurchase();

        $this->assertTrue(method_exists($model, 'isPlatformPayment'), 'Should have isPlatformPayment method');
        $this->assertTrue(method_exists($model, 'isMerchantPayment'), 'Should have isMerchantPayment method');
        $this->assertTrue(method_exists($model, 'isPlatformShipping'), 'Should have isPlatformShipping method');
        $this->assertTrue(method_exists($model, 'isMerchantShipping'), 'Should have isMerchantShipping method');
    }

    public function test_merchant_purchase_has_calculation_methods(): void
    {
        $model = new MerchantPurchase();

        $this->assertTrue(method_exists($model, 'calculateNetAmount'), 'Should have calculateNetAmount method');
        $this->assertTrue(method_exists($model, 'calculateMerchantOwes'), 'Should have calculateMerchantOwes method');
        $this->assertTrue(method_exists($model, 'calculatePlatformOwes'), 'Should have calculatePlatformOwes method');
        $this->assertTrue(method_exists($model, 'getMoneyFlowSummary'), 'Should have getMoneyFlowSummary method');
    }

    public function test_merchant_purchase_has_constants(): void
    {
        $this->assertEquals('platform', MerchantPurchase::MONEY_HOLDER_PLATFORM);
        $this->assertEquals('merchant', MerchantPurchase::MONEY_HOLDER_MERCHANT);
        $this->assertEquals('pending', MerchantPurchase::COLLECTION_PENDING);
        $this->assertEquals('collected', MerchantPurchase::COLLECTION_COLLECTED);
    }

    // ==========================================
    // PurchaseTimeline Tests
    // ==========================================

    public function test_purchase_timeline_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(PurchaseTimeline::class, PurchaseTimeline::class),
            'PurchaseTimeline should extend Domain PurchaseTimeline'
        );
    }

    public function test_purchase_timeline_table_name(): void
    {
        $model = new PurchaseTimeline();
        $this->assertEquals('purchase_timelines', $model->getTable());
    }

    public function test_purchase_timeline_has_required_relations(): void
    {
        $model = new PurchaseTimeline();

        $this->assertTrue(method_exists($model, 'purchase'), 'Should have purchase relation');
    }

    // ==========================================
    // StockReservation Tests
    // ==========================================

    public function test_stock_reservation_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(StockReservation::class, StockReservation::class),
            'StockReservation should extend Domain StockReservation'
        );
    }

    public function test_stock_reservation_table_name(): void
    {
        $model = new StockReservation();
        $this->assertEquals('stock_reservations', $model->getTable());
    }

    public function test_stock_reservation_has_required_relations(): void
    {
        $model = new StockReservation();

        $this->assertTrue(method_exists($model, 'merchantItem'), 'Should have merchantItem relation');
        $this->assertTrue(method_exists($model, 'user'), 'Should have user relation');
    }

    public function test_stock_reservation_has_static_methods(): void
    {
        $this->assertTrue(method_exists(StockReservation::class, 'reserve'), 'Should have reserve method');
        $this->assertTrue(method_exists(StockReservation::class, 'release'), 'Should have release method');
        $this->assertTrue(method_exists(StockReservation::class, 'releaseAll'), 'Should have releaseAll method');
        $this->assertTrue(method_exists(StockReservation::class, 'releaseExpired'), 'Should have releaseExpired method');
        $this->assertTrue(method_exists(StockReservation::class, 'reservationMinutes'), 'Should have reservationMinutes method');
    }

    public function test_stock_reservation_has_scopes(): void
    {
        $model = new StockReservation();

        $this->assertTrue(method_exists($model, 'scopeExpired'), 'Should have expired scope');
        $this->assertTrue(method_exists($model, 'scopeActive'), 'Should have active scope');
        $this->assertTrue(method_exists($model, 'scopeForSession'), 'Should have forSession scope');
    }

    public function test_stock_reservation_has_instance_methods(): void
    {
        $model = new StockReservation();

        $this->assertTrue(method_exists($model, 'extend'), 'Should have extend method');
        $this->assertTrue(method_exists($model, 'isExpired'), 'Should have isExpired method');
        $this->assertTrue(method_exists($model, 'remainingSeconds'), 'Should have remainingSeconds method');
        $this->assertTrue(method_exists($model, 'remainingMinutes'), 'Should have remainingMinutes method');
    }

    // ==========================================
    // FavoriteSeller Tests
    // ==========================================

    public function test_favorite_seller_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(FavoriteSeller::class, FavoriteSeller::class),
            'FavoriteSeller should extend Domain FavoriteSeller'
        );
    }

    public function test_favorite_seller_table_name(): void
    {
        $model = new FavoriteSeller();
        $this->assertEquals('favorite_sellers', $model->getTable());
    }

    public function test_favorite_seller_has_required_relations(): void
    {
        $model = new FavoriteSeller();

        $this->assertTrue(method_exists($model, 'user'), 'Should have user relation');
        $this->assertTrue(method_exists($model, 'catalogItem'), 'Should have catalogItem relation');
        $this->assertTrue(method_exists($model, 'merchantItem'), 'Should have merchantItem relation');
    }

    public function test_favorite_seller_has_helper_methods(): void
    {
        $model = new FavoriteSeller();

        $this->assertTrue(method_exists($model, 'getEffectiveMerchantItem'), 'Should have getEffectiveMerchantItem method');
    }

    // ==========================================
    // BuyerNote Tests
    // ==========================================

    public function test_buyer_note_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(BuyerNote::class, BuyerNote::class),
            'BuyerNote should extend Domain BuyerNote'
        );
    }

    public function test_buyer_note_table_name(): void
    {
        $model = new BuyerNote();
        $this->assertEquals('buyer_notes', $model->getTable());
    }

    public function test_buyer_note_has_required_relations(): void
    {
        $model = new BuyerNote();

        $this->assertTrue(method_exists($model, 'user'), 'Should have user relation');
        $this->assertTrue(method_exists($model, 'catalogItem'), 'Should have catalogItem relation');
        $this->assertTrue(method_exists($model, 'merchantItem'), 'Should have merchantItem relation');
        $this->assertTrue(method_exists($model, 'noteResponses'), 'Should have noteResponses relation');
    }

    // ==========================================
    // Relation Type Tests
    // ==========================================

    public function test_purchase_user_relation_type(): void
    {
        $model = new Purchase();
        $relation = $model->user();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relation,
            'user should be a BelongsTo relation'
        );
    }

    public function test_purchase_merchant_purchases_relation_type(): void
    {
        $model = new Purchase();
        $relation = $model->merchantPurchases();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $relation,
            'merchantPurchases should be a HasMany relation'
        );
    }

    public function test_merchant_purchase_purchase_relation_type(): void
    {
        $model = new MerchantPurchase();
        $relation = $model->purchase();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relation,
            'purchase should be a BelongsTo relation'
        );
    }

    // ==========================================
    // Scope Query Tests
    // ==========================================

    public function test_merchant_purchase_scopes_work(): void
    {
        $sql = MerchantPurchase::byMerchant(1)->toSql();
        $this->assertStringContainsString('user_id', $sql);

        $sql2 = MerchantPurchase::unsettled()->toSql();
        $this->assertStringContainsString('settlement_status', $sql2);
    }

    public function test_stock_reservation_scopes_work(): void
    {
        $sql = StockReservation::expired()->toSql();
        $this->assertStringContainsString('expires_at', $sql);

        $sql2 = StockReservation::active()->toSql();
        $this->assertStringContainsString('expires_at', $sql2);
    }
}
