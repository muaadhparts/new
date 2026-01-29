<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\CatalogReview;

/**
 * ReviewStatisticsService - Centralized review statistics service
 *
 * Single source of truth for all review statistics calculations.
 * Replaces static methods in CatalogReview model.
 *
 * Domain: Catalog
 * Responsibility: Calculate review scores, percentages, and counts
 *
 * ARCHITECTURE:
 * - Service Layer Pattern
 * - Single Responsibility Principle
 * - Dependency Injection ready
 */
class ReviewStatisticsService
{
    /**
     * Get average review score for a catalog item
     *
     * @param int $catalogItemId
     * @return string Formatted average score (e.g., "4.5")
     */
    public function getAverageScore(int $catalogItemId): string
    {
        $stars = CatalogReview::where('catalog_item_id', $catalogItemId)->avg('rating');
        return number_format($stars ?? 0, 1);
    }

    /**
     * Get score percentage for a catalog item (out of 100%)
     *
     * @param int $catalogItemId
     * @return float Score percentage (0-100)
     */
    public function getScorePercentage(int $catalogItemId): float
    {
        $stars = CatalogReview::where('catalog_item_id', $catalogItemId)->avg('rating');
        $percentage = number_format((float)($stars ?? 0), 1, '.', '') * 20;
        return $percentage;
    }

    /**
     * Get total review count for a catalog item
     *
     * @param int $catalogItemId
     * @return string Formatted review count
     */
    public function getReviewCount(int $catalogItemId): string
    {
        $count = CatalogReview::where('catalog_item_id', $catalogItemId)->count();
        return number_format($count);
    }

    /**
     * Get percentage of reviews with a specific score
     *
     * @param int $catalogItemId
     * @param int $score Rating score (1-5)
     * @return float Percentage of reviews with this score
     */
    public function getScoreDistributionPercentage(int $catalogItemId, int $score): float
    {
        $totalCount = CatalogReview::where('catalog_item_id', $catalogItemId)->count();
        
        if ($totalCount == 0) {
            return 0;
        }

        $scoreCount = CatalogReview::where('catalog_item_id', $catalogItemId)
            ->where('rating', $score)
            ->count();

        return ($scoreCount / $totalCount) * 100;
    }

    /**
     * Get formatted percentage of reviews with a specific score
     *
     * @param int $catalogItemId
     * @param int $score Rating score (1-5)
     * @return string Formatted percentage (e.g., "45%")
     */
    public function getScoreDistributionPercentageFormatted(int $catalogItemId, int $score): string
    {
        $percentage = $this->getScoreDistributionPercentage($catalogItemId, $score);
        return number_format($percentage, 0) . '%';
    }

    /**
     * Get average review score for a merchant (across all their items)
     *
     * @param int $userId Merchant user ID
     * @return float Score percentage (0-100)
     */
    public function getMerchantScorePercentage(int $userId): float
    {
        $stars = CatalogReview::whereHas('merchantItem', function ($query) use ($userId) {
            $query->where('user_id', '=', $userId);
        })->avg('rating');

        $percentage = number_format((float)($stars ?? 0), 1, '.', '') * 20;
        return $percentage;
    }

    /**
     * Get total review count for a merchant (across all their items)
     *
     * @param int $userId Merchant user ID
     * @return int Total review count
     */
    public function getMerchantReviewCount(int $userId): int
    {
        return CatalogReview::whereHas('merchantItem', function ($query) use ($userId) {
            $query->where('user_id', '=', $userId);
        })->count();
    }

    /**
     * Get review statistics summary for a catalog item
     *
     * @param int $catalogItemId
     * @return array Statistics array with scores, counts, and distribution
     */
    public function getStatisticsSummary(int $catalogItemId): array
    {
        return [
            'average_score' => $this->getAverageScore($catalogItemId),
            'score_percentage' => $this->getScorePercentage($catalogItemId),
            'review_count' => $this->getReviewCount($catalogItemId),
            'distribution' => [
                5 => $this->getScoreDistributionPercentage($catalogItemId, 5),
                4 => $this->getScoreDistributionPercentage($catalogItemId, 4),
                3 => $this->getScoreDistributionPercentage($catalogItemId, 3),
                2 => $this->getScoreDistributionPercentage($catalogItemId, 2),
                1 => $this->getScoreDistributionPercentage($catalogItemId, 1),
            ],
        ];
    }

    /**
     * Get merchant statistics summary
     *
     * @param int $userId Merchant user ID
     * @return array Statistics array with merchant scores and counts
     */
    public function getMerchantStatisticsSummary(int $userId): array
    {
        return [
            'score_percentage' => $this->getMerchantScorePercentage($userId),
            'review_count' => $this->getMerchantReviewCount($userId),
        ];
    }
}
