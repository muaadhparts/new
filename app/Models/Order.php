<?php

namespace App\Models;
use DB;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
	protected $fillable = ['user_id', 'cart', 'method','shipping', 'pickup_location', 'totalQty', 'pay_amount', 'txnid', 'charge_id', 'order_number', 'payment_status', 'customer_name', 'customer_email', 'customer_phone', 'customer_address', 'customer_city', 'customer_zip','customer_state', 'customer_country','shipping_name', 'shipping_email', 'shipping_phone', 'shipping_address', 'shipping_city', 'shipping_zip','shipping_state','shipping_country', 'order_note','coupon_code','coupon_discount','status','affilate_user','affilate_charge','currency_sign','currency_name','currency_value','shipping_cost','packing_cost','tax','tax_location','dp','pay_id','vendor_shipping_id','vendor_packing_id','wallet_price','shipping_title','packing_title','affilate_users','commission','is_shipping','vendor_ids','customer_shipping_choice','shipping_status'];


    protected $casts = [
        'cart' => 'array',
        'customer_shipping_choice' => 'array',
        'shipping_status' => 'array',
    ];

    /**
     * Get the user that owns this order.
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function vendororders()
    {
        return $this->hasMany('App\Models\VendorOrder','order_id');
    }

    public function notifications()
    {
        return $this->hasMany('App\Models\Notification','order_id');
    }

    public function tracks()
    {
        return $this->hasMany('App\Models\OrderTrack','order_id');
    }

    /**
     * علاقة مع ShipmentStatusLog
     */
    public function shipmentLogs()
    {
        return $this->hasMany('App\Models\ShipmentStatusLog', 'order_id')->orderBy('status_date', 'desc');
    }

    /**
     * Get latest shipment status for this order
     */
    public function getLatestShipmentStatus()
    {
        return $this->shipmentLogs()->first();
    }

    /**
     * Get all tracking numbers for this order
     */
    public function getTrackingNumbers()
    {
        return $this->shipmentLogs()->pluck('tracking_number')->unique()->values();
    }

    /**
     * Check if order has active shipments
     */
    public function hasShipments()
    {
        return $this->shipmentLogs()->count() > 0;
    }

    /**
     * Get shipment info from vendor_shipping_id JSON
     */
    public function getShipmentInfo()
    {
        if (!$this->vendor_shipping_id) {
            return [];
        }

        $shippingData = is_string($this->vendor_shipping_id)
            ? json_decode($this->vendor_shipping_id, true)
            : $this->vendor_shipping_id;

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
            ->where('s1.order_id', $this->id)
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
        $vendor_shipping_id = 0;
        $user = array();
        foreach ($cart->items as $prod) {
                $user[] = $prod['item']['user_id'];
        }
        $users = array_unique($user);
        if(count($users) == 1)
        {
            $shipping_data  = DB::table('shippings')->whereUserId($users[0])->get();
            if(count($shipping_data) == 0){
                $shipping_data  = DB::table('shippings')->whereUserId(0)->get();
            }
            else{
                $vendor_shipping_id = $users[0];
            }
        }
        else {
            $shipping_data  = DB::table('shippings')->whereUserId(0)->get();
        }
        $data['shipping_data'] = $shipping_data;
        $data['vendor_shipping_id'] = $vendor_shipping_id;
        return $data; 
    }

    public static function getPackingData($cart)
    {
        $vendor_packing_id = 0;
        $user = array();
        foreach ($cart->items as $prod) {
                $user[] = $prod['item']['user_id'];
        }
        $users = array_unique($user);
        if(count($users) == 1)
        {
            $package_data  = DB::table('packages')->whereUserId($users[0])->get();

            // No fallback - if vendor has no packages, return empty collection
            if(count($package_data) > 0){
                $vendor_packing_id = $users[0];
            }
        }
        else {
            // Multi-vendor cart - no global packaging
            $package_data = collect();
        }
        $data['package_data'] = $package_data;
        $data['vendor_packing_id'] = $vendor_packing_id;
        return $data;
    }

    /**
     * Get customer's shipping choice for a specific vendor
     *
     * @param int $vendorId
     * @return array|null
     */
    public function getCustomerShippingChoice($vendorId)
    {
        $choices = $this->customer_shipping_choice;

        // Handle double-encoded JSON string
        if (is_string($choices)) {
            $choices = json_decode($choices, true);
        }

        if (!$choices || !is_array($choices)) {
            return null;
        }

        // vendorId might be string or int
        return $choices[$vendorId] ?? $choices[(string)$vendorId] ?? null;
    }

    /**
     * Get shipping status for a specific vendor
     *
     * @param int $vendorId
     * @return array|null
     */
    public function getVendorShippingStatus($vendorId)
    {
        $statuses = $this->shipping_status;

        if (!$statuses || !is_array($statuses)) {
            return null;
        }

        return $statuses[$vendorId] ?? null;
    }

    /**
     * Update shipping status for a vendor
     *
     * @param int $vendorId
     * @param array $statusData
     * @return void
     */
    public function updateVendorShippingStatus($vendorId, array $statusData)
    {
        $statuses = $this->shipping_status ?? [];
        $statuses[$vendorId] = array_merge($statuses[$vendorId] ?? [], $statusData);
        $this->shipping_status = $statuses;
        $this->save();
    }

    /**
     * Check if vendor has Tryoto shipping choice from customer
     *
     * @param int $vendorId
     * @return bool
     */
    public function hasTryotoChoice($vendorId)
    {
        $choice = $this->getCustomerShippingChoice($vendorId);
        return $choice && ($choice['provider'] ?? '') === 'tryoto';
    }
}