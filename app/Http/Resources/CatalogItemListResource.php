<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

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
        // Get vendor context from request or catalog item attribute
        $vendorId = (int) ($request->get('user') ?? $this->getAttribute('vendor_user_id') ?? 0);

        // Get vendor-aware pricing using the merchant_items system
        $currentPrice = method_exists($this, 'ApishowPrice')
            ? (string) $this->ApishowPrice($vendorId ?: null)
            : (string) 0;

        $previousPrice = method_exists($this, 'ApishowPreviousPrice')
            ? (string) $this->ApishowPreviousPrice($vendorId ?: null)
            : (string) 0;

        // Get active merchant item for additional data
        $mp = method_exists($this, 'activeMerchant')
            ? $this->activeMerchant($vendorId ?: null)
            : null;

        return [
            'id' => $this->id,
            'title' => $this->name,
            'thumbnail' => \Illuminate\Support\Facades\Storage::url($this->thumbnail) ?? asset('assets/images/noimage.png'),
            'rating' =>  $this->catalogReviews()->avg('rating') > 0 ? (string) round($this->catalogReviews()->avg('rating'), 2) : (string) round(0.00, 2),
            'current_price' => $currentPrice,
            'previous_price' => $previousPrice,
            // is_discount and discount_date are on merchant_items, not catalog_items
            'sale_end_date' => $this->when($mp && $mp->is_discount == 1, $mp->discount_date ?? null),

            // Add vendor context for API consumers
            'vendor' => $mp ? [
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
