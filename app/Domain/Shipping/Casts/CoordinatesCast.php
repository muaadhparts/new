<?php

namespace App\Domain\Shipping\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use App\Domain\Shipping\ValueObjects\Coordinates;

/**
 * Coordinates Cast
 *
 * Casts latitude/longitude to Coordinates value object.
 */
class CoordinatesCast implements CastsAttributes
{
    /**
     * The latitude attribute name
     */
    protected string $latitudeKey;

    /**
     * The longitude attribute name
     */
    protected string $longitudeKey;

    /**
     * Create a new cast instance.
     */
    public function __construct(string $latitudeKey = 'latitude', string $longitudeKey = 'longitude')
    {
        $this->latitudeKey = $latitudeKey;
        $this->longitudeKey = $longitudeKey;
    }

    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Coordinates
    {
        $latitude = $attributes[$this->latitudeKey] ?? null;
        $longitude = $attributes[$this->longitudeKey] ?? null;

        if ($latitude === null || $longitude === null) {
            return null;
        }

        return new Coordinates((float) $latitude, (float) $longitude);
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [
                $this->latitudeKey => null,
                $this->longitudeKey => null,
            ];
        }

        if ($value instanceof Coordinates) {
            return [
                $this->latitudeKey => $value->getLatitude(),
                $this->longitudeKey => $value->getLongitude(),
            ];
        }

        if (is_array($value)) {
            return [
                $this->latitudeKey => $value['latitude'] ?? $value[0] ?? null,
                $this->longitudeKey => $value['longitude'] ?? $value[1] ?? null,
            ];
        }

        return [
            $this->latitudeKey => null,
            $this->longitudeKey => null,
        ];
    }
}
