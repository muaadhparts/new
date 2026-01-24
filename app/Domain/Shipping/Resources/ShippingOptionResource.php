<?php

namespace App\Domain\Shipping\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shipping Option Resource
 *
 * Transforms shipping option data for API responses.
 */
class ShippingOptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'] ?? null,
            'name' => $this->resource['name'] ?? null,
            'courier' => $this->when(
                isset($this->resource['courier']),
                fn() => new CourierResource($this->resource['courier'])
            ),
            'price' => monetaryUnit()->format($this->resource['price'] ?? 0),
            'price_raw' => $this->resource['price'] ?? 0,
            'estimated_days' => $this->resource['estimated_days'] ?? null,
            'estimated_delivery' => $this->resource['estimated_delivery'] ?? null,
            'is_express' => $this->resource['is_express'] ?? false,
            'supports_cod' => $this->resource['supports_cod'] ?? true,
        ];
    }
}
