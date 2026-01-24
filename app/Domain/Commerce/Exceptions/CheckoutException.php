<?php

namespace App\Domain\Commerce\Exceptions;

use App\Domain\Platform\Exceptions\DomainException;

/**
 * Checkout Exception
 *
 * Thrown for checkout-related errors.
 */
class CheckoutException extends DomainException
{
    protected string $errorCode = 'CHECKOUT_ERROR';

    public function __construct(
        string $message = 'Checkout failed',
        int $code = 400,
        array $context = []
    ) {
        parent::__construct($message, $code, null, $context);
    }

    /**
     * Checkout validation failed
     */
    public static function validationFailed(array $errors): self
    {
        return new self(
            'Checkout validation failed',
            422,
            ['errors' => $errors, 'reason' => 'validation']
        );
    }

    /**
     * No shipping address
     */
    public static function noShippingAddress(): self
    {
        return new self(
            'Shipping address is required',
            400,
            ['reason' => 'no_address']
        );
    }

    /**
     * No payment method
     */
    public static function noPaymentMethod(): self
    {
        return new self(
            'Payment method is required',
            400,
            ['reason' => 'no_payment']
        );
    }

    /**
     * Shipping not available
     */
    public static function shippingUnavailable(string $reason = ''): self
    {
        return new self(
            'Shipping is not available for your location' . ($reason ? ": {$reason}" : ''),
            400,
            ['reason' => 'shipping_unavailable']
        );
    }

    /**
     * Minimum order not met
     */
    public static function minimumNotMet(float $minimum, float $current, string $currency = 'SAR'): self
    {
        return new self(
            "Minimum order amount is {$minimum} {$currency}. Current: {$current} {$currency}",
            400,
            ['minimum' => $minimum, 'current' => $current, 'currency' => $currency, 'reason' => 'minimum_not_met']
        );
    }

    /**
     * Checkout already completed
     */
    public static function alreadyCompleted(int $purchaseId): self
    {
        return new self(
            'This checkout has already been completed',
            400,
            ['purchase_id' => $purchaseId, 'reason' => 'already_completed']
        );
    }

    public function getDomain(): string
    {
        return 'Commerce';
    }

    public function shouldReport(): bool
    {
        return $this->context['reason'] ?? '' === 'validation' ? false : true;
    }
}
