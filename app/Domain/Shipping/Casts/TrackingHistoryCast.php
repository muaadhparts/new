<?php

namespace App\Domain\Shipping\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Tracking History Cast
 *
 * Handles shipment tracking history array.
 */
class TrackingHistoryCast implements CastsAttributes
{
    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $history = is_string($value) ? json_decode($value, true) : $value;

        if (!is_array($history)) {
            return [];
        }

        // Sort by timestamp descending (newest first)
        usort($history, function ($a, $b) {
            return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
        });

        return $history;
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === []) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Add tracking event.
     */
    public static function addEvent(array $history, string $status, string $location, ?string $note = null): array
    {
        $history[] = [
            'status' => $status,
            'location' => $location,
            'note' => $note,
            'timestamp' => now()->timestamp,
            'datetime' => now()->toIso8601String(),
        ];

        return $history;
    }

    /**
     * Get latest status.
     */
    public static function getLatestStatus(array $history): ?string
    {
        if (empty($history)) {
            return null;
        }

        // History is sorted, first item is latest
        return $history[0]['status'] ?? null;
    }
}
