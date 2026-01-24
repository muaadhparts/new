<?php

namespace App\Domain\Merchant\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domain\Shipping\Resources\CityResource;

/**
 * Merchant Branch Resource
 *
 * Transforms MerchantBranch model for API responses.
 */
class MerchantBranchResource extends JsonResource
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
            'phone' => $this->phone,
            'address' => $this->address,
            'is_main' => (bool) $this->is_main,
            'is_active' => (bool) $this->status,
            'city' => new CityResource($this->whenLoaded('city')),
            'coordinates' => $this->when(
                $this->latitude && $this->longitude,
                fn() => [
                    'latitude' => $this->latitude,
                    'longitude' => $this->longitude,
                ]
            ),
            'working_hours' => $this->working_hours,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
