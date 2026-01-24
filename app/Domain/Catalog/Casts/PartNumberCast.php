<?php

namespace App\Domain\Catalog\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use App\Domain\Catalog\ValueObjects\PartNumber;

/**
 * Part Number Cast
 *
 * Casts part number strings to PartNumber value object.
 */
class PartNumberCast implements CastsAttributes
{
    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?PartNumber
    {
        if ($value === null || $value === '') {
            return null;
        }

        return new PartNumber($value);
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof PartNumber) {
            return $value->getNormalized();
        }

        // Normalize the part number before storage
        return strtoupper(preg_replace('/[\s\-]/', '', (string) $value));
    }
}
