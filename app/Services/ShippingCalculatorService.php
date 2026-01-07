<?php

namespace App\Services;

use App\Models\City;
use App\Models\User;
use App\Models\MerchantLocation;
use Illuminate\Support\Facades\Log;

/**
 * Unified Shipping Calculator Service
 *
 * Architecture:
 * - Data: city_name (English only) - no city_name_ar
 * - No hardcoded values
 * - No fallback for any data
 * - Each merchant calculated independently
 * - Chargeable weight = max(actual, volumetric)
 * - Merchant origin from merchant_locations table
 */
class ShippingCalculatorService
{
    /**
     * حساب الوزن الحجمي
     * المعادلة القياسية: (ط × ع × ا) / 5000
     */
    public static function calculateVolumetricWeight(?float $length, ?float $width, ?float $height): ?float
    {
        if ($length === null || $width === null || $height === null) {
            return null;
        }

        if ($length <= 0 || $width <= 0 || $height <= 0) {
            return null;
        }

        return ($length * $width * $height) / 5000;
    }

    /**
     * حساب الوزن القابل للشحن
     * = الأعلى بين الوزن الفعلي والحجمي
     */
    public static function calculateChargeableWeight(?float $actualWeight, ?float $volumetricWeight): ?float
    {
        if ($actualWeight === null && $volumetricWeight === null) {
            return null;
        }

        $actual = $actualWeight ?? 0;
        $volumetric = $volumetricWeight ?? 0;

        return max($actual, $volumetric);
    }

    /**
     * Get merchant's origin city (shipping origin)
     *
     * Source: merchant_locations table (warehouse/origin locations)
     * - Finds first active merchant location
     * - Uses city_id to get city data
     */
    public static function getMerchantCity(int $merchantId): ?array
    {
        // Get first active merchant location
        $merchantLocation = MerchantLocation::where('user_id', $merchantId)
            ->where('status', 1)
            ->first();

        if (!$merchantLocation) {
            Log::warning('ShippingCalculator: Merchant has no active location', ['merchant_id' => $merchantId]);
            return null;
        }

        if (!$merchantLocation->city_id) {
            Log::warning('ShippingCalculator: Merchant location has no city', [
                'merchant_id' => $merchantId,
                'merchant_location_id' => $merchantLocation->id,
            ]);
            return null;
        }

        $city = City::find($merchantLocation->city_id);
        if (!$city) {
            Log::warning('ShippingCalculator: City not found for merchant location', [
                'merchant_id' => $merchantId,
                'merchant_location_id' => $merchantLocation->id,
                'city_id' => $merchantLocation->city_id,
            ]);
            return null;
        }

        return [
            'city_id' => $city->id,
            'city_name' => $city->city_name,
            'country_id' => $city->country_id,
            'merchant_location_id' => $merchantLocation->id,
            'warehouse_address' => $merchantLocation->location,
            'latitude' => $merchantLocation->latitude,
            'longitude' => $merchantLocation->longitude,
            'source' => 'merchant_location',
        ];
    }

    /**
     * جلب مدينة العميل (مدينة المستلم)
     *
     * المصدر: الخريطة فقط - city_id من session
     * لا fallback - يجب أن يكون city_id موجود
     */
    public static function getCustomerCity(?int $cityId): ?array
    {
        if (!$cityId) {
            Log::warning('ShippingCalculator: Customer city_id is required (from map)');
            return null;
        }

        $city = City::find($cityId);

        if (!$city) {
            Log::warning('ShippingCalculator: Customer city not found', ['city_id' => $cityId]);
            return null;
        }

        return [
            'city_id' => $city->id,
            'city_name' => $city->city_name,
            'country_id' => $city->country_id,
            'source' => 'map',
        ];
    }

    /**
     * تجهيز بيانات الشحن لإرسالها لـ Tryoto
     * بدون أي قيم ثابتة
     */
    public static function prepareShippingRequest(array $merchantShippingData, array $customerData): array
    {
        $errors = [];

        $merchantCity = $merchantShippingData['merchant_city'] ?? null;
        if (!$merchantCity) {
            $errors[] = 'merchant_city_missing';
        }

        $customerCity = $customerData['city_name'] ?? null;
        if (!$customerCity) {
            $errors[] = 'customer_city_missing';
        }

        $chargeableWeight = $merchantShippingData['chargeable_weight'] ?? null;
        if ($chargeableWeight === null) {
            $errors[] = 'chargeable_weight_missing';
        }

        $dimensions = $merchantShippingData['dimensions'] ?? [];
        $length = $dimensions['length'] ?? null;
        $width = $dimensions['width'] ?? null;
        $height = $dimensions['height'] ?? null;

        if ($length === null) $errors[] = 'length_missing';
        if ($width === null) $errors[] = 'width_missing';
        if ($height === null) $errors[] = 'height_missing';

        if (!empty($errors)) {
            return [
                'valid' => false,
                'errors' => $errors,
                'message' => __('Incomplete shipping data'),
            ];
        }

        return [
            'valid' => true,
            'request_data' => [
                'originCity' => $merchantCity,
                'destinationCity' => $customerCity,
                'weight' => $chargeableWeight,
                'xlength' => $length,
                'xwidth' => $width,
                'xheight' => $height,
                'codAmount' => $customerData['cod_amount'] ?? 0,
            ],
            'meta' => [
                'merchant_id' => $merchantShippingData['merchant_id'] ?? null,
                'actual_weight' => $merchantShippingData['actual_weight'] ?? null,
                'volumetric_weight' => $merchantShippingData['volumetric_weight'] ?? null,
                'items_count' => $merchantShippingData['items_count'] ?? 0,
            ],
        ];
    }

    /**
     * التحقق من اكتمال بيانات الشحن للتاجر
     */
    public static function validateMerchantShippingData(array $merchantShippingData): array
    {
        $errors = [];
        $warnings = [];

        if (empty($merchantShippingData['merchant_city'])) {
            $errors[] = [
                'field' => 'merchant_city',
                'message' => __('Merchant city is not set'),
            ];
        }

        if ($merchantShippingData['actual_weight'] === null) {
            $errors[] = [
                'field' => 'weight',
                'message' => __('CatalogItem weights are missing'),
            ];
        }

        $dims = $merchantShippingData['dimensions'] ?? [];
        if ($dims['length'] === null || $dims['width'] === null || $dims['height'] === null) {
            $errors[] = [
                'field' => 'dimensions',
                'message' => __('CatalogItem dimensions are missing'),
            ];
        }

        if ($merchantShippingData['chargeable_weight'] === null) {
            $errors[] = [
                'field' => 'chargeable_weight',
                'message' => __('Cannot calculate shipping weight'),
            ];
        }

        if (!empty($merchantShippingData['missing_data'])) {
            foreach ($merchantShippingData['missing_data'] as $missing) {
                $warnings[] = $missing;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * حساب أبعاد الشحنة من مجموعة منتجات
     */
    public static function calculatePackageDimensions(array $items): array
    {
        $totalWeight = 0;
        $maxLength = null;
        $maxWidth = null;
        $totalHeight = 0;
        $hasCompleteData = true;
        $missingItems = [];

        foreach ($items as $index => $item) {
            $qty = (int) ($item['qty'] ?? 1);
            $weight = $item['weight'] ?? null;
            $length = $item['length'] ?? null;
            $width = $item['width'] ?? null;
            $height = $item['height'] ?? null;

            if ($weight !== null && $weight > 0) {
                $totalWeight += (float) $weight * $qty;
            } else {
                $hasCompleteData = false;
                $missingItems[] = "Item {$index}: weight";
            }

            if ($length !== null && $length > 0) {
                $maxLength = $maxLength === null ? (float) $length : max($maxLength, (float) $length);
            } else {
                $hasCompleteData = false;
                $missingItems[] = "Item {$index}: length";
            }

            if ($width !== null && $width > 0) {
                $maxWidth = $maxWidth === null ? (float) $width : max($maxWidth, (float) $width);
            } else {
                $hasCompleteData = false;
                $missingItems[] = "Item {$index}: width";
            }

            if ($height !== null && $height > 0) {
                $totalHeight += (float) $height * $qty;
            } else {
                $hasCompleteData = false;
                $missingItems[] = "Item {$index}: height";
            }
        }

        $volumetricWeight = self::calculateVolumetricWeight($maxLength, $maxWidth, $totalHeight);
        $chargeableWeight = self::calculateChargeableWeight(
            $totalWeight > 0 ? $totalWeight : null,
            $volumetricWeight
        );

        return [
            'actual_weight' => $totalWeight > 0 ? round($totalWeight, 3) : null,
            'volumetric_weight' => $volumetricWeight !== null ? round($volumetricWeight, 3) : null,
            'chargeable_weight' => $chargeableWeight !== null ? round($chargeableWeight, 3) : null,
            'dimensions' => [
                'length' => $maxLength !== null ? round($maxLength, 2) : null,
                'width' => $maxWidth !== null ? round($maxWidth, 2) : null,
                'height' => $totalHeight > 0 ? round($totalHeight, 2) : null,
            ],
            'has_complete_data' => $hasCompleteData,
            'missing_items' => $missingItems,
        ];
    }
}
