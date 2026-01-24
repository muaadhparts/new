<?php

namespace App\Domain\Shipping\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shipment Resource
 *
 * Transforms ShipmentTracking model for API responses.
 */
class ShipmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_number' => $this->tracking_number,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'courier' => new CourierResource($this->whenLoaded('courier')),
            'origin' => [
                'city' => $this->origin_city,
                'address' => $this->origin_address,
            ],
            'destination' => [
                'city' => $this->destination_city,
                'address' => $this->destination_address,
            ],
            'recipient' => [
                'name' => $this->recipient_name,
                'phone' => $this->recipient_phone,
            ],
            'weight' => $this->weight,
            'dimensions' => $this->when(
                $this->length && $this->width && $this->height,
                fn() => [
                    'length' => $this->length,
                    'width' => $this->width,
                    'height' => $this->height,
                ]
            ),
            'estimated_delivery' => $this->estimated_delivery?->toISOString(),
            'shipped_at' => $this->shipped_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'tracking_url' => $this->tracking_url,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get status label
     */
    protected function getStatusLabel(): string
    {
        return __("statuses.shipment.{$this->status}");
    }
}
