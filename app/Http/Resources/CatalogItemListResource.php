<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * CatalogItemListResource
 *
 * API Resource for catalog item listing.
 * Gets merchant data from eager-loaded merchantItems relation.
 */
class CatalogItemListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Get first merchant item from eager-loaded relation
        $mp = $this->relationLoaded('merchantItems')
            ? $this->merchantItems->first()
            : null;

        $merchantId = $mp?->user_id ?? 0;

        // Price from merchant item
        $currentPrice = $mp
            ? (string) $this->ApishowPrice($merchantId)
            : '0';

        $previousPrice = ($mp && $mp->previous_price > 0)
            ? (string) $this->ApishowPreviousPrice($merchantId)
            : '0';

        // Rating - use pre-computed if available
        $rating = $this->catalog_reviews_avg_rating ?? 0;

        return [
            'id' => $this->id,
            'name' => $this->localized_name,
            'thumbnail' => $this->thumbnail_url,
            'rating' => (string) round($rating, 2),
            'current_price' => $currentPrice,
            'previous_price' => $previousPrice,
            'merchant' => $mp ? [
                'user_id' => $mp->user_id,
                'merchant_item_id' => $mp->id,
                'stock' => (int) $mp->stock,
                'status' => (int) $mp->status,
            ] : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
