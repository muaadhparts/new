<?php

namespace App\Domain\Commerce\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use App\Domain\Identity\Models\User;
use App\Domain\Shipping\Models\Shipping;
use App\Domain\Catalog\Models\CatalogEvent;
use App\Domain\Shipping\Models\ShipmentTracking;
use App\Domain\Shipping\Models\DeliveryCourier;

/**
 * Purchase Model - Customer orders
 *
 * Domain: Commerce
 * Table: purchases
 *
 * ARCHITECTURAL PRINCIPLE - CART DATA HANDLING:
 * This model is the SINGLE SOURCE OF TRUTH for cart data storage.
 * The 'array' cast handles all encoding/decoding automatically.
 *
 * @property int $id
 * @property int $user_id
 * @property array $cart
 * @property string|null $method
 * @property string|null $shipping
 * @property int $totalQty
 * @property float $pay_amount
 * @property string|null $txnid
 * @property string|null $purchase_number
 * @property string $payment_status
 * @property string $status
 */
class Purchase extends Model
{
    protected $table = 'purchases';

    /**
     * STATUS PROTECTION FLAG
     * Set to true to block direct status modifications.
     */
    public static bool $strictStatusProtection = false;

    protected static function boot()
    {
        parent::boot();

        static::saving(function (Purchase $purchase) {
            if ($purchase->isDirty('status')) {
                $oldStatus = $purchase->getOriginal('status');
                $newStatus = $purchase->status;

                if (config('app.debug')) {
                    Log::warning('Purchase: Direct status modification detected', [
                        'purchase_id' => $purchase->id,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
                    ]);
                }

                if (static::$strictStatusProtection) {
                    throw new \RuntimeException(
                        'Direct status modification is not allowed. ' .
                        'Use ShipmentTrackingService to create tracking records instead.'
                    );
                }
            }
        });
    }

    protected $fillable = [
        'user_id', 'cart', 'method', 'shipping', 'totalQty', 'pay_amount',
        'txnid', 'charge_id', 'purchase_number', 'payment_status',
        'customer_name', 'customer_email', 'customer_phone', 'customer_address',
        'customer_city', 'customer_zip', 'customer_latitude', 'customer_longitude',
        'customer_state', 'customer_country', 'purchase_note', 'discount_code',
        'discount_amount', 'status', 'affilate_user', 'affilate_charge',
        'currency_sign', 'currency_name', 'currency_value', 'shipping_cost',
        'tax', 'tax_location', 'pay_id', 'merchant_shipping_id', 'shipping_name',
        'affilate_users', 'commission', 'merchant_ids', 'customer_shipping_choice',
        'shipping_status', 'couriers'
    ];

    protected $casts = [
        'cart' => 'array',
        'customer_shipping_choice' => 'array',
        'shipping_status' => 'array',
        'shipping_name' => 'array',
    ];

    // =========================================================================
    // CART DATA ACCESS METHODS
    // =========================================================================

    public function getCartItems(): array
    {
        $cart = $this->cart;

        if (is_string($cart)) {
            $cart = json_decode($cart, true);
        }

        return $cart['items'] ?? [];
    }

    public function getCartTotalQty(): int
    {
        $cart = $this->cart;

        if (is_string($cart)) {
            $cart = json_decode($cart, true);
        }

        return (int)($cart['totalQty'] ?? 0);
    }

    public function getCartTotalPrice(): float
    {
        $cart = $this->cart;

        if (is_string($cart)) {
            $cart = json_decode($cart, true);
        }

        return (float)($cart['totalPrice'] ?? 0);
    }

    public function getCartItemsByMerchant(): array
    {
        $items = $this->getCartItems();
        $grouped = [];

        foreach ($items as $key => $item) {
            $merchantId = $item['user_id'] ?? $item['item']['user_id'] ?? 0;

            if (!isset($grouped[$merchantId])) {
                $grouped[$merchantId] = [
                    'items' => [],
                    'totalQty' => 0,
                    'totalPrice' => 0,
                ];
            }

            $grouped[$merchantId]['items'][$key] = $item;
            $grouped[$merchantId]['totalQty'] += (int)($item['qty'] ?? 1);
            $grouped[$merchantId]['totalPrice'] += (float)($item['price'] ?? 0);
        }

        return $grouped;
    }

    public function getCartItemsForMerchant(int $merchantId): array
    {
        $items = $this->getCartItems();
        $merchantItems = [];

        foreach ($items as $key => $item) {
            $itemMerchantId = $item['user_id'] ?? $item['item']['user_id'] ?? 0;

            if ((int)$itemMerchantId === $merchantId) {
                $merchantItems[$key] = $item;
            }
        }

        return $merchantItems;
    }

    public function hasItemsForMerchant(int $merchantId): bool
    {
        return !empty($this->getCartItemsForMerchant($merchantId));
    }

    public function getMerchantIdsFromCart(): array
    {
        $items = $this->getCartItems();
        $merchantIds = [];

        foreach ($items as $item) {
            $merchantId = $item['user_id'] ?? $item['item']['user_id'] ?? 0;
            if ($merchantId && !in_array($merchantId, $merchantIds)) {
                $merchantIds[] = $merchantId;
            }
        }

        return $merchantIds;
    }

    // =========================================================================
    // SHIPPING NAMES METHODS
    // =========================================================================

    /**
     * Get shipping names with details (pre-computed for views)
     * Returns array of shipping names indexed by merchant ID
     *
     * @return array<int, string>
     */
    public function getShippingNamesWithDetails(): array
    {
        $shippingNames = $this->shipping_name;

        if (!$shippingNames || !is_array($shippingNames)) {
            return [];
        }

        $result = [];
        $shippingIds = array_unique(array_values($shippingNames));
        $shippings = Shipping::whereIn('id', $shippingIds)->pluck('name', 'id');

        foreach ($shippingNames as $merchantId => $shippingId) {
            $result[$merchantId] = $shippings[$shippingId] ?? __('Shipping');
        }

        return $result;
    }

    /**
     * Get formatted shipping names string for display
     *
     * @return string
     */
    public function getFormattedShippingNames(): string
    {
        $names = $this->getShippingNamesWithDetails();

        if (empty($names)) {
            return $this->shipping_name && !is_array($this->shipping_name)
                ? (string) $this->shipping_name
                : __('Shipping Cost');
        }

        return implode(', ', array_values($names));
    }

    // =========================================================================
    // RELATIONS
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function merchantPurchases(): HasMany
    {
        return $this->hasMany(MerchantPurchase::class, 'purchase_id');
    }

    public function catalogEvents(): HasMany
    {
        return $this->hasMany(CatalogEvent::class, 'purchase_id');
    }

    public function notifications(): HasMany
    {
        return $this->catalogEvents();
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(PurchaseTimeline::class, 'purchase_id');
    }

    public function tracks(): HasMany
    {
        return $this->timelines();
    }

    public function shipmentTrackings(): HasMany
    {
        return $this->hasMany(ShipmentTracking::class, 'purchase_id')->orderBy('occurred_at', 'desc');
    }

    public function deliveryCouriers(): HasMany
    {
        return $this->hasMany(DeliveryCourier::class, 'purchase_id');
    }

    // =========================================================================
    // SHIPMENT METHODS
    // =========================================================================

    public function getLatestShipmentStatus()
    {
        return $this->shipmentTrackings()->first();
    }

    public function getTrackingNumbers()
    {
        return $this->shipmentTrackings()->pluck('tracking_number')->unique()->values();
    }

    public function hasShipments(): bool
    {
        return $this->shipmentTrackings()->count() > 0;
    }

    public function getShipmentInfo(): array
    {
        if (!$this->merchant_shipping_id) {
            return [];
        }

        $shippingData = is_string($this->merchant_shipping_id)
            ? json_decode($this->merchant_shipping_id, true)
            : $this->merchant_shipping_id;

        return isset($shippingData['oto']) && is_array($shippingData['oto'])
            ? $shippingData['oto']
            : [];
    }

    public function allShipmentsDelivered(): bool
    {
        $latestStatuses = DB::table('shipment_trackings as s1')
            ->select('s1.merchant_id', 's1.status')
            ->whereIn('s1.id', function($sub) {
                $sub->selectRaw('MAX(id)')
                    ->from('shipment_trackings')
                    ->where('purchase_id', $this->id)
                    ->groupBy('merchant_id');
            })
            ->where('s1.purchase_id', $this->id)
            ->get();

        if ($latestStatuses->isEmpty()) {
            return false;
        }

        foreach ($latestStatuses as $status) {
            if ($status->status !== 'delivered') {
                return false;
            }
        }

        return true;
    }

    // =========================================================================
    // STATIC METHODS
    // =========================================================================

    public static function getShipData($cart): array
    {
        $merchant_shipping_id = 0;
        $users = [];

        foreach ($cart->items as $cartItem) {
            $users[] = $cartItem['item']['user_id'];
        }
        $users = array_unique($users);

        if (count($users) == 1) {
            $merchantId = (int) $users[0];

            $shipping_data = Shipping::where('status', 1)
                ->where(function ($q) use ($merchantId) {
                    $q->where('user_id', $merchantId)
                      ->orWhere(function ($q2) use ($merchantId) {
                          $q2->where('user_id', 0)
                             ->where('operator', $merchantId);
                      });
                })
                ->get();

            if ($shipping_data->count() > 0) {
                $merchant_shipping_id = $merchantId;
            }
        } else {
            $shipping_data = collect();
        }

        return [
            'shipping_data' => $shipping_data,
            'merchant_shipping_id' => $merchant_shipping_id,
        ];
    }

    // =========================================================================
    // CUSTOMER SHIPPING METHODS
    // =========================================================================

    public function getCustomerShippingChoice($merchantId)
    {
        $choices = $this->customer_shipping_choice;

        if (is_string($choices)) {
            $choices = json_decode($choices, true);
        }

        if (!$choices || !is_array($choices)) {
            return null;
        }

        return $choices[$merchantId] ?? $choices[(string)$merchantId] ?? null;
    }

    public function getMerchantShippingStatus($merchantId)
    {
        $statuses = $this->shipping_status;

        if (!$statuses || !is_array($statuses)) {
            return null;
        }

        return $statuses[$merchantId] ?? null;
    }

    public function updateMerchantShippingStatus($merchantId, array $statusData): void
    {
        $statuses = $this->shipping_status ?? [];
        $statuses[$merchantId] = array_merge($statuses[$merchantId] ?? [], $statusData);
        $this->shipping_status = $statuses;
        $this->save();
    }

    public function hasTryotoChoice($merchantId): bool
    {
        $choice = $this->getCustomerShippingChoice($merchantId);
        return $choice && ($choice['provider'] ?? '') === 'tryoto';
    }
}
