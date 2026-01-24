<?php

namespace App\Domain\Catalog\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Catalog Item Resource
 *
 * Transforms CatalogItem model for API responses.
 */
class CatalogItemResource extends JsonResource
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
            'sku' => $this->sku,
            'part_number' => $this->part_number,
            'description' => $this->description,
            'description_ar' => $this->description_ar,
            'thumbnail' => $this->thumbnail,
            'image' => $this->image,
            'images' => $this->images,
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'merchant_items' => MerchantItemResource::collection($this->whenLoaded('merchantItems')),
            'reviews_count' => $this->when($this->reviews_count !== null, $this->reviews_count),
            'average_rating' => $this->when($this->average_rating !== null, $this->average_rating),
            'min_price' => $this->when($this->min_price !== null, fn() => monetaryUnit()->format($this->min_price)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get additional data for the resource.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'currency' => monetaryUnit()->getCurrent()->code ?? 'SAR',
            ],
        ];
    }
}
