<?php

namespace App\Domain\Identity\Events;

use App\Domain\Platform\Events\DomainEvent;

/**
 * Event fired when a user's email/phone is verified
 */
class UserVerifiedEvent extends DomainEvent
{
    public const TYPE_EMAIL = 'email';
    public const TYPE_PHONE = 'phone';

    public function __construct(
        public readonly int $userId,
        public readonly string $verificationType,
        public readonly string $verifiedValue
    ) {
        parent::__construct();
    }

    public function aggregateType(): string
    {
        return 'User';
    }

    public function aggregateId(): int|string
    {
        return $this->userId;
    }

    public function payload(): array
    {
        return [
            'user_id' => $this->userId,
            'verification_type' => $this->verificationType,
            'verified_value' => $this->maskedValue(),
        ];
    }

    /**
     * Check if email verification
     */
    public function isEmailVerification(): bool
    {
        return $this->verificationType === self::TYPE_EMAIL;
    }

    /**
     * Check if phone verification
     */
    public function isPhoneVerification(): bool
    {
        return $this->verificationType === self::TYPE_PHONE;
    }

    /**
     * Get masked value for logging
     */
    public function maskedValue(): string
    {
        if ($this->verificationType === self::TYPE_EMAIL) {
            $parts = explode('@', $this->verifiedValue);
            $name = $parts[0];
            $domain = $parts[1] ?? '';
            $masked = substr($name, 0, 2) . str_repeat('*', max(0, strlen($name) - 2));
            return $masked . '@' . $domain;
        }

        // Phone - show last 4 digits
        return str_repeat('*', max(0, strlen($this->verifiedValue) - 4))
            . substr($this->verifiedValue, -4);
    }
}
