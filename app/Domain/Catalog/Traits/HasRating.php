<?php

namespace App\Domain\Catalog\Traits;

/**
 * Has Rating Trait
 *
 * Provides rating functionality for reviewable models.
 */
trait HasRating
{
    /**
     * Get rating column
     */
    public function getRatingColumn(): string
    {
        return $this->ratingColumn ?? 'rating';
    }

    /**
     * Get rating count column
     */
    public function getRatingCountColumn(): string
    {
        return $this->ratingCountColumn ?? 'rating_count';
    }

    /**
     * Get current rating
     */
    public function getRating(): float
    {
        return (float) ($this->{$this->getRatingColumn()} ?? 0);
    }

    /**
     * Get rating count
     */
    public function getRatingCount(): int
    {
        return (int) ($this->{$this->getRatingCountColumn()} ?? 0);
    }

    /**
     * Check if has ratings
     */
    public function hasRatings(): bool
    {
        return $this->getRatingCount() > 0;
    }

    /**
     * Get formatted rating
     */
    public function getFormattedRating(): string
    {
        return number_format($this->getRating(), 1);
    }

    /**
     * Get rating stars (1-5)
     */
    public function getRatingStars(): int
    {
        return (int) round($this->getRating());
    }

    /**
     * Get rating percentage (out of 100)
     */
    public function getRatingPercentage(): float
    {
        return ($this->getRating() / 5) * 100;
    }

    /**
     * Update rating from reviews
     */
    public function updateRatingFromReviews(): bool
    {
        if (!method_exists($this, 'reviews')) {
            return false;
        }

        $stats = $this->reviews()
            ->where('status', 'approved')
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as count')
            ->first();

        return $this->update([
            $this->getRatingColumn() => round($stats->avg_rating ?? 0, 2),
            $this->getRatingCountColumn() => $stats->count ?? 0,
        ]);
    }

    /**
     * Add rating
     */
    public function addRating(float $newRating): bool
    {
        $currentRating = $this->getRating();
        $currentCount = $this->getRatingCount();

        $newCount = $currentCount + 1;
        $newAverage = (($currentRating * $currentCount) + $newRating) / $newCount;

        return $this->update([
            $this->getRatingColumn() => round($newAverage, 2),
            $this->getRatingCountColumn() => $newCount,
        ]);
    }

    /**
     * Scope highly rated
     */
    public function scopeHighlyRated($query, float $minRating = 4.0)
    {
        return $query->where($this->getRatingColumn(), '>=', $minRating);
    }

    /**
     * Scope with ratings
     */
    public function scopeWithRatings($query)
    {
        return $query->where($this->getRatingCountColumn(), '>', 0);
    }

    /**
     * Scope order by rating
     */
    public function scopeOrderByRating($query, string $direction = 'desc')
    {
        return $query->orderBy($this->getRatingColumn(), $direction);
    }

    /**
     * Scope popular (high rating + count)
     */
    public function scopePopular($query)
    {
        return $query->withRatings()
            ->orderByRaw("{$this->getRatingColumn()} * LOG({$this->getRatingCountColumn()} + 1) DESC");
    }
}
