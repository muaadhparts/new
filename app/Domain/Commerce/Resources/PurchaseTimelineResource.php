<?php

namespace App\Domain\Commerce\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Purchase Timeline Resource
 *
 * Transforms PurchaseTimeline model for API responses.
 */
class PurchaseTimelineResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'comment' => $this->comment,
            'metadata' => $this->metadata,
            'actor_type' => $this->actor_type,
            'actor_name' => $this->getActorName(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * Get status label
     */
    protected function getStatusLabel(): string
    {
        return __("statuses.order.{$this->status}");
    }

    /**
     * Get actor name
     */
    protected function getActorName(): ?string
    {
        if ($this->relationLoaded('actor') && $this->actor) {
            return $this->actor->name;
        }
        return null;
    }
}
