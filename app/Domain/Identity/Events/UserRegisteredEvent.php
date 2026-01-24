<?php

namespace App\Domain\Identity\Events;

use App\Domain\Platform\Events\DomainEvent;

/**
 * Event fired when a new user registers
 */
class UserRegisteredEvent extends DomainEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
        public readonly string $role,
        public readonly ?string $registrationSource = null,
        public readonly ?int $referrerId = null
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
            'email' => $this->email,
            'role' => $this->role,
            'registration_source' => $this->registrationSource,
            'referrer_id' => $this->referrerId,
            'was_referred' => $this->referrerId !== null,
        ];
    }

    /**
     * Check if user registered as customer
     */
    public function isCustomer(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if user registered as merchant
     */
    public function isMerchant(): bool
    {
        return $this->role === 'merchant';
    }

    /**
     * Check if registration was via referral
     */
    public function wasReferred(): bool
    {
        return $this->referrerId !== null;
    }

    /**
     * Check if registered via social login
     */
    public function isSocialRegistration(): bool
    {
        return in_array($this->registrationSource, ['google', 'facebook', 'apple']);
    }
}
