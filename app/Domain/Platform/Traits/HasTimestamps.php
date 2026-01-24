<?php

namespace App\Domain\Platform\Traits;

use Carbon\Carbon;

/**
 * Has Timestamps Trait
 *
 * Provides enhanced timestamp functionality.
 */
trait HasTimestamps
{
    /**
     * Get created at formatted
     */
    public function getCreatedAtFormattedAttribute(): string
    {
        return $this->created_at?->format('Y-m-d H:i') ?? '';
    }

    /**
     * Get updated at formatted
     */
    public function getUpdatedAtFormattedAttribute(): string
    {
        return $this->updated_at?->format('Y-m-d H:i') ?? '';
    }

    /**
     * Get created at for humans
     */
    public function getCreatedAtHumanAttribute(): string
    {
        return $this->created_at?->diffForHumans() ?? '';
    }

    /**
     * Get updated at for humans
     */
    public function getUpdatedAtHumanAttribute(): string
    {
        return $this->updated_at?->diffForHumans() ?? '';
    }

    /**
     * Get created at in Arabic format
     */
    public function getCreatedAtArabicAttribute(): string
    {
        if (!$this->created_at) {
            return '';
        }

        return $this->created_at->locale('ar')->translatedFormat('j F Y - h:i A');
    }

    /**
     * Check if created today
     */
    public function wasCreatedToday(): bool
    {
        return $this->created_at?->isToday() ?? false;
    }

    /**
     * Check if updated today
     */
    public function wasUpdatedToday(): bool
    {
        return $this->updated_at?->isToday() ?? false;
    }

    /**
     * Check if created within days
     */
    public function wasCreatedWithinDays(int $days): bool
    {
        return $this->created_at?->greaterThan(now()->subDays($days)) ?? false;
    }

    /**
     * Check if is new (created within 7 days)
     */
    public function isNew(): bool
    {
        return $this->wasCreatedWithinDays(7);
    }

    /**
     * Scope to created today
     */
    public function scopeCreatedToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope to created this week
     */
    public function scopeCreatedThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    /**
     * Scope to created this month
     */
    public function scopeCreatedThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    /**
     * Scope to created between dates
     */
    public function scopeCreatedBetween($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Scope to recent (last N days)
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
