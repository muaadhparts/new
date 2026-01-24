<?php

namespace App\Domain\Shipping\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use App\Domain\Shipping\ValueObjects\Address;

/**
 * Address Cast
 *
 * Casts JSON address data to Address value object.
 */
class AddressCast implements CastsAttributes
{
    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Address
    {
        if ($value === null) {
            return null;
        }

        $data = is_string($value) ? json_decode($value, true) : $value;

        if (!is_array($data)) {
            return null;
        }

        // Ensure required fields have default values
        $data['street'] = $data['street'] ?? '';
        $data['city'] = $data['city'] ?? '';
        $data['country'] = $data['country'] ?? 'Saudi Arabia';

        try {
            return Address::fromArray($data);
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Address) {
            return json_encode($value->toArray(), JSON_UNESCAPED_UNICODE);
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $value;
    }
}
