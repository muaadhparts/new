<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
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

    protected $fillable = ['user_id', 'catalog_item_id', 'merchant_item_id', 'review', 'rating', 'review_date'];

    public $timestamps = false;

    // =========================================================
    // RELATIONS
    // =========================================================

    /**
     * The catalog item this review is for
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id')->withDefault();
    }

    /**
     * The merchant item this review is for
     */
    public function merchantItem(): BelongsTo
    {
        return $this->belongsTo(MerchantItem::class, 'merchant_item_id')->withDefault();
    }

    /**
     * The user who wrote this review
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    // =========================================================
    // STATIC METHODS
    // =========================================================

    /**
     * Calculate average score for a catalog item
     */
    public static function averageScore($catalogItemId): string
    {
        $stars = self::where('catalog_item_id', $catalogItemId)->avg('rating');
        return number_format($stars, 1);
    }

    /**
     * Calculate score percentage for a catalog item
     */
    public static function scorePercentage($catalogItemId): float
    {
        $stars = self::where('catalog_item_id', $catalogItemId)->avg('rating');
        $percentage = number_format((float)$stars, 1, '.', '') * 20;
        return $percentage;
    }

    /**
     * Get review count for a catalog item
     */
    public static function reviewCount($catalogItemId): string
    {
        $count = self::where('catalog_item_id', $catalogItemId)->count();
        return number_format($count);
    }

    /**
     * Get percentage of reviews with a specific score
     */
    public static function customScorePercentage($catalogItemId, $score): float
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
     * Get formatted percentage of reviews with a specific score
     */
    public static function customReviewPercentage($catalogItemId, $score): string
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
     * Get merchant score percentage
     */
    public static function merchantScorePercentage($user_id): float
    {
        $stars = self::whereHas('merchantItem', function ($query) use ($user_id) {
            $query->where('user_id', '=', $user_id);
        })->avg('rating');
        $percentage = number_format((float)$stars, 1, '.', '') * 20;
        return $percentage;
    }

    /**
     * Get merchant review count
     */
    public static function merchantReviewCount($user_id): int
    {
        $count = self::whereHas('merchantItem', function ($query) use ($user_id) {
            $query->where('user_id', '=', $user_id);
        })->count();
        return $count;
    }
}
