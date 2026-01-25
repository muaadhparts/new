<?php

namespace App\Domain\Shipping\Contracts;

use App\Domain\Shipping\Models\City;
use App\Domain\Merchant\Models\MerchantBranch;

/**
 * ShippingCalculatorInterface - Contract for shipping calculations
 *
 * All shipping cost calculations MUST go through this interface.
 */
interface ShippingCalculatorInterface
{
    /**
     * Calculate volumetric weight
     * Formula: (L × W × H) / 5000
     */
    public static function calculateVolumetricWeight(?float $length, ?float $width, ?float $height): ?float;

    /**
     * Calculate chargeable weight (max of actual vs volumetric)
     */
    public static function calculateChargeableWeight(float $actualWeight, ?float $volumetricWeight): float;

    /**
     * Get branch city for shipping origin
     */
    public function getBranchCity(MerchantBranch $branch): ?City;

    /**
     * Prepare shipping request data
     */
    public function prepareShippingRequest(
        int $branchId,
        int $destinationCityId,
        float $weight,
        ?float $length = null,
        ?float $width = null,
        ?float $height = null
    ): array;

    /**
     * Validate merchant shipping data
     */
    public function validateMerchantShippingData(int $merchantId): array;
}
