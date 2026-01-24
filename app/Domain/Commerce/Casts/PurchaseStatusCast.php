<?php

namespace App\Domain\Commerce\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Purchase Status Cast
 *
 * Casts purchase status with label and color support.
 */
class PurchaseStatusCast implements CastsAttributes
{
    /**
     * Status labels in Arabic
     */
    protected static array $labels = [
        'pending' => 'قيد الانتظار',
        'confirmed' => 'مؤكد',
        'processing' => 'قيد التجهيز',
        'shipped' => 'تم الشحن',
        'delivered' => 'تم التوصيل',
        'cancelled' => 'ملغي',
        'refunded' => 'مسترد',
    ];

    /**
     * Status colors
     */
    protected static array $colors = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'processing' => 'primary',
        'shipped' => 'secondary',
        'delivered' => 'success',
        'cancelled' => 'danger',
        'refunded' => 'dark',
    ];

    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value;
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        // Normalize status
        return strtolower(trim($value));
    }

    /**
     * Get label for a status
     */
    public static function getLabel(string $status): string
    {
        return static::$labels[$status] ?? $status;
    }

    /**
     * Get color for a status
     */
    public static function getColor(string $status): string
    {
        return static::$colors[$status] ?? 'secondary';
    }

    /**
     * Get all statuses
     */
    public static function all(): array
    {
        return array_keys(static::$labels);
    }
}
