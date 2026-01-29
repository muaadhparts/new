<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\Brand;

/**
 * BrandService - Centralized brand management service
 *
 * Single source of truth for all brand-related operations.
 *
 * Domain: Catalog
 * Responsibility: Manage brands, queries, and business logic
 *
 * ARCHITECTURE:
 * - Service Layer Pattern
 * - Single Responsibility Principle
 * - Dependency Injection ready
 */
class BrandService
{
    /**
     * Find brand by slug
     *
     * @param string $slug Brand slug
     * @param bool $activeOnly Only return active brands
     * @return Brand|null
     */
    public function findBySlug(string $slug, bool $activeOnly = true): ?Brand
    {
        $query = Brand::where('slug', $slug);

        if ($activeOnly) {
            $query->where('status', 1);
        }

        return $query->first();
    }

    /**
     * Find brand by ID
     *
     * @param int $id Brand ID
     * @param bool $activeOnly Only return active brands
     * @return Brand|null
     */
    public function findById(int $id, bool $activeOnly = true): ?Brand
    {
        $query = Brand::where('id', $id);

        if ($activeOnly) {
            $query->where('status', 1);
        }

        return $query->first();
    }

    /**
     * Get all active brands
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllActive()
    {
        return Brand::where('status', 1)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get brands with pagination
     *
     * @param int $perPage Items per page
     * @param bool $activeOnly Only return active brands
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 20, bool $activeOnly = true)
    {
        $query = Brand::query();

        if ($activeOnly) {
            $query->where('status', 1);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Search brands by name
     *
     * @param string $search Search term
     * @param bool $activeOnly Only return active brands
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search(string $search, bool $activeOnly = true)
    {
        $query = Brand::where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('name_ar', 'like', "%{$search}%");
        });

        if ($activeOnly) {
            $query->where('status', 1);
        }

        return $query->orderBy('name')->get();
    }
}
