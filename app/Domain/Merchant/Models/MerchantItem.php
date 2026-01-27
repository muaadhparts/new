<?php

namespace App\Domain\Merchant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;
use App\Domain\Identity\Models\User;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\Models\CatalogReview;
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
 * @property string $item_type
 * @property string|null $affiliate_link
 * @property float $price
 * @property float|null $previous_price
 * @property int $stock
 * @property int|null $whole_sell_qty
 * @property float|null $whole_sell_discount
 * @property bool $preordered
 * @property int|null $minimum_qty
 * @property int $stock_check
 * @property int $status
 * @property string|null $ship
 * @property int $item_condition
 * @property string|null $details
 * @property string|null $policy
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

    protected $casts = [
        'catalog_item_id' => 'integer',
        'user_id' => 'integer',
        'merchant_branch_id' => 'integer',
        'quality_brand_id' => 'integer',
        'price' => 'decimal:2',
        'previous_price' => 'decimal:2',
        'stock' => 'integer',
        'whole_sell_qty' => 'integer',
        'whole_sell_discount' => 'decimal:2',
        'preordered' => 'boolean',
        'minimum_qty' => 'integer',
        'stock_check' => 'integer',
        'status' => 'integer',
        'item_condition' => 'integer',
    ];

    /* =========================================================================
     |  RELATIONSHIPS
     | ========================================================================= */

    /**
     * The catalog item this merchant item belongs to.
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    /**
     * The user (merchant) who owns this item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias for user() - merchant relationship.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The quality brand for this item.
     */
    public function qualityBrand(): BelongsTo
    {
        return $this->belongsTo(QualityBrand::class, 'quality_brand_id');
    }

    /**
     * The branch this item belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(MerchantBranch::class, 'merchant_branch_id');
    }

    /**
     * Alias for branch() - merchantBranch relationship.
     */
    public function merchantBranch(): BelongsTo
    {
        return $this->belongsTo(MerchantBranch::class, 'merchant_branch_id');
    }

    /**
     * Photos for this merchant item.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(MerchantPhoto::class, 'merchant_item_id')
            ->where('status', 1)
            ->orderBy('sort_order');
    }

    /**
     * Primary photo for this merchant item.
     */
    public function primaryPhoto(): HasOne
    {
        return $this->hasOne(MerchantPhoto::class, 'merchant_item_id')
            ->where('is_primary', true)
            ->where('status', 1);
    }

    /**
     * Reviews for this merchant item.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(CatalogReview::class, 'merchant_item_id');
    }

    /* =========================================================================
     |  SCOPES
     | ========================================================================= */

    /**
     * Scope: Only active items (status = 1).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    /**
     * Scope: Only inactive items (status = 0).
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 0);
    }

    /**
     * Scope: Only items with stock > 0 or preordered.
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('stock', '>', 0)
              ->orWhere('preordered', true);
        });
    }

    /**
     * Scope: Only out of stock items.
     */
    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('stock', '<=', 0)
            ->where('preordered', false);
    }

    /**
     * Scope: Only normal (non-affiliate) items.
     */
    public function scopeNormal(Builder $query): Builder
    {
        return $query->where('item_type', 'normal');
    }

    /**
     * Scope: Only affiliate items.
     */
    public function scopeAffiliate(Builder $query): Builder
    {
        return $query->where('item_type', 'affiliate');
    }

    /**
     * Scope: Filter by merchant ID.
     */
    public function scopeForMerchant(Builder $query, int $merchantId): Builder
    {
        return $query->where('user_id', $merchantId);
    }

    /**
     * Scope: Filter by catalog item ID.
     */
    public function scopeForCatalogItem(Builder $query, int $catalogItemId): Builder
    {
        return $query->where('catalog_item_id', $catalogItemId);
    }

    /**
     * Scope: Filter by branch ID.
     */
    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('merchant_branch_id', $branchId);
    }

    /**
     * Scope: Alias for forBranch.
     */
    public function scopeInBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('merchant_branch_id', $branchId);
    }

    /**
     * Scope: Filter by quality brand ID.
     */
    public function scopeForQualityBrand(Builder $query, int $qualityBrandId): Builder
    {
        return $query->where('quality_brand_id', $qualityBrandId);
    }

    /**
     * Scope: Only items that have a branch assigned.
     */
    public function scopeWithBranch(Builder $query): Builder
    {
        return $query->whereNotNull('merchant_branch_id');
    }

    /**
     * Scope: Only items where the merchant is approved (is_merchant = 2).
     */
    public function scopeWithApprovedMerchant(Builder $query): Builder
    {
        return $query->whereHas('user', fn($q) => $q->where('is_merchant', 2));
    }

    /**
     * Scope: Active, in stock, with approved merchant.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->active()
            ->inStock()
            ->withApprovedMerchant();
    }

    /**
     * Scope: Order by price ascending (cheapest first).
     */
    public function scopeCheapest(Builder $query): Builder
    {
        return $query->orderBy('price', 'asc');
    }

    /**
     * Scope: Load full relations for display.
     */
    public function scopeWithFullRelations(Builder $query): Builder
    {
        return $query->with([
            'catalogItem:id,part_number,name,slug,thumbnail,weight',
            'user:id,name,shop_name,shop_name_ar,merchant_logo',
            'qualityBrand:id,code,name_en,name_ar,logo',
            'merchantBranch:id,branch_name,warehouse_name,city_id',
            'merchantBranch.city:id,city_name',
            'photos',
        ]);
    }

    /* =========================================================================
     |  ACCESSORS
     | ========================================================================= */

    /**
     * Check if this item is active.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 1;
    }

    /**
     * Check if this item is in stock.
     */
    public function getIsInStockAttribute(): bool
    {
        return $this->stock > 0 || $this->preordered;
    }

    /**
     * Check if this item is available (active and in stock).
     */
    public function getIsAvailableAttribute(): bool
    {
        return $this->is_active && $this->is_in_stock;
    }

    /**
     * Check if this item has a discount.
     */
    public function getHasDiscountAttribute(): bool
    {
        return $this->previous_price && $this->previous_price > $this->price;
    }

    /**
     * Get the discount percentage.
     */
    public function getDiscountPercentageAttribute(): float
    {
        if (!$this->has_discount || $this->previous_price <= 0) {
            return 0;
        }
        return round((($this->previous_price - $this->price) / $this->previous_price) * 100, 1);
    }

    /**
     * Get the condition label.
     */
    public function getConditionLabelAttribute(): string
    {
        return match ($this->item_condition) {
            1 => __('New'),
            2 => __('Used'),
            3 => __('Refurbished'),
            default => __('Unknown'),
        };
    }

    /**
     * Get the primary photo URL.
     */
    public function getPrimaryPhotoUrlAttribute(): ?string
    {
        if ($this->relationLoaded('primaryPhoto') && $this->primaryPhoto) {
            return $this->primaryPhoto->photo_url;
        }
        if ($this->relationLoaded('photos') && $this->photos->isNotEmpty()) {
            return $this->photos->first()->photo_url;
        }
        return $this->catalogItem?->thumbnail_url;
    }

    /* =========================================================================
     |  PRICE METHODS
     | ========================================================================= */

    /**
     * Calculate price with merchant commission.
     */
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

    /**
     * Get the price with commission applied.
     */
    public function getPriceWithCommission(): float
    {
        return $this->merchantSizePrice();
    }

    /**
     * Get the formatted price with commission.
     */
    public function getFormattedPrice(): string
    {
        return monetaryUnit()->convertAndFormat($this->getPriceWithCommission());
    }

    /**
     * Show price (alias for getFormattedPrice).
     */
    public function showPrice(): string
    {
        return $this->getFormattedPrice();
    }

    /**
     * Get the formatted previous price with commission.
     */
    public function getFormattedPreviousPrice(): ?string
    {
        if (!$this->has_discount) {
            return null;
        }

        $commission = $this->getMerchantCommission();
        $price = (float) $this->previous_price;

        if ($commission && $commission->is_active) {
            $price = $price + (float) ($commission->fixed_commission ?? 0) + ($price * (float) ($commission->percentage_commission ?? 0) / 100);
        }

        return monetaryUnit()->convertAndFormat($price);
    }

    /**
     * Get the discount percentage based on commission-adjusted prices.
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

    /**
     * Get merchant commission for this item's merchant.
     */
    protected function getMerchantCommission(): ?MerchantCommission
    {
        if (!$this->user_id) {
            return null;
        }

        return Cache::remember(
            "merchant_commission_{$this->user_id}",
            3600,
            fn() => MerchantCommission::where('user_id', $this->user_id)
                ->where('is_active', true)
                ->first()
        );
    }

    /* =========================================================================
     |  HELPER METHODS
     | ========================================================================= */

    /**
     * Check if this item is owned by a specific merchant.
     */
    public function isOwnedBy(int $merchantId): bool
    {
        return $this->user_id === $merchantId;
    }

    /**
     * Check if this item can be purchased.
     */
    public function canBePurchased(): bool
    {
        return $this->is_available && $this->user?->is_merchant === 2;
    }

    /**
     * Get the average rating for this merchant item.
     */
    public function getAverageRating(): float
    {
        if ($this->relationLoaded('reviews')) {
            return round($this->reviews->avg('rating') ?? 0, 1);
        }
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    /**
     * Get reviews count.
     */
    public function getReviewsCount(): int
    {
        if ($this->relationLoaded('reviews')) {
            return $this->reviews->count();
        }
        return $this->reviews()->count();
    }
}
