<?php

namespace App\Domain\Catalog\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Category Resource
 *
 * Transforms Category model for API responses.
 */
class CategoryResource extends JsonResource
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
            'icon' => $this->icon,
            'image' => $this->image,
            'level' => $this->level,
            'parent_id' => $this->parent_id,
            'is_active' => (bool) $this->status,
            'parent' => new CategoryResource($this->whenLoaded('parent')),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'items_count' => $this->when($this->catalog_items_count !== null, $this->catalog_items_count),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
