<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Identity\Models\User;
use App\Domain\Merchant\Models\MerchantItem;

/**
 * CatalogReview Model - Product reviews
 *
 * Domain: Catalog
 * Table: catalog_reviews
 *
 * @property int $id
 * @property int $user_id
 * @property int $catalog_item_id
 * @property int|null $merchant_item_id
 * @property string|null $review
 * @property int $rating
 * @property string|null $review_date
 */
class CatalogReview extends Model
{
    protected $table = 'catalog_reviews';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'catalog_item_id',
        'merchant_item_id',
        'review',
        'rating',
        'review_date',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'catalog_item_id' => 'integer',
        'merchant_item_id' => 'integer',
        'rating' => 'integer',
        'review_date' => 'datetime',
    ];

    /* =========================================================================
     |  RELATIONSHIPS
     | ========================================================================= */

    /**
     * The catalog item this review is for.
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    /**
     * The merchant item this review is for.
     */
    public function merchantItem(): BelongsTo
    {
        return $this->belongsTo(MerchantItem::class, 'merchant_item_id');
    }

    /**
     * The user who wrote this review.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /* =========================================================================
     |  SCOPES
     | ========================================================================= */

    /**
     * Scope: Filter by catalog item ID.
     */
    public function scopeForCatalogItem(Builder $query, int $catalogItemId): Builder
    {
        return $query->where('catalog_item_id', $catalogItemId);
    }

    /**
     * Scope: Filter by merchant item ID.
     */
    public function scopeForMerchantItem(Builder $query, int $merchantItemId): Builder
    {
        return $query->where('merchant_item_id', $merchantItemId);
    }

    /**
     * Scope: Filter by merchant user ID.
     */
    public function scopeForMerchant(Builder $query, int $merchantId): Builder
    {
        return $query->whereHas('merchantItem', fn($q) => $q->where('user_id', $merchantId));
    }

    /**
     * Scope: Filter by specific rating.
     */
    public function scopeByRating(Builder $query, int $rating): Builder
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope: Filter high-rated reviews (4+).
     */
    public function scopeHighRated(Builder $query, int $minRating = 4): Builder
    {
        return $query->where('rating', '>=', $minRating);
    }

    /**
     * Scope: Order by most recent.
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('review_date', 'desc');
    }

    /* =========================================================================
     |  ACCESSORS
     | ========================================================================= */

    /**
     * Get rating as stars.
     */
    public function getRatingStarsAttribute(): string
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    /**
     * Get reviewer name.
     */
    public function getReviewerNameAttribute(): string
    {
        if ($this->relationLoaded('user') && $this->user) {
            return $this->user->name;
        }
        return __('Anonymous');
    }

    /**
     * Get formatted date.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->review_date?->format('Y-m-d') ?? '';
    }

    /* =========================================================================
     |  STATIC METHODS
     | ========================================================================= */

    /**
     * Calculate average score for a catalog item.
     */
    public static function averageScore(int $catalogItemId): string
    {
        $stars = self::where('catalog_item_id', $catalogItemId)->avg('rating');
        return number_format($stars ?? 0, 1);
    }

    /**
     * Calculate score percentage for a catalog item.
     */
    public static function scorePercentage(int $catalogItemId): float
    {
        $stars = self::where('catalog_item_id', $catalogItemId)->avg('rating');
        $percentage = number_format((float)($stars ?? 0), 1, '.', '') * 20;
        return $percentage;
    }

    /**
     * Get review count for a catalog item.
     */
    public static function reviewCount(int $catalogItemId): string
    {
        $count = self::where('catalog_item_id', $catalogItemId)->count();
        return number_format($count);
    }

    /**
     * Get percentage of reviews with a specific score.
     */
    public static function customScorePercentage(int $catalogItemId, int $score): float
    {
        $totalCount = self::where('catalog_item_id', $catalogItemId)->count();
        if ($totalCount == 0) {
            return 0;
        }
        $scoreCount = self::where('catalog_item_id', $catalogItemId)->where('rating', $score)->count();
        $avg = ($scoreCount / $totalCount) * 100;
        return $avg;
    }

    /**
     * Get formatted percentage of reviews with a specific score.
     */
    public static function customReviewPercentage(int $catalogItemId, int $score): string
    {
        $totalCount = self::where('catalog_item_id', $catalogItemId)->count();
        if ($totalCount == 0) {
            return '0%';
        }
        $scoreCount = self::where('catalog_item_id', $catalogItemId)->where('rating', $score)->count();
        $avg = ($scoreCount / $totalCount) * 100;
        return round($avg, 2) . '%';
    }

    /**
     * Get merchant score percentage.
     */
    public static function merchantScorePercentage(int $userId): float
    {
        $stars = self::whereHas('merchantItem', function ($query) use ($userId) {
            $query->where('user_id', '=', $userId);
        })->avg('rating');
        $percentage = number_format((float)($stars ?? 0), 1, '.', '') * 20;
        return $percentage;
    }

    /**
     * Get merchant review count.
     */
    public static function merchantReviewCount(int $userId): int
    {
        $count = self::whereHas('merchantItem', function ($query) use ($userId) {
            $query->where('user_id', '=', $userId);
        })->count();
        return $count;
    }
}
