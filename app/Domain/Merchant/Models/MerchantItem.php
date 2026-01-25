<?php

namespace App\Domain\Merchant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use App\Domain\Identity\Models\User;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\Models\QualityBrand;

/**
 * MerchantItem Model - Merchant's inventory items
 *
 * Domain: Merchant
 * Table: merchant_items
 *
 * @property int $id
 * @property int $catalog_item_id
 * @property int $user_id
 * @property int|null $merchant_branch_id
 * @property int|null $quality_brand_id
 * @property float $price
 * @property float|null $previous_price
 * @property int $stock
 * @property int $status
 */
class MerchantItem extends Model
{
    use HasFactory;

    protected $table = 'merchant_items';

    protected $fillable = [
        'catalog_item_id',
        'user_id',
        'merchant_branch_id',
        'quality_brand_id',
        'item_type',
        'affiliate_link',
        'price',
        'previous_price',
        'stock',
        'whole_sell_qty',
        'whole_sell_discount',
        'preordered',
        'minimum_qty',
        'stock_check',
        'status',
        'ship',
        'item_condition',
        'details',
        'policy',
    ];

    // =========================================================
    // RELATIONS
    // =========================================================

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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(MerchantBranch::class, 'merchant_branch_id');
    }

    public function merchantBranch(): BelongsTo
    {
        return $this->belongsTo(MerchantBranch::class, 'merchant_branch_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(MerchantPhoto::class, 'merchant_item_id')
            ->where('status', 1)
            ->orderBy('sort_order');
    }

    public function primaryPhoto(): HasOne
    {
        return $this->hasOne(MerchantPhoto::class, 'merchant_item_id')
            ->where('is_primary', true)
            ->where('status', 1);
    }

    // =========================================================
    // SCOPES
    // =========================================================

    public function scopeAffiliate($query)
    {
        return $query->where('item_type', 'affiliate');
    }

    public function scopeNormal($query)
    {
        return $query->where('item_type', 'normal');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('stock', '>', 0)
              ->orWhere('preordered', true);
        });
    }

    public function scopeInBranch($query, $branchId)
    {
        return $query->where('merchant_branch_id', $branchId);
    }

    public function scopeWithBranch($query)
    {
        return $query->whereNotNull('merchant_branch_id');
    }

    // =========================================================
    // PRICE METHODS
    // =========================================================

    public function merchantSizePrice(): float
    {
        $base = (float) ($this->price ?? 0);

        if ($base <= 0) {
            return 0.0;
        }

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

    public function showPrice(): string
    {
        $final = $this->merchantSizePrice();
        return CatalogItem::convertPrice($final);
    }

    public function offPercentage(): float
    {
        if (!$this->previous_price || $this->previous_price <= 0) {
            return 0;
        }

        $current = $this->merchantSizePrice();
        if ($current === null || $current <= 0) {
            return 0;
        }

        $prev = (float) $this->previous_price;

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
