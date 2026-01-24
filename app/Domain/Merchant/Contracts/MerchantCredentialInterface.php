<?php

namespace App\Domain\Merchant\Contracts;

/**
 * MerchantCredentialInterface - Contract for merchant credentials
 *
 * All merchant credential operations MUST go through this interface.
 */
interface MerchantCredentialInterface
{
    /**
     * Get a merchant credential
     */
    public function get(
        int $userId,
        string $serviceName,
        string $keyName = 'api_key',
        string $environment = 'live'
    ): ?string;

    /**
     * Set a merchant credential
     */
    public function set(
        int $userId,
        string $serviceName,
        string $keyName,
        string $value,
        string $environment = 'live'
    ): bool;

    /**
     * Check if merchant has credential
     */
    public function has(
        int $userId,
        string $serviceName,
        string $keyName = 'api_key',
        string $environment = 'live'
    ): bool;

    /**
     * Get all credentials for a merchant service
     */
    public function getAllForService(int $userId, string $serviceName): array;

    /**
     * Delete a credential
     */
    public function delete(
        int $userId,
        string $serviceName,
        string $keyName,
        string $environment = 'live'
    ): bool;

    /**
     * Clear credential cache for merchant
     */
    public function clearCache(int $userId): void;
}
