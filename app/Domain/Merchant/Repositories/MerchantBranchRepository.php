<?php

namespace App\Domain\Merchant\Repositories;

use App\Domain\Platform\Repositories\BaseRepository;
use App\Domain\Merchant\Models\MerchantBranch;
use Illuminate\Database\Eloquent\Collection;

/**
 * Merchant Branch Repository
 *
 * Repository for merchant branch data access.
 */
class MerchantBranchRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function model(): string
    {
        return MerchantBranch::class;
    }

    /**
     * Get branches by merchant.
     */
    public function getByMerchant(int $merchantId): Collection
    {
        return $this->query()
            ->where('merchant_id', $merchantId)
            ->orderBy('is_main', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get main branch for merchant.
     */
    public function getMainBranch(int $merchantId): ?MerchantBranch
    {
        return $this->query()
            ->where('merchant_id', $merchantId)
            ->where('is_main', 1)
            ->first();
    }

    /**
     * Get active branches by merchant.
     */
    public function getActiveByMerchant(int $merchantId): Collection
    {
        return $this->query()
            ->where('merchant_id', $merchantId)
            ->where('status', 1)
            ->get();
    }

    /**
     * Get branches by city.
     */
    public function getByCity(int $cityId): Collection
    {
        return $this->query()
            ->where('city_id', $cityId)
            ->where('status', 1)
            ->get();
    }

    /**
     * Get nearby branches.
     */
    public function getNearby(float $lat, float $lng, int $radiusKm = 10): Collection
    {
        return $this->query()
            ->selectRaw("*,
                (6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance",
                [$lat, $lng, $lat]
            )
            ->having('distance', '<', $radiusKm)
            ->orderBy('distance')
            ->get();
    }

    /**
     * Set branch as main.
     */
    public function setAsMain(int $branchId, int $merchantId): bool
    {
        // Remove main from all branches
        $this->query()
            ->where('merchant_id', $merchantId)
            ->update(['is_main' => 0]);

        // Set new main branch
        return $this->update($branchId, ['is_main' => 1]);
    }
}
