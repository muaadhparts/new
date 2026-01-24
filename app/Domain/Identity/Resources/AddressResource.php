<?php

namespace App\Domain\Identity\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domain\Shipping\Resources\CityResource;

/**
 * Address Resource
 *
 * Transforms user address data for API responses.
 */
class AddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'street' => $this->street,
            'building' => $this->building,
            'apartment' => $this->apartment,
            'landmark' => $this->landmark,
            'postal_code' => $this->postal_code,
            'city' => new CityResource($this->whenLoaded('city')),
            'city_name' => $this->city_name ?? $this->city?->name,
            'is_default' => (bool) $this->is_default,
            'coordinates' => $this->when(
                $this->latitude && $this->longitude,
                fn() => [
                    'latitude' => $this->latitude,
                    'longitude' => $this->longitude,
                ]
            ),
            'full_address' => $this->getFullAddress(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * Get full address string
     */
    protected function getFullAddress(): string
    {
        $parts = array_filter([
            $this->street,
            $this->building,
            $this->apartment ? "Apt {$this->apartment}" : null,
            $this->city_name ?? $this->city?->name,
        ]);

        return implode(', ', $parts);
    }
}
