<?php

namespace App\Domain\Shipping\Models;

use App\Domain\Accounting\Services\PaymentAccountingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Identity\Models\Courier;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Identity\Models\User;
use App\Domain\Merchant\Models\MerchantBranch;
use App\Domain\Commerce\Models\MerchantPurchase;

/**
 * DeliveryCourier - Local Courier Delivery Record
 *
 * Domain: Shipping
 * Table: delivery_couriers
 *
 * WARNING: cod_amount CALCULATION RULES
 * cod_amount = The ACTUAL cash amount the courier collects from customer
 * MUST be calculated via PaymentAccountingService::calculateCourierCodAmount()
 * NEVER calculate cod_amount manually! It equals pay_amount (not pay_amount + fee!)
 *
 * @property int $id
 * @property int $purchase_id
 * @property int $merchant_id
 * @property int $courier_id
 * @property int|null $merchant_branch_id
 * @property int|null $service_area_id
 * @property string $status
 * @property float $delivery_fee
 * @property float $cod_amount
 * @property float $purchase_amount
 * @property string $payment_method
 * @property string $fee_status
 * @property string $settlement_status
 * @property \Carbon\Carbon|null $delivered_at
 * @property \Carbon\Carbon|null $approved_at
 * @property \Carbon\Carbon|null $ready_at
 * @property \Carbon\Carbon|null $picked_up_at
 * @property \Carbon\Carbon|null $confirmed_at
 * @property bool $customer_confirmed
 * @property \Carbon\Carbon|null $settled_at
 * @property string|null $notes
 * @property string|null $rejection_reason
 */
class DeliveryCourier extends Model
{
    use HasFactory;

    // ============================================================
    // STATUS CONSTANTS - Delivery Status
    // ============================================================
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';

    // Valid status transitions
    public const STATUS_TRANSITIONS = [
        self::STATUS_PENDING_APPROVAL => [self::STATUS_APPROVED, self::STATUS_REJECTED],
        self::STATUS_APPROVED => [self::STATUS_READY_FOR_PICKUP],
        self::STATUS_READY_FOR_PICKUP => [self::STATUS_PICKED_UP],
        self::STATUS_PICKED_UP => [self::STATUS_DELIVERED],
        self::STATUS_DELIVERED => [self::STATUS_CONFIRMED],
        self::STATUS_CONFIRMED => [],
        self::STATUS_REJECTED => [],
    ];

    // ============================================================
    // FEE STATUS CONSTANTS
    // ============================================================
    public const FEE_PENDING = 'pending';
    public const FEE_PAID = 'paid';
    public const FEE_COLLECTED = 'collected';

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
        'merchant_branch_id',
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

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class, 'courier_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function merchantBranch(): BelongsTo
    {
        return $this->belongsTo(MerchantBranch::class, 'merchant_branch_id')->withDefault();
    }

    public function servicearea(): BelongsTo
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

    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isReadyForPickup(): bool
    {
        return $this->status === self::STATUS_READY_FOR_PICKUP;
    }

    public function isPickedUp(): bool
    {
        return $this->status === self::STATUS_PICKED_UP;
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_CONFIRMED]);
    }

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

    public function canTransitionTo(string $newStatus): bool
    {
        $allowedTransitions = self::STATUS_TRANSITIONS[$this->status] ?? [];
        return in_array($newStatus, $allowedTransitions);
    }

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

    public function approve(): void
    {
        $this->transitionTo(self::STATUS_APPROVED);
        $this->approved_at = now();
        $this->save();
    }

    public function reject(?string $reason = null): void
    {
        $this->transitionTo(self::STATUS_REJECTED);
        $this->rejection_reason = $reason;
        $this->save();
    }

    public function markReadyForPickup(): void
    {
        $this->transitionTo(self::STATUS_READY_FOR_PICKUP);
        $this->ready_at = now();
        $this->save();
    }

    public function confirmHandoverToCourier(): void
    {
        $this->transitionTo(self::STATUS_PICKED_UP);
        $this->picked_up_at = now();
        $this->save();
    }

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

            $this->updatePurchasePaymentStatusIfAllDelivered();
        }
    }

    protected function updatePurchasePaymentStatusIfAllDelivered(): void
    {
        $purchase = $this->purchase;
        if (!$purchase) {
            return;
        }

        $accountingService = app(PaymentAccountingService::class);

        $merchantPurchase = MerchantPurchase::where('purchase_id', $purchase->id)
            ->where('user_id', $this->merchant_id)
            ->where('collection_status', MerchantPurchase::COLLECTION_PENDING)
            ->first();

        if ($merchantPurchase) {
            $accountingService->markCollectedByCourier($merchantPurchase, $this->courier_id);
        }

        $pendingDeliveries = static::where('purchase_id', $purchase->id)
            ->whereNotIn('status', [self::STATUS_DELIVERED, self::STATUS_CONFIRMED])
            ->count();

        if ($pendingDeliveries === 0) {
            $purchase->payment_status = 'Completed';
            $purchase->save();
        }
    }

    public function confirmByCustomer(): void
    {
        $this->transitionTo(self::STATUS_CONFIRMED);
        $this->confirmed_at = now();
        $this->customer_confirmed = true;
        $this->save();
    }

    public function markAsSettled(): void
    {
        $this->settlement_status = self::SETTLEMENT_SETTLED;
        $this->settled_at = now();
        $this->save();
    }

    public function initializeAssignment(
        int $courierId,
        int $serviceAreaId,
        ?int $merchantBranchId,
        float $deliveryFee,
        float $purchaseAmount,
        string $paymentMethod
    ): void {
        $this->courier_id = $courierId;
        $this->service_area_id = $serviceAreaId;
        $this->merchant_branch_id = $merchantBranchId;
        $this->status = self::STATUS_PENDING_APPROVAL;
        $this->delivery_fee = $deliveryFee;
        $this->purchase_amount = $purchaseAmount;
        $this->payment_method = $paymentMethod;
        $this->cod_amount = $paymentMethod === self::PAYMENT_COD ? $purchaseAmount : 0;

        $this->approved_at = null;
        $this->ready_at = null;
        $this->picked_up_at = null;
        $this->delivered_at = null;
        $this->confirmed_at = null;
        $this->customer_confirmed = false;
        $this->rejection_reason = null;

        $this->save();
    }

    public static function createForPurchase(
        int $purchaseId,
        int $merchantId,
        int $courierId,
        int $serviceAreaId,
        ?int $merchantBranchId,
        float $deliveryFee,
        float $purchaseAmount,
        string $paymentMethod
    ): self {
        $delivery = new self();
        $delivery->purchase_id = $purchaseId;
        $delivery->merchant_id = $merchantId;
        $delivery->courier_id = $courierId;
        $delivery->service_area_id = $serviceAreaId;
        $delivery->merchant_branch_id = $merchantBranchId;
        $delivery->status = self::STATUS_PENDING_APPROVAL;
        $delivery->delivery_fee = $deliveryFee;
        $delivery->purchase_amount = $purchaseAmount;
        $delivery->payment_method = $paymentMethod;
        $delivery->cod_amount = $paymentMethod === self::PAYMENT_COD ? $purchaseAmount : 0;
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

    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? ucfirst($this->status);
    }

    public function getWorkflowStepAttribute(): int
    {
        return match($this->status) {
            self::STATUS_PENDING_APPROVAL => 1,
            self::STATUS_APPROVED => 2,
            self::STATUS_READY_FOR_PICKUP => 3,
            self::STATUS_PICKED_UP => 4,
            self::STATUS_DELIVERED => 5,
            self::STATUS_CONFIRMED => 6,
            self::STATUS_REJECTED => 0,
            default => 0,
        };
    }

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
}
