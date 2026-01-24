<?php

namespace App\Domain\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Country Model - Country data
 *
 * Domain: Shipping
 * Table: countries
 *
 * @property int $id
 * @property string $country_code
 * @property string $country_name
 * @property string|null $country_name_ar
 * @property float|null $tax
 * @property int $status
 * @property bool $is_synced
 * @property \Carbon\Carbon|null $synced_at
 */
class Country extends Model
{
    protected $table = 'countries';

    protected $fillable = [
        'country_code',
        'country_name',
        'country_name_ar',
        'tax',
        'status',
        'is_synced',
        'synced_at'
    ];

    protected $casts = [
        'tax' => 'float',
        'status' => 'integer',
        'is_synced' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public $timestamps = false;

    // =========================================================
    // RELATIONS
    // =========================================================

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'country_id');
    }

    // =========================================================
    // SCOPES
    // =========================================================

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeSynced($query)
    {
        return $query->where('is_synced', true);
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('country_code', $code);
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Get localized country name
     */
    public function getLocalizedName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if ($locale === 'ar' && $this->country_name_ar) {
            return $this->country_name_ar;
        }

        return $this->country_name;
    }

    /**
     * Get display name (alias for getLocalizedName)
     */
    public function getDisplayName(): string
    {
        return $this->getLocalizedName();
    }

    /**
     * Check if country has tax
     */
    public function hasTax(): bool
    {
        return $this->tax !== null && $this->tax > 0;
    }

    /**
     * Calculate tax amount for a given price
     */
    public function calculateTax(float $amount): float
    {
        if (!$this->hasTax()) {
            return 0.0;
        }

        return $amount * ($this->tax / 100);
    }
}
