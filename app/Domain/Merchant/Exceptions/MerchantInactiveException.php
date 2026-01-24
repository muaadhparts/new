<?php

namespace App\Domain\Merchant\Exceptions;

use App\Domain\Platform\Exceptions\DomainException;

/**
 * Merchant Inactive Exception
 *
 * Thrown when attempting to interact with an inactive merchant.
 */
class MerchantInactiveException extends DomainException
{
    protected string $errorCode = 'MERCHANT_INACTIVE';

    public function __construct(
        public readonly int $merchantId,
        public readonly string $status = 'inactive'
    ) {
        $message = "Merchant (ID: {$merchantId}) is {$status}";

        parent::__construct($message, 403, null, [
            'merchant_id' => $merchantId,
            'status' => $status,
        ]);
    }

    /**
     * Create for suspended merchant
     */
    public static function suspended(int $merchantId): self
    {
        return new self($merchantId, 'suspended');
    }

    /**
     * Create for pending merchant
     */
    public static function pending(int $merchantId): self
    {
        return new self($merchantId, 'pending approval');
    }

    public function getDomain(): string
    {
        return 'Merchant';
    }

    public function getUserMessage(): string
    {
        return __('messages.merchant_unavailable');
    }

    public function shouldReport(): bool
    {
        return false;
    }
}
