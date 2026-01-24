<?php

namespace App\Domain\Platform\Exceptions;

use Exception;
use Throwable;

/**
 * Base Domain Exception
 *
 * All domain-specific exceptions should extend this class.
 * Provides common functionality for error tracking and context.
 */
abstract class DomainException extends Exception
{
    /**
     * Additional context for the exception
     */
    protected array $context = [];

    /**
     * Error code for API responses
     */
    protected string $errorCode = 'DOMAIN_ERROR';

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get exception context
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get error code for API
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get domain name this exception belongs to
     */
    abstract public function getDomain(): string;

    /**
     * Convert to array for logging/API response
     */
    public function toArray(): array
    {
        return [
            'error_code' => $this->errorCode,
            'message' => $this->getMessage(),
            'domain' => $this->getDomain(),
            'context' => $this->context,
        ];
    }

    /**
     * Get user-friendly message (can be overridden for localization)
     */
    public function getUserMessage(): string
    {
        return $this->getMessage();
    }

    /**
     * Check if exception should be reported to error tracking
     */
    public function shouldReport(): bool
    {
        return true;
    }
}
