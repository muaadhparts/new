<?php

namespace App\Domain\Merchant\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Merchant Resource
 *
 * Transforms User (merchant) model for API responses.
 */
class MerchantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'shop_name' => $this->shop_name,
            'shop_logo' => $this->shop_logo,
            'shop_banner' => $this->shop_banner,
            'slug' => $this->slug,
            'is_verified' => (bool) $this->is_verified,
            'is_active' => (bool) $this->status,
            'rating' => $this->average_rating,
            'reviews_count' => $this->reviews_count ?? 0,
            'items_count' => $this->when($this->merchant_items_count !== null, $this->merchant_items_count),
            'branches' => MerchantBranchResource::collection($this->whenLoaded('branches')),
            'settings' => new MerchantSettingResource($this->whenLoaded('merchantSetting')),
            'joined_at' => $this->created_at?->toISOString(),
        ];
    }
}
