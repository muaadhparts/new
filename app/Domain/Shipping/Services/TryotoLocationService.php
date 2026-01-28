<?php

namespace App\Domain\Shipping\Services;

use App\Domain\Shipping\Models\City;
use App\Domain\Shipping\Models\Country;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * TryotoLocationService - Location Resolution from Database
 *
 * التوجيه المعماري:
 * - Tryoto هو المصدر الوحيد للمدن (يتم الاستيراد يدوياً عبر CLI)
 * - هذا الـ service READ-ONLY - لا يُنشئ مدن جديدة
 * - البيانات: name (إنجليزي فقط), latitude, longitude, country_id, tryoto_supported
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
     * @param string $name City from Google Maps
     * @param string|null $stateName State from Google Maps
     * @param string|null $countryName Country from Google Maps
     * @param float $latitude User's latitude
     * @param float $longitude User's longitude
     * @return array
     */
    public function resolveMapCity(
        string $name,
        ?string $stateName,
        ?string $countryName,
        float $latitude,
        float $longitude
    ): array {
        Log::debug('TryotoLocation: Resolving location from DB', [
            'city' => $name,
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
                'original' => compact('name', 'stateName', 'countryName'),
                'coordinates' => compact('latitude', 'longitude'),
                'message' => "الدولة '{$countryName}' غير مدعومة من خدمة الشحن"
            ];
        }

        // ==========================================
        // الخطوة 1: البحث عن المدينة بالاسم في DB
        // ==========================================
        $city = $this->findCityByName($name, $country->id);

        if ($city && $city->tryoto_supported) {
            // تحديث الإحداثيات إذا لم تكن موجودة (تحسين البيانات تدريجياً)
            if (!$city->latitude || !$city->longitude) {
                $city->update([
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ]);
                Log::debug('TryotoLocation: Updated city coordinates', [
                    'city' => $city->name,
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ]);
            }

            Log::debug('TryotoLocation: City found in DB', [
                'city' => $city->name,
                'has_coordinates' => ($city->latitude && $city->longitude)
            ]);

            return [
                'success' => true,
                'strategy' => 'exact_city',
                'resolved_name' => $city->name,
                'tryoto_name' => $city->name,
                'resolved_type' => 'city',
                'city_id' => $city->id,
                'country_id' => $country->id,
                'original' => compact('name', 'stateName', 'countryName'),
                'coordinates' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ],
                'message' => "المدينة '{$city->name}' مدعومة للشحن"
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
                        'city' => $stateCity->name,
                        'latitude' => $latitude,
                        'longitude' => $longitude
                    ]);
                }

                Log::debug('TryotoLocation: State found as city in DB', [
                    'state' => $stateName,
                    'found_as' => $stateCity->name
                ]);

                return [
                    'success' => true,
                    'strategy' => 'fallback_state',
                    'resolved_name' => $stateCity->name,
                    'tryoto_name' => $stateCity->name,
                    'resolved_type' => 'state',
                    'city_id' => $stateCity->id,
                    'country_id' => $country->id,
                    'original' => compact('name', 'stateName', 'countryName'),
                    'coordinates' => [
                        'latitude' => $latitude,
                        'longitude' => $longitude
                    ],
                    'message' => "المدينة '{$name}' غير مدعومة، سيتم الشحن إلى '{$stateCity->name}'"
                ];
            }
        }

        // ==========================================
        // الخطوة 3: البحث عن أقرب مدينة مدعومة في الدولة
        // ==========================================
        $nearestCity = $this->findNearestSupportedCity($country->id, $latitude, $longitude);

        if ($nearestCity) {
            Log::debug('TryotoLocation: Found nearest supported city', [
                'original_city' => $name,
                'nearest_city' => $nearestCity->name,
                'distance_km' => $nearestCity->distance_km
            ]);

            return [
                'success' => true,
                'strategy' => 'nearest_city_same_country',
                'resolved_name' => $nearestCity->name,
                'tryoto_name' => $nearestCity->name,
                'resolved_type' => 'city',
                'city_id' => $nearestCity->id,
                'country_id' => $country->id,
                'original' => compact('name', 'stateName', 'countryName'),
                'coordinates' => [
                    'latitude' => $nearestCity->latitude,
                    'longitude' => $nearestCity->longitude
                ],
                'original_coordinates' => compact('latitude', 'longitude'),
                'distance_km' => $nearestCity->distance_km,
                'message' => "المنطقة '{$stateName}' غير مدعومة، سيتم الشحن إلى أقرب منطقة: '{$nearestCity->name}' ({$nearestCity->distance_km} كم)"
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
            'original' => compact('name', 'stateName', 'countryName'),
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
     * ملاحظة: البحث بـ name الإنجليزي فقط - لا يوجد city_name_ar
     */
    protected function findCityByName(string $name, int $countryId): ?City
    {
        // مطابقة تامة أولاً
        $city = City::where('country_id', $countryId)
            ->where('tryoto_supported', 1)
            ->where('name', $name)
            ->first();

        if ($city) return $city;

        // مطابقة جزئية (LIKE)
        $city = City::where('country_id', $countryId)
            ->where('tryoto_supported', 1)
            ->where('name', 'LIKE', "%{$name}%")
            ->first();

        if ($city) return $city;

        // مطابقة بدون مسافات وعلامات
        $cleanName = $this->cleanname($name);
        $city = City::where('country_id', $countryId)
            ->where('tryoto_supported', 1)
            ->whereRaw("REPLACE(REPLACE(name, ' ', ''), '-', '') LIKE ?", ["%{$cleanName}%"])
            ->first();

        if ($city) return $city;

        // محاولة استخراج الكلمة الرئيسية من الاسم (مثل Province, Region, City)
        $mainName = $this->extractMainName($name);
        if ($mainName && $mainName !== $name) {
            $city = City::where('country_id', $countryId)
                ->where('tryoto_supported', 1)
                ->where('name', 'LIKE', "%{$mainName}%")
                ->first();

            if ($city) return $city;

            // ✅ جديد: تجربة الأسماء البديلة (بدون Al، بدون The، إلخ)
            $alternativeNames = $this->getAlternativeNames($mainName);
            foreach ($alternativeNames as $altName) {
                $city = City::where('country_id', $countryId)
                    ->where('tryoto_supported', 1)
                    ->where(function($q) use ($altName) {
                        $q->where('name', $altName)
                          ->orWhere('name', 'LIKE', "%{$altName}%");
                    })
                    ->first();

                if ($city) return $city;
            }
        }

        // ✅ جديد: تجربة الأسماء البديلة للاسم الأصلي
        $alternativeNames = $this->getAlternativeNames($name);
        foreach ($alternativeNames as $altName) {
            $city = City::where('country_id', $countryId)
                ->where('tryoto_supported', 1)
                ->where(function($q) use ($altName) {
                    $q->where('name', $altName)
                      ->orWhere('name', 'LIKE', "%{$altName}%");
                })
                ->first();

            if ($city) return $city;
        }

        return null;
    }

    /**
     * توليد أسماء بديلة للمدينة
     * مثال: "Al Qassim" => ["Qassim", "AlQassim"]
     */
    protected function getAlternativeNames(string $name): array
    {
        $alternatives = [];

        // إزالة البادئات الشائعة
        $prefixes = ['Al ', 'Al-', 'The ', 'As ', 'Ash ', 'Ad ', 'An '];
        foreach ($prefixes as $prefix) {
            if (stripos($name, $prefix) === 0) {
                $alternatives[] = trim(substr($name, strlen($prefix)));
            }
        }

        // إضافة البادئات إذا لم تكن موجودة
        if (!preg_match('/^(Al|The|As|Ash|Ad|An)[\s\-]/i', $name)) {
            $alternatives[] = 'Al ' . $name;
            $alternatives[] = 'Al-' . $name;
        }

        // إزالة المسافات والشرطات
        $noSpaces = str_replace([' ', '-'], '', $name);
        if ($noSpaces !== $name) {
            $alternatives[] = $noSpaces;
        }

        // إضافة نسخة بشرطة بدلاً من مسافة
        $withDash = str_replace(' ', '-', $name);
        if ($withDash !== $name) {
            $alternatives[] = $withDash;
        }

        return array_unique($alternatives);
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
    protected function cleanname(string $name): string
    {
        return preg_replace('/[\s\-\'\"\.]+/', '', strtolower($name));
    }

    /**
     * الحد الأقصى للمسافة بالكيلومتر لاختيار أقرب مدينة
     * إذا كانت أقرب مدينة أبعد من هذا الحد، يتم الرفض
     */
    private const MAX_DISTANCE_KM = 150;

    /**
     * البحث عن أقرب مدينة مدعومة باستخدام الإحداثيات
     *
     * هذا هو الحل الصحيح: نستخدم صيغة Haversine مباشرة في SQL
     * مع حد أقصى للمسافة (150 كم) لمنع اختيار مدينة بعيدة جداً
     */
    protected function findNearestSupportedCity(int $countryId, float $lat, float $lng): ?City
    {
        // صيغة Haversine لحساب المسافة بالكيلومتر
        $haversine = "(6371 * acos(
            cos(radians(?)) *
            cos(radians(latitude)) *
            cos(radians(longitude) - radians(?)) +
            sin(radians(?)) *
            sin(radians(latitude))
        ))";

        // البحث عن أقرب مدينة ضمن الحد الأقصى للمسافة
        $city = City::where('country_id', $countryId)
            ->where('tryoto_supported', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("*, {$haversine} as distance_km", [$lat, $lng, $lat])
            ->havingRaw('distance_km <= ?', [self::MAX_DISTANCE_KM])
            ->orderBy('distance_km', 'asc')
            ->first();

        if ($city) {
            Log::debug('TryotoLocation: Found nearest city within limit', [
                'city' => $city->name,
                'distance_km' => round($city->distance_km, 2),
                'max_allowed' => self::MAX_DISTANCE_KM
            ]);
            return $city;
        }

        // تحقق إذا كانت هناك مدن لكن بعيدة جداً
        $nearestAnyDistance = City::where('country_id', $countryId)
            ->where('tryoto_supported', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("name, {$haversine} as distance_km", [$lat, $lng, $lat])
            ->orderBy('distance_km', 'asc')
            ->first();

        if ($nearestAnyDistance) {
            Log::warning('TryotoLocation: Nearest city is too far', [
                'nearest_city' => $nearestAnyDistance->name,
                'distance_km' => round($nearestAnyDistance->distance_km, 2),
                'max_allowed' => self::MAX_DISTANCE_KM,
                'user_coordinates' => compact('lat', 'lng')
            ]);
            return null;
        }

        // لا توجد مدن بإحداثيات في هذه الدولة
        Log::warning('TryotoLocation: No cities with coordinates in country', [
            'country_id' => $countryId
        ]);

        return null;
    }

    /**
     * Verify if a city is supported (from DB)
     * ملاحظة: البحث بـ name الإنجليزي فقط - لا يوجد city_name_ar
     */
    public function verifyCitySupport(string $name, ?string $countryName = null): array
    {
        $query = City::where('tryoto_supported', 1)
            ->where(function ($q) use ($name) {
                $q->where('name', $name)
                    ->orWhere('name', 'LIKE', "%{$name}%");
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
                'name' => $city->name,
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
     * ملاحظة: name فقط - لا يوجد city_name_ar
     */
    public function getSupportedCities(int $countryId): array
    {
        return City::where('country_id', $countryId)
            ->where('tryoto_supported', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'latitude', 'longitude'])
            ->toArray();
    }

    /**
     * Search cities by name (for autocomplete)
     * ملاحظة: البحث بـ name الإنجليزي فقط - لا يوجد city_name_ar
     */
    public function searchCities(string $query, ?int $countryId = null, int $limit = 10): array
    {
        $q = City::where('tryoto_supported', 1)
            ->where('name', 'LIKE', "%{$query}%");

        if ($countryId) {
            $q->where('country_id', $countryId);
        }

        return $q->with('country:id,country_name,country_code')
            ->limit($limit)
            ->get(['id', 'name', 'country_id', 'latitude', 'longitude'])
            ->toArray();
    }

    /**
     * ========================================================================
     * FALLBACK: Resolve by Coordinates Only
     * ========================================================================
     * عندما يفشل الـ Geocoding ولا تتوفر أسماء المدينة/الدولة
     * نبحث عن أقرب مدينة مدعومة في أي دولة (بحث عالمي)
     *
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    public function resolveByCoordinatesOnly(float $latitude, float $longitude): array
    {
        Log::info('TryotoLocation: Fallback - resolving by coordinates only', [
            'latitude' => $latitude,
            'longitude' => $longitude
        ]);

        // صيغة Haversine لحساب المسافة بالكيلومتر
        $haversine = "(6371 * acos(
            cos(radians(?)) *
            cos(radians(latitude)) *
            cos(radians(longitude) - radians(?)) +
            sin(radians(?)) *
            sin(radians(latitude))
        ))";

        // البحث عن أقرب مدينة مدعومة (بدون قيد الدولة)
        $city = City::where('tryoto_supported', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("*, {$haversine} as distance_km", [$latitude, $longitude, $latitude])
            ->havingRaw('distance_km <= ?', [self::MAX_DISTANCE_KM])
            ->orderBy('distance_km', 'asc')
            ->with('country:id,country_name,country_code')
            ->first();

        if ($city) {
            Log::info('TryotoLocation: Found nearest city globally', [
                'city' => $city->name,
                'country' => $city->country->country_name ?? 'Unknown',
                'distance_km' => round($city->distance_km, 2)
            ]);

            return [
                'success' => true,
                'strategy' => 'coordinates_fallback',
                'resolved_name' => $city->name,
                'tryoto_name' => $city->name,
                'resolved_type' => 'city',
                'city_id' => $city->id,
                'country_id' => $city->country_id,
                'country_name' => $city->country->country_name ?? null,
                'coordinates' => [
                    'latitude' => $city->latitude,
                    'longitude' => $city->longitude
                ],
                'original_coordinates' => compact('latitude', 'longitude'),
                'distance_km' => round($city->distance_km, 2),
                'message' => "تم تحديد أقرب مدينة للشحن: '{$city->name}' (" . ($city->country->country_name ?? '') . ")"
            ];
        }

        // لا توجد مدن قريبة
        Log::warning('TryotoLocation: No supported cities within range', [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'max_distance_km' => self::MAX_DISTANCE_KM
        ]);

        return [
            'success' => false,
            'strategy' => 'no_nearby_cities',
            'coordinates' => compact('latitude', 'longitude'),
            'message' => "لا توجد مدن مدعومة للشحن بالقرب من موقعك"
        ];
    }
}
