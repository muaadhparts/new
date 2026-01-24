<?php

namespace Tests\Regression\Domain;

use Tests\TestCase;

// Base Event
use App\Domain\Platform\Events\DomainEvent;

// Commerce Events
use App\Domain\Commerce\Events\OrderPlacedEvent;
use App\Domain\Commerce\Events\OrderStatusChangedEvent;
use App\Domain\Commerce\Events\PaymentReceivedEvent;

// Merchant Events
use App\Domain\Merchant\Events\StockUpdatedEvent;
use App\Domain\Merchant\Events\PriceChangedEvent;
use App\Domain\Merchant\Events\MerchantStatusChangedEvent;

// Catalog Events
use App\Domain\Catalog\Events\ProductReviewedEvent;
use App\Domain\Catalog\Events\ProductFavoritedEvent;
use App\Domain\Catalog\Events\ProductViewedEvent;

// Shipping Events
use App\Domain\Shipping\Events\ShipmentCreatedEvent;
use App\Domain\Shipping\Events\ShipmentStatusChangedEvent;
use App\Domain\Shipping\Events\DeliveryCompletedEvent;

// Identity Events
use App\Domain\Identity\Events\UserRegisteredEvent;
use App\Domain\Identity\Events\UserVerifiedEvent;
use App\Domain\Identity\Events\UserLoginEvent;

/**
 * Regression Tests for Domain Events
 *
 * Phase 14: Domain Events
 *
 * This test ensures that domain events are properly structured and functional.
 */
class DomainEventsTest extends TestCase
{
    // =========================================================================
    // ORDER PLACED EVENT
    // =========================================================================

    /** @test */
    public function order_placed_event_can_be_created()
    {
        $event = new OrderPlacedEvent(
            purchaseId: 1,
            customerId: 100,
            totalAmount: 500.00,
            currency: 'SAR',
            itemCount: 3,
            merchantIds: [10, 20]
        );

        $this->assertEquals(1, $event->purchaseId);
        $this->assertEquals(100, $event->customerId);
        $this->assertEquals(500.00, $event->totalAmount);
        $this->assertEquals('SAR', $event->currency);
        $this->assertEquals(3, $event->itemCount);
        $this->assertEquals([10, 20], $event->merchantIds);
    }

    /** @test */
    public function order_placed_event_has_domain_event_properties()
    {
        $event = new OrderPlacedEvent(1, 100, 500.00, 'SAR', 3);

        $this->assertEquals('Purchase', $event->aggregateType());
        $this->assertEquals(1, $event->aggregateId());
        $this->assertNotEmpty($event->eventId);
        $this->assertNotNull($event->occurredAt);
    }

    /** @test */
    public function order_placed_event_payload_is_correct()
    {
        $event = new OrderPlacedEvent(1, 100, 500.00, 'SAR', 3, [10]);

        $payload = $event->payload();

        $this->assertEquals(1, $payload['purchase_id']);
        $this->assertEquals(100, $payload['customer_id']);
        $this->assertEquals(500.00, $payload['total_amount']);
    }

    // =========================================================================
    // ORDER STATUS CHANGED EVENT
    // =========================================================================

    /** @test */
    public function order_status_changed_event_can_be_created()
    {
        $event = new OrderStatusChangedEvent(
            purchaseId: 1,
            previousStatus: 'pending',
            newStatus: 'processing',
            changedBy: 5
        );

        $this->assertEquals('pending', $event->previousStatus);
        $this->assertEquals('processing', $event->newStatus);
        $this->assertTrue($event->isProcessing());
    }

    /** @test */
    public function order_status_changed_detects_completion()
    {
        $event = new OrderStatusChangedEvent(1, 'processing', 'completed');

        $this->assertTrue($event->isCompleted());
        $this->assertFalse($event->isCancelled());
    }

    /** @test */
    public function order_status_changed_detects_cancellation()
    {
        $event = new OrderStatusChangedEvent(1, 'pending', 'cancelled');

        $this->assertTrue($event->isCancelled());
        $this->assertFalse($event->isCompleted());
    }

    // =========================================================================
    // PAYMENT RECEIVED EVENT
    // =========================================================================

    /** @test */
    public function payment_received_event_can_be_created()
    {
        $event = new PaymentReceivedEvent(
            purchaseId: 1,
            amount: 500.00,
            currency: 'SAR',
            paymentMethod: 'credit_card',
            transactionId: 'TXN123'
        );

        $this->assertEquals(500.00, $event->amount);
        $this->assertEquals('credit_card', $event->paymentMethod);
        $this->assertEquals('TXN123', $event->transactionId);
    }

    /** @test */
    public function payment_received_detects_full_payment()
    {
        $event = new PaymentReceivedEvent(1, 500.00, 'SAR', 'credit_card');

        $this->assertTrue($event->isFullPayment(500.00));
        $this->assertFalse($event->isFullPayment(600.00));
    }

    // =========================================================================
    // STOCK UPDATED EVENT
    // =========================================================================

    /** @test */
    public function stock_updated_event_can_be_created()
    {
        $event = new StockUpdatedEvent(
            merchantItemId: 1,
            merchantId: 10,
            catalogItemId: 100,
            previousStock: 50,
            newStock: 45,
            reason: 'sale'
        );

        $this->assertEquals(50, $event->previousStock);
        $this->assertEquals(45, $event->newStock);
        $this->assertEquals(-5, $event->stockChange());
    }

    /** @test */
    public function stock_updated_detects_increase_decrease()
    {
        $decrease = new StockUpdatedEvent(1, 10, 100, 50, 45, 'sale');
        $increase = new StockUpdatedEvent(1, 10, 100, 45, 60, 'restock');

        $this->assertTrue($decrease->wasDecreased());
        $this->assertFalse($decrease->wasIncreased());

        $this->assertTrue($increase->wasIncreased());
        $this->assertFalse($increase->wasDecreased());
    }

    /** @test */
    public function stock_updated_detects_out_of_stock()
    {
        $outOfStock = new StockUpdatedEvent(1, 10, 100, 5, 0, 'sale');
        $stillHasStock = new StockUpdatedEvent(1, 10, 100, 50, 45, 'sale');

        $this->assertTrue($outOfStock->isNowOutOfStock());
        $this->assertFalse($stillHasStock->isNowOutOfStock());
    }

    /** @test */
    public function stock_updated_detects_low_stock()
    {
        $lowStock = new StockUpdatedEvent(1, 10, 100, 10, 3, 'sale');
        $normalStock = new StockUpdatedEvent(1, 10, 100, 50, 45, 'sale');

        $this->assertTrue($lowStock->isLowStock(5));
        $this->assertFalse($normalStock->isLowStock(5));
    }

    // =========================================================================
    // PRICE CHANGED EVENT
    // =========================================================================

    /** @test */
    public function price_changed_event_can_be_created()
    {
        $event = new PriceChangedEvent(
            merchantItemId: 1,
            merchantId: 10,
            catalogItemId: 100,
            previousPrice: 100.00,
            newPrice: 120.00
        );

        $this->assertEquals(100.00, $event->previousPrice);
        $this->assertEquals(120.00, $event->newPrice);
        $this->assertEquals(20.0, $event->priceChangePercent());
    }

    /** @test */
    public function price_changed_detects_increase_decrease()
    {
        $increase = new PriceChangedEvent(1, 10, 100, 100.00, 120.00);
        $decrease = new PriceChangedEvent(1, 10, 100, 100.00, 80.00);

        $this->assertTrue($increase->wasIncreased());
        $this->assertFalse($increase->wasDecreased());

        $this->assertTrue($decrease->wasDecreased());
        $this->assertFalse($decrease->wasIncreased());
    }

    /** @test */
    public function price_changed_detects_discount_changes()
    {
        $applied = new PriceChangedEvent(1, 10, 100, 100.00, 100.00, null, 20.0);
        $removed = new PriceChangedEvent(1, 10, 100, 100.00, 100.00, 20.0, null);

        $this->assertTrue($applied->discountWasApplied());
        $this->assertFalse($applied->discountWasRemoved());

        $this->assertTrue($removed->discountWasRemoved());
        $this->assertFalse($removed->discountWasApplied());
    }

    // =========================================================================
    // MERCHANT STATUS CHANGED EVENT
    // =========================================================================

    /** @test */
    public function merchant_status_changed_event_can_be_created()
    {
        $event = new MerchantStatusChangedEvent(
            merchantId: 10,
            previousStatus: 'pending',
            newStatus: 'active',
            reason: 'Approved'
        );

        $this->assertEquals('pending', $event->previousStatus);
        $this->assertEquals('active', $event->newStatus);
        $this->assertTrue($event->wasActivated());
    }

    /** @test */
    public function merchant_status_detects_transitions()
    {
        $activated = new MerchantStatusChangedEvent(10, 'pending', 'active');
        $deactivated = new MerchantStatusChangedEvent(10, 'active', 'inactive');
        $suspended = new MerchantStatusChangedEvent(10, 'active', 'suspended');

        $this->assertTrue($activated->wasActivated());
        $this->assertTrue($deactivated->wasDeactivated());
        $this->assertTrue($suspended->wasSuspended());
    }

    // =========================================================================
    // PRODUCT REVIEWED EVENT
    // =========================================================================

    /** @test */
    public function product_reviewed_event_can_be_created()
    {
        $event = new ProductReviewedEvent(
            reviewId: 1,
            catalogItemId: 100,
            customerId: 50,
            rating: 5,
            comment: 'Great product!'
        );

        $this->assertEquals(5, $event->rating);
        $this->assertTrue($event->hasComment());
        $this->assertTrue($event->isPositive());
    }

    /** @test */
    public function product_reviewed_detects_sentiment()
    {
        $positive = new ProductReviewedEvent(1, 100, 50, 5);
        $negative = new ProductReviewedEvent(1, 100, 50, 1);
        $neutral = new ProductReviewedEvent(1, 100, 50, 3);

        $this->assertTrue($positive->isPositive());
        $this->assertFalse($positive->isNegative());

        $this->assertTrue($negative->isNegative());
        $this->assertFalse($negative->isPositive());

        $this->assertFalse($neutral->isPositive());
        $this->assertFalse($neutral->isNegative());
    }

    // =========================================================================
    // PRODUCT FAVORITED EVENT
    // =========================================================================

    /** @test */
    public function product_favorited_event_can_be_created()
    {
        $added = new ProductFavoritedEvent(100, 50, true);
        $removed = new ProductFavoritedEvent(100, 50, false);

        $this->assertTrue($added->wasAdded());
        $this->assertFalse($added->wasRemoved());

        $this->assertTrue($removed->wasRemoved());
        $this->assertFalse($removed->wasAdded());
    }

    // =========================================================================
    // PRODUCT VIEWED EVENT
    // =========================================================================

    /** @test */
    public function product_viewed_event_can_be_created()
    {
        $authenticated = new ProductViewedEvent(100, 50, null, 'search');
        $guest = new ProductViewedEvent(100, null, 'session123', 'category');

        $this->assertTrue($authenticated->isAuthenticated());
        $this->assertTrue($authenticated->isFromSearch());

        $this->assertFalse($guest->isAuthenticated());
        $this->assertTrue($guest->isFromCategory());
    }

    // =========================================================================
    // SHIPMENT CREATED EVENT
    // =========================================================================

    /** @test */
    public function shipment_created_event_can_be_created()
    {
        $event = new ShipmentCreatedEvent(
            shipmentId: 1,
            purchaseId: 100,
            merchantId: 10,
            trackingNumber: 'TRK123456',
            carrier: 'SMSA',
            courierId: 5
        );

        $this->assertEquals('TRK123456', $event->trackingNumber);
        $this->assertEquals('SMSA', $event->carrier);
        $this->assertTrue($event->hasCourier());
    }

    // =========================================================================
    // SHIPMENT STATUS CHANGED EVENT
    // =========================================================================

    /** @test */
    public function shipment_status_changed_event_can_be_created()
    {
        $event = new ShipmentStatusChangedEvent(
            shipmentId: 1,
            purchaseId: 100,
            previousStatus: 'pending',
            newStatus: 'in_transit',
            location: 'Riyadh Sorting Center'
        );

        $this->assertTrue($event->isInTransit());
        $this->assertFalse($event->isDelivered());
    }

    /** @test */
    public function shipment_status_detects_completion()
    {
        $delivered = new ShipmentStatusChangedEvent(1, 100, 'out_for_delivery', 'delivered');
        $failed = new ShipmentStatusChangedEvent(1, 100, 'out_for_delivery', 'failed');
        $returned = new ShipmentStatusChangedEvent(1, 100, 'failed', 'returned');

        $this->assertTrue($delivered->isDelivered());
        $this->assertTrue($failed->isFailed());
        $this->assertTrue($returned->wasReturned());
    }

    // =========================================================================
    // DELIVERY COMPLETED EVENT
    // =========================================================================

    /** @test */
    public function delivery_completed_event_can_be_created()
    {
        $event = new DeliveryCompletedEvent(
            shipmentId: 1,
            purchaseId: 100,
            customerId: 50,
            courierId: 5,
            receivedBy: 'Ahmed',
            signature: 'base64signature'
        );

        $this->assertTrue($event->wasSigned());
        $this->assertTrue($event->wasReceivedByProxy());
    }

    // =========================================================================
    // USER REGISTERED EVENT
    // =========================================================================

    /** @test */
    public function user_registered_event_can_be_created()
    {
        $event = new UserRegisteredEvent(
            userId: 1,
            email: 'test@example.com',
            role: 'user',
            registrationSource: 'google',
            referrerId: 100
        );

        $this->assertTrue($event->isCustomer());
        $this->assertTrue($event->isSocialRegistration());
        $this->assertTrue($event->wasReferred());
    }

    /** @test */
    public function user_registered_detects_role()
    {
        $customer = new UserRegisteredEvent(1, 'test@example.com', 'user');
        $merchant = new UserRegisteredEvent(1, 'test@example.com', 'merchant');

        $this->assertTrue($customer->isCustomer());
        $this->assertFalse($customer->isMerchant());

        $this->assertTrue($merchant->isMerchant());
        $this->assertFalse($merchant->isCustomer());
    }

    // =========================================================================
    // USER VERIFIED EVENT
    // =========================================================================

    /** @test */
    public function user_verified_event_can_be_created()
    {
        $emailVerified = new UserVerifiedEvent(1, 'email', 'test@example.com');
        $phoneVerified = new UserVerifiedEvent(1, 'phone', '+966501234567');

        $this->assertTrue($emailVerified->isEmailVerification());
        $this->assertTrue($phoneVerified->isPhoneVerification());
    }

    /** @test */
    public function user_verified_masks_sensitive_data()
    {
        $emailEvent = new UserVerifiedEvent(1, 'email', 'test@example.com');
        $phoneEvent = new UserVerifiedEvent(1, 'phone', '+966501234567');

        $this->assertStringContainsString('**', $emailEvent->maskedValue());
        $this->assertStringContainsString('****', $phoneEvent->maskedValue());
        $this->assertStringEndsWith('4567', $phoneEvent->maskedValue());
    }

    // =========================================================================
    // USER LOGIN EVENT
    // =========================================================================

    /** @test */
    public function user_login_event_can_be_created()
    {
        $event = new UserLoginEvent(
            userId: 1,
            loginMethod: 'password',
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
            rememberMe: true
        );

        $this->assertTrue($event->isPasswordLogin());
        $this->assertTrue($event->usedRememberMe());
        $this->assertFalse($event->isSocialLogin());
    }

    /** @test */
    public function user_login_detects_social_login()
    {
        $google = new UserLoginEvent(1, 'google');
        $facebook = new UserLoginEvent(1, 'facebook');
        $password = new UserLoginEvent(1, 'password');

        $this->assertTrue($google->isSocialLogin());
        $this->assertTrue($facebook->isSocialLogin());
        $this->assertFalse($password->isSocialLogin());
    }

    // =========================================================================
    // DOMAIN EVENT BASE CLASS
    // =========================================================================

    /** @test */
    public function domain_event_generates_unique_event_id()
    {
        $event1 = new OrderPlacedEvent(1, 100, 500.00, 'SAR', 3);
        $event2 = new OrderPlacedEvent(1, 100, 500.00, 'SAR', 3);

        $this->assertNotEquals($event1->eventId, $event2->eventId);
    }

    /** @test */
    public function domain_event_can_convert_to_array()
    {
        $event = new OrderPlacedEvent(1, 100, 500.00, 'SAR', 3);

        $array = $event->toArray();

        $this->assertArrayHasKey('event_id', $array);
        $this->assertArrayHasKey('event_name', $array);
        $this->assertArrayHasKey('aggregate_type', $array);
        $this->assertArrayHasKey('aggregate_id', $array);
        $this->assertArrayHasKey('occurred_at', $array);
        $this->assertArrayHasKey('payload', $array);
    }

    // =========================================================================
    // DIRECTORY STRUCTURE
    // =========================================================================

    /** @test */
    public function platform_events_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Platform/Events'));
    }

    /** @test */
    public function commerce_events_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Commerce/Events'));
    }

    /** @test */
    public function merchant_events_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Merchant/Events'));
    }

    /** @test */
    public function catalog_events_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Catalog/Events'));
    }

    /** @test */
    public function shipping_events_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Shipping/Events'));
    }

    /** @test */
    public function identity_events_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Identity/Events'));
    }
}
