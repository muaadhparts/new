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
        'brand_quality_id',
        'product_type',
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
        'licence_type',
        'license_qty',
        'license',
        'ship',
        'product_condition',
        'color_all',
        'color_price',
        'details',
        'policy',
        'features',
        'colors',
        'size',
        'size_qty',
        'size_price',
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
        return $this->belongsTo(QualityBrand::class, 'brand_quality_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope for affiliate items
     */
    public function scopeAffiliate($query)
    {
        return $query->where('product_type', 'affiliate');
    }

    /**
     * Scope for normal items
     */
    public function scopeNormal($query)
    {
        return $query->where('product_type', 'normal');
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
     * Calculate final price for vendor display with size/options and commissions.
     */
    public function vendorSizePrice()
    {
        // Base price = merchant item price + any increments (sizes/options) if numeric
        $base = (float) ($this->price ?? 0);

        if (!empty($this->size_price) && is_numeric($this->size_price)) {
            $base += (float) $this->size_price;
        }

        // Skip commission when base price is zero or less
        if ($base <= 0) {
            return 0.0;
        }

        // Add platform commission (fixed + percentage) to base price - with cache
        $gs = cache()->remember('muaadhsettings', now()->addDay(), fn () => DB::table('muaadhsettings')->first());

        $final = $base;

        if ($gs) {
            $fixed    = (float) ($gs->fixed_commission ?? 0);
            $percent  = (float) ($gs->percentage_commission ?? 0);

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
     * Return price formatted with current currency.
     */
    public function showPrice(): string
    {
        $final = $this->vendorSizePrice();
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

        $current = $this->vendorSizePrice();
        if ($current === null || $current <= 0) {
            return 0;
        }

        // Build previous final price similar to current price
        $prev = (float) $this->previous_price;

        // Add size price to previous price if exists
        if (!empty($this->size_price)) {
            $raw = $this->size_price;
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $first = array_values($decoded)[0] ?? 0;
                    $prev += (float) $first;
                } else {
                    $parts = explode(',', $raw);
                    $prev += isset($parts[0]) && $parts[0] !== '' ? (float) $parts[0] : 0.0;
                }
            } elseif (is_array($raw)) {
                $first = array_values($raw)[0] ?? 0;
                $prev += (float) $first;
            }
        }

        // Add commission to previous price
        $gs = cache()->remember('muaadhsettings', now()->addDay(), fn () => DB::table('muaadhsettings')->first());
        $prev = $prev + (float) $gs->fixed_commission + ($prev * (float) $gs->percentage_commission / 100);

        if ($prev <= 0) {
            return 0;
        }

        $percentage = ((float) $prev - (float) $current) * 100 / (float) $prev;
        return round($percentage, 2);
    }

    /**
     * Get color list as array
     */
    public function getColorAllAttribute($value)
    {
        return $value === null ? [] : (is_array($value) ? $value : explode(',', $value));
    }

    /**
     * Get color prices as array
     */
    public function getColorPriceAttribute($value)
    {
        return $value === null ? [] : (is_array($value) ? $value : explode(',', $value));
    }

    /**
     * Get size as array
     */
    public function getSizeAttribute($value)
    {
        return $value === null ? '' : explode(',', $value);
    }

    /**
     * Get size qty as array
     */
    public function getSizeQtyAttribute($value)
    {
        return $value === null ? '' : explode(',', $value);
    }

    /**
     * Get size price as array
     */
    public function getSizePriceAttribute($value)
    {
        return $value === null ? '' : explode(',', $value);
    }

}
