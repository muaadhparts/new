<?php

namespace App\Services;

use App\Models\ApiCredential;
use Illuminate\Support\Facades\Cache;

class ApiCredentialService
{
    /**
     * Cache TTL in seconds (1 hour)
     */
    protected int $cacheTtl = 3600;

    /**
     * Get a credential value with caching
     */
    public function get(string $serviceName, string $keyName = 'api_key'): ?string
    {
        $cacheKey = "api_credential:{$serviceName}:{$keyName}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($serviceName, $keyName) {
            return ApiCredential::getCredential($serviceName, $keyName);
        });
    }

    /**
     * Set or update a credential
     */
    public function set(
        string $serviceName,
        string $keyName,
        string $value,
        ?string $description = null,
        ?\DateTime $expiresAt = null
    ): ApiCredential {
        // Clear cache
        $this->clearCache($serviceName, $keyName);

        return ApiCredential::setCredential($serviceName, $keyName, $value, $description, $expiresAt);
    }

    /**
     * Clear credential cache
     */
    public function clearCache(string $serviceName, ?string $keyName = null): void
    {
        if ($keyName) {
            Cache::forget("api_credential:{$serviceName}:{$keyName}");
        } else {
            // Clear all credentials for this service
            $credentials = ApiCredential::forService($serviceName)->get();
            foreach ($credentials as $credential) {
                Cache::forget("api_credential:{$serviceName}:{$credential->key_name}");
            }
        }
    }

    /**
     * Get Google Maps API Key
     */
    public function getGoogleMapsKey(): ?string
    {
        return $this->get('google_maps', 'api_key') ?? config('services.google_maps.api_key');
    }

    /**
     * Get MyFatoorah credentials
     */
    public function getMyFatoorahKey(): ?string
    {
        return $this->get('myfatoorah', 'api_key') ?? config('myfatoorah.api_key');
    }

    /**
     * Get Tryoto refresh token
     */
    public function getTryotoRefreshToken(): ?string
    {
        return $this->get('tryoto', 'refresh_token') ?? config('tryoto.refresh_token');
    }

    /**
     * Get DigitalOcean Spaces credentials
     */
    public function getDigitalOceanKey(): ?string
    {
        return $this->get('digitalocean', 'access_key') ?? config('filesystems.disks.do.key');
    }

    public function getDigitalOceanSecret(): ?string
    {
        return $this->get('digitalocean', 'secret_key') ?? config('filesystems.disks.do.secret');
    }

    /**
     * Deactivate a credential
     */
    public function deactivate(string $serviceName, string $keyName): bool
    {
        $credential = ApiCredential::where('service_name', $serviceName)
            ->where('key_name', $keyName)
            ->first();

        if ($credential) {
            $this->clearCache($serviceName, $keyName);
            return $credential->update(['is_active' => false]);
        }

        return false;
    }

    /**
     * Get all credentials for a service (masked values for display)
     */
    public function getServiceCredentials(string $serviceName): array
    {
        $credentials = ApiCredential::forService($serviceName)->get();

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
     * Import credentials from .env (one-time migration helper)
     */
    public function importFromEnv(): array
    {
        $imported = [];

        $mappings = [
            ['google_maps', 'api_key', env('GOOGLE_MAPS_API_KEY'), 'Google Maps API Key'],
            ['myfatoorah', 'api_key', env('MYFATOORAH_API_KEY'), 'MyFatoorah API Key'],
            ['myfatoorah', 'secret_key', env('MYFATOORAH_SECRET_KEY'), 'MyFatoorah Secret Key'],
            ['tryoto', 'refresh_token', env('TRYOTO_REFRESH_TOKEN'), 'Tryoto Refresh Token'],
            ['digitalocean', 'access_key', env('DO_ACCESS_KEY_ID'), 'DigitalOcean Spaces Access Key'],
            ['digitalocean', 'secret_key', env('DO_SECRET_ACCESS_KEY'), 'DigitalOcean Spaces Secret Key'],
            ['stripe', 'public_key', env('STRIPE_KEY'), 'Stripe Public Key'],
            ['stripe', 'secret_key', env('STRIPE_SECRET'), 'Stripe Secret Key'],
        ];

        foreach ($mappings as [$service, $key, $value, $description]) {
            if ($value) {
                $this->set($service, $key, $value, $description);
                $imported[] = "{$service}.{$key}";
            }
        }

        return $imported;
    }
}
