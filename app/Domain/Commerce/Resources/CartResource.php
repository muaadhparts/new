<?php

namespace App\Domain\Commerce\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Cart Resource
 *
 * Transforms cart data for API responses.
 */
class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'items' => CartItemResource::collection($this->resource['items'] ?? []),
            'merchants_count' => $this->resource['merchants_count'] ?? 0,
            'items_count' => $this->resource['items_count'] ?? 0,
            'subtotal' => monetaryUnit()->format($this->resource['subtotal'] ?? 0),
            'shipping' => monetaryUnit()->format($this->resource['shipping'] ?? 0),
            'tax' => monetaryUnit()->format($this->resource['tax'] ?? 0),
            'discount' => monetaryUnit()->format($this->resource['discount'] ?? 0),
            'total' => monetaryUnit()->format($this->resource['total'] ?? 0),
            'currency' => monetaryUnit()->getCurrent()->code ?? 'SAR',
        ];
    }
}
