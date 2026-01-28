# Event-Driven Architecture Plan

> **Status**: ALL PHASES COMPLETE - Event-Driven Core Operational
> **Created**: 2026-01-28
> **Updated**: 2026-01-28
> **Purpose**: Build the core capability that serves all future channels (Web, Mobile, API, WhatsApp)

## Progress Summary

| Phase | Status | Description |
|-------|--------|-------------|
| Phase 1 | ✅ Complete | Foundation - PurchasePlacedEvent wired end-to-end |
| Phase 2 | ✅ Complete | Purchase Flow - Notifications via Listeners only |
| Phase 3 | ✅ Complete | Payment Flow - PaymentReceivedEvent wired |
| Phase 4 | ✅ Complete | Shipping Flow - DeliveryCompletedEvent wired |
| Phase 5 | ✅ Complete | Additional Events - ShipmentCreated, StockUpdated |

---

## The Golden Question

**"If I change the View tomorrow, do I need to modify Domain/Service/Controllers?"**

**Answer Must Be: NO**

---

## Current State Analysis

### What Exists
```
app/Domain/
├── Platform/Events/DomainEvent.php     ← Base class (GOOD)
├── Commerce/Events/
│   ├── PurchasePlacedEvent.php         ← EXISTS but NOT DISPATCHED
│   ├── PurchaseStatusChangedEvent.php
│   └── PaymentReceivedEvent.php
├── Merchant/Events/
│   ├── StockUpdatedEvent.php
│   └── PriceChangedEvent.php
├── Shipping/Events/
│   ├── ShipmentCreatedEvent.php
│   ├── ShipmentStatusChangedEvent.php
│   └── DeliveryCompletedEvent.php
└── ... (15 total Domain Events)
```

### The Problem (SOLVED 2026-01-28)
```php
// EventServiceProvider.php - NOW 3 Domain Events registered!
protected $listen = [
    // ... Laravel events ...

    // ✅ SOLVED - Domain Events now registered
    PurchasePlacedEvent::class => [
        LogDomainEventListener::class,
        SendPurchaseConfirmationListener::class,
        NotifyMerchantsOfPurchaseListener::class,
    ],
    PaymentReceivedEvent::class => [...],
    DeliveryCompletedEvent::class => [...],
];
```

```php
// MerchantPurchaseCreator.php - NOW dispatches event!
$purchase->save();
$this->createMerchantPurchase(...);
DB::commit();

// ✅ SOLVED - Event dispatched
PurchasePlacedEvent::dispatch(PurchasePlacedEvent::fromPurchase($purchase));

$this->sendNotifications($purchase, $merchant);  // DEPRECATED - to be removed
```

---

## Architecture Vision

```
┌─────────────────────────────────────────────────────────────────────┐
│                         DOMAIN LAYER                                │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐             │
│  │   Service   │───►│   Event     │───►│  Dispatcher │             │
│  │  (Action)   │    │  (Fact)     │    │  (Laravel)  │             │
│  └─────────────┘    └─────────────┘    └──────┬──────┘             │
└─────────────────────────────────────────────────┼───────────────────┘
                                                  │
           ┌──────────────────────────────────────┼──────────────────┐
           │                                      ▼                  │
           │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │
           │  │  Listener   │  │  Listener   │  │  Listener   │     │
           │  │ (Notify)    │  │ (Stock)     │  │ (Ledger)    │     │
           │  └─────────────┘  └─────────────┘  └─────────────┘     │
           │                    LISTENERS LAYER                      │
           └─────────────────────────────────────────────────────────┘
                                      │
     ┌────────────────────────────────┼────────────────────────────┐
     │                                ▼                            │
     │  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐       │
     │  │   Web   │  │ Mobile  │  │   API   │  │WhatsApp │       │
     │  │  View   │  │   App   │  │  JSON   │  │   Bot   │       │
     │  └─────────┘  └─────────┘  └─────────┘  └─────────┘       │
     │                     PRESENTATION LAYER                      │
     └─────────────────────────────────────────────────────────────┘
```

---

## Implementation Phases

### Phase 1: Event Contract (Foundation)

**Goal**: Define naming, structure, and rules for all events.

#### 1.1 Event Naming Convention
```
{Domain}{Aggregate}{Action}Event

Examples:
- CommercePurchasePlacedEvent      ← Purchase was placed
- CommercePurchaseConfirmedEvent   ← Purchase was confirmed
- CommercePaymentReceivedEvent     ← Payment was received
- MerchantStockUpdatedEvent        ← Stock was updated
- ShippingDeliveryCompletedEvent   ← Delivery was completed
```

#### 1.2 Event Structure
```php
abstract class DomainEvent
{
    // Identity
    public readonly string $eventId;
    public readonly DateTimeImmutable $occurredAt;

    // Aggregate
    abstract public function aggregateType(): string;
    abstract public function aggregateId(): int|string;

    // Data
    abstract public function payload(): array;

    // Serialization
    public function toArray(): array;
}
```

#### 1.3 Event Rules
1. Events are **immutable facts** (what happened)
2. Events contain **all data needed** by listeners
3. Events are **past tense** (Placed, Confirmed, Completed)
4. Events **never call services** (data only)
5. Events are **serializable** (for queues/storage)

---

### Phase 2: Core Events Implementation

#### 2.1 Purchase Flow Events
```
Purchase Created → PurchasePlacedEvent
    ├── NotifyCustomerListener (email)
    ├── NotifyMerchantListener (email/push)
    ├── CreateAccountingEntriesListener
    ├── UpdateInventoryListener
    └── LogEventListener

Purchase Confirmed → PurchaseConfirmedEvent
    ├── NotifyCustomerListener
    ├── CreateShipmentListener (if needed)
    └── LogEventListener

Payment Received → PaymentReceivedEvent
    ├── UpdatePurchaseStatusListener
    ├── RecordPaymentEntryListener
    └── NotifyCustomerListener
```

#### 2.2 Shipping Flow Events
```
Shipment Created → ShipmentCreatedEvent
    ├── NotifyCustomerListener (tracking)
    ├── NotifyMerchantListener
    └── UpdatePurchaseStatusListener

Delivery Completed → DeliveryCompletedEvent
    ├── NotifyCustomerListener
    ├── SettleCodListener (if COD)
    ├── UpdatePurchaseStatusListener
    └── LogEventListener
```

#### 2.3 Stock Flow Events
```
Stock Updated → StockUpdatedEvent
    ├── CheckLowStockListener
    ├── NotifyMerchantListener (if low)
    └── LogEventListener
```

---

### Phase 3: Registration & Wiring

#### 3.1 EventServiceProvider Update
```php
protected $listen = [
    // Commerce Events
    PurchasePlacedEvent::class => [
        NotifyCustomerOfPurchaseListener::class,
        NotifyMerchantOfPurchaseListener::class,
        CreateAccountingEntriesListener::class,
        LogDomainEventListener::class,
    ],

    PaymentReceivedEvent::class => [
        UpdatePurchasePaymentStatusListener::class,
        RecordPaymentEntryListener::class,
        LogDomainEventListener::class,
    ],

    // Shipping Events
    DeliveryCompletedEvent::class => [
        NotifyCustomerOfDeliveryListener::class,
        SettleCodPaymentListener::class,
        UpdatePurchaseDeliveryStatusListener::class,
        LogDomainEventListener::class,
    ],

    // ... more events
];
```

#### 3.2 Dispatch from Services
```php
// MerchantPurchaseCreator.php
public function createPurchase(int $branchId, array $paymentData): array
{
    // ... existing code ...

    DB::commit();

    // 🎯 Dispatch Event (this is the change!)
    PurchasePlacedEvent::dispatch(
        PurchasePlacedEvent::fromPurchase($purchase)
    );

    return ['success' => true, 'purchase' => $purchase];
}
```

---

### Phase 4: Listener Implementation

#### 4.1 Listener Structure
```php
namespace App\Domain\Commerce\Listeners;

use App\Domain\Commerce\Events\PurchasePlacedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyCustomerOfPurchaseListener implements ShouldQueue
{
    public function handle(PurchasePlacedEvent $event): void
    {
        // Get data from event payload
        $payload = $event->payload();

        // Send notification
        // This is the ONLY place that sends customer email for purchases
    }
}
```

#### 4.2 Listener Categories
| Category | Purpose | Queue |
|----------|---------|-------|
| Notify | Send emails/SMS/push | Yes |
| Accounting | Create ledger entries | Yes |
| Inventory | Update stock | Yes |
| Status | Update related records | No |
| Log | Record for audit | Yes |

---

### Phase 5: Remove Direct Calls

**Before (scattered)**:
```php
// In MerchantPurchaseCreator
$this->sendNotifications($purchase, $merchant);

// In some Controller
Mail::send(...);

// In another Service
$this->notificationService->notify(...);
```

**After (centralized)**:
```php
// Only in MerchantPurchaseCreator
PurchasePlacedEvent::dispatch(PurchasePlacedEvent::fromPurchase($purchase));

// Listeners handle everything
// - Customer email → NotifyCustomerOfPurchaseListener
// - Merchant email → NotifyMerchantOfPurchaseListener
// - Accounting → CreateAccountingEntriesListener
```

---

## File Structure

```
app/Domain/
├── Commerce/
│   ├── Events/
│   │   ├── PurchasePlacedEvent.php       ← Update
│   │   ├── PurchaseConfirmedEvent.php    ← Create
│   │   └── PaymentReceivedEvent.php      ← Update
│   └── Listeners/
│       ├── NotifyCustomerOfPurchaseListener.php    ← Create
│       ├── NotifyMerchantOfPurchaseListener.php    ← Create
│       └── CreateAccountingEntriesListener.php     ← Create
├── Shipping/
│   ├── Events/
│   │   └── ... (existing)
│   └── Listeners/
│       └── ... (create)
└── Platform/
    ├── Events/
    │   └── DomainEvent.php               ← Keep
    └── Listeners/
        └── LogDomainEventListener.php    ← Create (logs all events)
```

---

## Implementation Order

### Week 1: Foundation
1. ✅ Study complete
2. ✅ Update CLAUDE.md with Event-Driven rules (Rule #9)
3. ✅ Create LogDomainEventListener (logs all events)
4. ✅ Wire PurchasePlacedEvent end-to-end
   - Added dispatch to MerchantPurchaseCreator.php:188
   - Registered in EventServiceProvider with 3 listeners
   - Created domain log channel in config/logging.php

### Week 2: Purchase Flow (COMPLETE 2026-01-28)
5. ✅ Updated SendPurchaseConfirmationListener (uses MuaadhMailer)
6. ✅ Updated NotifyMerchantsOfPurchaseListener (uses MuaadhMailer)
7. [ ] Create CreateAccountingEntriesListener (future)
8. ✅ Removed sendNotifications() call from MerchantPurchaseCreator
   - Method marked @deprecated, kept for reference

### Week 3: Payment Flow (COMPLETE 2026-01-28)
9. ✅ Wire PaymentReceivedEvent
   - Dispatched in createPurchase() when payment_status='Completed'
   - Dispatched in updatePaymentStatus() when transitioning to 'Completed'
10. ✅ Created SendPaymentConfirmationListener
11. ✅ Registered in EventServiceProvider

### Week 4: Shipping Flow (COMPLETE 2026-01-28)
12. ✅ Wire DeliveryCompletedEvent
    - Dispatched in DeliveryCourier::markAsDelivered()
13. ✅ Updated SendDeliveryConfirmationListener (uses MuaadhMailer)
14. ✅ Registered in EventServiceProvider

### Phase 5: Additional Events (COMPLETE 2026-01-28)
15. ✅ Wire ShipmentCreatedEvent
    - Dispatched in ShipmentTrackingService::createTrackingRecord()
    - Created NotifyCustomerOfShipmentListener
16. ✅ Wire StockUpdatedEvent
    - Fixed MerchantItemObserver to dispatch correctly
    - Registered NotifyLowStockListener

---

## Success Criteria

### Channel Independence
- [ ] Web checkout dispatches PurchasePlacedEvent
- [ ] Mobile API checkout dispatches same event
- [ ] WhatsApp checkout dispatches same event
- [ ] All channels get same behavior

### Single Responsibility
- [ ] Notifications only in Listeners
- [ ] Accounting only in Listeners
- [ ] Services only dispatch events

### Testability
- [ ] Events can be faked in tests
- [ ] Listeners can be tested independently
- [ ] No direct calls to notification services

---

## Rules for CLAUDE.md

```markdown
### 8. Event-Driven Core (NEW)
**All significant actions MUST dispatch Domain Events.**

```php
// FORBIDDEN - Direct side effects in services
$this->sendNotifications($purchase, $merchant);
Mail::send($email, $data);
$this->accountingService->createEntry(...);

// REQUIRED - Dispatch event, listeners handle side effects
PurchasePlacedEvent::dispatch(PurchasePlacedEvent::fromPurchase($purchase));
```

**Event Rules:**
1. Events are immutable facts (past tense: Placed, Confirmed, Completed)
2. Events contain all data needed by listeners
3. Services dispatch events, Listeners handle side effects
4. One event can have many listeners
5. Listeners are single-responsibility (one task each)

**Channel Independence:**
- Web, Mobile, API, WhatsApp → Same event
- View changes → No domain/service changes
- New channel → Just consume existing events
```

---

## Next Immediate Step

**Start with**: Wire PurchasePlacedEvent end-to-end

1. Update EventServiceProvider to register PurchasePlacedEvent
2. Create LogDomainEventListener (simple logger)
3. Add dispatch to MerchantPurchaseCreator
4. Test the flow
