<?php

namespace App\Services;

use App\Models\MonetaryUnit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * ============================================================================
 * MONETARY UNIT SERVICE - SINGLE SOURCE OF TRUTH
 * ============================================================================
 *
 * This service provides centralized monetary unit management.
 * ALL monetary unit operations MUST go through this service.
 *
 * Usage:
 * ------
 * // Get service instance
 * $monetaryService = app(MonetaryUnitService::class);
 *
 * // Or via helper
 * monetaryUnit()->format(100);
 *
 * // Or via Facade (if registered)
 * MonetaryUnit::format(100);
 *
 * Key Methods:
 * ------------
 * getCurrent()       - Get current monetary unit (session or default)
 * getDefault()       - Get default monetary unit (is_default = 1)
 * getByCode('SAR')   - Get monetary unit by code
 * getAll()           - Get all monetary units
 * convert($amount)   - Convert from base to current
 * format($amount)    - Format with sign
 * convertAndFormat() - Convert + format in one call
 *
 * Configuration:
 * --------------
 * Base Monetary Unit: SAR (configured in config/muaadh.php)
 * Session Key: 'monetary_unit'
 * Cache TTL: 1 hour
 *
 * ============================================================================
 */
class MonetaryUnitService
{
    /**
     * Session key for storing selected monetary unit
     */
    public const SESSION_KEY = 'monetary_unit';

    /**
     * Base monetary unit code (all prices stored in this unit)
     */
    public const BASE_MONETARY_UNIT = 'SAR';

    /**
     * Cache TTL in seconds (1 hour)
     */
    public const CACHE_TTL = 3600;

    /**
     * Current monetary unit instance
     */
    protected ?MonetaryUnit $current = null;

    /**
     * Default monetary unit instance
     */
    protected ?MonetaryUnit $default = null;

    /**
     * Monetary format setting (0 = sign before, 1 = sign after)
     */
    protected int $format = 0;

    /**
     * Decimal separator
     */
    protected string $decimalSeparator = '.';

    /**
     * Thousand separator
     */
    protected string $thousandSeparator = ',';

    /**
     * Constructor - loads settings
     */
    public function __construct()
    {
        $this->loadSettings();
    }

    // ========================================================================
    // CORE GETTERS
    // ========================================================================

    /**
     * Get current monetary unit (from session or default)
     *
     * This is the SINGLE source of truth for current monetary unit.
     * All code should use this instead of direct MonetaryUnit queries.
     */
    public function getCurrent(): ?MonetaryUnit
    {
        if ($this->current) {
            return $this->current;
        }

        // Check session key
        if (Session::has(self::SESSION_KEY)) {
            $id = Session::get(self::SESSION_KEY);
            $this->current = $this->getById($id);
        }

        // Fallback to default
        if (!$this->current) {
            $this->current = $this->getDefault();
        }

        return $this->current;
    }

    /**
     * Get default monetary unit (is_default = 1)
     */
    public function getDefault(): ?MonetaryUnit
    {
        if ($this->default) {
            return $this->default;
        }

        $this->default = Cache::remember(
            'monetary_unit_default',
            self::CACHE_TTL,
            fn() => MonetaryUnit::where('is_default', 1)->first()
        );

        return $this->default;
    }

    /**
     * Get monetary unit by ID
     */
    public function getById(int $id): ?MonetaryUnit
    {
        return Cache::remember(
            "monetary_unit_{$id}",
            self::CACHE_TTL,
            fn() => MonetaryUnit::find($id)
        );
    }

    /**
     * Get monetary unit by code (e.g., 'SAR', 'USD')
     */
    public function getByCode(string $code): ?MonetaryUnit
    {
        return Cache::remember(
            "monetary_unit_code_{$code}",
            self::CACHE_TTL,
            fn() => MonetaryUnit::where('name', $code)->first()
        );
    }

    /**
     * Get base monetary unit (SAR)
     */
    public function getBase(): ?MonetaryUnit
    {
        return $this->getByCode(self::BASE_MONETARY_UNIT);
    }

    /**
     * Get all monetary units
     */
    public function getAll(): \Illuminate\Support\Collection
    {
        return Cache::remember(
            'monetary_units_all',
            self::CACHE_TTL,
            fn() => MonetaryUnit::all()
        );
    }

    // ========================================================================
    // MONETARY UNIT INFO GETTERS
    // ========================================================================

    /**
     * Get current monetary unit code (e.g., 'SAR', 'USD')
     */
    public function getCode(): string
    {
        return $this->getCurrent()->name ?? self::BASE_MONETARY_UNIT;
    }

    /**
     * Get current monetary unit sign (e.g., 'ر.س', '$')
     */
    public function getSign(): string
    {
        return $this->getCurrent()->sign ?? 'ر.س';
    }

    /**
     * Get current monetary unit exchange rate value
     */
    public function getValue(): float
    {
        return (float) ($this->getCurrent()->value ?? 1);
    }

    /**
     * Get base monetary unit code
     */
    public function getBaseCode(): string
    {
        return self::BASE_MONETARY_UNIT;
    }

    /**
     * Get base monetary unit sign
     */
    public function getBaseSign(): string
    {
        return $this->getBase()->sign ?? 'ر.س';
    }

    /**
     * Get monetary unit format (0 = sign before, 1 = sign after)
     */
    public function getFormat(): int
    {
        return $this->format;
    }

    /**
     * Check if current monetary unit is the base
     */
    public function isBase(): bool
    {
        return $this->getCode() === self::BASE_MONETARY_UNIT;
    }

    // ========================================================================
    // CONVERSION METHODS
    // ========================================================================

    /**
     * Convert amount from base monetary unit to current
     */
    public function convert(float $amount): float
    {
        return round($amount * $this->getValue(), 2);
    }

    /**
     * Convert amount from current monetary unit to base
     */
    public function convertToBase(float $amount): float
    {
        $value = $this->getValue();
        return $value > 0 ? round($amount / $value, 2) : $amount;
    }

    /**
     * Convert amount between two specific monetary units
     */
    public function convertBetween(float $amount, string $fromCode, string $toCode): float
    {
        if ($fromCode === $toCode) {
            return $amount;
        }

        $from = $this->getByCode($fromCode);
        $to = $this->getByCode($toCode);

        if (!$from || !$to) {
            return $amount;
        }

        // Convert to base first, then to target
        $baseAmount = $from->value > 0 ? $amount / $from->value : $amount;
        return round($baseAmount * $to->value, 2);
    }

    // ========================================================================
    // FORMATTING METHODS
    // ========================================================================

    /**
     * Format number with decimal and thousand separators
     */
    public function formatNumber(float $amount, int $decimals = 2): string
    {
        return number_format($amount, $decimals, $this->decimalSeparator, $this->thousandSeparator);
    }

    /**
     * Format amount with monetary unit sign (no conversion)
     */
    public function format(float $amount, int $decimals = 2): string
    {
        $formatted = $this->formatNumber($amount, $decimals);
        $sign = $this->getSign();

        return $this->format === 0
            ? $sign . $formatted
            : $formatted . $sign;
    }

    /**
     * Format amount with specific monetary unit sign
     */
    public function formatWith(float $amount, string $sign, int $decimals = 2): string
    {
        $formatted = $this->formatNumber($amount, $decimals);

        return $this->format === 0
            ? $sign . $formatted
            : $formatted . $sign;
    }

    /**
     * Convert AND format in one call
     */
    public function convertAndFormat(float $amount, int $decimals = 2): string
    {
        return $this->format($this->convert($amount), $decimals);
    }

    /**
     * Format amount in base monetary unit
     */
    public function formatBase(float $amount, int $decimals = 2): string
    {
        return $this->formatWith($amount, $this->getBaseSign(), $decimals);
    }

    // ========================================================================
    // SESSION MANAGEMENT
    // ========================================================================

    /**
     * Set current monetary unit by ID
     */
    public function setCurrent(int $id): bool
    {
        $monetaryUnit = $this->getById($id);
        if (!$monetaryUnit) {
            return false;
        }

        Session::put(self::SESSION_KEY, $id);
        $this->current = $monetaryUnit;

        return true;
    }

    /**
     * Set current monetary unit by code
     */
    public function setCurrentByCode(string $code): bool
    {
        $monetaryUnit = $this->getByCode($code);
        if (!$monetaryUnit) {
            return false;
        }

        return $this->setCurrent($monetaryUnit->id);
    }

    /**
     * Reset to default monetary unit
     */
    public function resetToDefault(): void
    {
        Session::forget(self::SESSION_KEY);
        $this->current = null;
    }

    // ========================================================================
    // CACHE MANAGEMENT
    // ========================================================================

    /**
     * Clear all monetary unit caches
     */
    public function clearCache(): void
    {
        Cache::forget('monetary_unit_default');
        Cache::forget('monetary_units_all');

        // Clear individual unit caches
        foreach ($this->getAll() as $unit) {
            Cache::forget("monetary_unit_{$unit->id}");
            Cache::forget("monetary_unit_code_{$unit->name}");
        }

        // Reset instance cache
        $this->current = null;
        $this->default = null;
    }

    // ========================================================================
    // DATA FOR VIEWS/JS
    // ========================================================================

    /**
     * Get monetary unit data for JavaScript
     */
    public function toArray(): array
    {
        $current = $this->getCurrent();

        return [
            'id' => $current->id ?? null,
            'code' => $this->getCode(),
            'sign' => $this->getSign(),
            'value' => $this->getValue(),
            'format' => $this->format,
            'decimal_separator' => $this->decimalSeparator,
            'thousand_separator' => $this->thousandSeparator,
            'is_base' => $this->isBase(),
            'base_code' => self::BASE_MONETARY_UNIT,
        ];
    }

    /**
     * Get monetary unit data as JSON for JavaScript
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    // ========================================================================
    // PROTECTED METHODS
    // ========================================================================

    /**
     * Load settings from muaadhsettings
     */
    protected function loadSettings(): void
    {
        $gs = Cache::remember('muaadhsettings_monetary', self::CACHE_TTL, function () {
            return DB::table('muaadhsettings')
                ->select('currency_format', 'decimal_separator', 'thousand_separator')
                ->first();
        });

        if ($gs) {
            // Note: database column is still 'currency_format' for compatibility
            $this->format = (int) ($gs->currency_format ?? 0);
            $this->decimalSeparator = $gs->decimal_separator ?? '.';
            $this->thousandSeparator = $gs->thousand_separator ?? ',';
        }
    }
}
