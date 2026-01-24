<?php

namespace App\Domain\Shipping\Exceptions;

use App\Domain\Platform\Exceptions\DomainException;

/**
 * Invalid Address Exception
 *
 * Thrown when a shipping address is invalid.
 */
class InvalidAddressException extends DomainException
{
    protected string $errorCode = 'INVALID_ADDRESS';

    /**
     * Fields with errors
     */
    protected array $fieldErrors = [];

    public function __construct(
        string $message = 'Invalid address',
        array $fieldErrors = [],
        array $context = []
    ) {
        $this->fieldErrors = $fieldErrors;
        $context['field_errors'] = $fieldErrors;

        parent::__construct($message, 400, null, $context);
    }

    /**
     * Create for missing required fields
     */
    public static function missingFields(array $fields): self
    {
        $errors = [];
        foreach ($fields as $field) {
            $errors[$field] = ["The {$field} field is required"];
        }

        return new self(
            'Address is missing required fields: ' . implode(', ', $fields),
            $errors
        );
    }

    /**
     * Create for invalid city
     */
    public static function invalidCity(string $city): self
    {
        return new self(
            "Invalid city: {$city}",
            ['city' => ["City '{$city}' is not recognized"]]
        );
    }

    /**
     * Create for invalid postal code
     */
    public static function invalidPostalCode(string $postalCode): self
    {
        return new self(
            "Invalid postal code: {$postalCode}",
            ['postal_code' => ['Invalid postal code format']]
        );
    }

    /**
     * Create for unverifiable address
     */
    public static function unverifiable(): self
    {
        return new self(
            'Address could not be verified',
            [],
            ['reason' => 'unverifiable']
        );
    }

    public function getDomain(): string
    {
        return 'Shipping';
    }

    /**
     * Get field-level errors
     */
    public function getFieldErrors(): array
    {
        return $this->fieldErrors;
    }

    public function shouldReport(): bool
    {
        return false;
    }
}
