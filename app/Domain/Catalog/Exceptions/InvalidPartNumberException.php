<?php

namespace App\Domain\Catalog\Exceptions;

use App\Domain\Platform\Exceptions\DomainException;

/**
 * Invalid Part Number Exception
 *
 * Thrown when a part number format is invalid.
 */
class InvalidPartNumberException extends DomainException
{
    protected string $errorCode = 'INVALID_PART_NUMBER';

    public function __construct(
        public readonly string $partNumber,
        string $reason = 'invalid format'
    ) {
        $message = "Invalid part number '{$partNumber}': {$reason}";

        parent::__construct($message, 400, null, [
            'part_number' => $partNumber,
            'reason' => $reason,
        ]);
    }

    /**
     * Create for empty part number
     */
    public static function empty(): self
    {
        return new self('', 'Part number cannot be empty');
    }

    /**
     * Create for too short part number
     */
    public static function tooShort(string $partNumber, int $minLength = 3): self
    {
        return new self($partNumber, "Part number must be at least {$minLength} characters");
    }

    /**
     * Create for invalid characters
     */
    public static function invalidCharacters(string $partNumber): self
    {
        return new self($partNumber, 'Part number contains invalid characters');
    }

    public function getDomain(): string
    {
        return 'Catalog';
    }

    public function shouldReport(): bool
    {
        return false;
    }
}
