<?php

namespace App\Domain\Identity\Events;

use App\Domain\Platform\Events\DomainEvent;

/**
 * Event fired when a user logs in
 */
class UserLoginEvent extends DomainEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly string $loginMethod,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
        public readonly bool $rememberMe = false
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
            'login_method' => $this->loginMethod,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'remember_me' => $this->rememberMe,
        ];
    }

    /**
     * Check if social login
     */
    public function isSocialLogin(): bool
    {
        return in_array($this->loginMethod, ['google', 'facebook', 'apple']);
    }

    /**
     * Check if password login
     */
    public function isPasswordLogin(): bool
    {
        return $this->loginMethod === 'password';
    }

    /**
     * Check if remember me was used
     */
    public function usedRememberMe(): bool
    {
        return $this->rememberMe;
    }
}
