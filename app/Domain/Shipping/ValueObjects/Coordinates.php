<?php

namespace App\Domain\Shipping\ValueObjects;

use InvalidArgumentException;

/**
 * Coordinates Value Object
 *
 * Immutable representation of geographic coordinates.
 */
final class Coordinates
{
    private float $latitude;
    private float $longitude;

    private function __construct(float $latitude, float $longitude)
    {
        if ($latitude < -90 || $latitude > 90) {
            throw new InvalidArgumentException('Latitude must be between -90 and 90');
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new InvalidArgumentException('Longitude must be between -180 and 180');
        }

        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    /**
     * Create from lat/lng
     */
    public static function create(float $latitude, float $longitude): self
    {
        return new self($latitude, $longitude);
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (float) ($data['lat'] ?? $data['latitude'] ?? 0),
            (float) ($data['lng'] ?? $data['longitude'] ?? 0)
        );
    }

    /**
     * Create from string "lat,lng"
     */
    public static function fromString(string $coords): self
    {
        $parts = explode(',', $coords);

        if (count($parts) !== 2) {
            throw new InvalidArgumentException('Invalid coordinates format. Expected "lat,lng"');
        }

        return new self(
            (float) trim($parts[0]),
            (float) trim($parts[1])
        );
    }

    // Getters
    public function latitude(): float
    {
        return $this->latitude;
    }

    public function longitude(): float
    {
        return $this->longitude;
    }

    public function lat(): float
    {
        return $this->latitude;
    }

    public function lng(): float
    {
        return $this->longitude;
    }

    /**
     * Calculate distance to another point in kilometers
     * Uses Haversine formula
     */
    public function distanceTo(Coordinates $other): float
    {
        $earthRadius = 6371; // km

        $latDiff = deg2rad($other->latitude - $this->latitude);
        $lngDiff = deg2rad($other->longitude - $this->longitude);

        $a = sin($latDiff / 2) * sin($latDiff / 2)
            + cos(deg2rad($this->latitude)) * cos(deg2rad($other->latitude))
            * sin($lngDiff / 2) * sin($lngDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Check if within radius of another point
     */
    public function isWithinRadius(Coordinates $center, float $radiusKm): bool
    {
        return $this->distanceTo($center) <= $radiusKm;
    }

    /**
     * Check if equals another coordinate (with precision)
     */
    public function equals(Coordinates $other, int $precision = 6): bool
    {
        return round($this->latitude, $precision) === round($other->latitude, $precision)
            && round($this->longitude, $precision) === round($other->longitude, $precision);
    }

    /**
     * Get Google Maps URL
     */
    public function googleMapsUrl(): string
    {
        return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return "{$this->latitude},{$this->longitude}";
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'lat' => $this->latitude,
            'lng' => $this->longitude,
        ];
    }
}
