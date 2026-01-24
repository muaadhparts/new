<?php

namespace App\Domain\Platform\Contracts;

/**
 * PlatformSettingsInterface - Contract for platform settings
 *
 * Single source for platform-wide configuration.
 */
interface PlatformSettingsInterface
{
    /**
     * Get a setting value by key
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a setting value
     */
    public function set(string $key, mixed $value): bool;

    /**
     * Check if setting exists
     */
    public function has(string $key): bool;

    /**
     * Get all settings
     */
    public function all(): array;

    /**
     * Get settings by group
     */
    public function getGroup(string $group): array;

    /**
     * Clear settings cache
     */
    public function clearCache(): void;
}
