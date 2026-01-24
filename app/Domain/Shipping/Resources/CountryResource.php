<?php

namespace App\Domain\Shipping\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Country Resource
 *
 * Transforms Country model for API responses.
 */
class CountryResource extends JsonResource
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
            'iso3' => $this->iso3,
            'phone_code' => $this->phone_code,
            'currency' => $this->currency,
            'flag' => $this->flag,
            'is_active' => (bool) $this->status,
        ];
    }
}
