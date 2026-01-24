<?php

namespace App\Domain\Catalog\Exceptions;

use App\Domain\Platform\Exceptions\DomainException;

/**
 * Catalog Item Not Found Exception
 *
 * Thrown when a catalog item cannot be found.
 */
class CatalogItemNotFoundException extends DomainException
{
    protected string $errorCode = 'CATALOG_ITEM_NOT_FOUND';

    public function __construct(
        public readonly int|string $itemIdentifier,
        string $identifierType = 'id'
    ) {
        $message = "Catalog item not found with {$identifierType}: {$itemIdentifier}";

        parent::__construct($message, 404, null, [
            'identifier' => $itemIdentifier,
            'identifier_type' => $identifierType,
        ]);
    }

    /**
     * Create for item ID
     */
    public static function withId(int $id): self
    {
        return new self($id, 'id');
    }

    /**
     * Create for item SKU
     */
    public static function withSku(string $sku): self
    {
        return new self($sku, 'sku');
    }

    /**
     * Create for part number
     */
    public static function withPartNumber(string $partNumber): self
    {
        return new self($partNumber, 'part_number');
    }

    public function getDomain(): string
    {
        return 'Catalog';
    }

    public function getUserMessage(): string
    {
        return __('messages.item_not_found');
    }
}
