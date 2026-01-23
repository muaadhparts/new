<?php

namespace App\Helpers;

use App\Models\MonetaryUnit;
use DB;
use Session;

class PriceHelper
{

    public static function showPrice($price)
    {
        $ps = platformSettings();
        if (is_numeric($price) && floor($price) != $price) {
            return number_format($price, 2, $ps->get('decimal_separator', '.'), $ps->get('thousand_separator', ','));
        } else {
            return number_format($price, 0, $ps->get('decimal_separator', '.'), $ps->get('thousand_separator', ','));
        }
    }

    public static function apishowPrice($price)
    {
        if (is_numeric($price) && floor($price) != $price) {
            return round($price, 2);
        } else {
            return round($price, 0);
        }
    }

    public static function showCurrencyPrice($price)
    {
        // Use centralized MonetaryUnitService
        return monetaryUnit()->format((float) $price);
    }

    public static function showAdminCurrencyPrice($price)
    {
        // Use centralized MonetaryUnitService with base/default currency
        return monetaryUnit()->formatBase((float) $price);
    }

    public static function showOrderCurrencyPrice($price, $currency)
    {
        // Use centralized MonetaryUnitService with custom sign
        return monetaryUnit()->formatWith((float) $price, $currency);
    }

    public static function ImageCreateName($image)
    {
        $name = time() . preg_replace('/[^A-Za-z0-9\-]/', '', $image->getClientOriginalName()) . '.' . $image->getClientOriginalExtension();
        return $name;
    }

    // Old checkout methods removed (getPurchaseTotal, getPurchaseTotalAmount)
    // Now using branch-based checkout (CheckoutMerchantController)

    /**
     * Return JSON response with proper Arabic text encoding
     * @param mixed $data The data to return (string or array)
     * @param int $status HTTP status code (default 200)
     * @return \Illuminate\Http\JsonResponse
     */
    public static function jsonResponse($data, $status = 200)
    {
        return response()->json($data, $status, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Calculate shipping cost based on size and weight
     * @param array $catalogItems Array of catalogItems with qty and weight
     * @param string $shippingMethod Shipping method (standard, express, etc)
     * @return float Calculated shipping cost
     */
    public static function calculateShippingByWeight($catalogItems, $shippingMethod = 'standard')
    {
        $totalWeight = 0;
        $totalVolume = 0;

        foreach ($catalogItems as $catalogItem) {
            $qty = $catalogItem['qty'] ?? 1;
            $weight = (float)($catalogItem['item']['weight'] ?? 1);
            $size = $catalogItem['item']['size'] ?? null;

            // Calculate total weight
            $totalWeight += $qty * $weight;

            // Calculate total volume if size is available
            if ($size && is_array($size) && count($size) >= 3) {
                $length = (float)($size[0] ?? 10);
                $width = (float)($size[1] ?? 10);
                $height = (float)($size[2] ?? 10);
                $volume = ($length * $width * $height) / 1000000; // Convert to cubic meters
                $totalVolume += $qty * $volume;
            }
        }

        // Base shipping calculation
        $baseRate = 5.00; // Base shipping rate
        $weightRate = 0.50; // Per kg
        $volumeRate = 10.00; // Per cubic meter

        // Method multipliers
        $methodMultipliers = [
            'standard' => 1.0,
            'express' => 1.5,
            'overnight' => 2.0,
            'tryoto' => 1.2
        ];

        $multiplier = $methodMultipliers[$shippingMethod] ?? 1.0;

        $shippingCost = ($baseRate + ($totalWeight * $weightRate) + ($totalVolume * $volumeRate)) * $multiplier;

        return round($shippingCost, 2);
    }

    /**
     * Calculate shipping dimensions for catalogItems
     * @param array $catalogItems Array of catalogItems
     * @return array Combined dimensions and weight
     */
    public static function calculateShippingDimensions($catalogItems)
    {
        $totalWeight = 0;
        $maxLength = 0;
        $maxWidth = 0;
        $totalHeight = 0;

        foreach ($catalogItems as $catalogItem) {
            $qty = $catalogItem['qty'] ?? 1;
            $weight = (float)($catalogItem['item']['weight'] ?? 1);

            // Try to get dimensions from multiple possible sources
            $size = $catalogItem['item']['size'] ?? null;

            // If size is stored as JSON string, decode it
            if (is_string($size)) {
                $size = json_decode($size, true);
            }

            $totalWeight += $qty * $weight;

            // Handle different dimension formats
            $length = 0;
            $width = 0;
            $height = 0;

            if ($size && is_array($size)) {
                if (count($size) >= 3) {
                    // Array format [length, width, height]
                    $length = (float)($size[0] ?? 0);
                    $width = (float)($size[1] ?? 0);
                    $height = (float)($size[2] ?? 0);
                } elseif (isset($size['length']) || isset($size['width']) || isset($size['height'])) {
                    // Associative array format
                    $length = (float)($size['length'] ?? 0);
                    $width = (float)($size['width'] ?? 0);
                    $height = (float)($size['height'] ?? 0);
                }
            }

            // If no dimensions found, use default values based on weight
            if ($length == 0 && $width == 0 && $height == 0) {
                // Default dimensions based on weight (rough estimation)
                $estimatedVolume = max(0.001, $weight * 0.0005); // 0.5L per kg minimum
                $cubicRoot = pow($estimatedVolume, 1/3);
                $length = $width = $height = max(10, $cubicRoot * 100); // minimum 10cm
            }

            // Use maximum length and width, sum heights for stacking
            $maxLength = max($maxLength, $length);
            $maxWidth = max($maxWidth, $width);
            $totalHeight += $qty * $height;
        }

        // Ensure minimum dimensions for shipping
        $maxLength = max(10, $maxLength);  // minimum 10cm
        $maxWidth = max(10, $maxWidth);    // minimum 10cm
        $totalHeight = max(5, $totalHeight); // minimum 5cm

        return [
            'weight' => max(0.1, $totalWeight), // minimum 100g
            'length' => $maxLength,
            'width' => $maxWidth,
            'height' => $totalHeight,
            'volume' => ($maxLength * $maxWidth * $totalHeight) / 1000000 // cubic meters
        ];
    }

}
