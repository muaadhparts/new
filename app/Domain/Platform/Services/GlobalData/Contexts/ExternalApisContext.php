<?php

namespace App\Domain\Platform\Services\GlobalData\Contexts;

use App\Services\ApiCredentialService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ExternalApisContext
 *
 * مفاتيح API الخارجية:
 * - Google Maps
 * - (يمكن إضافة المزيد لاحقاً)
 */
class ExternalApisContext implements ContextInterface
{
    private ?string $googleMapsApiKey = null;

    public function load(): void
    {
        $this->googleMapsApiKey = Cache::remember('google_maps_api_key', 3600, function () {
            try {
                $key = app(ApiCredentialService::class)->getGoogleMapsKey();
                return !empty($key) ? $key : null;
            } catch (\Exception $e) {
                Log::error('Google Maps: Failed to retrieve API key', [
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    public function toArray(): array
    {
        return [
            'googleMapsApiKey' => $this->googleMapsApiKey,
        ];
    }

    public function reset(): void
    {
        $this->googleMapsApiKey = null;
    }

    // === Getters ===

    public function getGoogleMapsApiKey(): ?string
    {
        return $this->googleMapsApiKey;
    }
}
