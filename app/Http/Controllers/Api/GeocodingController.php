<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Services\TryotoLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingController extends Controller
{
    protected $tryotoService;

    public function __construct(TryotoLocationService $tryotoService)
    {
        $this->tryotoService = $tryotoService;
    }

    /**
     * Reverse geocode coordinates to address with smart city resolution
     *
     * التدفق الصحيح:
     * 1. المرحلة الأولى: الحصول على البيانات من Google Maps (الدولة، المنطقة، المدينة، العنوان)
     * 2. المرحلة الثانية: سؤال Tryoto API للتحقق (في TryotoLocationService)
     * 3. المرحلة الثالثة: قرار الشحن (مدينة مدعومة أو أقرب بديل)
     * 4. المرحلة الرابعة: حفظ البيانات كـ Cache ذكي
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reverseGeocode(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180'
        ]);

        $latitude = $request->latitude;
        $longitude = $request->longitude;

        Log::info('🌍 Reverse Geocoding Started', compact('latitude', 'longitude'));

        try {
            // ==========================================
            // المرحلة الأولى: الاعتماد على الخريطة فقط
            // ==========================================
            $geocodeResult = $this->getGoogleGeocode($latitude, $longitude);

            if (!$geocodeResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في الحصول على معلومات الموقع'
                ], 400);
            }

            $addressComponents = $geocodeResult['data'];

            // Extract location details من الخريطة
            $cityName = $addressComponents['city'] ?? $addressComponents['administrative_area_level_1'] ?? null;
            $stateName = $addressComponents['administrative_area_level_1'] ?? null;
            $countryName = $addressComponents['country'] ?? null;
            $countryCode = $addressComponents['country_code'] ?? null;
            $formattedAddress = $geocodeResult['formatted_address'] ?? '';

            if (!$cityName || !$countryName) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على معلومات كافية للموقع'
                ], 400);
            }

            Log::info('📍 Location extracted from map', [
                'city' => $cityName,
                'state' => $stateName,
                'country' => $countryName,
                'address' => $formattedAddress
            ]);

            // Get or create country
            $country = Country::where('country_code', $countryCode)
                ->orWhere('country_name', $countryName)
                ->first();

            if (!$country) {
                return response()->json([
                    'success' => false,
                    'message' => 'الدولة غير مدعومة في النظام'
                ], 400);
            }

            // ==========================================
            // المرحلة الثانية + الثالثة: التحقق من Tryoto وقرار الشحن
            // ==========================================
            $cityResolution = $this->tryotoService->resolveMapCity(
                $cityName,
                $latitude,
                $longitude,
                $country->id
            );

            if (!$cityResolution['verified']) {
                return response()->json([
                    'success' => false,
                    'message' => $cityResolution['message'] ?? 'المدينة غير مدعومة',
                    'data' => [
                        'original_city' => $cityName,
                        'country' => [
                            'id' => $country->id,
                            'name' => $country->country_name,
                            'name_ar' => $country->country_name_ar
                        ]
                    ]
                ], 400);
            }

            // ==========================================
            // المرحلة الرابعة: تخزين البيانات (Cache ذكي)
            // ==========================================

            // Get or create state
            $state = State::where('country_id', $country->id)
                ->where(function ($query) use ($stateName) {
                    $query->where('state', $stateName)
                        ->orWhere('state_ar', $stateName);
                })
                ->first();

            if (!$state && $stateName) {
                $state = State::create([
                    'country_id' => $country->id,
                    'state' => $stateName,
                    'state_ar' => $this->tryotoService->translateRegion($stateName),
                    'status' => 1,
                    'tax' => 0,
                    'owner_id' => 0
                ]);

                Log::info('💾 New state saved to cache', [
                    'state' => $stateName,
                    'country' => $country->country_name
                ]);
            }

            // حفظ المدينة (سواء مدعومة أو بديلة)
            $resolvedCityName = $cityResolution['city_name'];
            $cityCoordinates = $cityResolution['coordinates'] ?? ['lat' => $latitude, 'lng' => $longitude];

            $city = City::where('city_name', $resolvedCityName)
                ->where('country_id', $country->id)
                ->first();

            if (!$city) {
                // Save the verified/supported city
                $city = City::create([
                    'state_id' => $state ? $state->id : null,
                    'country_id' => $country->id,
                    'city_name' => $resolvedCityName,
                    'city_name_ar' => $cityResolution['city_name_ar'] ?? $this->tryotoService->translateCity($resolvedCityName),
                    'latitude' => $cityCoordinates['lat'],
                    'longitude' => $cityCoordinates['lng'],
                    'status' => 1
                ]);

                Log::info('💾 City saved to cache DB', [
                    'city' => $resolvedCityName,
                    'strategy' => $cityResolution['strategy'],
                    'coordinates' => $cityCoordinates,
                    'note' => 'This city was VERIFIED by Tryoto API and saved as cache'
                ]);
            } elseif (!$city->latitude || !$city->longitude) {
                // Update coordinates if missing
                $city->update([
                    'latitude' => $cityCoordinates['lat'],
                    'longitude' => $cityCoordinates['lng']
                ]);

                Log::info('💾 City coordinates updated in cache', [
                    'city' => $resolvedCityName,
                    'coordinates' => $cityCoordinates
                ]);
            }

            // ==========================================
            // إعداد الـ Response
            // ==========================================
            $response = [
                'success' => true,
                'message' => $cityResolution['message'] ?? 'تم تحديد الموقع بنجاح',
                'data' => [
                    'country' => [
                        'id' => $country->id,
                        'name' => $country->country_name,
                        'name_ar' => $country->country_name_ar,
                        'code' => $country->country_code
                    ],
                    'state' => $state ? [
                        'id' => $state->id,
                        'name' => $state->state,
                        'name_ar' => $state->state_ar
                    ] : null,
                    'city' => [
                        'id' => $city->id,
                        'name' => $city->city_name,
                        'name_ar' => $city->city_name_ar
                    ],
                    'coordinates' => [
                        'latitude' => $latitude,
                        'longitude' => $longitude
                    ],
                    'address' => [
                        'en' => $formattedAddress,
                        'ar' => $formattedAddress
                    ],
                    'resolution_info' => [
                        'strategy' => $cityResolution['strategy'],
                        'original_city' => $cityName,
                        'resolved_city' => $resolvedCityName,
                        'is_nearest_city' => $cityResolution['strategy'] === 'nearest_city',
                        'distance_km' => $cityResolution['distance_km'] ?? null,
                        'shipping_companies' => $cityResolution['companies'] ?? 0
                    ]
                ]
            ];

            // Add warning if using nearest city
            if ($cityResolution['strategy'] === 'nearest_city') {
                $response['warning'] = "الموقع المحدد ({$cityName}) غير مدعوم. سيتم استخدام أقرب مدينة: {$resolvedCityName} ({$cityResolution['distance_km']} كم)";

                Log::warning('⚠️ Using nearest city', [
                    'original' => $cityName,
                    'alternative' => $resolvedCityName,
                    'distance' => $cityResolution['distance_km']
                ]);
            }

            Log::info('✅ Reverse geocoding completed successfully', [
                'strategy' => $cityResolution['strategy'],
                'city' => $resolvedCityName
            ]);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('❌ Reverse geocoding error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معالجة الموقع'
            ], 500);
        }
    }

    /**
     * Get geocoding data from Google Maps API
     *
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    protected function getGoogleGeocode($latitude, $longitude)
    {
        try {
            $apiKey = config('services.google_maps.api_key');

            if (!$apiKey) {
                return [
                    'success' => false,
                    'message' => 'Google Maps API key not configured'
                ];
            }

            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => "{$latitude},{$longitude}",
                'key' => $apiKey,
                'language' => 'ar'
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Google Maps API request failed'
                ];
            }

            $data = $response->json();

            if ($data['status'] !== 'OK' || empty($data['results'])) {
                return [
                    'success' => false,
                    'message' => 'No results found'
                ];
            }

            $result = $data['results'][0];
            $components = [];

            foreach ($result['address_components'] as $component) {
                $types = $component['types'];

                if (in_array('locality', $types)) {
                    $components['city'] = $component['long_name'];
                }

                if (in_array('administrative_area_level_2', $types) && !isset($components['city'])) {
                    $components['city'] = $component['long_name'];
                }

                if (in_array('administrative_area_level_1', $types)) {
                    $components['administrative_area_level_1'] = $component['long_name'];
                }

                if (in_array('country', $types)) {
                    $components['country'] = $component['long_name'];
                    $components['country_code'] = $component['short_name'];
                }

                if (in_array('postal_code', $types)) {
                    $components['postal_code'] = $component['long_name'];
                }
            }

            return [
                'success' => true,
                'data' => $components,
                'formatted_address' => $result['formatted_address']
            ];

        } catch (\Exception $e) {
            Log::error('Google Geocode API error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Search for supported cities
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchCities(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2'
        ]);

        $query = $request->query;

        $cities = City::where('status', 1)
            ->where(function ($q) use ($query) {
                $q->where('city_name', 'like', "%{$query}%")
                    ->orWhere('city_name_ar', 'like', "%{$query}%");
            })
            ->with(['country:id,country_name,country_name_ar', 'state:id,state,state_ar'])
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $cities->map(function ($city) {
                return [
                    'id' => $city->id,
                    'name' => $city->city_name,
                    'name_ar' => $city->city_name_ar,
                    'state' => $city->state ? [
                        'id' => $city->state->id,
                        'name' => $city->state->state,
                        'name_ar' => $city->state->state_ar
                    ] : null,
                    'country' => [
                        'id' => $city->country->id,
                        'name' => $city->country->country_name,
                        'name_ar' => $city->country->country_name_ar
                    ],
                    'coordinates' => [
                        'lat' => $city->latitude,
                        'lng' => $city->longitude
                    ]
                ];
            })
        ]);
    }
}
