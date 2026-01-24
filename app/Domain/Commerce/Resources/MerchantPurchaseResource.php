<?php

namespace App\Domain\Commerce\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domain\Merchant\Resources\MerchantResource;
use App\Domain\Shipping\Resources\ShipmentResource;

/**
 * Merchant Purchase Resource
 *
 * Transforms MerchantPurchase model for API responses.
 */
class MerchantPurchaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'subtotal' => monetaryUnit()->format($this->subtotal),
            'shipping_cost' => monetaryUnit()->format($this->shipping_cost),
            'tax' => monetaryUnit()->format($this->tax),
            'total' => monetaryUnit()->format($this->total),
            'items' => $this->getCartItems(),
            'merchant' => new MerchantResource($this->whenLoaded('merchant')),
            'shipment' => new ShipmentResource($this->whenLoaded('shipment')),
            'notes' => $this->merchant_notes,
            'accepted_at' => $this->accepted_at?->toISOString(),
            'shipped_at' => $this->shipped_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * Get status label
     */
    protected function getStatusLabel(): string
    {
        return __("statuses.merchant_order.{$this->status}");
    }

    /**
     * Get cart items
     */
    protected function getCartItems(): array
    {
        $cart = $this->cart;
        if (is_string($cart)) {
            $cart = json_decode($cart, true);
        }
        return $cart ?? [];
    }
}
