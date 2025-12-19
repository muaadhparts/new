<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_key',
        'domain',
        'owner_name',
        'owner_email',
        'status',
        'license_type',
        'max_domains',
        'used_domains',
        'activated_at',
        'expires_at',
        'features',
        'notes',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'features' => 'array',
    ];

    /**
     * Generate a unique license key
     */
    public static function generateLicenseKey(): string
    {
        $prefix = 'MU'; // Muaadh
        $segments = [];

        for ($i = 0; $i < 4; $i++) {
            $segments[] = strtoupper(bin2hex(random_bytes(2)));
        }

        return $prefix . '-' . implode('-', $segments);
    }

    /**
     * Check if license is valid
     */
    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if license is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Activate the license
     */
    public function activate(string $domain = null): bool
    {
        if ($this->used_domains >= $this->max_domains && $this->max_domains > 0) {
            return false;
        }

        $this->status = 'active';
        $this->activated_at = now();
        $this->domain = $domain ?? request()->getHost();
        $this->used_domains += 1;

        return $this->save();
    }

    /**
     * Deactivate the license
     */
    public function deactivate(): bool
    {
        $this->status = 'inactive';
        return $this->save();
    }

    /**
     * Suspend the license
     */
    public function suspend(): bool
    {
        $this->status = 'suspended';
        return $this->save();
    }

    /**
     * Get active license for current domain
     */
    public static function getActiveLicense(): ?self
    {
        return self::where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Validate a license key
     */
    public static function validateKey(string $key): ?self
    {
        return self::where('license_key', $key)->first();
    }

    /**
     * Check if system is activated
     */
    public static function isSystemActivated(): bool
    {
        $license = self::getActiveLicense();
        return $license !== null && $license->isValid();
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'active' => 'badge-success',
            'inactive' => 'badge-warning',
            'expired' => 'badge-danger',
            'suspended' => 'badge-dark',
            default => 'badge-secondary',
        };
    }

    /**
     * Get license type badge class
     */
    public function getTypeBadgeAttribute(): string
    {
        return match($this->license_type) {
            'unlimited' => 'badge-primary',
            'developer' => 'badge-info',
            'extended' => 'badge-success',
            'standard' => 'badge-secondary',
            default => 'badge-light',
        };
    }
}
