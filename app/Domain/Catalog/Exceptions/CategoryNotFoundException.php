<?php

namespace App\Domain\Catalog\Exceptions;

use App\Domain\Platform\Exceptions\DomainException;

/**
 * Category Not Found Exception
 *
 * Thrown when a category cannot be found.
 */
class CategoryNotFoundException extends DomainException
{
    protected string $errorCode = 'CATEGORY_NOT_FOUND';

    public function __construct(
        public readonly int|string $categoryIdentifier,
        string $identifierType = 'id'
    ) {
        $message = "Category not found with {$identifierType}: {$categoryIdentifier}";

        parent::__construct($message, 404, null, [
            'identifier' => $categoryIdentifier,
            'identifier_type' => $identifierType,
        ]);
    }

    /**
     * Create for category ID
     */
    public static function withId(int $id): self
    {
        return new self($id, 'id');
    }

    /**
     * Create for category slug
     */
    public static function withSlug(string $slug): self
    {
        return new self($slug, 'slug');
    }

    public function getDomain(): string
    {
        return 'Catalog';
    }

    public function getUserMessage(): string
    {
        return __('messages.category_not_found');
    }
}
