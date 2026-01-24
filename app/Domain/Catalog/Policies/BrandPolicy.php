<?php

namespace App\Domain\Catalog\Policies;

use App\Models\User;
use App\Models\Brand;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Brand Policy
 *
 * Determines authorization for brand actions.
 */
class BrandPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any brands.
     */
    public function viewAny(?User $user): bool
    {
        return true; // Public access
    }

    /**
     * Determine if user can view the brand.
     */
    public function view(?User $user, Brand $brand): bool
    {
        // Active brands are public
        if ($brand->status === 1) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can create brands.
     */
    public function create(User $user): bool
    {
        return false; // Operators only
    }

    /**
     * Determine if user can update the brand.
     */
    public function update(User $user, Brand $brand): bool
    {
        return false;
    }

    /**
     * Determine if user can delete the brand.
     */
    public function delete(User $user, Brand $brand): bool
    {
        return false;
    }
}
