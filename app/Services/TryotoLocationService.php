<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tryoto Location Service
 *
 * Handles dynamic location discovery and caching for Tryoto shipping
 *
 * Strategy:
 * 1. Countries/States/Cities are loaded from local DB (fast)
 * 2. When user selects a city → verify with Tryoto API
 * 3. If city is supported but not in DB → auto-save it
 * 4. DB acts as a learning cache that grows over time
 */
class TryotoLocationService
{
    protected $url;
    protected $refreshToken;
    protected $accessToken;
    protected $cacheName;
    protected $cacheTime;

    public function __construct()
    {
        // Use the same config pattern as TryotoService
        if (!config('services.tryoto.sandbox')) {
            $this->url = config('laravel-tryoto.tryoto.live.url');
            $this->refreshToken = config('laravel-tryoto.tryoto.live.token');
        } else {
            $this->url = config('laravel-tryoto.tryoto.test.url');
            $this->refreshToken = config('laravel-tryoto.tryoto.test.token');
        }

        $this->cacheName = config('laravel-tryoto.cache_name', 'tryoto_access_token');
        $this->cacheTime = config('laravel-tryoto.cache_time', 30);

        $this->accessToken = $this->authorize();
    }

    /**
     * Get access token (same as TryotoService)
     */
    protected function authorize()
    {
        if (Cache::has($this->cacheName)) {
            return Cache::get($this->cacheName);
        }

        try {
            $response = Http::timeout(10)->post($this->url . '/rest/v2/refreshToken', [
                'refresh_token' => $this->refreshToken,
            ]);

            if ($response->successful()) {
                $token = 'Bearer ' . $response->json()['access_token'];
                Cache::put($this->cacheName, $token, now()->addMinutes($this->cacheTime));
                return $token;
            }

            Log::error('Tryoto authorization failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Tryoto authorization exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verify if a city is supported by Tryoto
     *
     * @param string $cityName City name to check
     * @param string $testDestination Optional destination city for testing
     * @return array ['supported' => bool, 'companies' => array, 'region' => string|null]
     */
    public function verifyCitySupport($cityName, $testDestination = 'Riyadh')
    {
        if (!$this->accessToken) {
            return ['supported' => false, 'error' => 'Authentication failed'];
        }

        try {
            // Test both directions to be sure
            $response = Http::withHeaders([
                'Authorization' => $this->accessToken,
                'Accept' => 'application/json',
            ])
                ->timeout(15)
                ->post($this->url . '/rest/v2/checkOTODeliveryFee', [
                    'originCity' => $cityName,
                    'destinationCity' => $testDestination,
                    'weight' => 1,
                    'xlength' => 30,
                    'xheight' => 30,
                    'xwidth' => 30,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $companies = $data['deliveryCompany'] ?? [];

                // Extract region from response if available
                $region = $data['originRegion'] ?? $data['region'] ?? null;

                return [
                    'supported' => !empty($companies),
                    'companies' => $companies,
                    'company_count' => count($companies),
                    'region' => $region,
                    'full_response' => $data
                ];
            }

            return ['supported' => false, 'error' => 'API request failed'];
        } catch (\Exception $e) {
            Log::error('Tryoto city verification failed', [
                'city' => $cityName,
                'error' => $e->getMessage()
            ]);

            return ['supported' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Verify and auto-save location if supported
     *
     * @param int $countryId
     * @param int $stateId
     * @param string $cityName
     * @return array ['verified' => bool, 'city_id' => int|null, 'message' => string]
     */
    public function verifyAndSaveLocation($countryId, $stateId, $cityName)
    {
        // Check if city already exists
        $existingCity = City::where('city_name', $cityName)
            ->where('country_id', $countryId)
            ->first();

        if ($existingCity) {
            return [
                'verified' => true,
                'city_id' => $existingCity->id,
                'message' => 'City already in database',
                'cached' => true
            ];
        }

        // Verify with Tryoto API
        $verification = $this->verifyCitySupport($cityName);

        if (!$verification['supported']) {
            return [
                'verified' => false,
                'city_id' => null,
                'message' => 'City not supported by Tryoto shipping',
                'error' => $verification['error'] ?? 'Unknown error'
            ];
        }

        // City is supported → save it to database
        try {
            DB::beginTransaction();

            // Get country info
            $country = Country::find($countryId);
            if (!$country) {
                DB::rollBack();
                return [
                    'verified' => false,
                    'message' => 'Country not found'
                ];
            }

            // Get or create state
            $state = State::find($stateId);
            if (!$state) {
                // Try to extract region from Tryoto response
                $regionName = $verification['region'] ?? 'Default';

                $state = State::firstOrCreate(
                    [
                        'country_id' => $countryId,
                        'state' => $regionName
                    ],
                    [
                        'state_ar' => $this->translateRegion($regionName),
                        'tax' => 0,
                        'status' => 1,
                        'owner_id' => 0
                    ]
                );

                $stateId = $state->id;
            }

            // Create city
            $city = City::create([
                'state_id' => $stateId,
                'country_id' => $countryId,
                'city_name' => $cityName,
                'city_name_ar' => $this->translateCity($cityName),
                'status' => 1
            ]);

            DB::commit();

            Log::info('New Tryoto city auto-saved', [
                'country' => $country->country_name,
                'state' => $state->state,
                'city' => $cityName,
                'companies' => $verification['company_count']
            ]);

            return [
                'verified' => true,
                'city_id' => $city->id,
                'message' => 'City verified and saved successfully',
                'cached' => false,
                'companies' => $verification['company_count']
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to save verified city', [
                'city' => $cityName,
                'error' => $e->getMessage()
            ]);

            return [
                'verified' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Simple translation helper for regions
     */
    protected function translateRegion($regionName)
    {
        $translations = [
            'Riyadh Region' => 'منطقة الرياض',
            'Makkah Region' => 'منطقة مكة المكرمة',
            'Eastern Region' => 'المنطقة الشرقية',
            'Madinah Region' => 'منطقة المدينة المنورة',
            'Al-Qassim Region' => 'منطقة القصيم',
            'Asir Region' => 'منطقة عسير',
            'Tabuk Region' => 'منطقة تبوك',
            'Hail Region' => 'منطقة حائل',
            'Najran Region' => 'منطقة نجران',
            'Jazan Region' => 'منطقة جازان',
            'Northern Borders Region' => 'منطقة الحدود الشمالية',
            'Al Jouf Region' => 'منطقة الجوف',
            'Default' => 'افتراضي'
        ];

        return $translations[$regionName] ?? $regionName;
    }

    /**
     * Simple translation helper for cities
     */
    protected function translateCity($cityName)
    {
        $translations = [
            'Riyadh' => 'الرياض',
            'Jeddah' => 'جدة',
            'Mecca' => 'مكة المكرمة',
            'Medina' => 'المدينة المنورة',
            'Dammam' => 'الدمام',
            'Al Khobar' => 'الخبر',
            'Dhahran' => 'الظهران',
            'Tabuk' => 'تبوك',
            'Buraidah' => 'بريدة',
            'Khamis Mushait' => 'خميس مشيط',
            'Hail' => 'حائل',
            'Najran' => 'نجران',
            'Jazan' => 'جازان',
            'Taif' => 'الطائف',
            'Yanbu' => 'ينبع',
            'Abha' => 'أبها',
            'Dubai' => 'دبي',
            'Manama' => 'المنامة',
            'Doha' => 'الدوحة',
            'Kuwait City' => 'مدينة الكويت',
            'Muscat' => 'مسقط',
            'Cairo' => 'القاهرة',
            'Amman' => 'عمان',
            'Beirut' => 'بيروت',
            'Baghdad' => 'بغداد'
        ];

        return $translations[$cityName] ?? $cityName;
    }

    /**
     * Get all countries from database (cached from Tryoto discoveries)
     */
    public function getCountries()
    {
        return Country::where('status', 1)
            ->orderBy('country_name')
            ->get(['id', 'country_code', 'country_name', 'country_name_ar']);
    }

    /**
     * Get states for a country from database
     */
    public function getStates($countryId)
    {
        return State::where('country_id', $countryId)
            ->where('status', 1)
            ->orderBy('state')
            ->get(['id', 'state', 'state_ar', 'country_id']);
    }

    /**
     * Get cities for a state from database
     */
    public function getCities($stateId)
    {
        return City::where('state_id', $stateId)
            ->where('status', 1)
            ->orderBy('city_name')
            ->get(['id', 'city_name', 'city_name_ar', 'state_id', 'country_id']);
    }
}
