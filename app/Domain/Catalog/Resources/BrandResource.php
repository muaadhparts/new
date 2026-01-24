<?php

namespace App\Domain\Catalog\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Brand Resource
 *
 * Transforms Brand model for API responses.
 */
class BrandResource extends JsonResource
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
            'slug' => $this->slug,
            'logo' => $this->logo,
            'description' => $this->description,
            'is_active' => (bool) $this->status,
            'items_count' => $this->when($this->catalog_items_count !== null, $this->catalog_items_count),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
