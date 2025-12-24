<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * TryotoLocationService - Location Resolution from Database
 *
 * التوجيه المعماري:
 * - Tryoto هو المصدر الوحيد للمدن (يتم الاستيراد يدوياً عبر CLI)
 * - هذا الـ service READ-ONLY - لا يُنشئ مدن جديدة
 * - البيانات: city_name (إنجليزي فقط), latitude, longitude, country_id, tryoto_supported
 * - لا يوجد اسم عربي للمدن (city_name_ar محذوف)
 *
 * السيناريو:
 * 1. البيانات تأتي من Google Maps (الدولة، المنطقة، المدينة، الإحداثيات)
 * 2. البحث يكون في DB فقط (المدن المُزامنة من Tryoto API)
 * 3. لا يوجد API calls في وقت الـ request = سريع جداً
 *
 * التسلسل:
 * City → State → أقرب مدينة مدعومة في نفس الدولة (من DB) → رفض
 */
class TryotoLocationService
{
    protected TryotoService $tryotoService;

    public function __construct()
    {
        $this->tryotoService = app(TryotoService::class);
    }

    /**
     * Main method: Resolve map location from DB
     *
     * @param string $cityName City from Google Maps
     * @param string|null $stateName State from Google Maps
     * @param string|null $countryName Country from Google Maps
     * @param float $latitude User's latitude
     * @param float $longitude User's longitude
     * @return array
     */
    public function resolveMapCity(
        string $cityName,
        ?string $stateName,
        ?string $countryName,
        float $latitude,
        float $longitude
    ): array {
        Log::debug('TryotoLocation: Resolving location from DB', [
            'city' => $cityName,
            'state' => $stateName,
            'country' => $countryName,
            'coordinates' => compact('latitude', 'longitude')
        ]);

        // ==========================================
        // الخطوة 0: البحث عن الدولة في DB
        // ==========================================
        $country = $this->findCountry($countryName);

        if (!$country) {
            Log::warning('TryotoLocation: Country not found in DB', ['country' => $countryName]);
            return [
                'success' => false,
                'strategy' => 'country_not_supported',
                'original' => compact('cityName', 'stateName', 'countryName'),
                'coordinates' => compact('latitude', 'longitude'),
                'message' => "الدولة '{$countryName}' غير مدعومة من خدمة الشحن"
            ];
        }

        // ==========================================
        // الخطوة 1: البحث عن المدينة بالاسم في DB
        // ==========================================
        $city = $this->findCityByName($cityName, $country->id);

        if ($city && $city->tryoto_supported) {
            // تحديث الإحداثيات إذا لم تكن موجودة (تحسين البيانات تدريجياً)
            if (!$city->latitude || !$city->longitude) {
                $city->update([
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ]);
                Log::debug('TryotoLocation: Updated city coordinates', [
                    'city' => $city->city_name,
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ]);
            }

            Log::debug('TryotoLocation: City found in DB', [
                'city' => $city->city_name,
                'has_coordinates' => ($city->latitude && $city->longitude)
            ]);

            return [
                'success' => true,
                'strategy' => 'exact_city',
                'resolved_name' => $city->city_name,
                'tryoto_name' => $city->city_name,
                'resolved_type' => 'city',
                'city_id' => $city->id,
                'country_id' => $country->id,
                'original' => compact('cityName', 'stateName', 'countryName'),
                'coordinates' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ],
                'message' => "المدينة '{$city->city_name}' مدعومة للشحن"
            ];
        }

        // ==========================================
        // الخطوة 2: البحث عن المحافظة/State في DB
        // ==========================================
        if ($stateName) {
            $stateCity = $this->findCityByName($stateName, $country->id);

            if ($stateCity && $stateCity->tryoto_supported) {
                // تحديث الإحداثيات إذا لم تكن موجودة
                if (!$stateCity->latitude || !$stateCity->longitude) {
                    $stateCity->update([
                        'latitude' => $latitude,
                        'longitude' => $longitude
                    ]);
                    Log::debug('TryotoLocation: Updated state city coordinates', [
                        'city' => $stateCity->city_name,
                        'latitude' => $latitude,
                        'longitude' => $longitude
                    ]);
                }

                Log::debug('TryotoLocation: State found as city in DB', [
                    'state' => $stateName,
                    'found_as' => $stateCity->city_name
                ]);

                return [
                    'success' => true,
                    'strategy' => 'fallback_state',
                    'resolved_name' => $stateCity->city_name,
                    'tryoto_name' => $stateCity->city_name,
                    'resolved_type' => 'state',
                    'city_id' => $stateCity->id,
                    'country_id' => $country->id,
                    'original' => compact('cityName', 'stateName', 'countryName'),
                    'coordinates' => [
                        'latitude' => $latitude,
                        'longitude' => $longitude
                    ],
                    'message' => "المدينة '{$cityName}' غير مدعومة، سيتم الشحن إلى '{$stateCity->city_name}'"
                ];
            }
        }

        // ==========================================
        // الخطوة 3: البحث عن أقرب مدينة مدعومة في الدولة
        // ==========================================
        $nearestCity = $this->findNearestSupportedCity($country->id, $latitude, $longitude);

        if ($nearestCity) {
            Log::debug('TryotoLocation: Found nearest supported city', [
                'original_city' => $cityName,
                'nearest_city' => $nearestCity->city_name,
                'distance_km' => $nearestCity->distance_km
            ]);

            return [
                'success' => true,
                'strategy' => 'nearest_city_same_country',
                'resolved_name' => $nearestCity->city_name,
                'tryoto_name' => $nearestCity->city_name,
                'resolved_type' => 'city',
                'city_id' => $nearestCity->id,
                'country_id' => $country->id,
                'original' => compact('cityName', 'stateName', 'countryName'),
                'coordinates' => [
                    'latitude' => $nearestCity->latitude,
                    'longitude' => $nearestCity->longitude
                ],
                'original_coordinates' => compact('latitude', 'longitude'),
                'distance_km' => $nearestCity->distance_km,
                'message' => "المنطقة '{$stateName}' غير مدعومة، سيتم الشحن إلى أقرب منطقة: '{$nearestCity->city_name}' ({$nearestCity->distance_km} كم)"
            ];
        }

        // ==========================================
        // فشل - لا توجد مدن مدعومة في الدولة
        // ==========================================
        Log::warning('TryotoLocation: No supported cities in country', [
            'country' => $countryName
        ]);

        return [
            'success' => false,
            'strategy' => 'no_supported_cities',
            'original' => compact('cityName', 'stateName', 'countryName'),
            'coordinates' => compact('latitude', 'longitude'),
            'message' => "لا توجد مدن مدعومة للشحن في '{$countryName}'"
        ];
    }

    /**
     * البحث عن الدولة في DB
     */
    protected function findCountry(?string $countryName): ?Country
    {
        if (!$countryName) return null;

        return Country::where('country_name', $countryName)
            ->orWhere('country_name_ar', $countryName)
            ->orWhere('country_code', strtoupper(substr($countryName, 0, 2)))
            ->first();
    }

    /**
     * البحث عن مدينة بالاسم (مع مطابقة مرنة)
     * ملاحظة: البحث بـ city_name الإنجليزي فقط - لا يوجد city_name_ar
     */
    protected function findCityByName(string $name, int $countryId): ?City
    {
        // مطابقة تامة أولاً
        $city = City::where('country_id', $countryId)
            ->where('tryoto_supported', 1)
            ->where('city_name', $name)
            ->first();

        if ($city) return $city;

        // مطابقة جزئية (LIKE)
        $city = City::where('country_id', $countryId)
            ->where('tryoto_supported', 1)
            ->where('city_name', 'LIKE', "%{$name}%")
            ->first();

        if ($city) return $city;

        // مطابقة بدون مسافات وعلامات
        $cleanName = $this->cleanCityName($name);
        $city = City::where('country_id', $countryId)
            ->where('tryoto_supported', 1)
            ->whereRaw("REPLACE(REPLACE(city_name, ' ', ''), '-', '') LIKE ?", ["%{$cleanName}%"])
            ->first();

        if ($city) return $city;

        // محاولة استخراج الكلمة الرئيسية من الاسم (مثل Province, Region, City)
        $mainName = $this->extractMainName($name);
        if ($mainName && $mainName !== $name) {
            return City::where('country_id', $countryId)
                ->where('tryoto_supported', 1)
                ->where('city_name', 'LIKE', "%{$mainName}%")
                ->first();
        }

        return null;
    }

    /**
     * استخراج الاسم الرئيسي من اسم المنطقة
     * مثال: "Riyadh Province" => "Riyadh"
     */
    protected function extractMainName(string $name): ?string
    {
        // إزالة الكلمات الشائعة في نهاية الأسماء
        $suffixes = [
            ' Province', ' Region', ' Governorate', ' District', ' City',
            ' محافظة', ' منطقة', ' إمارة', ' مدينة'
        ];

        foreach ($suffixes as $suffix) {
            if (str_ends_with($name, $suffix)) {
                return trim(str_replace($suffix, '', $name));
            }
        }

        // إزالة الكلمات الشائعة في بداية الأسماء
        $prefixes = ['محافظة ', 'منطقة ', 'إمارة ', 'مدينة '];
        foreach ($prefixes as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return trim(str_replace($prefix, '', $name));
            }
        }

        return null;
    }

    /**
     * تنظيف اسم المدينة للمقارنة
     */
    protected function cleanCityName(string $name): string
    {
        return preg_replace('/[\s\-\'\"\.]+/', '', strtolower($name));
    }

    /**
     * البحث عن أقرب مدينة مدعومة باستخدام الإحداثيات
     *
     * هذا هو الحل الصحيح: نستخدم صيغة Haversine مباشرة في SQL
     * مع fallback إذا لم تكن هناك مدن بإحداثيات
     */
    protected function findNearestSupportedCity(int $countryId, float $lat, float $lng): ?City
    {
        // أولاً: نحاول إيجاد أقرب مدينة لها إحداثيات
        $haversine = "(6371 * acos(
            cos(radians(?)) *
            cos(radians(latitude)) *
            cos(radians(longitude) - radians(?)) +
            sin(radians(?)) *
            sin(radians(latitude))
        ))";

        $city = City::where('country_id', $countryId)
            ->where('tryoto_supported', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("*, {$haversine} as distance_km", [$lat, $lng, $lat])
            ->orderBy('distance_km', 'asc')
            ->first();

        if ($city) {
            return $city;
        }

        // Fallback: إذا لم توجد مدن بإحداثيات، نختار أي مدينة مدعومة
        // ونحدث إحداثياتها بإحداثيات المستخدم الحالية
        Log::debug('TryotoLocation: No cities with coordinates, using fallback', [
            'country_id' => $countryId,
            'user_lat' => $lat,
            'user_lng' => $lng
        ]);

        $fallbackCity = City::where('country_id', $countryId)
            ->where('tryoto_supported', 1)
            ->first();

        if ($fallbackCity) {
            // تحديث إحداثيات المدينة بإحداثيات المستخدم
            $fallbackCity->update([
                'latitude' => $lat,
                'longitude' => $lng
            ]);

            // إضافة distance_km = 0 لأنها الأقرب (الوحيدة المتاحة)
            $fallbackCity->distance_km = 0;

            Log::debug('TryotoLocation: Fallback city selected and coordinates updated', [
                'city' => $fallbackCity->city_name,
                'latitude' => $lat,
                'longitude' => $lng
            ]);
        }

        return $fallbackCity;
    }

    /**
     * Verify if a city is supported (from DB)
     * ملاحظة: البحث بـ city_name الإنجليزي فقط - لا يوجد city_name_ar
     */
    public function verifyCitySupport(string $cityName, ?string $countryName = null): array
    {
        $query = City::where('tryoto_supported', 1)
            ->where(function ($q) use ($cityName) {
                $q->where('city_name', $cityName)
                    ->orWhere('city_name', 'LIKE', "%{$cityName}%");
            });

        if ($countryName) {
            $country = $this->findCountry($countryName);
            if ($country) {
                $query->where('country_id', $country->id);
            }
        }

        $city = $query->first();

        if ($city) {
            return [
                'supported' => true,
                'city_id' => $city->id,
                'city_name' => $city->city_name,
                'country_id' => $city->country_id,
                'latitude' => $city->latitude,
                'longitude' => $city->longitude
            ];
        }

        return ['supported' => false];
    }

    /**
     * Get statistics about synced cities
     */
    public function getSyncStats(): array
    {
        return [
            'total_cities' => City::where('tryoto_supported', 1)->count(),
            'with_coordinates' => City::where('tryoto_supported', 1)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->count(),
            'by_country' => City::where('tryoto_supported', 1)
                ->join('countries', 'cities.country_id', '=', 'countries.id')
                ->selectRaw('countries.country_name, countries.country_code, COUNT(*) as city_count')
                ->groupBy('countries.id', 'countries.country_name', 'countries.country_code')
                ->get()
                ->toArray()
        ];
    }

    /**
     * Get all supported countries from DB
     */
    public function getSupportedCountries(): array
    {
        return Country::whereHas('cities', function ($q) {
            $q->where('tryoto_supported', 1);
        })
            ->get(['id', 'country_code', 'country_name'])
            ->toArray();
    }

    /**
     * Get all supported cities for a country
     * ملاحظة: city_name فقط - لا يوجد city_name_ar
     */
    public function getSupportedCities(int $countryId): array
    {
        return City::where('country_id', $countryId)
            ->where('tryoto_supported', 1)
            ->orderBy('city_name')
            ->get(['id', 'city_name', 'latitude', 'longitude'])
            ->toArray();
    }

    /**
     * Search cities by name (for autocomplete)
     * ملاحظة: البحث بـ city_name الإنجليزي فقط - لا يوجد city_name_ar
     */
    public function searchCities(string $query, ?int $countryId = null, int $limit = 10): array
    {
        $q = City::where('tryoto_supported', 1)
            ->where('city_name', 'LIKE', "%{$query}%");

        if ($countryId) {
            $q->where('country_id', $countryId);
        }

        return $q->with('country:id,country_name,country_code')
            ->limit($limit)
            ->get(['id', 'city_name', 'country_id', 'latitude', 'longitude'])
            ->toArray();
    }
}
