<?php

namespace App\Domain\Platform\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * JSON Cast
 *
 * Enhanced JSON cast with default value support.
 */
class JsonCast implements CastsAttributes
{
    /**
     * Default value when null or empty
     */
    protected mixed $default;

    /**
     * Whether to return object instead of array
     */
    protected bool $asObject;

    /**
     * Create a new cast instance.
     */
    public function __construct(mixed $default = [], bool $asObject = false)
    {
        $this->default = $default;
        $this->asObject = $asObject;
    }

    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return $this->default;
        }

        $decoded = json_decode($value, !$this->asObject);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->default;
        }

        return $decoded;
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            // Validate it's valid JSON
            json_decode($value);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $value;
            }
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
