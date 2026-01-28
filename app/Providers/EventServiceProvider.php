<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

// Domain Events
use App\Domain\Commerce\Events\PurchasePlacedEvent;
use App\Domain\Commerce\Events\PurchaseStatusChangedEvent;
use App\Domain\Commerce\Events\PaymentReceivedEvent;
use App\Domain\Shipping\Events\DeliveryCompletedEvent;
use App\Domain\Shipping\Events\ShipmentCreatedEvent;
use App\Domain\Shipping\Events\ShipmentStatusChangedEvent;
use App\Domain\Merchant\Events\StockUpdatedEvent;
use App\Domain\Merchant\Events\PriceChangedEvent;
use App\Domain\Merchant\Events\MerchantStatusChangedEvent;
use App\Domain\Identity\Events\UserRegisteredEvent;
use App\Domain\Identity\Events\UserLoginEvent;
use App\Domain\Identity\Events\UserVerifiedEvent;
use App\Domain\Catalog\Events\ProductViewedEvent;
use App\Domain\Catalog\Events\ProductReviewedEvent;
use App\Domain\Catalog\Events\ProductFavoritedEvent;

// Domain Listeners
use App\Domain\Platform\Listeners\LogDomainEventListener;
use App\Domain\Commerce\Listeners\SendPurchaseConfirmationListener;
use App\Domain\Commerce\Listeners\NotifyMerchantsOfPurchaseListener;
use App\Domain\Commerce\Listeners\SendPaymentConfirmationListener;
use App\Domain\Commerce\Listeners\NotifyPurchaseStatusChangeListener;
use App\Domain\Shipping\Listeners\SendDeliveryConfirmationListener;
use App\Domain\Shipping\Listeners\NotifyCustomerOfShipmentListener;
use App\Domain\Shipping\Listeners\NotifyShipmentStatusChangeListener;
use App\Domain\Merchant\Listeners\NotifyLowStockListener;
use App\Domain\Merchant\Listeners\NotifyPriceDropListener;
use App\Domain\Merchant\Listeners\NotifyMerchantStatusChangeListener;
use App\Domain\Identity\Listeners\LogUserActivityListener;
use App\Domain\Identity\Listeners\SendWelcomeEmailListener;
use App\Domain\Identity\Listeners\SendVerificationEmailListener;
use App\Domain\Catalog\Listeners\TrackProductViewListener;
use App\Domain\Catalog\Listeners\NotifyMerchantOfReviewListener;
use App\Domain\Catalog\Listeners\UpdateProductRatingListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Laravel Auth Events
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // ═══════════════════════════════════════════════════════════════════
        // DOMAIN EVENTS - Event-Driven Architecture Core
        // All channels (Web, Mobile, API, WhatsApp) dispatch same events
        // ═══════════════════════════════════════════════════════════════════

        // Commerce: Purchase Placed
        PurchasePlacedEvent::class => [
            LogDomainEventListener::class,
            SendPurchaseConfirmationListener::class,
            NotifyMerchantsOfPurchaseListener::class,
            // CreateAccountingEntriesListener::class, // TODO: Add
        ],

        // Commerce: Payment Received
        PaymentReceivedEvent::class => [
            LogDomainEventListener::class,
            SendPaymentConfirmationListener::class,
        ],

        // Commerce: Purchase Status Changed
        PurchaseStatusChangedEvent::class => [
            LogDomainEventListener::class,
            NotifyPurchaseStatusChangeListener::class,
        ],

        // Shipping: Shipment Created
        ShipmentCreatedEvent::class => [
            LogDomainEventListener::class,
            NotifyCustomerOfShipmentListener::class,
        ],

        // Shipping: Shipment Status Changed
        ShipmentStatusChangedEvent::class => [
            LogDomainEventListener::class,
            NotifyShipmentStatusChangeListener::class,
        ],

        // Shipping: Delivery Completed
        DeliveryCompletedEvent::class => [
            LogDomainEventListener::class,
            SendDeliveryConfirmationListener::class,
        ],

        // Merchant: Stock Updated
        StockUpdatedEvent::class => [
            LogDomainEventListener::class,
            NotifyLowStockListener::class,
        ],

        // Merchant: Price Changed
        PriceChangedEvent::class => [
            LogDomainEventListener::class,
            NotifyPriceDropListener::class,
        ],

        // Merchant: Status Changed
        MerchantStatusChangedEvent::class => [
            LogDomainEventListener::class,
            NotifyMerchantStatusChangeListener::class,
        ],

        // ═══════════════════════════════════════════════════════════════════
        // IDENTITY EVENTS
        // ═══════════════════════════════════════════════════════════════════

        // Identity: User Registered
        UserRegisteredEvent::class => [
            LogDomainEventListener::class,
            SendVerificationEmailListener::class,
            SendWelcomeEmailListener::class,
        ],

        // Identity: User Login
        UserLoginEvent::class => [
            LogDomainEventListener::class,
            LogUserActivityListener::class,
        ],

        // Identity: User Verified
        UserVerifiedEvent::class => [
            LogDomainEventListener::class,
        ],

        // ═══════════════════════════════════════════════════════════════════
        // CATALOG EVENTS
        // ═══════════════════════════════════════════════════════════════════

        // Catalog: Product Viewed
        ProductViewedEvent::class => [
            LogDomainEventListener::class,
            TrackProductViewListener::class,
        ],

        // Catalog: Product Reviewed
        ProductReviewedEvent::class => [
            LogDomainEventListener::class,
            UpdateProductRatingListener::class,
            NotifyMerchantOfReviewListener::class,
        ],

        // Catalog: Product Favorited
        ProductFavoritedEvent::class => [
            LogDomainEventListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
