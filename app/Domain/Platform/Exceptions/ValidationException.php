<?php

namespace App\Domain\Platform\Exceptions;

/**
 * Validation Exception
 *
 * Thrown when domain validation fails.
 */
class ValidationException extends DomainException
{
    protected string $errorCode = 'VALIDATION_ERROR';

    /**
     * Validation errors by field
     */
    protected array $errors = [];

    public function __construct(
        string $message = 'Validation failed',
        array $errors = [],
        array $context = []
    ) {
        $this->errors = $errors;
        parent::__construct($message, 422, null, $context);
    }

    /**
     * Create from field errors
     */
    public static function withErrors(array $errors): self
    {
        $message = 'The given data was invalid.';
        return new self($message, $errors);
    }

    /**
     * Create for a single field
     */
    public static function forField(string $field, string $message): self
    {
        return new self($message, [$field => [$message]]);
    }

    public function getDomain(): string
    {
        return 'Platform';
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'errors' => $this->errors,
        ]);
    }

    public function shouldReport(): bool
    {
        return false; // Validation errors are expected
    }
}
