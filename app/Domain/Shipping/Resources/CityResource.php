<?php

namespace App\Domain\Shipping\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * City Resource
 *
 * Transforms City model for API responses.
 */
class CityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'code' => $this->code,
            'country' => new CountryResource($this->whenLoaded('country')),
            'is_active' => (bool) $this->status,
            'shipping_zone' => $this->shipping_zone,
        ];
    }
}
