<?php

namespace App\Domain\Identity\Scopes;

use Illuminate\Database\Eloquent\Builder;

/**
 * Operator Scope
 *
 * Local scopes for operator (admin) queries.
 */
trait OperatorScope
{
    /**
     * Scope to get active operators.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    /**
     * Scope to get inactive operators.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 0);
    }

    /**
     * Scope to filter by role.
     */
    public function scopeWithRole(Builder $query, int $roleId): Builder
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Scope to get super admins.
     */
    public function scopeSuperAdmins(Builder $query): Builder
    {
        return $query->whereHas('role', function ($q) {
            $q->where('name', 'Super Admin');
        });
    }

    /**
     * Scope to search by name or email.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");
        });
    }

    /**
     * Scope to filter by email.
     */
    public function scopeByEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', $email);
    }

    /**
     * Scope to get operators with permission.
     */
    public function scopeWithPermission(Builder $query, string $permission): Builder
    {
        return $query->whereHas('role', function ($q) use ($permission) {
            $q->whereJsonContains('permissions', $permission)
                ->orWhereJsonContains('permissions', '*');
        });
    }

    /**
     * Scope to get recently active operators.
     */
    public function scopeRecentlyActive(Builder $query, int $days = 7): Builder
    {
        return $query->where('last_login_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to order by newest.
     */
    public function scopeNewest(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }
}
