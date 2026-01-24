<?php

namespace App\Domain\Catalog\Events;

use App\Domain\Platform\Events\DomainEvent;

/**
 * Event fired when a product is viewed
 */
class ProductViewedEvent extends DomainEvent
{
    public function __construct(
        public readonly int $catalogItemId,
        public readonly ?int $customerId = null,
        public readonly ?string $sessionId = null,
        public readonly ?string $source = null
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
            'catalog_item_id' => $this->catalogItemId,
            'customer_id' => $this->customerId,
            'session_id' => $this->sessionId,
            'source' => $this->source,
            'is_authenticated' => $this->customerId !== null,
        ];
    }

    /**
     * Check if view is from authenticated user
     */
    public function isAuthenticated(): bool
    {
        return $this->customerId !== null;
    }

    /**
     * Check if view is from search
     */
    public function isFromSearch(): bool
    {
        return $this->source === 'search';
    }

    /**
     * Check if view is from category browse
     */
    public function isFromCategory(): bool
    {
        return $this->source === 'category';
    }
}
