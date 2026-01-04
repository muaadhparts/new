<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryCourier extends Model
{
    use HasFactory;

    protected $table = 'delivery_couriers';

    public $timestamps = false;

    protected $fillable = [];

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

    public function pickup()
    {
        return $this->belongsTo(PickupPoint::class, 'pickup_point_id')->withDefault();
    }

    public function servicearea()
    {
        return $this->belongsTo(CourierServiceArea::class, 'service_area_id')->withDefault();
    }
}
