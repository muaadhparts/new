<?php

namespace App\Domain\Identity\Exceptions;

use App\Domain\Platform\Exceptions\DomainException;

/**
 * User Not Found Exception
 *
 * Thrown when a user cannot be found.
 */
class UserNotFoundException extends DomainException
{
    protected string $errorCode = 'USER_NOT_FOUND';

    public function __construct(
        public readonly int|string $userIdentifier,
        string $identifierType = 'id'
    ) {
        $message = "User not found with {$identifierType}: {$userIdentifier}";

        parent::__construct($message, 404, null, [
            'identifier' => $userIdentifier,
            'identifier_type' => $identifierType,
        ]);
    }

    /**
     * Create for user ID
     */
    public static function withId(int $id): self
    {
        return new self($id, 'id');
    }

    /**
     * Create for email
     */
    public static function withEmail(string $email): self
    {
        return new self($email, 'email');
    }

    /**
     * Create for phone
     */
    public static function withPhone(string $phone): self
    {
        return new self($phone, 'phone');
    }

    public function getDomain(): string
    {
        return 'Identity';
    }

    public function getUserMessage(): string
    {
        return __('messages.user_not_found');
    }
}
