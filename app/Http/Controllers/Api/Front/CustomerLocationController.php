<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Services\CustomerLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CustomerLocationController
 *
 * API for customer location (city selection)
 * Independent from Checkout/Cart
 */
class CustomerLocationController extends Controller
{
    protected CustomerLocationService $locationService;

    public function __construct(CustomerLocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    /**
     * Get current location status
     */
    public function status(): JsonResponse
    {
        if (!$this->locationService->hasCity()) {
            return response()->json([
                'has_location' => false,
            ]);
        }

        return response()->json([
            'has_location' => true,
            'city_id' => $this->locationService->getCityId(),
            'city_name' => $this->locationService->getCityName(),
        ]);
    }

    /**
     * Set location manually (city selection)
     */
    public function setManually(Request $request): JsonResponse
    {
        $request->validate([
            'city_id' => 'required|integer|exists:cities,id',
        ]);

        try {
            $data = $this->locationService->setManually((int) $request->city_id);

            return response()->json([
                'success' => true,
                'city_id' => $data['city_id'],
                'city_name' => $data['city_name'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Set location from geolocation
     */
    public function setFromGeolocation(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        try {
            $data = $this->locationService->setFromGeolocation(
                (float) $request->lat,
                (float) $request->lng
            );

            return response()->json([
                'success' => true,
                'city_id' => $data['city_id'],
                'city_name' => $data['city_name'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'requires_manual' => true,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get available cities for dropdown
     */
    public function getCities(Request $request): JsonResponse
    {
        $cities = $this->locationService->getAvailableCities();

        // Optional search filter
        $search = $request->get('search');
        if ($search) {
            $search = mb_strtolower($search);
            $cities = array_filter($cities, function ($city) use ($search) {
                return str_contains(mb_strtolower($city['name'] ?? ''), $search);
            });
            $cities = array_values($cities);
        }

        return response()->json([
            'success' => true,
            'cities' => $cities,
        ]);
    }

    /**
     * Clear location
     */
    public function clear(): JsonResponse
    {
        $this->locationService->clear();

        return response()->json(['success' => true]);
    }
}
