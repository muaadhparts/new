<?php

namespace App\Domain\Merchant\Exceptions;

use App\Domain\Platform\Exceptions\DomainException;

/**
 * Invalid Price Exception
 *
 * Thrown when a price value is invalid.
 */
class InvalidPriceException extends DomainException
{
    protected string $errorCode = 'INVALID_PRICE';

    public function __construct(
        public readonly float $price,
        string $reason = 'invalid'
    ) {
        $message = "Invalid price: {$price}. Reason: {$reason}";

        parent::__construct($message, 400, null, [
            'price' => $price,
            'reason' => $reason,
        ]);
    }

    /**
     * Create for negative price
     */
    public static function negative(float $price): self
    {
        return new self($price, 'Price cannot be negative');
    }

    /**
     * Create for zero price
     */
    public static function zero(): self
    {
        return new self(0, 'Price cannot be zero');
    }

    /**
     * Create for price below cost
     */
    public static function belowCost(float $price, float $cost): self
    {
        $exception = new self($price, "Price is below cost ({$cost})");
        $exception->context['cost'] = $cost;
        return $exception;
    }

    /**
     * Create for invalid discount
     */
    public static function invalidDiscount(float $discount): self
    {
        return new self($discount, 'Discount must be between 0 and 100');
    }

    public function getDomain(): string
    {
        return 'Merchant';
    }

    public function shouldReport(): bool
    {
        return false;
    }
}
