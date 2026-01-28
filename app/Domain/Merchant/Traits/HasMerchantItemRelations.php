<?php

namespace App\Domain\Merchant\Traits;

use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\Models\CatalogReview;
use App\Domain\Catalog\Models\QualityBrand;
use App\Domain\Identity\Models\User;
use App\Domain\Merchant\Models\MerchantBranch;
use App\Domain\Merchant\Models\MerchantPhoto;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * HasMerchantItemRelations Trait
 *
 * Defines all relationships for MerchantItem model.
 * Extracted from model to keep it clean and focused.
 */
trait HasMerchantItemRelations
{
    /**
     * The catalog item this merchant item belongs to
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    /**
     * The merchant (user) who owns this item
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias for user relationship
     */
    public function merchant(): BelongsTo
    {
        return $this->user();
    }

    /**
     * The quality brand of this item
     */
    public function qualityBrand(): BelongsTo
    {
        return $this->belongsTo(QualityBrand::class, 'quality_brand_id');
    }

    /**
     * The branch this item belongs to
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(MerchantBranch::class, 'merchant_branch_id');
    }

    /**
     * Alias for branch relationship
     */
    public function merchantBranch(): BelongsTo
    {
        return $this->branch();
    }

    /**
     * Photos of this merchant item
     */
    public function photos(): HasMany
    {
        return $this->hasMany(MerchantPhoto::class, 'merchant_item_id');
    }

    /**
     * Primary photo of this item
     */
    public function primaryPhoto(): HasOne
    {
        return $this->hasOne(MerchantPhoto::class, 'merchant_item_id')
            ->where('is_primary', true)
            ->orWhere(function ($q) {
                $q->whereNull('is_primary')
                  ->orWhere('is_primary', false);
            })
            ->orderBy('is_primary', 'desc')
            ->orderBy('id', 'asc');
    }

    /**
     * Reviews for this merchant item
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(CatalogReview::class, 'merchant_item_id');
    }
}
