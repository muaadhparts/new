<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\Catalog;

/**
 * CatalogService - Centralized catalog management service
 *
 * Single source of truth for all catalog-related operations.
 *
 * Domain: Catalog
 * Responsibility: Manage catalogs, queries, and business logic
 *
 * ARCHITECTURE:
 * - Service Layer Pattern
 * - Single Responsibility Principle
 * - Dependency Injection ready
 */
class CatalogService
{
    /**
     * Find catalog by slug
     *
     * @param string $slug Catalog slug
     * @param bool $activeOnly Only return active catalogs
     * @return Catalog|null
     */
    public function findBySlug(string $slug, bool $activeOnly = true): ?Catalog
    {
        $query = Catalog::where('slug', $slug);

        if ($activeOnly) {
            $query->where('status', 1);
        }

        return $query->first();
    }

    /**
     * Find catalog by code and brand
     *
     * @param string $code Catalog code
     * @param int $brandId Brand ID
     * @param bool $activeOnly Only return active catalogs
     * @return Catalog|null
     */
    public function findByCodeAndBrand(string $code, int $brandId, bool $activeOnly = true): ?Catalog
    {
        $query = Catalog::where('code', $code)
            ->where('brand_id', $brandId);

        if ($activeOnly) {
            $query->where('status', 1);
        }

        return $query->first();
    }

    /**
     * Get all catalogs for a brand
     *
     * @param int $brandId Brand ID
     * @param bool $activeOnly Only return active catalogs
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCatalogsForBrand(int $brandId, bool $activeOnly = true)
    {
        $query = Catalog::where('brand_id', $brandId);

        if ($activeOnly) {
            $query->where('status', 1);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get all active catalogs
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllActive()
    {
        return Catalog::where('status', 1)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get catalogs with pagination
     *
     * @param int $perPage Items per page
     * @param bool $activeOnly Only return active catalogs
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 20, bool $activeOnly = true)
    {
        $query = Catalog::query();

        if ($activeOnly) {
            $query->where('status', 1);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Search catalogs by name
     *
     * @param string $search Search term
     * @param bool $activeOnly Only return active catalogs
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search(string $search, bool $activeOnly = true)
    {
        $query = Catalog::where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('name_ar', 'like', "%{$search}%");
        });

        if ($activeOnly) {
            $query->where('status', 1);
        }

        return $query->orderBy('name')->get();
    }
}
