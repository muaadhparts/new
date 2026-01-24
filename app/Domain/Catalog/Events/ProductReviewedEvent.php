<?php

namespace App\Domain\Catalog\Events;

use App\Domain\Platform\Events\DomainEvent;

/**
 * Event fired when a product receives a review
 */
class ProductReviewedEvent extends DomainEvent
{
    public function __construct(
        public readonly int $reviewId,
        public readonly int $catalogItemId,
        public readonly int $customerId,
        public readonly int $rating,
        public readonly ?string $comment = null
    ) {
        parent::__construct();
    }

    public function aggregateType(): string
    {
        return 'CatalogItem';
    }

    public function aggregateId(): int|string
    {
        return $this->catalogItemId;
    }

    public function payload(): array
    {
        return [
            'review_id' => $this->reviewId,
            'catalog_item_id' => $this->catalogItemId,
            'customer_id' => $this->customerId,
            'rating' => $this->rating,
            'has_comment' => !empty($this->comment),
        ];
    }

    /**
     * Check if this is a positive review
     */
    public function isPositive(): bool
    {
        return $this->rating >= 4;
    }

    /**
     * Check if this is a negative review
     */
    public function isNegative(): bool
    {
        return $this->rating <= 2;
    }

    /**
     * Check if review has comment
     */
    public function hasComment(): bool
    {
        return !empty($this->comment);
    }
}
