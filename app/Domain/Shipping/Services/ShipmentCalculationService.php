<?php

namespace App\Domain\Shipping\Services;

use App\Domain\Commerce\Models\Purchase;

/**
 * ShipmentCalculationService
 * 
 * Handles all shipment dimension and weight calculations
 * 
 * Responsibilities:
 * - Calculate total weight from cart items
 * - Calculate total volume from cart items
 * - Estimate cubic dimensions from volume
 * - Provide default dimensions when data is missing
 */
class ShipmentCalculationService
{
    /**
     * Calculate purchase dimensions and weight from cart
     * 
     * @param Purchase $purchase
     * @return array ['weight' => float, 'length' => int, 'width' => int, 'height' => int]
     */
    public function calculatePurchaseDimensions(Purchase $purchase): array
    {
        $cart = $purchase->cart; // Model cast handles decoding
        $items = $cart['items'] ?? $cart ?? [];

        $totalWeight = $this->calculateTotalWeight($items);
        $totalVolume = $this->calculateTotalVolume($items);
        $dimensions = $this->estimateDimensions($totalVolume);

        return [
            'weight' => max(0.5, $totalWeight), // Minimum 0.5 kg
            'length' => $dimensions['length'],
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
        ];
    }

    /**
     * Calculate total weight from cart items
     * 
     * @param array $items Cart items
     * @return float Total weight in kg
     */
    public function calculateTotalWeight(array $items): float
    {
        $totalWeight = 0;

        foreach ($items as $item) {
            $qty = (int)($item['qty'] ?? 1);
            $itemData = $item['item'] ?? $item;
            $weight = (float)($itemData['weight'] ?? 1); // Default 1kg per item
            
            $totalWeight += $weight * $qty;
        }

        return $totalWeight;
    }

    /**
     * Calculate total volume from cart items
     * 
     * @param array $items Cart items
     * @return float Total volume in cm³
     */
    public function calculateTotalVolume(array $items): float
    {
        $totalVolume = 0;

        foreach ($items as $item) {
            $qty = (int)($item['qty'] ?? 1);
            $itemData = $item['item'] ?? $item;

            // Try to get actual dimensions if available
            $length = (float)($itemData['length'] ?? 0);
            $width = (float)($itemData['width'] ?? 0);
            $height = (float)($itemData['height'] ?? 0);

            if ($length > 0 && $width > 0 && $height > 0) {
                // Use actual dimensions
                $itemVolume = $length * $width * $height;
            } else {
                // Estimate volume per item (default 30x30x30 = 27000 cm³)
                $itemVolume = 27000;
            }

            $totalVolume += $itemVolume * $qty;
        }

        return $totalVolume;
    }

    /**
     * Estimate cubic dimensions from total volume
     * 
     * Assumes a cubic package shape for simplicity
     * 
     * @param float $volume Total volume in cm³
     * @return array ['length' => int, 'width' => int, 'height' => int]
     */
    public function estimateDimensions(float $volume): array
    {
        // Calculate cubic root to get equal dimensions
        $cubicRoot = pow($volume, 1/3);
        
        // Round up to nearest cm, minimum 30cm
        $dimension = max(30, (int)ceil($cubicRoot));

        return [
            'length' => $dimension,
            'width' => $dimension,
            'height' => $dimension,
        ];
    }

    /**
     * Get default dimensions when no cart data available
     * 
     * @return array
     */
    public function getDefaultDimensions(): array
    {
        return [
            'weight' => 1.0, // 1 kg
            'length' => 30,  // 30 cm
            'width' => 30,   // 30 cm
            'height' => 30,  // 30 cm
        ];
    }

    /**
     * Validate dimensions
     * 
     * @param array $dimensions
     * @return bool
     */
    public function validateDimensions(array $dimensions): bool
    {
        $required = ['weight', 'length', 'width', 'height'];

        foreach ($required as $field) {
            if (!isset($dimensions[$field]) || $dimensions[$field] <= 0) {
                return false;
            }
        }

        return true;
    }
}
