<?php

namespace App\Domain\Shipping\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

/**
 * ShippingQuoteService
 *
 * حساب تكلفة الشحن - Quote فقط بدون إنشاء شحنة
 *
 * المتطلبات:
 * - merchant_id + branch_id: لتحديد مدينة المصدر
 * - weight: وزن المنتج الحقيقي
 * - coordinates: إحداثيات العميل من المتصفح
 */
class ShippingQuoteService
{
    protected TryotoService $tryotoService;
    protected GoogleMapsService $googleMapsService;

    private const SESSION_KEY = 'shipping_quote_location';

    public function __construct(TryotoService $tryotoService, GoogleMapsService $googleMapsService)
    {
        $this->tryotoService = $tryotoService;
        $this->googleMapsService = $googleMapsService;
    }

    /**
     * الحصول على تسعيرة الشحن
     */
    public function getCatalogItemQuote(int $merchantId, int $branchId, float $weight, ?array $coordinates = null): array
    {
        // التحقق من البيانات
        if ($merchantId <= 0 || $branchId <= 0 || $weight <= 0) {
            return ['success' => false, 'error_code' => 'INVALID_DATA'];
        }

        // الإحداثيات
        $coords = $coordinates ?? $this->getStoredCoordinates();
        if (!$coords || empty($coords['latitude']) || empty($coords['longitude'])) {
            return [
                'success' => false,
                'requires_location' => true,
                'message' => __('يرجى تفعيل خدمة الموقع'),
            ];
        }

        // مدينة المصدر (الفرع)
        $origin = $this->resolveOriginCity($merchantId, $branchId);
        if (!$origin) {
            return [
                'success' => false,
                'error_code' => 'MERCHANT_CITY_NOT_SET',
                'message' => __('مدينة التاجر غير مدعومة'),
            ];
        }

        // مدينة الوجهة (العميل)
        $destination = $this->resolveDestinationCity((float)$coords['latitude'], (float)$coords['longitude']);
        if (!$destination['success']) {
            return $destination;
        }

        // استدعاء Tryoto API
        return $this->callTryotoApi($merchantId, $origin['city_name'], $destination['city_name'], $weight);
    }

    /**
     * تحديد مدينة المصدر من الفرع
     */
    protected function resolveOriginCity(int $merchantId, int $branchId): ?array
    {
        $branchData = ShippingCalculatorService::getBranchCity($branchId);

        if (!$branchData || ($branchData['merchant_id'] ?? null) != $merchantId) {
            return null;
        }

        if (empty($branchData['city_name'])) {
            return null;
        }

        // التحقق من دعم المدينة
        $city = DB::table('cities')
            ->where('city_name', $branchData['city_name'])
            ->where('tryoto_supported', 1)
            ->first();

        if (!$city) {
            // محاولة مطابقة جزئية
            $city = DB::table('cities')
                ->where('tryoto_supported', 1)
                ->where(fn($q) => $q->where('city_name', 'LIKE', $branchData['city_name'] . '%')
                    ->orWhere('city_name', 'LIKE', '%' . $branchData['city_name']))
                ->first();
        }

        return $city ? ['city_name' => $city->city_name, 'city_id' => $city->id] : null;
    }

    /**
     * تحديد مدينة الوجهة من الإحداثيات
     */
    protected function resolveDestinationCity(float $lat, float $lng): array
    {
        // Geocoding
        $geocode = $this->googleMapsService->reverseGeocode($lat, $lng, 'en');

        if (!$geocode['success'] || empty($geocode['data'])) {
            return ['success' => false, 'error_code' => 'GEOCODING_FAILED', 'message' => __('لم نتمكن من تحديد موقعك')];
        }

        $cityName = $geocode['data']['city'] ?? null;
        $stateName = $geocode['data']['state'] ?? null;
        $countryName = $geocode['data']['country'] ?? null;

        if (!$countryName) {
            return ['success' => false, 'error_code' => 'COUNTRY_NOT_FOUND', 'message' => __('لم نتمكن من تحديد دولتك')];
        }

        $country = DB::table('countries')->where('country_name', $countryName)->first();
        if (!$country) {
            return ['success' => false, 'error_code' => 'COUNTRY_NOT_SUPPORTED', 'message' => __('الدولة غير مدعومة')];
        }

        // البحث عن المدينة
        $city = null;
        foreach (array_filter([$cityName, $stateName]) as $name) {
            $city = DB::table('cities')
                ->where('country_id', $country->id)
                ->where('tryoto_supported', 1)
                ->where(fn($q) => $q->where('city_name', $name)
                    ->orWhere('city_name', 'LIKE', $name . '%')
                    ->orWhere('city_name', 'LIKE', '%' . $name))
                ->first();
            if ($city) break;
        }

        if (!$city) {
            return ['success' => false, 'error_code' => 'CITY_NOT_SUPPORTED', 'message' => __('مدينتك غير مدعومة حالياً')];
        }

        return ['success' => true, 'city_name' => $city->city_name, 'city_id' => $city->id];
    }

    /**
     * استدعاء Tryoto API
     */
    protected function callTryotoApi(int $merchantId, string $origin, string $destination, float $weight): array
    {
        $cacheKey = "quote:{$merchantId}:{$origin}:{$destination}:{$weight}";
        $cached = Cache::get($cacheKey);
        if ($cached) return $cached;

        try {
            $result = $this->tryotoService
                ->forMerchant($merchantId)
                ->getDeliveryOptions($origin, $destination, $weight, 0, []);

            if (!$result['success'] || empty($result['raw']['deliveryCompany'])) {
                return ['success' => false, 'message' => __('لا تتوفر خيارات شحن')];
            }

            $options = collect($result['raw']['deliveryCompany'])->map(fn($opt) => [
                'id' => $opt['deliveryOptionId'] ?? null,
                'name' => $opt['deliveryOptionName'] ?? $opt['deliveryCompanyName'] ?? __('شحن'),
                'price' => (float)($opt['price'] ?? 0),
                'estimated_days' => $this->parseDeliveryTime($opt['avgDeliveryTime'] ?? ''),
            ])->sortBy('price')->values()->toArray();

            $response = [
                'success' => true,
                'options' => $options,
                'origin' => $origin,
                'destination' => $destination,
            ];

            Cache::put($cacheKey, $response, 900);
            return $response;

        } catch (\Exception $e) {
            return ['success' => false, 'message' => __('حدث خطأ في الاتصال')];
        }
    }

    /**
     * الحصول على أرخص خيار
     */
    public function getCheapestOption(array $result): ?array
    {
        return ($result['success'] && !empty($result['options'])) ? $result['options'][0] : null;
    }

    /**
     * تحويل وقت التوصيل
     */
    protected function parseDeliveryTime(string $time): ?string
    {
        if (empty($time)) return null;
        if (stripos($time, 'same') !== false) return '0';
        if (stripos($time, 'next') !== false) return '1';
        if (preg_match('/(\d+)\s*(?:to|-)\s*(\d+)/i', $time, $m)) return $m[1] . '-' . $m[2];
        if (preg_match('/(\d+)/', $time, $m)) return $m[1];
        return null;
    }

    // ========================================
    // Session Storage
    // ========================================

    public function storeCoordinates(float $lat, float $lng): array
    {
        $data = ['latitude' => $lat, 'longitude' => $lng, 'stored_at' => now()->toISOString()];

        // محاولة تحديد المدينة
        $resolution = $this->resolveDestinationCity($lat, $lng);
        if ($resolution['success']) {
            $data['resolved_city'] = $resolution['city_name'];
        }

        Session::put(self::SESSION_KEY, $data);
        return $data;
    }

    public function getStoredCoordinates(): ?array
    {
        $data = Session::get(self::SESSION_KEY);
        if (!$data || empty($data['latitude']) || empty($data['longitude'])) return null;
        return $data;
    }

    public function clearStoredCoordinates(): void
    {
        Session::forget(self::SESSION_KEY);
    }
}
