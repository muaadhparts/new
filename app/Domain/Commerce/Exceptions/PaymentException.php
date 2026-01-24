<?php

namespace App\Domain\Commerce\Exceptions;

use App\Domain\Platform\Exceptions\DomainException;

/**
 * Payment Exception
 *
 * Thrown for payment-related errors.
 */
class PaymentException extends DomainException
{
    protected string $errorCode = 'PAYMENT_ERROR';

    public function __construct(
        string $message = 'Payment failed',
        int $code = 400,
        array $context = []
    ) {
        parent::__construct($message, $code, null, $context);
    }

    /**
     * Payment declined
     */
    public static function declined(string $reason = 'unknown'): self
    {
        return new self(
            'Payment was declined',
            400,
            ['reason' => 'declined', 'decline_reason' => $reason]
        );
    }

    /**
     * Payment gateway error
     */
    public static function gatewayError(string $gateway, string $error): self
    {
        return new self(
            "Payment gateway error: {$error}",
            502,
            ['reason' => 'gateway_error', 'gateway' => $gateway, 'gateway_error' => $error]
        );
    }

    /**
     * Invalid payment method
     */
    public static function invalidMethod(string $method): self
    {
        return new self(
            "Invalid payment method: {$method}",
            400,
            ['reason' => 'invalid_method', 'method' => $method]
        );
    }

    /**
     * Payment amount mismatch
     */
    public static function amountMismatch(float $expected, float $received): self
    {
        return new self(
            "Payment amount mismatch. Expected: {$expected}, Received: {$received}",
            400,
            ['reason' => 'amount_mismatch', 'expected' => $expected, 'received' => $received]
        );
    }

    /**
     * Payment timeout
     */
    public static function timeout(): self
    {
        return new self(
            'Payment request timed out',
            504,
            ['reason' => 'timeout']
        );
    }

    /**
     * Refund failed
     */
    public static function refundFailed(int $purchaseId, string $reason): self
    {
        return new self(
            "Refund failed: {$reason}",
            400,
            ['reason' => 'refund_failed', 'purchase_id' => $purchaseId, 'refund_reason' => $reason]
        );
    }

    public function getDomain(): string
    {
        return 'Commerce';
    }
}
