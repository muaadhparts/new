<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Services\GoogleMapsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GeocodingController extends Controller
{
    protected $googleMapsService;

    public function __construct(GoogleMapsService $googleMapsService)
    {
        $this->googleMapsService = $googleMapsService;
    }

    /**
     * Reverse geocode coordinates to location data
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reverseGeocode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $latitude = $request->latitude;
        $longitude = $request->longitude;

        // Get bilingual location data
        $locationData = $this->googleMapsService->getBilingualNames($latitude, $longitude);

        if (!$locationData['success']) {
            return response()->json([
                'success' => false,
                'error' => $locationData['error']
            ], 400);
        }

        $arabicData = $locationData['data']['ar'];
        $englishData = $locationData['data']['en'];

        // Process and store location data in database
        try {
            DB::beginTransaction();

            $result = $this->processLocationData($arabicData, $englishData, $latitude, $longitude);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing location data', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process location data'
            ], 500);
        }
    }

    /**
     * Process and store location data
     *
     * @param array $arabicData
     * @param array $englishData
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    protected function processLocationData($arabicData, $englishData, $latitude, $longitude)
    {
        // Extract country code (prioritize English data for country code)
        $countryCode = $englishData['country_code'] ?? $arabicData['country_code'] ?? null;

        if (!$countryCode) {
            throw new \Exception('Country code not found in geocoding results');
        }

        // Country names
        $countryNameEn = $englishData['country'] ?? null;
        $countryNameAr = $arabicData['country'] ?? $countryNameEn;

        // State names
        $stateNameEn = $englishData['state'] ?? null;
        $stateNameAr = $arabicData['state'] ?? $stateNameEn;

        // City names
        $cityNameEn = $englishData['city'] ?? null;
        $cityNameAr = $arabicData['city'] ?? $cityNameEn;

        // Address
        $addressEn = $englishData['address'] ?? null;
        $addressAr = $arabicData['address'] ?? $addressEn;

        // Find or create Country
        $country = Country::where('country_code', $countryCode)->first();

        if (!$country) {
            $country = Country::create([
                'country_code' => $countryCode,
                'country_name' => $countryNameEn ?? $countryCode,
                'country_name_ar' => $countryNameAr ?? $countryNameEn ?? $countryCode,
                'tax' => 0,
                'status' => 1
            ]);
        }

        // Find or create State
        $state = null;
        if ($stateNameEn) {
            $state = State::where('country_id', $country->id)
                ->where(function ($query) use ($stateNameEn, $stateNameAr) {
                    $query->where('state', $stateNameEn)
                        ->orWhere('state_ar', $stateNameAr);
                })
                ->first();

            if (!$state) {
                $state = State::create([
                    'country_id' => $country->id,
                    'state' => $stateNameEn,
                    'state_ar' => $stateNameAr ?? $stateNameEn,
                    'tax' => 0,
                    'status' => 1,
                    'owner_id' => 0
                ]);
            }
        }

        // Find or create City
        $city = null;
        if ($cityNameEn && $state) {
            $city = City::where('state_id', $state->id)
                ->where(function ($query) use ($cityNameEn, $cityNameAr) {
                    $query->where('city_name', $cityNameEn)
                        ->orWhere('city_name_ar', $cityNameAr);
                })
                ->first();

            if (!$city) {
                $city = City::create([
                    'state_id' => $state->id,
                    'country_id' => $country->id,
                    'city_name' => $cityNameEn,
                    'city_name_ar' => $cityNameAr ?? $cityNameEn,
                    'status' => 1
                ]);
            }
        }

        return [
            'country' => [
                'id' => $country->id,
                'code' => $country->country_code,
                'name' => $country->country_name,
                'name_ar' => $country->country_name_ar
            ],
            'state' => $state ? [
                'id' => $state->id,
                'name' => $state->state,
                'name_ar' => $state->state_ar
            ] : null,
            'city' => $city ? [
                'id' => $city->id,
                'name' => $city->city_name,
                'name_ar' => $city->city_name_ar
            ] : null,
            'address' => [
                'en' => $addressEn,
                'ar' => $addressAr
            ],
            'coordinates' => [
                'latitude' => $latitude,
                'longitude' => $longitude
            ]
        ];
    }

    /**
     * Get all countries
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCountries()
    {
        $countries = Country::where('status', 1)
            ->orderBy('country_name')
            ->get(['id', 'country_code', 'country_name', 'country_name_ar']);

        return response()->json([
            'success' => true,
            'data' => $countries
        ]);
    }

    /**
     * Get states by country
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatesByCountry(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country_id' => 'required|exists:countries,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $states = State::where('country_id', $request->country_id)
            ->where('status', 1)
            ->orderBy('state')
            ->get(['id', 'state', 'state_ar', 'country_id']);

        return response()->json([
            'success' => true,
            'data' => $states
        ]);
    }

    /**
     * Get cities by state
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCitiesByState(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'state_id' => 'required|exists:states,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $cities = City::where('state_id', $request->state_id)
            ->where('status', 1)
            ->orderBy('city_name')
            ->get(['id', 'city_name', 'city_name_ar', 'state_id', 'country_id']);

        return response()->json([
            'success' => true,
            'data' => $cities
        ]);
    }
}
