<?php

namespace App\Domain\Catalog\Policies;

use App\Domain\Identity\Models\User;
use App\Domain\Catalog\Models\Category;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Category Policy
 *
 * Determines authorization for category actions.
 */
class CategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any categories.
     */
    public function viewAny(?User $user): bool
    {
        return true; // Public access
    }

    /**
     * Determine if user can view the category.
     */
    public function view(?User $user, Category $category): bool
    {
        // Active categories are public
        if ($category->status === 1) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can create categories.
     */
    public function create(User $user): bool
    {
        // Only operators can create categories
        return false;
    }

    /**
     * Determine if user can update the category.
     */
    public function update(User $user, Category $category): bool
    {
        return false;
    }

    /**
     * Determine if user can delete the category.
     */
    public function delete(User $user, Category $category): bool
    {
        return false;
    }
}
