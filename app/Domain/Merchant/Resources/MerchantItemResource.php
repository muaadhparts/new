<?php

namespace App\Domain\Merchant\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domain\Catalog\Resources\CatalogItemResource;

/**
 * Merchant Item Resource
 *
 * Transforms MerchantItem model for API responses.
 */
class MerchantItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'price' => monetaryUnit()->format($this->price),
            'price_raw' => $this->price,
            'discount' => $this->discount,
            'discount_type' => $this->discount_type,
            'discounted_price' => $this->when(
                $this->discount > 0,
                fn() => monetaryUnit()->format($this->getDiscountedPrice())
            ),
            'stock' => $this->stock,
            'is_in_stock' => $this->stock > 0,
            'is_active' => (bool) $this->status,
            'condition' => $this->condition,
            'warranty' => $this->warranty,
            'merchant' => new MerchantResource($this->whenLoaded('merchant')),
            'catalog_item' => new CatalogItemResource($this->whenLoaded('catalogItem')),
            'branch' => new MerchantBranchResource($this->whenLoaded('branch')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get discounted price
     */
    protected function getDiscountedPrice(): float
    {
        if ($this->discount_type === 'percent') {
            return $this->price * (1 - $this->discount / 100);
        }
        return max(0, $this->price - $this->discount);
    }
}
