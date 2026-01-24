<?php

namespace App\Domain\Commerce\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domain\Identity\Resources\UserResource;

/**
 * Purchase Resource
 *
 * Transforms Purchase model for API responses.
 */
class PurchaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'subtotal' => monetaryUnit()->format($this->subtotal),
            'shipping_cost' => monetaryUnit()->format($this->shipping_cost),
            'tax' => monetaryUnit()->format($this->tax),
            'discount' => monetaryUnit()->format($this->discount),
            'total' => monetaryUnit()->format($this->total),
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'notes' => $this->notes,
            'customer' => new UserResource($this->whenLoaded('user')),
            'merchant_purchases' => MerchantPurchaseResource::collection($this->whenLoaded('merchantPurchases')),
            'timeline' => PurchaseTimelineResource::collection($this->whenLoaded('timelines')),
            'items_count' => $this->items_count ?? 0,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get status label
     */
    protected function getStatusLabel(): string
    {
        return __("statuses.order.{$this->status}");
    }
}
