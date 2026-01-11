<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * DeliveryCourier - Local Courier Delivery Record
 *
 * ============================================================
 * STATUS STATE MACHINE (NEW WORKFLOW)
 * ============================================================
 *
 * DELIVERY STATUS (status column):
 * ┌─────────────────────┐
 * │   pending_approval  │  ← After checkout - waiting for courier approval
 * └──────────┬──────────┘
 *            │ Courier approves or rejects
 *      ┌─────┴─────┐
 *      ▼           ▼
 * ┌──────────┐ ┌──────────┐
 * │ approved │ │ rejected │  ← Courier rejected (needs reassignment)
 * └────┬─────┘ └──────────┘
 *      │ Merchant prepares order
 *      ▼
 * ┌─────────────────────┐
 * │  ready_for_pickup   │  ← Ready for courier to pick up
 * └──────────┬──────────┘
 *      │ Merchant hands over to courier
 *      ▼
 * ┌─────────────────────┐
 * │     picked_up       │  ← Courier picked up from merchant
 * └──────────┬──────────┘
 *      │ Courier delivers to customer
 *      ▼
 * ┌─────────────────────┐
 * │     delivered       │  ← Delivered to customer
 * └──────────┬──────────┘
 *      │ Customer confirms (optional)
 *      ▼
 * ┌─────────────────────┐
 * │     confirmed       │  ← Customer confirmed receipt
 * └─────────────────────┘
 *
 * ============================================================
 */
class DeliveryCourier extends Model
{
    use HasFactory;

    // ============================================================
    // STATUS CONSTANTS - Delivery Status (NEW WORKFLOW)
    // ============================================================
    public const STATUS_PENDING_APPROVAL = 'pending_approval';   // Waiting for courier approval
    public const STATUS_APPROVED = 'approved';                   // Courier approved, merchant preparing
    public const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';   // Merchant ready, waiting courier
    public const STATUS_PICKED_UP = 'picked_up';                 // Courier picked up from merchant
    public const STATUS_DELIVERED = 'delivered';                 // Delivered to customer
    public const STATUS_CONFIRMED = 'confirmed';                 // Customer confirmed (optional)
    public const STATUS_REJECTED = 'rejected';                   // Courier rejected

    // Valid status transitions
    public const STATUS_TRANSITIONS = [
        self::STATUS_PENDING_APPROVAL => [self::STATUS_APPROVED, self::STATUS_REJECTED],
        self::STATUS_APPROVED => [self::STATUS_READY_FOR_PICKUP],
        self::STATUS_READY_FOR_PICKUP => [self::STATUS_PICKED_UP],
        self::STATUS_PICKED_UP => [self::STATUS_DELIVERED],
        self::STATUS_DELIVERED => [self::STATUS_CONFIRMED],
        self::STATUS_CONFIRMED => [], // Terminal state
        self::STATUS_REJECTED => [], // Terminal state - needs reassignment
    ];

    // ============================================================
    // FEE STATUS CONSTANTS
    // ============================================================
    public const FEE_PENDING = 'pending';
    public const FEE_PAID = 'paid';         // Online payment - fee credited
    public const FEE_COLLECTED = 'collected'; // COD - fee included in collection

    // ============================================================
    // SETTLEMENT STATUS CONSTANTS
    // ============================================================
    public const SETTLEMENT_PENDING = 'pending';
    public const SETTLEMENT_SETTLED = 'settled';

    // ============================================================
    // PAYMENT METHOD CONSTANTS
    // ============================================================
    public const PAYMENT_COD = 'cod';
    public const PAYMENT_ONLINE = 'online';

    protected $table = 'delivery_couriers';

    public $timestamps = true;

    protected $fillable = [
        'purchase_id',
        'merchant_id',
        'courier_id',
        'merchant_location_id',
        'service_area_id',
        'status',
        'delivery_fee',
        'cod_amount',
        'purchase_amount',
        'payment_method',
        'fee_status',
        'settlement_status',
        'delivered_at',
        'approved_at',
        'ready_at',
        'picked_up_at',
        'confirmed_at',
        'customer_confirmed',
        'settled_at',
        'notes',
        'rejection_reason',
    ];

    protected $casts = [
        'delivery_fee' => 'decimal:2',
        'cod_amount' => 'decimal:2',
        'purchase_amount' => 'decimal:2',
        'delivered_at' => 'datetime',
        'approved_at' => 'datetime',
        'ready_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'settled_at' => 'datetime',
        'customer_confirmed' => 'boolean',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function courier()
    {
        return $this->belongsTo(Courier::class, 'courier_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function merchantLocation()
    {
        return $this->belongsTo(MerchantLocation::class, 'merchant_location_id')->withDefault();
    }

    public function servicearea()
    {
        return $this->belongsTo(CourierServiceArea::class, 'service_area_id')->withDefault();
    }

    // ============================================================
    // STATUS CHECK METHODS
    // ============================================================

    public function isCod(): bool
    {
        return $this->payment_method === self::PAYMENT_COD;
    }

    public function isOnlinePayment(): bool
    {
        return $this->payment_method === self::PAYMENT_ONLINE;
    }

    /**
     * Is waiting for courier approval?
     */
    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    /**
     * Courier approved, merchant should prepare
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Merchant prepared, waiting for courier to pick up
     */
    public function isReadyForPickup(): bool
    {
        return $this->status === self::STATUS_READY_FOR_PICKUP;
    }

    /**
     * Courier picked up from merchant, on the way to customer
     */
    public function isPickedUp(): bool
    {
        return $this->status === self::STATUS_PICKED_UP;
    }

    /**
     * Delivered to customer (awaiting optional confirmation)
     */
    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    /**
     * Customer confirmed receipt
     */
    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    /**
     * Courier rejected the delivery
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Is completed (delivered or confirmed)?
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_CONFIRMED]);
    }

    /**
     * Is in progress (approved, ready, picked up)?
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_READY_FOR_PICKUP,
            self::STATUS_PICKED_UP,
        ]);
    }

    public function isSettled(): bool
    {
        return $this->settlement_status === self::SETTLEMENT_SETTLED;
    }

    // ============================================================
    // STATUS TRANSITION METHODS
    // ============================================================

    /**
     * Check if transition to new status is valid
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $allowedTransitions = self::STATUS_TRANSITIONS[$this->status] ?? [];
        return in_array($newStatus, $allowedTransitions);
    }

    /**
     * Transition to new status with validation
     *
     * @throws \InvalidArgumentException if transition is not allowed
     */
    public function transitionTo(string $newStatus): void
    {
        if (!$this->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from '{$this->status}' to '{$newStatus}'"
            );
        }

        $this->status = $newStatus;
        $this->save();
    }

    /**
     * STEP 1: Courier approves the delivery
     * Called by: Courier
     */
    public function approve(): void
    {
        $this->transitionTo(self::STATUS_APPROVED);
        $this->approved_at = now();
        $this->save();
    }

    /**
     * STEP 1 ALT: Courier rejects the delivery
     * Called by: Courier
     */
    public function reject(?string $reason = null): void
    {
        $this->transitionTo(self::STATUS_REJECTED);
        $this->rejection_reason = $reason;
        $this->save();
    }

    /**
     * STEP 2: Merchant marks order ready for pickup
     * Called by: Merchant
     */
    public function markReadyForPickup(): void
    {
        $this->transitionTo(self::STATUS_READY_FOR_PICKUP);
        $this->ready_at = now();
        $this->save();
    }

    /**
     * STEP 3: Merchant confirms handover to courier
     * Called by: Merchant
     */
    public function confirmHandoverToCourier(): void
    {
        $this->transitionTo(self::STATUS_PICKED_UP);
        $this->picked_up_at = now();
        $this->save();
    }

    /**
     * STEP 4: Courier delivers to customer
     * Called by: Courier
     */
    public function markAsDelivered(): void
    {
        $this->transitionTo(self::STATUS_DELIVERED);
        $this->delivered_at = now();
        $this->save();

        // Process financial transactions
        if ($this->isOnlinePayment()) {
            $this->courier->recordDeliveryFeeEarned($this->delivery_fee);
            $this->fee_status = self::FEE_PAID;
            $this->save();
        } elseif ($this->isCod()) {
            $this->courier->recordCodCollection($this->purchase_amount);
            $this->fee_status = self::FEE_COLLECTED;
            $this->save();
        }
    }

    /**
     * STEP 5 (Optional): Customer confirms receipt
     * Called by: Customer
     */
    public function confirmByCustomer(): void
    {
        $this->transitionTo(self::STATUS_CONFIRMED);
        $this->confirmed_at = now();
        $this->customer_confirmed = true;
        $this->save();
    }

    /**
     * Mark as settled
     */
    public function markAsSettled(): void
    {
        $this->settlement_status = self::SETTLEMENT_SETTLED;
        $this->settled_at = now();
        $this->save();
    }

    /**
     * Initialize or reassign courier for delivery
     * Used when creating new delivery record or reassigning after rejection
     *
     * @param int $courierId
     * @param int $serviceAreaId
     * @param int|null $merchantLocationId
     * @param float $deliveryFee
     * @param float $purchaseAmount
     * @param string $paymentMethod (cod|online)
     */
    public function initializeAssignment(
        int $courierId,
        int $serviceAreaId,
        ?int $merchantLocationId,
        float $deliveryFee,
        float $purchaseAmount,
        string $paymentMethod
    ): void {
        $this->courier_id = $courierId;
        $this->service_area_id = $serviceAreaId;
        $this->merchant_location_id = $merchantLocationId;
        $this->status = self::STATUS_PENDING_APPROVAL;
        $this->delivery_fee = $deliveryFee;
        $this->purchase_amount = $purchaseAmount;
        $this->payment_method = $paymentMethod;
        $this->cod_amount = $paymentMethod === self::PAYMENT_COD ? ($purchaseAmount + $deliveryFee) : 0;

        // Reset timestamps from previous assignment (if reassigning)
        $this->approved_at = null;
        $this->ready_at = null;
        $this->picked_up_at = null;
        $this->delivered_at = null;
        $this->confirmed_at = null;
        $this->customer_confirmed = false;
        $this->rejection_reason = null;

        $this->save();
    }

    /**
     * Create new delivery record for a purchase
     *
     * @param int $purchaseId
     * @param int $merchantId
     * @param int $courierId
     * @param int $serviceAreaId
     * @param int|null $merchantLocationId
     * @param float $deliveryFee
     * @param float $purchaseAmount
     * @param string $paymentMethod
     * @return static
     */
    public static function createForPurchase(
        int $purchaseId,
        int $merchantId,
        int $courierId,
        int $serviceAreaId,
        ?int $merchantLocationId,
        float $deliveryFee,
        float $purchaseAmount,
        string $paymentMethod
    ): self {
        $delivery = new self();
        $delivery->purchase_id = $purchaseId;
        $delivery->merchant_id = $merchantId;
        $delivery->courier_id = $courierId;
        $delivery->service_area_id = $serviceAreaId;
        $delivery->merchant_location_id = $merchantLocationId;
        $delivery->status = self::STATUS_PENDING_APPROVAL;
        $delivery->delivery_fee = $deliveryFee;
        $delivery->purchase_amount = $purchaseAmount;
        $delivery->payment_method = $paymentMethod;
        $delivery->cod_amount = $paymentMethod === self::PAYMENT_COD ? ($purchaseAmount + $deliveryFee) : 0;
        $delivery->save();

        return $delivery;
    }

    // ============================================================
    // QUERY SCOPES
    // ============================================================

    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_PENDING_APPROVAL);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeReadyForPickup($query)
    {
        return $query->where('status', self::STATUS_READY_FOR_PICKUP);
    }

    public function scopePickedUp($query)
    {
        return $query->where('status', self::STATUS_PICKED_UP);
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING_APPROVAL,
            self::STATUS_APPROVED,
            self::STATUS_READY_FOR_PICKUP,
            self::STATUS_PICKED_UP,
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [
            self::STATUS_DELIVERED,
            self::STATUS_CONFIRMED,
        ]);
    }

    public function scopeUnsettled($query)
    {
        return $query->where('settlement_status', self::SETTLEMENT_PENDING);
    }

    public function scopeCod($query)
    {
        return $query->where('payment_method', self::PAYMENT_COD);
    }

    public function scopeOnlinePayment($query)
    {
        return $query->where('payment_method', self::PAYMENT_ONLINE);
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    /**
     * Get all valid status values with labels
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING_APPROVAL => __('Waiting Courier Approval'),
            self::STATUS_APPROVED => __('Courier Approved'),
            self::STATUS_READY_FOR_PICKUP => __('Ready for Pickup'),
            self::STATUS_PICKED_UP => __('Courier Picked Up'),
            self::STATUS_DELIVERED => __('Delivered'),
            self::STATUS_CONFIRMED => __('Customer Confirmed'),
            self::STATUS_REJECTED => __('Courier Rejected'),
        ];
    }

    /**
     * Get status label for display
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get current workflow step number (1-6)
     */
    public function getWorkflowStepAttribute(): int
    {
        return match($this->status) {
            self::STATUS_PENDING_APPROVAL => 1,
            self::STATUS_APPROVED => 2,
            self::STATUS_READY_FOR_PICKUP => 3,
            self::STATUS_PICKED_UP => 4,
            self::STATUS_DELIVERED => 5,
            self::STATUS_CONFIRMED => 6,
            self::STATUS_REJECTED => 0, // Special case
            default => 0,
        };
    }

    /**
     * Get next action description based on current status
     */
    public function getNextActionAttribute(): array
    {
        return match($this->status) {
            self::STATUS_PENDING_APPROVAL => [
                'actor' => 'courier',
                'action' => __('Approve or Reject'),
                'button' => __('Approve Delivery'),
            ],
            self::STATUS_APPROVED => [
                'actor' => 'merchant',
                'action' => __('Prepare Order'),
                'button' => __('Mark Ready for Pickup'),
            ],
            self::STATUS_READY_FOR_PICKUP => [
                'actor' => 'merchant',
                'action' => __('Hand Over to Courier'),
                'button' => __('Confirm Handover'),
            ],
            self::STATUS_PICKED_UP => [
                'actor' => 'courier',
                'action' => __('Deliver to Customer'),
                'button' => __('Mark as Delivered'),
            ],
            self::STATUS_DELIVERED => [
                'actor' => 'customer',
                'action' => __('Confirm Receipt (Optional)'),
                'button' => __('Confirm Receipt'),
            ],
            default => [
                'actor' => 'none',
                'action' => __('Completed'),
                'button' => null,
            ],
        };
    }

    // ============================================================
    // BACKWARD COMPATIBILITY (DEPRECATED - Will be removed)
    // ============================================================

    /**
     * @deprecated Use isPendingApproval() instead
     */
    public function isPending(): bool
    {
        return $this->isPendingApproval();
    }

    /**
     * @deprecated Use isReadyForPickup() instead
     */
    public function isReadyForCollection(): bool
    {
        return $this->isReadyForPickup();
    }

    /**
     * @deprecated Use isPickedUp() instead
     */
    public function isAccepted(): bool
    {
        return $this->isPickedUp();
    }

    /**
     * @deprecated Use approve() instead
     */
    public function accept(): void
    {
        $this->approve();
    }

    /**
     * @deprecated Use markReadyForPickup() instead
     */
    public function markReadyForCollection(): void
    {
        $this->markReadyForPickup();
    }
}
