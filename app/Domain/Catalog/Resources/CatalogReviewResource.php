<?php

namespace App\Domain\Catalog\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domain\Identity\Resources\UserResource;

/**
 * Catalog Review Resource
 *
 * Transforms CatalogReview model for API responses.
 */
class CatalogReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'is_verified_purchase' => (bool) $this->is_verified_purchase,
            'is_approved' => (bool) $this->status,
            'user' => new UserResource($this->whenLoaded('user')),
            'catalog_item' => new CatalogItemResource($this->whenLoaded('catalogItem')),
            'images' => $this->images,
            'helpful_count' => $this->helpful_count ?? 0,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
