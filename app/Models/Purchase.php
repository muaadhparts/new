<?php

namespace App\Models;
use DB;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $table = 'purchases';

	protected $fillable = ['user_id', 'cart', 'method', 'shipping', 'totalQty', 'pay_amount', 'txnid', 'charge_id', 'purchase_number', 'payment_status', 'customer_name', 'customer_email', 'customer_phone', 'customer_address', 'customer_city', 'customer_zip', 'customer_state', 'customer_country', 'purchase_note', 'discount_code', 'discount_amount', 'status', 'affilate_user', 'affilate_charge', 'currency_sign', 'currency_name', 'currency_value', 'shipping_cost', 'packing_cost', 'tax', 'tax_location', 'pay_id', 'merchant_shipping_id', 'merchant_packing_id', 'wallet_price', 'shipping_title', 'packing_title', 'affilate_users', 'commission', 'merchant_ids', 'customer_shipping_choice', 'shipping_status', 'couriers'];


    protected $casts = [
        'cart' => 'array',
        'customer_shipping_choice' => 'array',
        'shipping_status' => 'array',
    ];

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
     * علاقة مع ShipmentStatusLog
     */
    public function shipmentLogs()
    {
        return $this->hasMany('App\Models\ShipmentStatusLog', 'purchase_id')->orderBy('status_date', 'desc');
    }

    /**
     * Get latest shipment status for this purchase
     */
    public function getLatestShipmentStatus()
    {
        return $this->shipmentLogs()->first();
    }

    /**
     * Get all tracking numbers for this purchase
     */
    public function getTrackingNumbers()
    {
        return $this->shipmentLogs()->pluck('tracking_number')->unique()->values();
    }

    /**
     * Check if purchase has active shipments
     */
    public function hasShipments()
    {
        return $this->shipmentLogs()->count() > 0;
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
     * Check if all shipments are delivered (Optimized - Single Query)
     */
    public function allShipmentsDelivered()
    {
        // استخدام subquery للحصول على آخر حالة لكل tracking number في استعلام واحد
        $latestStatuses = DB::table('shipment_status_logs as s1')
            ->select('s1.tracking_number', 's1.status')
            ->leftJoin('shipment_status_logs as s2', function($join) {
                $join->on('s1.tracking_number', '=', 's2.tracking_number')
                     ->whereRaw('s1.status_date < s2.status_date OR (s1.status_date = s2.status_date AND s1.id < s2.id)');
            })
            ->whereNull('s2.id')
            ->where('s1.purchase_id', $this->id)
            ->get();

        if ($latestStatuses->isEmpty()) {
            return false;
        }

        // التحقق من أن جميع الشحنات delivered
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
