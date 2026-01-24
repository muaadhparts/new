<?php

namespace Tests\Regression\Domain;

use Tests\TestCase;

// Commerce Listeners
use App\Domain\Commerce\Listeners\SendOrderConfirmationListener;
use App\Domain\Commerce\Listeners\NotifyMerchantsListener;
use App\Domain\Commerce\Listeners\UpdateOrderStatusListener;

// Merchant Listeners
use App\Domain\Merchant\Listeners\LogStockChangeListener;
use App\Domain\Merchant\Listeners\NotifyLowStockListener;
use App\Domain\Merchant\Listeners\NotifyOutOfStockListener;

// Shipping Listeners
use App\Domain\Shipping\Listeners\SendShippingNotificationListener;
use App\Domain\Shipping\Listeners\SendDeliveryConfirmationListener;
use App\Domain\Shipping\Listeners\UpdateShipmentTrackingListener;

// Identity Listeners
use App\Domain\Identity\Listeners\SendWelcomeEmailListener;
use App\Domain\Identity\Listeners\LogUserActivityListener;
use App\Domain\Identity\Listeners\SendVerificationEmailListener;

// Catalog Listeners
use App\Domain\Catalog\Listeners\UpdateProductRatingListener;
use App\Domain\Catalog\Listeners\NotifyMerchantOfReviewListener;
use App\Domain\Catalog\Listeners\TrackProductViewListener;

// Events for type checking
use App\Domain\Commerce\Events\OrderPlacedEvent;
use App\Domain\Commerce\Events\PaymentReceivedEvent;
use App\Domain\Merchant\Events\StockUpdatedEvent;
use App\Domain\Shipping\Events\ShipmentCreatedEvent;
use App\Domain\Shipping\Events\DeliveryCompletedEvent;
use App\Domain\Shipping\Events\ShipmentStatusChangedEvent;
use App\Domain\Identity\Events\UserRegisteredEvent;
use App\Domain\Identity\Events\UserLoginEvent;
use App\Domain\Catalog\Events\ProductReviewedEvent;
use App\Domain\Catalog\Events\ProductViewedEvent;

use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Regression Tests for Event Listeners
 *
 * Phase 17: Event Listeners
 *
 * This test ensures that event listeners are properly structured and functional.
 */
class EventListenersTest extends TestCase
{
    // =========================================================================
    // COMMERCE LISTENERS
    // =========================================================================

    /** @test */
    public function send_order_confirmation_listener_can_be_instantiated()
    {
        $listener = new SendOrderConfirmationListener();
        $this->assertInstanceOf(SendOrderConfirmationListener::class, $listener);
    }

    /** @test */
    public function send_order_confirmation_listener_is_queueable()
    {
        $this->assertTrue(
            in_array(ShouldQueue::class, class_implements(SendOrderConfirmationListener::class))
        );
    }

    /** @test */
    public function send_order_confirmation_listener_has_handle_method()
    {
        $this->assertTrue(method_exists(SendOrderConfirmationListener::class, 'handle'));

        $reflection = new \ReflectionMethod(SendOrderConfirmationListener::class, 'handle');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals(OrderPlacedEvent::class, $parameters[0]->getType()->getName());
    }

    /** @test */
    public function notify_merchants_listener_can_be_instantiated()
    {
        $listener = new NotifyMerchantsListener();
        $this->assertInstanceOf(NotifyMerchantsListener::class, $listener);
    }

    /** @test */
    public function notify_merchants_listener_is_queueable()
    {
        $this->assertTrue(
            in_array(ShouldQueue::class, class_implements(NotifyMerchantsListener::class))
        );
    }

    /** @test */
    public function update_order_status_listener_can_be_instantiated()
    {
        $listener = new UpdateOrderStatusListener();
        $this->assertInstanceOf(UpdateOrderStatusListener::class, $listener);
    }

    /** @test */
    public function update_order_status_listener_handles_payment_event()
    {
        $reflection = new \ReflectionMethod(UpdateOrderStatusListener::class, 'handle');
        $parameters = $reflection->getParameters();

        $this->assertEquals(PaymentReceivedEvent::class, $parameters[0]->getType()->getName());
    }

    // =========================================================================
    // MERCHANT LISTENERS
    // =========================================================================

    /** @test */
    public function log_stock_change_listener_can_be_instantiated()
    {
        $listener = new LogStockChangeListener();
        $this->assertInstanceOf(LogStockChangeListener::class, $listener);
    }

    /** @test */
    public function log_stock_change_listener_is_queueable()
    {
        $this->assertTrue(
            in_array(ShouldQueue::class, class_implements(LogStockChangeListener::class))
        );
    }

    /** @test */
    public function log_stock_change_listener_handles_stock_event()
    {
        $reflection = new \ReflectionMethod(LogStockChangeListener::class, 'handle');
        $parameters = $reflection->getParameters();

        $this->assertEquals(StockUpdatedEvent::class, $parameters[0]->getType()->getName());
    }

    /** @test */
    public function notify_low_stock_listener_can_be_instantiated()
    {
        $listener = new NotifyLowStockListener();
        $this->assertInstanceOf(NotifyLowStockListener::class, $listener);
    }

    /** @test */
    public function notify_out_of_stock_listener_can_be_instantiated()
    {
        $listener = new NotifyOutOfStockListener();
        $this->assertInstanceOf(NotifyOutOfStockListener::class, $listener);
    }

    // =========================================================================
    // SHIPPING LISTENERS
    // =========================================================================

    /** @test */
    public function send_shipping_notification_listener_can_be_instantiated()
    {
        $listener = new SendShippingNotificationListener();
        $this->assertInstanceOf(SendShippingNotificationListener::class, $listener);
    }

    /** @test */
    public function send_shipping_notification_listener_is_queueable()
    {
        $this->assertTrue(
            in_array(ShouldQueue::class, class_implements(SendShippingNotificationListener::class))
        );
    }

    /** @test */
    public function send_shipping_notification_handles_shipment_created()
    {
        $reflection = new \ReflectionMethod(SendShippingNotificationListener::class, 'handle');
        $parameters = $reflection->getParameters();

        $this->assertEquals(ShipmentCreatedEvent::class, $parameters[0]->getType()->getName());
    }

    /** @test */
    public function send_delivery_confirmation_listener_can_be_instantiated()
    {
        $listener = new SendDeliveryConfirmationListener();
        $this->assertInstanceOf(SendDeliveryConfirmationListener::class, $listener);
    }

    /** @test */
    public function send_delivery_confirmation_handles_delivery_completed()
    {
        $reflection = new \ReflectionMethod(SendDeliveryConfirmationListener::class, 'handle');
        $parameters = $reflection->getParameters();

        $this->assertEquals(DeliveryCompletedEvent::class, $parameters[0]->getType()->getName());
    }

    /** @test */
    public function update_shipment_tracking_listener_can_be_instantiated()
    {
        $listener = new UpdateShipmentTrackingListener();
        $this->assertInstanceOf(UpdateShipmentTrackingListener::class, $listener);
    }

    /** @test */
    public function update_shipment_tracking_handles_status_changed()
    {
        $reflection = new \ReflectionMethod(UpdateShipmentTrackingListener::class, 'handle');
        $parameters = $reflection->getParameters();

        $this->assertEquals(ShipmentStatusChangedEvent::class, $parameters[0]->getType()->getName());
    }

    // =========================================================================
    // IDENTITY LISTENERS
    // =========================================================================

    /** @test */
    public function send_welcome_email_listener_can_be_instantiated()
    {
        $listener = new SendWelcomeEmailListener();
        $this->assertInstanceOf(SendWelcomeEmailListener::class, $listener);
    }

    /** @test */
    public function send_welcome_email_listener_is_queueable()
    {
        $this->assertTrue(
            in_array(ShouldQueue::class, class_implements(SendWelcomeEmailListener::class))
        );
    }

    /** @test */
    public function send_welcome_email_handles_user_registered()
    {
        $reflection = new \ReflectionMethod(SendWelcomeEmailListener::class, 'handle');
        $parameters = $reflection->getParameters();

        $this->assertEquals(UserRegisteredEvent::class, $parameters[0]->getType()->getName());
    }

    /** @test */
    public function log_user_activity_listener_can_be_instantiated()
    {
        $listener = new LogUserActivityListener();
        $this->assertInstanceOf(LogUserActivityListener::class, $listener);
    }

    /** @test */
    public function log_user_activity_handles_user_login()
    {
        $reflection = new \ReflectionMethod(LogUserActivityListener::class, 'handle');
        $parameters = $reflection->getParameters();

        $this->assertEquals(UserLoginEvent::class, $parameters[0]->getType()->getName());
    }

    /** @test */
    public function send_verification_email_listener_can_be_instantiated()
    {
        $listener = new SendVerificationEmailListener();
        $this->assertInstanceOf(SendVerificationEmailListener::class, $listener);
    }

    // =========================================================================
    // CATALOG LISTENERS
    // =========================================================================

    /** @test */
    public function update_product_rating_listener_can_be_instantiated()
    {
        $listener = new UpdateProductRatingListener();
        $this->assertInstanceOf(UpdateProductRatingListener::class, $listener);
    }

    /** @test */
    public function update_product_rating_listener_is_queueable()
    {
        $this->assertTrue(
            in_array(ShouldQueue::class, class_implements(UpdateProductRatingListener::class))
        );
    }

    /** @test */
    public function update_product_rating_handles_product_reviewed()
    {
        $reflection = new \ReflectionMethod(UpdateProductRatingListener::class, 'handle');
        $parameters = $reflection->getParameters();

        $this->assertEquals(ProductReviewedEvent::class, $parameters[0]->getType()->getName());
    }

    /** @test */
    public function notify_merchant_of_review_listener_can_be_instantiated()
    {
        $listener = new NotifyMerchantOfReviewListener();
        $this->assertInstanceOf(NotifyMerchantOfReviewListener::class, $listener);
    }

    /** @test */
    public function track_product_view_listener_can_be_instantiated()
    {
        $listener = new TrackProductViewListener();
        $this->assertInstanceOf(TrackProductViewListener::class, $listener);
    }

    /** @test */
    public function track_product_view_handles_product_viewed()
    {
        $reflection = new \ReflectionMethod(TrackProductViewListener::class, 'handle');
        $parameters = $reflection->getParameters();

        $this->assertEquals(ProductViewedEvent::class, $parameters[0]->getType()->getName());
    }

    // =========================================================================
    // LISTENER PROPERTIES
    // =========================================================================

    /** @test */
    public function listeners_have_retry_configuration()
    {
        $listeners = [
            SendOrderConfirmationListener::class,
            NotifyMerchantsListener::class,
            LogStockChangeListener::class,
            SendShippingNotificationListener::class,
            SendWelcomeEmailListener::class,
            UpdateProductRatingListener::class,
        ];

        foreach ($listeners as $listenerClass) {
            $listener = new $listenerClass();
            $this->assertIsInt($listener->tries, "{$listenerClass} should have tries property");
            $this->assertGreaterThan(0, $listener->tries);
        }
    }

    /** @test */
    public function listeners_have_failed_method()
    {
        $listeners = [
            SendOrderConfirmationListener::class,
            NotifyMerchantsListener::class,
            UpdateOrderStatusListener::class,
            LogStockChangeListener::class,
            NotifyLowStockListener::class,
            NotifyOutOfStockListener::class,
            SendShippingNotificationListener::class,
            SendDeliveryConfirmationListener::class,
            UpdateShipmentTrackingListener::class,
            SendWelcomeEmailListener::class,
            LogUserActivityListener::class,
            SendVerificationEmailListener::class,
            UpdateProductRatingListener::class,
            NotifyMerchantOfReviewListener::class,
            TrackProductViewListener::class,
        ];

        foreach ($listeners as $listenerClass) {
            $this->assertTrue(
                method_exists($listenerClass, 'failed'),
                "{$listenerClass} should have failed() method"
            );
        }
    }

    // =========================================================================
    // DIRECTORY STRUCTURE
    // =========================================================================

    /** @test */
    public function commerce_listeners_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Commerce/Listeners'));
    }

    /** @test */
    public function merchant_listeners_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Merchant/Listeners'));
    }

    /** @test */
    public function shipping_listeners_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Shipping/Listeners'));
    }

    /** @test */
    public function identity_listeners_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Identity/Listeners'));
    }

    /** @test */
    public function catalog_listeners_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Catalog/Listeners'));
    }
}
