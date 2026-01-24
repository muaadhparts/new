<?php

namespace App\Domain\Catalog\Events;

use App\Domain\Platform\Events\DomainEvent;

/**
 * Event fired when a product is added to favorites
 */
class ProductFavoritedEvent extends DomainEvent
{
    public function __construct(
        public readonly int $catalogItemId,
        public readonly int $customerId,
        public readonly bool $added = true
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
            'action' => $this->added ? 'added' : 'removed',
        ];
    }

    /**
     * Check if item was added to favorites
     */
    public function wasAdded(): bool
    {
        return $this->added;
    }

    /**
     * Check if item was removed from favorites
     */
    public function wasRemoved(): bool
    {
        return !$this->added;
    }
}
