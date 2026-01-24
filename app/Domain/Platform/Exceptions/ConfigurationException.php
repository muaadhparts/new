<?php

namespace App\Domain\Platform\Exceptions;

/**
 * Configuration Exception
 *
 * Thrown when platform configuration is missing or invalid.
 */
class ConfigurationException extends DomainException
{
    protected string $errorCode = 'CONFIGURATION_ERROR';

    public function __construct(
        string $message = 'Configuration error',
        ?string $configKey = null,
        array $context = []
    ) {
        if ($configKey) {
            $context['config_key'] = $configKey;
        }
        parent::__construct($message, 500, null, $context);
    }

    /**
     * Create for missing configuration
     */
    public static function missing(string $key): self
    {
        return new self("Configuration key '{$key}' is missing", $key);
    }

    /**
     * Create for invalid configuration value
     */
    public static function invalid(string $key, string $reason): self
    {
        return new self(
            "Configuration key '{$key}' is invalid: {$reason}",
            $key
        );
    }

    public function getDomain(): string
    {
        return 'Platform';
    }
}
