<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TryotoLocationService - Location Resolution via Tryoto API ONLY
 *
 * السيناريو الصحيح:
 * 1. البيانات تأتي من Google Maps (الدولة، المنطقة، المدينة، الإحداثيات)
 * 2. Tryoto API هو المصدر الوحيد للتحقق من الدعم
 * 3. الجداول = مجرد Cache للنتائج السابقة من Tryoto
 * 4. لا توجد قوائم hardcoded نهائياً
 *
 * التسلسل عند عدم دعم المدينة:
 * City → State → Country → رفض العملية
 */
class TryotoLocationService
{
    protected TryotoService $tryotoService;

    public function __construct()
    {
        $this->tryotoService = app(TryotoService::class);
    }

    /**
     * Verify if a location is supported by Tryoto
     * يستخدم TryotoService الموحد
     *
     * @param string $locationName City/State/Country name to check
     * @param string $testDestination Optional destination for testing
     * @return array ['supported' => bool, 'companies' => array, 'region' => string|null]
     */
    public function verifyCitySupport(string $locationName, string $testDestination = 'Riyadh'): array
    {
        return $this->tryotoService->verifyCitySupport($locationName, $testDestination);
    }

    /**
     * Main method: Resolve map location via Tryoto API
     *
     * السيناريو:
     * 1. محاولة المدينة من Google Maps
     * 2. إذا فشلت → محاولة State
     * 3. إذا فشلت → محاولة Country
     * 4. إذا فشلت → رفض العملية
     *
     * @param string $cityName City from Google Maps
     * @param string|null $stateName State from Google Maps
     * @param string|null $countryName Country from Google Maps
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    public function resolveMapCity(
        string $cityName,
        ?string $stateName,
        ?string $countryName,
        float $latitude,
        float $longitude
    ): array {
        Log::info('TryotoLocation: Resolving map location via API', [
            'city' => $cityName,
            'state' => $stateName,
            'country' => $countryName,
            'coordinates' => compact('latitude', 'longitude')
        ]);

        // ==========================================
        // المحاولة 1: المدينة من Google Maps
        // ==========================================
        $cityResult = $this->tryotoService->verifyCitySupport($cityName);

        if ($cityResult['supported']) {
            Log::info('TryotoLocation: City supported directly', [
                'city' => $cityName,
                'companies' => $cityResult['company_count']
            ]);

            return [
                'success' => true,
                'strategy' => 'exact_city',
                'resolved_name' => $cityName,
                'resolved_type' => 'city',
                'original' => [
                    'city' => $cityName,
                    'state' => $stateName,
                    'country' => $countryName,
                ],
                'companies' => $cityResult['company_count'] ?? 0,
                'region' => $cityResult['region'] ?? null,
                'coordinates' => compact('latitude', 'longitude'),
                'message' => "المدينة '{$cityName}' مدعومة للشحن"
            ];
        }

        Log::info('TryotoLocation: City not supported, trying state...', [
            'city' => $cityName,
            'state' => $stateName
        ]);

        // ==========================================
        // المحاولة 2: المنطقة/State من Google Maps
        // ==========================================
        if ($stateName) {
            $stateResult = $this->tryotoService->verifyCitySupport($stateName);

            if ($stateResult['supported']) {
                Log::info('TryotoLocation: State supported', [
                    'state' => $stateName,
                    'companies' => $stateResult['company_count']
                ]);

                return [
                    'success' => true,
                    'strategy' => 'fallback_state',
                    'resolved_name' => $stateName,
                    'resolved_type' => 'state',
                    'original' => [
                        'city' => $cityName,
                        'state' => $stateName,
                        'country' => $countryName,
                    ],
                    'companies' => $stateResult['company_count'] ?? 0,
                    'region' => $stateResult['region'] ?? null,
                    'coordinates' => compact('latitude', 'longitude'),
                    'message' => "المدينة '{$cityName}' غير مدعومة، سيتم الشحن إلى المنطقة '{$stateName}'"
                ];
            }
        }

        Log::info('TryotoLocation: State not supported, trying country...', [
            'state' => $stateName,
            'country' => $countryName
        ]);

        // ==========================================
        // المحاولة 3: الدولة من Google Maps
        // ==========================================
        if ($countryName) {
            $countryResult = $this->tryotoService->verifyCitySupport($countryName);

            if ($countryResult['supported']) {
                Log::info('TryotoLocation: Country supported', [
                    'country' => $countryName,
                    'companies' => $countryResult['company_count']
                ]);

                return [
                    'success' => true,
                    'strategy' => 'fallback_country',
                    'resolved_name' => $countryName,
                    'resolved_type' => 'country',
                    'original' => [
                        'city' => $cityName,
                        'state' => $stateName,
                        'country' => $countryName,
                    ],
                    'companies' => $countryResult['company_count'] ?? 0,
                    'region' => $countryResult['region'] ?? null,
                    'coordinates' => compact('latitude', 'longitude'),
                    'message' => "المدينة '{$cityName}' والمنطقة '{$stateName}' غير مدعومتين، سيتم الشحن إلى '{$countryName}'"
                ];
            }
        }

        // ==========================================
        // فشل جميع المحاولات
        // ==========================================
        Log::warning('TryotoLocation: Location not supported by Tryoto', [
            'city' => $cityName,
            'state' => $stateName,
            'country' => $countryName
        ]);

        return [
            'success' => false,
            'strategy' => 'none',
            'original' => [
                'city' => $cityName,
                'state' => $stateName,
                'country' => $countryName,
            ],
            'coordinates' => compact('latitude', 'longitude'),
            'message' => "الدولة '{$countryName}' غير مدعومة من شركة الشحن Tryoto"
        ];
    }

    /**
     * Simple translation helper for regions (from Tryoto response)
     */
    public function translateRegion(?string $regionName): string
    {
        if (!$regionName) return '';

        // Basic translations - Tryoto may return these
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
        ];

        return $translations[$regionName] ?? $regionName;
    }

    /**
     * Get all countries from database (Cache from previous Tryoto discoveries)
     */
    public function getCountries()
    {
        return Country::where('status', 1)
            ->orderBy('country_name')
            ->get(['id', 'country_code', 'country_name', 'country_name_ar']);
    }

    /**
     * Get states for a country from database (Cache)
     */
    public function getStates(int $countryId)
    {
        return State::where('country_id', $countryId)
            ->where('status', 1)
            ->orderBy('state')
            ->get(['id', 'state', 'state_ar', 'country_id']);
    }

    /**
     * Get cities for a state from database (Cache)
     */
    public function getCities(int $stateId)
    {
        return City::where('state_id', $stateId)
            ->where('status', 1)
            ->orderBy('city_name')
            ->get(['id', 'city_name', 'city_name_ar', 'state_id', 'country_id']);
    }
}
