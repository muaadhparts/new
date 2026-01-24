<?php

namespace App\Domain\Merchant\Exceptions;

use App\Domain\Platform\Exceptions\DomainException;

/**
 * Merchant Not Found Exception
 *
 * Thrown when a merchant cannot be found.
 */
class MerchantNotFoundException extends DomainException
{
    protected string $errorCode = 'MERCHANT_NOT_FOUND';

    public function __construct(
        public readonly int|string $merchantIdentifier,
        string $identifierType = 'id'
    ) {
        $message = "Merchant not found with {$identifierType}: {$merchantIdentifier}";

        parent::__construct($message, 404, null, [
            'identifier' => $merchantIdentifier,
            'identifier_type' => $identifierType,
        ]);
    }

    /**
     * Create for merchant ID
     */
    public static function withId(int $id): self
    {
        return new self($id, 'id');
    }

    /**
     * Create for merchant slug
     */
    public static function withSlug(string $slug): self
    {
        return new self($slug, 'slug');
    }

    public function getDomain(): string
    {
        return 'Merchant';
    }

    public function getUserMessage(): string
    {
        return __('messages.merchant_not_found');
    }
}
