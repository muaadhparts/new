<?php

namespace App\Domain\Commerce\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Cart Item Resource
 *
 * Transforms cart item data for API responses.
 */
class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'] ?? null,
            'merchant_item_id' => $this->resource['merchant_item_id'] ?? null,
            'catalog_item_id' => $this->resource['catalog_item_id'] ?? null,
            'name' => $this->resource['name'] ?? null,
            'thumbnail' => $this->resource['thumbnail'] ?? null,
            'sku' => $this->resource['sku'] ?? null,
            'quantity' => $this->resource['quantity'] ?? 1,
            'price' => monetaryUnit()->format($this->resource['price'] ?? 0),
            'price_raw' => $this->resource['price'] ?? 0,
            'subtotal' => monetaryUnit()->format(
                ($this->resource['price'] ?? 0) * ($this->resource['quantity'] ?? 1)
            ),
            'merchant' => [
                'id' => $this->resource['merchant_id'] ?? null,
                'name' => $this->resource['merchant_name'] ?? null,
            ],
            'branch' => $this->when(
                isset($this->resource['branch_id']),
                fn() => [
                    'id' => $this->resource['branch_id'],
                    'name' => $this->resource['branch_name'] ?? null,
                ]
            ),
            'in_stock' => $this->resource['in_stock'] ?? true,
            'available_stock' => $this->resource['available_stock'] ?? null,
        ];
    }
}
