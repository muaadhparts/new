<?php

namespace App\Services;

use App\Models\MerchantCredential;
use Illuminate\Support\Facades\Cache;

class MerchantCredentialService
{
    protected int $cacheTtl = 3600;

    /**
     * Get a merchant credential - NO FALLBACK to system credentials
     *
     * MARKETPLACE POLICY:
     * - Each merchant MUST have their own payment/shipping credentials
     * - NO FALLBACK to system credentials for financial/shipping operations
     */
    public function get(
        int $userId,
        string $serviceName,
        string $keyName = 'api_key',
        string $environment = 'live'
    ): ?string {
        $cacheKey = "merchant_credential:{$userId}:{$serviceName}:{$keyName}:{$environment}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($userId, $serviceName, $keyName, $environment) {
            // Merchant-specific credential ONLY - NO FALLBACK
            return MerchantCredential::getCredential($userId, $serviceName, $keyName, $environment);
        });
    }

    /**
     * Get merchant credential only (no fallback)
     */
    public function getMerchantOnly(
        int $userId,
        string $serviceName,
        string $keyName = 'api_key',
        string $environment = 'live'
    ): ?string {
        $cacheKey = "merchant_credential_only:{$userId}:{$serviceName}:{$keyName}:{$environment}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($userId, $serviceName, $keyName, $environment) {
            return MerchantCredential::getCredential($userId, $serviceName, $keyName, $environment);
        });
    }

    /**
     * Set or update a merchant credential
     */
    public function set(
        int $userId,
        string $serviceName,
        string $keyName,
        string $value,
        string $environment = 'live',
        ?string $description = null,
        ?\DateTime $expiresAt = null
    ): MerchantCredential {
        $this->clearCache($userId, $serviceName, $keyName, $environment);

        return MerchantCredential::setCredential($userId, $serviceName, $keyName, $value, $environment, $description, $expiresAt);
    }

    /**
     * Clear credential cache
     */
    public function clearCache(int $userId, string $serviceName, ?string $keyName = null, ?string $environment = null): void
    {
        if ($keyName && $environment) {
            Cache::forget("merchant_credential:{$userId}:{$serviceName}:{$keyName}:{$environment}");
            Cache::forget("merchant_credential_only:{$userId}:{$serviceName}:{$keyName}:{$environment}");
        } elseif ($keyName) {
            Cache::forget("merchant_credential:{$userId}:{$serviceName}:{$keyName}:live");
            Cache::forget("merchant_credential:{$userId}:{$serviceName}:{$keyName}:sandbox");
            Cache::forget("merchant_credential_only:{$userId}:{$serviceName}:{$keyName}:live");
            Cache::forget("merchant_credential_only:{$userId}:{$serviceName}:{$keyName}:sandbox");
        } else {
            $credentials = MerchantCredential::forMerchant($userId)->forService($serviceName)->get();
            foreach ($credentials as $credential) {
                Cache::forget("merchant_credential:{$userId}:{$serviceName}:{$credential->key_name}:{$credential->environment}");
                Cache::forget("merchant_credential_only:{$userId}:{$serviceName}:{$credential->key_name}:{$credential->environment}");
            }
        }
    }

    /**
     * Get merchant's MyFatoorah key - NO FALLBACK
     *
     * MARKETPLACE POLICY:
     * - Merchant MUST have their own payment credentials
     * - NO FALLBACK to system credentials for financial operations
     *
     * Search purchase:
     * 1. merchant_credentials table (new encrypted storage)
     * 2. payment_gateways.information JSON (legacy storage)
     *
     * @param int $userId Merchant user ID
     * @return string|null API key or null if not configured
     */
    public function getMyFatoorahKey(int $userId): ?string
    {
        // 1. Try new merchant_credentials table first
        $key = $this->getMerchantOnly($userId, 'myfatoorah', 'api_key');

        if (!empty($key)) {
            return $key;
        }

        // 2. Fallback to legacy payment_gateways.information
        return $this->getFromPaymentGateway($userId, 'myfatoorah', 'api_key');
    }

    /**
     * Alias for backward compatibility
     * @deprecated Use getMyFatoorahKey() instead
     */
    public function getMyFatoorahKeyStrict(int $userId): ?string
    {
        return $this->getMyFatoorahKey($userId);
    }

    /**
     * Get credential from legacy payment_gateways table
     *
     * @param int $userId Merchant user ID
     * @param string $keyword Gateway keyword (e.g., 'myfatoorah', 'stripe')
     * @param string $keyName Key name in JSON (e.g., 'api_key', 'secret')
     * @return string|null
     */
    protected function getFromPaymentGateway(int $userId, string $keyword, string $keyName): ?string
    {
        $cacheKey = "payment_gateway_cred:{$userId}:{$keyword}:{$keyName}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($userId, $keyword, $keyName) {
            $gateway = \DB::table('payment_gateways')
                ->where('user_id', $userId)
                ->where('keyword', $keyword)
                ->first();

            if (!$gateway || empty($gateway->information)) {
                return null;
            }

            $info = json_decode($gateway->information, true);

            if (!is_array($info) || empty($info[$keyName])) {
                return null;
            }

            // Skip placeholder values
            $value = $info[$keyName];
            if ($value === '<YOUR_API_KEY>' || $value === 'YOUR_API_KEY' || empty(trim($value))) {
                return null;
            }

            return $value;
        });
    }

    /**
     * Get merchant's Tryoto refresh token - NO FALLBACK
     *
     * MARKETPLACE POLICY:
     * - Merchant MUST have their own shipping credentials
     * - NO FALLBACK to system credentials for shipping operations
     *
     * @param int $userId Merchant user ID
     * @return string|null Refresh token or null if not configured
     */
    public function getTryotoRefreshToken(int $userId): ?string
    {
        return $this->getMerchantOnly($userId, 'tryoto', 'refresh_token');
    }

    /**
     * Alias for backward compatibility
     * @deprecated Use getTryotoRefreshToken() instead
     */
    public function getTryotoRefreshTokenStrict(int $userId): ?string
    {
        return $this->getTryotoRefreshToken($userId);
    }

    /**
     * Check if merchant has payment credentials configured
     * Checks both merchant_credentials and legacy payment_gateways
     *
     * @param int $userId Merchant user ID
     * @return bool
     */
    public function hasPaymentCredentials(int $userId): bool
    {
        // Check new merchant_credentials table
        if ($this->hasOwnCredential($userId, 'myfatoorah', 'api_key')) {
            return true;
        }

        // Check legacy payment_gateways table
        $key = $this->getFromPaymentGateway($userId, 'myfatoorah', 'api_key');
        return !empty($key);
    }

    /**
     * Check if merchant has shipping credentials configured
     *
     * @param int $userId Merchant user ID
     * @return bool
     */
    public function hasShippingCredentials(int $userId): bool
    {
        return $this->hasOwnCredential($userId, 'tryoto', 'refresh_token');
    }

    /**
     * Get merchant configuration status for marketplace operations
     *
     * @param int $userId Merchant user ID
     * @return array
     */
    public function getMerchantMarketplaceStatus(int $userId): array
    {
        return [
            'merchant_id' => $userId,
            'has_payment_credentials' => $this->hasPaymentCredentials($userId),
            'has_shipping_credentials' => $this->hasShippingCredentials($userId),
            'can_accept_orders' => $this->hasPaymentCredentials($userId) && $this->hasShippingCredentials($userId),
            'missing' => array_filter([
                !$this->hasPaymentCredentials($userId) ? 'payment (MyFatoorah)' : null,
                !$this->hasShippingCredentials($userId) ? 'shipping (Tryoto)' : null,
            ]),
        ];
    }

    /**
     * Check if merchant has their own credential for a service
     */
    public function hasOwnCredential(int $userId, string $serviceName, string $keyName = 'api_key'): bool
    {
        return MerchantCredential::where('user_id', $userId)
            ->where('service_name', $serviceName)
            ->where('key_name', $keyName)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Deactivate a merchant credential
     */
    public function deactivate(int $userId, string $serviceName, string $keyName): bool
    {
        $credential = MerchantCredential::where('user_id', $userId)
            ->where('service_name', $serviceName)
            ->where('key_name', $keyName)
            ->first();

        if ($credential) {
            $this->clearCache($userId, $serviceName, $keyName);
            return $credential->update(['is_active' => false]);
        }

        return false;
    }

    /**
     * Get all credentials for a merchant (masked values for display)
     */
    public function getMerchantCredentials(int $userId): array
    {
        $credentials = MerchantCredential::forMerchant($userId)->get();

        return $credentials->map(function ($credential) {
            $value = $credential->decrypted_value;
            $masked = $value ? substr($value, 0, 4) . str_repeat('*', max(0, strlen($value) - 8)) . substr($value, -4) : null;

            return [
                'id' => $credential->id,
                'service_name' => $credential->service_name,
                'key_name' => $credential->key_name,
                'masked_value' => $masked,
                'description' => $credential->description,
                'is_active' => $credential->is_active,
                'expires_at' => $credential->expires_at?->toDateTimeString(),
                'last_used_at' => $credential->last_used_at?->toDateTimeString(),
            ];
        })->toArray();
    }

    /**
     * Delete a merchant credential
     */
    public function delete(int $userId, int $credentialId): bool
    {
        $credential = MerchantCredential::where('id', $credentialId)
            ->where('user_id', $userId)
            ->first();

        if ($credential) {
            $this->clearCache($userId, $credential->service_name, $credential->key_name);
            return $credential->delete();
        }

        return false;
    }
}
