<?php

namespace App\Services;

use App\Models\City;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * خدمة موحدة لحساب الشحن
 *
 * المبدأ الأساسي:
 * - لا قيم ثابتة (hardcoded)
 * - لا fallback لأي بيانات
 * - كل تاجر يُحسب شحنه مستقلاً
 * - الوزن القابل للشحن = الأعلى بين الفعلي والحجمي
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
     * جلب مدينة التاجر
     * من قاعدة البيانات فقط - بدون fallback
     */
    public static function getVendorCity(int $vendorId): ?array
    {
        $vendor = User::find($vendorId);

        if (!$vendor || !$vendor->city_id) {
            Log::warning('ShippingCalculator: Vendor city missing', [
                'vendor_id' => $vendorId,
                'has_city_id' => !empty($vendor->city_id),
            ]);

            return null;
        }

        $city = City::find($vendor->city_id);

        if (!$city) {
            Log::warning('ShippingCalculator: City not found', [
                'vendor_id' => $vendorId,
                'city_id' => $vendor->city_id,
            ]);

            return null;
        }

        return [
            'city_id' => $city->id,
            'city_name' => $city->city_name ?? $city->name ?? null,
            'city_name_ar' => $city->city_name_ar ?? $city->name_ar ?? $city->city_name ?? null,
            'country_id' => $city->country_id ?? null,
        ];
    }

    /**
     * جلب مدينة العميل من العنوان
     * من الخريطة/العنوان فقط - بدون fallback
     */
    public static function getCustomerCity(?int $cityId, ?string $addressData = null): ?array
    {
        if ($cityId) {
            $city = City::find($cityId);
            if ($city) {
                return [
                    'city_id' => $city->id,
                    'city_name' => $city->city_name ?? $city->name ?? null,
                    'city_name_ar' => $city->city_name_ar ?? $city->name_ar ?? $city->city_name ?? null,
                    'source' => 'city_id',
                ];
            }
        }

        if ($addressData) {
            $decoded = is_string($addressData) ? json_decode($addressData, true) : $addressData;

            if (is_array($decoded)) {
                $cityName = $decoded['city'] ?? $decoded['locality'] ?? $decoded['administrative_area_level_2'] ?? null;

                if ($cityName) {
                    $city = City::where('name', $cityName)
                        ->orWhere('name_ar', $cityName)
                        ->first();

                    if ($city) {
                        return [
                            'city_id' => $city->id,
                            'city_name' => $city->name,
                            'city_name_ar' => $city->name_ar ?? $city->name,
                            'source' => 'address_data',
                        ];
                    }

                    return [
                        'city_id' => null,
                        'city_name' => $cityName,
                        'city_name_ar' => $cityName,
                        'source' => 'address_raw',
                        'warning' => 'city_not_in_system',
                    ];
                }
            }
        }

        return null;
    }

    /**
     * تجهيز بيانات الشحن لإرسالها لـ Tryoto
     * بدون أي قيم ثابتة
     */
    public static function prepareShippingRequest(array $vendorShippingData, array $customerData): array
    {
        $errors = [];

        $vendorCity = $vendorShippingData['vendor_city'] ?? null;
        if (!$vendorCity) {
            $errors[] = 'vendor_city_missing';
        }

        $customerCity = $customerData['city_name'] ?? null;
        if (!$customerCity) {
            $errors[] = 'customer_city_missing';
        }

        $chargeableWeight = $vendorShippingData['chargeable_weight'] ?? null;
        if ($chargeableWeight === null) {
            $errors[] = 'chargeable_weight_missing';
        }

        $dimensions = $vendorShippingData['dimensions'] ?? [];
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
                'originCity' => $vendorCity,
                'destinationCity' => $customerCity,
                'weight' => $chargeableWeight,
                'xlength' => $length,
                'xwidth' => $width,
                'xheight' => $height,
                'codAmount' => $customerData['cod_amount'] ?? 0,
            ],
            'meta' => [
                'vendor_id' => $vendorShippingData['vendor_id'] ?? null,
                'actual_weight' => $vendorShippingData['actual_weight'] ?? null,
                'volumetric_weight' => $vendorShippingData['volumetric_weight'] ?? null,
                'items_count' => $vendorShippingData['items_count'] ?? 0,
            ],
        ];
    }

    /**
     * التحقق من اكتمال بيانات الشحن للتاجر
     */
    public static function validateVendorShippingData(array $vendorShippingData): array
    {
        $errors = [];
        $warnings = [];

        if (empty($vendorShippingData['vendor_city'])) {
            $errors[] = [
                'field' => 'vendor_city',
                'message' => __('Vendor city is not set'),
            ];
        }

        if ($vendorShippingData['actual_weight'] === null) {
            $errors[] = [
                'field' => 'weight',
                'message' => __('Product weights are missing'),
            ];
        }

        $dims = $vendorShippingData['dimensions'] ?? [];
        if ($dims['length'] === null || $dims['width'] === null || $dims['height'] === null) {
            $errors[] = [
                'field' => 'dimensions',
                'message' => __('Product dimensions are missing'),
            ];
        }

        if ($vendorShippingData['chargeable_weight'] === null) {
            $errors[] = [
                'field' => 'chargeable_weight',
                'message' => __('Cannot calculate shipping weight'),
            ];
        }

        if (!empty($vendorShippingData['missing_data'])) {
            foreach ($vendorShippingData['missing_data'] as $missing) {
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
