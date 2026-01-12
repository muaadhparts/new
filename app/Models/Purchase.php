<?php

namespace App\Models;
use DB;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Purchase Model
 *
 * ARCHITECTURAL PRINCIPLE - CART DATA HANDLING (2026-01-09):
 * ==========================================================
 * This model is the SINGLE SOURCE OF TRUTH for cart data storage.
 *
 * RULE: Only the storage layer (this model) handles data transformation.
 *       All other layers (controllers, services, views) work with structured arrays.
 *
 * WHAT THIS MEANS:
 * - Controllers should pass arrays directly: $purchase->cart = $cartArray;
 * - Controllers should read arrays directly: $items = $purchase->cart['items'];
 * - NO json_encode() outside this model
 * - NO json_decode() outside this model
 * - The 'array' cast handles all encoding/decoding automatically
 *
 * CART DATA STRUCTURE:
 * [
 *     'totalQty' => int,
 *     'totalPrice' => float,
 *     'items' => [
 *         'cart_key' => [
 *             'user_id' => int (merchant_id),
 *             'merchant_item_id' => int,
 *             'qty' => int,
 *             'price' => float,
 *             'item' => [...catalog item data...],
 *             ...
 *         ]
 *     ]
 * ]
 */
class Purchase extends Model
{
    protected $table = 'purchases';

    /**
     * STATUS PROTECTION FLAG
     *
     * Set to true to block direct status modifications.
     * Status should ONLY be changed via ShipmentTracking → OrderStatusResolverService
     *
     * @see OrderStatusResolverService::resolveAndUpdate() - Uses updateQuietly() to bypass this
     */
    public static bool $strictStatusProtection = false;

    /**
     * Boot method - Register model events
     */
    protected static function boot()
    {
        parent::boot();

        // Protect status field from direct modification
        static::saving(function (Purchase $purchase) {
            if ($purchase->isDirty('status')) {
                $oldStatus = $purchase->getOriginal('status');
                $newStatus = $purchase->status;

                // Log warning for direct status modification
                Log::warning('Purchase: Direct status modification detected', [
                    'purchase_id' => $purchase->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
                ]);

                // Block if strict protection is enabled
                if (static::$strictStatusProtection) {
                    throw new \RuntimeException(
                        'Direct status modification is not allowed. ' .
                        'Use ShipmentTrackingService to create tracking records instead.'
                    );
                }
            }
        });
    }

	protected $fillable = ['user_id', 'cart', 'method', 'shipping', 'totalQty', 'pay_amount', 'txnid', 'charge_id', 'purchase_number', 'payment_status', 'customer_name', 'customer_email', 'customer_phone', 'customer_address', 'customer_city', 'customer_zip', 'customer_latitude', 'customer_longitude', 'customer_state', 'customer_country', 'purchase_note', 'discount_code', 'discount_amount', 'status', 'affilate_user', 'affilate_charge', 'currency_sign', 'currency_name', 'currency_value', 'shipping_cost', 'packing_cost', 'tax', 'tax_location', 'pay_id', 'merchant_shipping_id', 'merchant_packing_id', 'wallet_price', 'shipping_title', 'packing_title', 'affilate_users', 'commission', 'merchant_ids', 'customer_shipping_choice', 'shipping_status', 'couriers'];

    /**
     * Attribute casts - THE STORAGE LAYER
     *
     * These casts handle ALL JSON encoding/decoding automatically.
     * External code should NEVER manually encode/decode these fields.
     */
    protected $casts = [
        'cart' => 'array',
        'customer_shipping_choice' => 'array',
        'shipping_status' => 'array',
    ];

    // =========================================================================
    // CART DATA ACCESS METHODS (Clean API for external code)
    // =========================================================================

    /**
     * Get cart items as array
     *
     * Use this instead of: json_decode($purchase->cart, true)['items']
     *
     * @return array
     */
    public function getCartItems(): array
    {
        $cart = $this->cart;

        // Handle legacy double-encoded data (backward compatibility)
        if (is_string($cart)) {
            $cart = json_decode($cart, true);
        }

        return $cart['items'] ?? [];
    }

    /**
     * Get cart total quantity
     *
     * @return int
     */
    public function getCartTotalQty(): int
    {
        $cart = $this->cart;

        if (is_string($cart)) {
            $cart = json_decode($cart, true);
        }

        return (int)($cart['totalQty'] ?? 0);
    }

    /**
     * Get cart total price
     *
     * @return float
     */
    public function getCartTotalPrice(): float
    {
        $cart = $this->cart;

        if (is_string($cart)) {
            $cart = json_decode($cart, true);
        }

        return (float)($cart['totalPrice'] ?? 0);
    }

    /**
     * Get cart items grouped by merchant
     *
     * @return array [merchant_id => ['items' => [...], 'totalQty' => x, 'totalPrice' => y]]
     */
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

    /**
     * Get cart items for a specific merchant
     *
     * @param int $merchantId
     * @return array
     */
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

    /**
     * Check if cart has items for a specific merchant
     *
     * @param int $merchantId
     * @return bool
     */
    public function hasItemsForMerchant(int $merchantId): bool
    {
        return !empty($this->getCartItemsForMerchant($merchantId));
    }

    /**
     * Get all merchant IDs from cart
     *
     * @return array
     */
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

    /**
     * Get the user that owns this purchase.
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function merchantPurchases()
    {
        return $this->hasMany('App\Models\MerchantPurchase','purchase_id');
    }

    public function catalogEvents()
    {
        return $this->hasMany('App\Models\CatalogEvent','purchase_id');
    }

    /**
     * Alias for catalogEvents() - backwards compatibility
     * Used by payment controllers for purchase notifications
     */
    public function notifications()
    {
        return $this->catalogEvents();
    }

    public function timelines()
    {
        return $this->hasMany('App\Models\PurchaseTimeline','purchase_id');
    }

    /**
     * Alias for timelines() - backwards compatibility
     * Used by many payment controllers
     */
    public function tracks()
    {
        return $this->timelines();
    }

    /**
     * علاقة مع ShipmentTracking
     */
    public function shipmentTrackings()
    {
        return $this->hasMany('App\Models\ShipmentTracking', 'purchase_id')->orderBy('occurred_at', 'desc');
    }

    /**
     * Get latest shipment status for this purchase
     */
    public function getLatestShipmentStatus()
    {
        return $this->shipmentTrackings()->first();
    }

    /**
     * Get all tracking numbers for this purchase
     */
    public function getTrackingNumbers()
    {
        return $this->shipmentTrackings()->pluck('tracking_number')->unique()->values();
    }

    /**
     * Check if purchase has active shipments
     */
    public function hasShipments()
    {
        return $this->shipmentTrackings()->count() > 0;
    }

    /**
     * Get shipment info from merchant_shipping_id JSON
     */
    public function getShipmentInfo()
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

    /**
     * Check if all shipments are delivered
     */
    public function allShipmentsDelivered()
    {
        // Get latest status per merchant from shipment_trackings
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

        // Check all shipments are delivered
        foreach ($latestStatuses as $status) {
            if ($status->status !== 'delivered') {
                return false;
            }
        }

        return true;
    }

    public static function getShipData($cart)
    {
//        dd($cart);
        $merchant_shipping_id = 0;
        $user = array();
        foreach ($cart->items as $cartItem) {
                $user[] = $cartItem['item']['user_id'];
        }
        $users = array_unique($user);
        if(count($users) == 1)
        {
            $shipping_data  = DB::table('shippings')->whereUserId($users[0])->get();
            if(count($shipping_data) == 0){
                $shipping_data  = DB::table('shippings')->whereUserId(0)->get();
            }
            else{
                $merchant_shipping_id = $users[0];
            }
        }
        else {
            $shipping_data  = DB::table('shippings')->whereUserId(0)->get();
        }
        $data['shipping_data'] = $shipping_data;
        $data['merchant_shipping_id'] = $merchant_shipping_id;
        return $data;
    }

    public static function getPackingData($cart)
    {
        $merchant_packing_id = 0;
        $user = array();
        foreach ($cart->items as $cartItem) {
                $user[] = $cartItem['item']['user_id'];
        }
        $users = array_unique($user);
        if(count($users) == 1)
        {
            $package_data  = DB::table('packages')->whereUserId($users[0])->get();

            // No fallback - if merchant has no packages, return empty collection
            if(count($package_data) > 0){
                $merchant_packing_id = $users[0];
            }
        }
        else {
            // Multi-merchant cart - no global packaging
            $package_data = collect();
        }
        $data['package_data'] = $package_data;
        $data['merchant_packing_id'] = $merchant_packing_id;
        return $data;
    }

    /**
     * Get customer's shipping choice for a specific merchant
     *
     * @param int $merchantId
     * @return array|null
     */
    public function getCustomerShippingChoice($merchantId)
    {
        $choices = $this->customer_shipping_choice;

        // Handle double-encoded JSON string
        if (is_string($choices)) {
            $choices = json_decode($choices, true);
        }

        if (!$choices || !is_array($choices)) {
            return null;
        }

        // merchantId might be string or int
        return $choices[$merchantId] ?? $choices[(string)$merchantId] ?? null;
    }

    /**
     * Get shipping status for a specific merchant
     *
     * @param int $merchantId
     * @return array|null
     */
    public function getMerchantShippingStatus($merchantId)
    {
        $statuses = $this->shipping_status;

        if (!$statuses || !is_array($statuses)) {
            return null;
        }

        return $statuses[$merchantId] ?? null;
    }

    /**
     * Update shipping status for a merchant
     *
     * @param int $merchantId
     * @param array $statusData
     * @return void
     */
    public function updateMerchantShippingStatus($merchantId, array $statusData)
    {
        $statuses = $this->shipping_status ?? [];
        $statuses[$merchantId] = array_merge($statuses[$merchantId] ?? [], $statusData);
        $this->shipping_status = $statuses;
        $this->save();
    }

    /**
     * Check if merchant has Tryoto shipping choice from customer
     *
     * @param int $merchantId
     * @return bool
     */
    public function hasTryotoChoice($merchantId)
    {
        $choice = $this->getCustomerShippingChoice($merchantId);
        return $choice && ($choice['provider'] ?? '') === 'tryoto';
    }
}
