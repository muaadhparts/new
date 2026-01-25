<?php

namespace App\Domain\Platform\Contracts;

use App\Domain\Platform\Models\MonetaryUnit;

/**
 * MonetaryUnitInterface - Contract for monetary unit operations
 *
 * All monetary formatting and conversion MUST go through this interface.
 */
interface MonetaryUnitInterface
{
    /**
     * Get current monetary unit (from session or default)
     */
    public function getCurrent(): MonetaryUnit;

    /**
     * Get default monetary unit
     */
    public function getDefault(): MonetaryUnit;

    /**
     * Get monetary unit by code
     */
    public function getByCode(string $code): ?MonetaryUnit;

    /**
     * Get all active monetary units
     */
    public function getAll(): array;

    /**
     * Set current monetary unit by code
     */
    public function setCurrent(string $code): bool;

    /**
     * Convert amount from base currency to current
     */
    public function convert(float $amount): float;

    /**
     * Format amount with currency sign
     */
    public function format(float $amount): string;

    /**
     * Convert and format in one call
     */
    public function convertAndFormat(float $amount): string;

    /**
     * Get current currency sign
     */
    public function getSign(): string;

    /**
     * Get current currency code
     */
    public function getCode(): string;
}
