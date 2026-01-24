<?php

namespace App\Domain\Platform\Policies;

use App\Domain\Identity\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Base Policy
 *
 * Abstract base class for all domain policies.
 */
abstract class BasePolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Operators with super admin can do anything
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Check if user owns the resource.
     */
    protected function owns(User $user, $model): bool
    {
        if (property_exists($model, 'user_id')) {
            return $model->user_id === $user->id;
        }

        return false;
    }

    /**
     * Check if user is the merchant owner.
     */
    protected function isMerchantOwner(User $user, $model): bool
    {
        if (property_exists($model, 'merchant_id')) {
            return $model->merchant_id === $user->id;
        }

        return false;
    }
}
