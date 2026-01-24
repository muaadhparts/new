<?php

namespace App\Domain\Shipping\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleMapsService
{
    protected $apiKey;
    protected $baseUrl = 'https://maps.googleapis.com/maps/api/geocode/json';
    protected ApiCredentialService $credentialService;

    public function __construct(?ApiCredentialService $credentialService = null)
    {
        $this->credentialService = $credentialService ?? app(ApiCredentialService::class);
        $this->apiKey = $this->credentialService->getGoogleMapsKey();
    }

    /**
     * Reverse geocode coordinates to address components
     *
     * @param float $latitude
     * @param float $longitude
     * @param string $language Language code (ar or en)
     * @return array
     */
    public function reverseGeocode($latitude, $longitude, $language = 'ar')
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl, [
                'latlng' => "{$latitude},{$longitude}",
                'key' => $this->apiKey,
                'language' => $language,
                'result_type' => 'street_address|route|locality|administrative_area_level_1|country'
            ]);

            if (!$response->successful()) {
                Log::error('Google Maps API Error', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return [
                    'success' => false,
                    'error' => 'Failed to connect to Google Maps API'
                ];
            }

            $data = $response->json();

            if ($data['status'] !== 'OK' || empty($data['results'])) {
                Log::warning('Google Maps API No Results', ['data' => $data]);
                return [
                    'success' => false,
                    'error' => $data['status'] ?? 'No results found'
                ];
            }

            return [
                'success' => true,
                'data' => $this->parseAddressComponents($data['results'][0], $language)
            ];
        } catch (\Exception $e) {
            Log::error('Google Maps Service Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => 'An error occurred while processing the request'
            ];
        }
    }

    /**
     * Parse address components from Google Maps response
     *
     * @param array $result
     * @param string $language
     * @return array
     */
    protected function parseAddressComponents($result, $language)
    {
        $components = [
            'country' => null,
            'country_code' => null,
            'state' => null,
            'city' => null,
            'district' => null,
            'street' => null,
            'postal_code' => null,
            'address' => $result['formatted_address'] ?? null,
            'latitude' => $result['geometry']['location']['lat'] ?? null,
            'longitude' => $result['geometry']['location']['lng'] ?? null
        ];

        foreach ($result['address_components'] as $component) {
            $types = $component['types'];
            $longName = $component['long_name'];
            $shortName = $component['short_name'];

            // Country
            if (in_array('country', $types)) {
                $components['country'] = $longName;
                $components['country_code'] = strtoupper($shortName);
            }

            // State / Administrative Area Level 1
            if (in_array('administrative_area_level_1', $types)) {
                $components['state'] = $longName;
            }

            // City - can be locality or administrative_area_level_2
            if (in_array('locality', $types)) {
                $components['city'] = $longName;
            } elseif (in_array('administrative_area_level_2', $types) && empty($components['city'])) {
                $components['city'] = $longName;
            }

            // District / Neighborhood
            if (in_array('sublocality_level_1', $types) || in_array('neighborhood', $types)) {
                $components['district'] = $longName;
            }

            // Street
            if (in_array('route', $types)) {
                $components['street'] = $longName;
            }

            // Postal Code
            if (in_array('postal_code', $types)) {
                $components['postal_code'] = $longName;
            }
        }

        return $components;
    }

    /**
     * Normalize text - remove diacritics and extra spaces
     *
     * @param string $text
     * @return string
     */
    public function normalizeText($text)
    {
        if (empty($text)) {
            return $text;
        }

        // Remove Arabic diacritics
        $text = preg_replace('/[\x{064B}-\x{065F}]/u', '', $text);

        // Remove extra spaces and trim
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }

    /**
     * Get both Arabic and English names for a location
     *
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    public function getBilingualNames($latitude, $longitude)
    {
        $arabicData = $this->reverseGeocode($latitude, $longitude, 'ar');
        $englishData = $this->reverseGeocode($latitude, $longitude, 'en');

        if (!$arabicData['success'] && !$englishData['success']) {
            return [
                'success' => false,
                'error' => 'Failed to get location data'
            ];
        }

        $result = [
            'success' => true,
            'data' => [
                'ar' => $arabicData['success'] ? $arabicData['data'] : null,
                'en' => $englishData['success'] ? $englishData['data'] : null,
            ]
        ];

        // Normalize all text fields
        if ($result['data']['ar']) {
            foreach (['country', 'state', 'city', 'address'] as $field) {
                if (!empty($result['data']['ar'][$field])) {
                    $result['data']['ar'][$field] = $this->normalizeText($result['data']['ar'][$field]);
                }
            }
        }

        if ($result['data']['en']) {
            foreach (['country', 'state', 'city', 'address'] as $field) {
                if (!empty($result['data']['en'][$field])) {
                    $result['data']['en'][$field] = $this->normalizeText($result['data']['en'][$field]);
                }
            }
        }

        return $result;
    }
}
