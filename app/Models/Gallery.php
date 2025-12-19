<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    protected $fillable = ['product_id', 'user_id', 'photo'];
    public $timestamps = false;

    /**
     * Get the product that owns this gallery image
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the vendor/user that owns this gallery image
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Filter by vendor
     */
    public function scopeForVendor($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by product
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }
}
