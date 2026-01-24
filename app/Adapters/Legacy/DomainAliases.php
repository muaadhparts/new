<?php

namespace App\Adapters\Legacy;

/**
 * ============================================================================
 * DOMAIN ALIASES - BACKWARD COMPATIBILITY LAYER
 * ============================================================================
 *
 * This class provides aliases from old model/service paths to new Domain paths.
 * It ensures existing code continues to work during the refactoring process.
 *
 * How it works:
 * - Old: App\Models\PlatformSetting → New: App\Domain\Platform\Models\PlatformSetting
 * - Old: App\Services\MonetaryUnitService → New: App\Domain\Platform\Services\MonetaryUnitService
 *
 * Register in: App\Providers\AppServiceProvider::register()
 *
 * ============================================================================
 */
class DomainAliases
{
    /**
     * Model aliases: old path => new path
     */
    protected static array $modelAliases = [
        // Platform Domain Models
        'App\\Models\\PlatformSetting' => \App\Domain\Platform\Models\PlatformSetting::class,
        'App\\Models\\Language' => \App\Domain\Platform\Models\Language::class,
        'App\\Models\\MonetaryUnit' => \App\Domain\Platform\Models\MonetaryUnit::class,
        'App\\Models\\Page' => \App\Domain\Platform\Models\Page::class,
        'App\\Models\\HomePageTheme' => \App\Domain\Platform\Models\HomePageTheme::class,
        'App\\Models\\FrontendSetting' => \App\Domain\Platform\Models\FrontendSetting::class,
    ];

    /**
     * Service aliases: old path => new path
     */
    protected static array $serviceAliases = [
        // Platform Domain Services
        'App\\Services\\MonetaryUnitService' => \App\Domain\Platform\Services\MonetaryUnitService::class,
    ];

    /**
     * Register all aliases
     */
    public static function register(): void
    {
        self::registerModelAliases();
        self::registerServiceBindings();
    }

    /**
     * Register model class aliases
     */
    protected static function registerModelAliases(): void
    {
        foreach (self::$modelAliases as $old => $new) {
            if (!class_exists($old, false)) {
                class_alias($new, $old);
            }
        }
    }

    /**
     * Register service container bindings
     */
    protected static function registerServiceBindings(): void
    {
        foreach (self::$serviceAliases as $old => $new) {
            app()->bind($old, $new);
        }
    }

    /**
     * Get all model aliases
     */
    public static function getModelAliases(): array
    {
        return self::$modelAliases;
    }

    /**
     * Get all service aliases
     */
    public static function getServiceAliases(): array
    {
        return self::$serviceAliases;
    }
}
