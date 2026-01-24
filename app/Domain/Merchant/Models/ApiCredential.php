<?php

namespace App\Domain\Merchant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * ApiCredential Model - Platform-level API credentials
 *
 * Domain: Merchant
 * Table: api_credentials
 *
 * @property int $id
 * @property string $service_name
 * @property string $key_name
 * @property string $encrypted_value
 * @property string|null $description
 * @property bool $is_active
 */
class ApiCredential extends Model
{
    protected $fillable = [
        'service_name',
        'key_name',
        'encrypted_value',
        'description',
        'is_active',
        'expires_at',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    // =========================================================
    // ENCRYPTION
    // =========================================================

    public function getDecryptedValueAttribute(): ?string
    {
        try {
            return Crypt::decryptString($this->encrypted_value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setValueAttribute(string $value): void
    {
        $this->attributes['encrypted_value'] = Crypt::encryptString($value);
    }

    // =========================================================
    // STATIC METHODS
    // =========================================================

    public static function getCredential(string $serviceName, string $keyName = 'api_key'): ?string
    {
        $credential = static::where('service_name', $serviceName)
            ->where('key_name', $keyName)
            ->where('is_active', true)
            ->first();

        if (!$credential) {
            return null;
        }

        $credential->update(['last_used_at' => now()]);

        return $credential->decrypted_value;
    }

    public static function setCredential(
        string $serviceName,
        string $keyName,
        string $value,
        ?string $description = null,
        ?\DateTime $expiresAt = null
    ): self {
        return static::updateOrCreate(
            [
                'service_name' => $serviceName,
                'key_name' => $keyName,
            ],
            [
                'encrypted_value' => Crypt::encryptString($value),
                'description' => $description,
                'expires_at' => $expiresAt,
                'is_active' => true,
            ]
        );
    }

    // =========================================================
    // SCOPES
    // =========================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForService($query, string $serviceName)
    {
        return $query->where('service_name', $serviceName);
    }

    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }
}
