<?php

namespace App\Domain\Identity\Exceptions;

use App\Domain\Platform\Exceptions\DomainException;

/**
 * Authentication Exception
 *
 * Thrown for authentication failures.
 */
class AuthenticationException extends DomainException
{
    protected string $errorCode = 'AUTHENTICATION_ERROR';

    public function __construct(
        string $message = 'Authentication failed',
        int $code = 401,
        array $context = []
    ) {
        parent::__construct($message, $code, null, $context);
    }

    /**
     * Invalid credentials
     */
    public static function invalidCredentials(): self
    {
        return new self(
            'Invalid email or password',
            401,
            ['reason' => 'invalid_credentials']
        );
    }

    /**
     * Account not found
     */
    public static function accountNotFound(string $identifier): self
    {
        return new self(
            'Account not found',
            401,
            ['identifier' => $identifier, 'reason' => 'not_found']
        );
    }

    /**
     * Account not verified
     */
    public static function notVerified(int $userId): self
    {
        return new self(
            'Please verify your email address',
            403,
            ['user_id' => $userId, 'reason' => 'not_verified']
        );
    }

    /**
     * Account locked
     */
    public static function accountLocked(int $userId, ?int $minutesRemaining = null): self
    {
        $message = 'Account is temporarily locked';
        if ($minutesRemaining) {
            $message .= ". Try again in {$minutesRemaining} minutes";
        }

        return new self(
            $message,
            403,
            ['user_id' => $userId, 'minutes_remaining' => $minutesRemaining, 'reason' => 'locked']
        );
    }

    /**
     * Account suspended
     */
    public static function accountSuspended(int $userId): self
    {
        return new self(
            'Your account has been suspended',
            403,
            ['user_id' => $userId, 'reason' => 'suspended']
        );
    }

    /**
     * Token expired
     */
    public static function tokenExpired(): self
    {
        return new self(
            'Authentication token has expired',
            401,
            ['reason' => 'token_expired']
        );
    }

    /**
     * Invalid token
     */
    public static function invalidToken(): self
    {
        return new self(
            'Invalid authentication token',
            401,
            ['reason' => 'invalid_token']
        );
    }

    public function getDomain(): string
    {
        return 'Identity';
    }

    public function getUserMessage(): string
    {
        return match ($this->context['reason'] ?? '') {
            'invalid_credentials' => __('auth.failed'),
            'not_verified' => __('auth.not_verified'),
            'locked' => __('auth.throttle'),
            'suspended' => __('auth.suspended'),
            default => $this->getMessage(),
        };
    }

    public function shouldReport(): bool
    {
        return false; // Auth failures are expected
    }
}
