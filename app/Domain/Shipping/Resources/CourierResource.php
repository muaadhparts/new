<?php

namespace App\Domain\Shipping\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Courier Resource
 *
 * Transforms DeliveryCourier model for API responses.
 */
class CourierResource extends JsonResource
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
            'logo' => $this->logo,
            'tracking_url_template' => $this->tracking_url_template,
            'is_active' => (bool) $this->status,
            'supports_cod' => (bool) $this->supports_cod,
            'average_delivery_days' => $this->average_delivery_days,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
