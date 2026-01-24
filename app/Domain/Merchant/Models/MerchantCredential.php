<?php

namespace App\Domain\Merchant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use App\Models\User;

/**
 * MerchantCredential Model - API credentials per merchant
 *
 * Domain: Merchant
 * Table: merchant_credentials
 *
 * @property int $id
 * @property int $user_id
 * @property string $service_name
 * @property string $key_name
 * @property string $environment
 * @property string $encrypted_value
 * @property bool $is_active
 */
class MerchantCredential extends Model
{
    protected $table = 'merchant_credentials';

    protected $fillable = [
        'user_id',
        'service_name',
        'key_name',
        'environment',
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
    // RELATIONS
    // =========================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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

    public static function getCredential(
        int $userId,
        string $serviceName,
        string $keyName = 'api_key',
        string $environment = 'live'
    ): ?string {
        $credential = static::where('user_id', $userId)
            ->where('service_name', $serviceName)
            ->where('key_name', $keyName)
            ->where('environment', $environment)
            ->where('is_active', true)
            ->first();

        if (!$credential) {
            return null;
        }

        $credential->update(['last_used_at' => now()]);

        return $credential->decrypted_value;
    }

    public static function setCredential(
        int $userId,
        string $serviceName,
        string $keyName,
        string $value,
        string $environment = 'live',
        ?string $description = null,
        ?\DateTime $expiresAt = null
    ): self {
        return static::updateOrCreate(
            [
                'user_id' => $userId,
                'service_name' => $serviceName,
                'key_name' => $keyName,
                'environment' => $environment,
            ],
            [
                'encrypted_value' => Crypt::encryptString($value),
                'description' => $description,
                'expires_at' => $expiresAt,
                'is_active' => true,
            ]
        );
    }

    public static function getAvailableServices(): array
    {
        return [
            'payment' => [
                'myfatoorah' => 'MyFatoorah',
                'tap' => 'Tap Payments',
                'moyasar' => 'Moyasar',
                'stripe' => 'Stripe',
            ],
            'shipping' => [
                'tryoto' => 'Tryoto',
                'aramex' => 'Aramex',
                'dhl' => 'DHL',
                'smsa' => 'SMSA Express',
                'fetchr' => 'Fetchr',
            ],
        ];
    }

    // =========================================================
    // SCOPES
    // =========================================================

    public function scopeForEnvironment($query, string $environment)
    {
        return $query->where('environment', $environment);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForMerchant($query, int $userId)
    {
        return $query->where('user_id', $userId);
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
