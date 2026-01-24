<?php

namespace App\Domain\Commerce\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cart Data Cast
 *
 * Casts cart JSON data with item structure validation.
 */
class CartDataCast implements CastsAttributes
{
    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $data = is_string($value) ? json_decode($value, true) : $value;

        if (!is_array($data)) {
            return [];
        }

        // Normalize cart items structure
        return array_map(function ($item) {
            return [
                'merchant_item_id' => $item['merchant_item_id'] ?? $item['id'] ?? null,
                'quantity' => (int) ($item['quantity'] ?? 1),
                'price' => (float) ($item['price'] ?? 0),
                'name' => $item['name'] ?? '',
                'options' => $item['options'] ?? [],
            ];
        }, $data);
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
     * Get cart total from items
     */
    public static function calculateTotal(array $items): float
    {
        return array_reduce($items, function ($total, $item) {
            return $total + (($item['price'] ?? 0) * ($item['quantity'] ?? 1));
        }, 0.0);
    }
}
