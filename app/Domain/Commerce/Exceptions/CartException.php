<?php

namespace App\Domain\Commerce\Exceptions;

use App\Domain\Platform\Exceptions\DomainException;

/**
 * Cart Exception
 *
 * Thrown for cart-related errors.
 */
class CartException extends DomainException
{
    protected string $errorCode = 'CART_ERROR';

    public function __construct(
        string $message = 'Cart operation failed',
        int $code = 400,
        array $context = []
    ) {
        parent::__construct($message, $code, null, $context);
    }

    /**
     * Cart is empty
     */
    public static function empty(): self
    {
        return new self('Cart is empty', 400, ['reason' => 'empty']);
    }

    /**
     * Item not found in cart
     */
    public static function itemNotFound(int $itemId): self
    {
        return new self(
            "Item not found in cart",
            404,
            ['merchant_item_id' => $itemId, 'reason' => 'not_found']
        );
    }

    /**
     * Invalid quantity
     */
    public static function invalidQuantity(int $quantity, int $minimum = 1): self
    {
        return new self(
            "Quantity must be at least {$minimum}",
            400,
            ['quantity' => $quantity, 'minimum' => $minimum, 'reason' => 'invalid_quantity']
        );
    }

    /**
     * Item unavailable
     */
    public static function itemUnavailable(int $itemId, string $reason = 'unavailable'): self
    {
        return new self(
            "Item is no longer available",
            400,
            ['merchant_item_id' => $itemId, 'reason' => $reason]
        );
    }

    /**
     * Cart expired (session timeout)
     */
    public static function expired(): self
    {
        return new self('Cart session has expired', 400, ['reason' => 'expired']);
    }

    public function getDomain(): string
    {
        return 'Commerce';
    }

    public function shouldReport(): bool
    {
        return false;
    }
}
