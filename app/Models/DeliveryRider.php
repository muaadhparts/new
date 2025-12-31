<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryRider extends Model
{
    use HasFactory;

    // timestamp
    public $timestamps = false;

    protected $fillable = [];

    public function rider()
    {
        return $this->belongsTo(Rider::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    /**
     * @deprecated Use merchant() instead
     */
    public function vendor()
    {
        return $this->merchant();
    }

    public function pickup()
    {
        return $this->belongsTo(PickupPoint::class, 'pickup_point_id')->withDefault();
    }

    public function servicearea()
    {
        return $this->belongsTo(RiderServiceArea::class, 'service_area_id')->withDefault();
    }
}
