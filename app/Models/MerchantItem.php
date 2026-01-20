<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class MerchantItem extends Model
{
    protected $table = 'merchant_items';


    protected $fillable = [
        'catalog_item_id',
        'user_id',
        'merchant_branch_id',
        'quality_brand_id',   // Quality brand (OEM, Aftermarket, etc.)
        'item_type',
        'affiliate_link',
        'price',
        'previous_price',
        'stock',
        'is_discount',
        'discount_date',
        'whole_sell_qty',
        'whole_sell_discount',
        'preordered',
        'minimum_qty',
        'stock_check',
        'popular',
        'status',
        'is_popular',
        'ship',
        'item_condition',
        'details',
        'policy',
        'features',
        // Homepage flags
        'featured',
        'top',
        'big',
        'trending',
        'best',
    ];

    /**
     * Get the underlying catalog item definition for this merchant item.
     */

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function qualityBrand(): BelongsTo
    {
        return $this->belongsTo(QualityBrand::class, 'quality_brand_id');
    }

    /**
     * The branch where this item is stocked
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(MerchantBranch::class, 'merchant_branch_id');
    }

    /**
     * Alias for branch() - more explicit
     */
    public function merchantBranch(): BelongsTo
    {
        return $this->belongsTo(MerchantBranch::class, 'merchant_branch_id');
    }

    /**
     * Photos for this merchant item
     */
    public function photos()
    {
        return $this->hasMany(MerchantPhoto::class, 'merchant_item_id')
            ->where('status', 1)
            ->orderBy('sort_order');
    }

    /**
     * Get primary photo for this merchant item
     */
    public function primaryPhoto()
    {
        return $this->hasOne(MerchantPhoto::class, 'merchant_item_id')
            ->where('is_primary', true)
            ->where('status', 1);
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope for affiliate items
     */
    public function scopeAffiliate($query)
    {
        return $query->where('item_type', 'affiliate');
    }

    /**
     * Scope for normal items
     */
    public function scopeNormal($query)
    {
        return $query->where('item_type', 'normal');
    }

    /**
     * Scope for active items (status = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope for in-stock items
     */
    public function scopeInStock($query)
    {
        return $query->where(function($q) {
            $q->where('stock', '>', 0)
              ->orWhere('preordered', true);
        });
    }

    /**
     * Scope for items in a specific branch
     */
    public function scopeInBranch($query, $branchId)
    {
        return $query->where('merchant_branch_id', $branchId);
    }

    /**
     * Scope for items with branch assigned
     */
    public function scopeWithBranch($query)
    {
        return $query->whereNotNull('merchant_branch_id');
    }

    /**
     * Calculate final price for merchant display with commissions.
     */
    public function merchantSizePrice()
    {
        $base = (float) ($this->price ?? 0);

        // Skip commission when base price is zero or less
        if ($base <= 0) {
            return 0.0;
        }

        // Get per-merchant commission or fall back to global settings
        $final = $base;
        $commission = $this->getMerchantCommission();

        if ($commission && $commission->is_active) {
            $fixed = (float) ($commission->fixed_commission ?? 0);
            $percent = (float) ($commission->percentage_commission ?? 0);

            if ($fixed > 0) {
                $final += $fixed;
            }
            if ($percent > 0) {
                $final += $base * ($percent / 100);
            }
        }

        return round($final, 2);
    }

    /**
     * Get commission settings for this merchant item's user.
     * Uses cache for performance.
     */
    protected function getMerchantCommission()
    {
        if (!$this->user_id) {
            return null;
        }

        return cache()->remember(
            "merchant_commission_{$this->user_id}",
            now()->addHours(1),
            fn () => MerchantCommission::where('user_id', $this->user_id)->first()
        );
    }

    /**
     * Return price formatted with current currency.
     */
    public function showPrice(): string
    {
        $final = $this->merchantSizePrice();
        return CatalogItem::convertPrice($final);
    }

    /**
     * Calculate discount percentage between previous price and current price
     */
    public function offPercentage(): float
    {
        if (!$this->previous_price || $this->previous_price <= 0) {
            return 0;
        }

        $current = $this->merchantSizePrice();
        if ($current === null || $current <= 0) {
            return 0;
        }

        // Build previous final price similar to current price
        $prev = (float) $this->previous_price;

        // Add commission to previous price (using per-merchant commission)
        $commission = $this->getMerchantCommission();
        if ($commission && $commission->is_active) {
            $prev = $prev + (float) ($commission->fixed_commission ?? 0) + ($prev * (float) ($commission->percentage_commission ?? 0) / 100);
        }

        if ($prev <= 0) {
            return 0;
        }

        $percentage = ((float) $prev - (float) $current) * 100 / (float) $prev;
        return round($percentage, 2);
    }

}
