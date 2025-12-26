<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

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

    /**
     * Get the decrypted value
     */
    public function getDecryptedValueAttribute(): ?string
    {
        try {
            return Crypt::decryptString($this->encrypted_value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set the encrypted value
     */
    public function setValueAttribute(string $value): void
    {
        $this->attributes['encrypted_value'] = Crypt::encryptString($value);
    }

    /**
     * Get credential by service and key name
     */
    public static function getCredential(string $serviceName, string $keyName = 'api_key'): ?string
    {
        $credential = static::where('service_name', $serviceName)
            ->where('key_name', $keyName)
            ->where('is_active', true)
            ->first();

        if (!$credential) {
            return null;
        }

        // Update last used timestamp
        $credential->update(['last_used_at' => now()]);

        return $credential->decrypted_value;
    }

    /**
     * Set or update a credential
     */
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

    /**
     * Check if credential is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Scope for active credentials
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific service
     */
    public function scopeForService($query, string $serviceName)
    {
        return $query->where('service_name', $serviceName);
    }
}
