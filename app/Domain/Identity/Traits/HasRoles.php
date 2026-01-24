<?php

namespace App\Domain\Identity\Traits;

use App\Domain\Identity\Enums\UserRole;

/**
 * Has Roles Trait
 *
 * Provides role-based access control functionality.
 */
trait HasRoles
{
    /**
     * Get role column
     */
    public function getRoleColumn(): string
    {
        return $this->roleColumn ?? 'role';
    }

    /**
     * Get user role
     */
    public function getRole(): ?UserRole
    {
        $role = $this->{$this->getRoleColumn()};

        if ($role instanceof UserRole) {
            return $role;
        }

        return UserRole::tryFrom($role);
    }

    /**
     * Check if user has role
     */
    public function hasRole(UserRole|string $role): bool
    {
        if (is_string($role)) {
            $role = UserRole::tryFrom($role);
        }

        return $this->getRole() === $role;
    }

    /**
     * Check if user has any of the roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if is customer
     */
    public function isCustomer(): bool
    {
        return $this->hasRole(UserRole::CUSTOMER);
    }

    /**
     * Check if is merchant
     */
    public function isMerchant(): bool
    {
        return $this->hasRole(UserRole::MERCHANT) || ($this->is_merchant ?? false);
    }

    /**
     * Check if is courier
     */
    public function isCourier(): bool
    {
        return $this->hasRole(UserRole::COURIER);
    }

    /**
     * Check if is operator
     */
    public function isOperator(): bool
    {
        return $this->hasRole(UserRole::OPERATOR);
    }

    /**
     * Assign role
     */
    public function assignRole(UserRole|string $role): bool
    {
        if (is_string($role)) {
            $role = UserRole::from($role);
        }

        return $this->update([$this->getRoleColumn() => $role->value]);
    }

    /**
     * Get dashboard route
     */
    public function getDashboardRoute(): string
    {
        return $this->getRole()?->dashboardRoute() ?? 'home';
    }

    /**
     * Scope by role
     */
    public function scopeRole($query, UserRole|string $role)
    {
        if ($role instanceof UserRole) {
            $role = $role->value;
        }

        return $query->where($this->getRoleColumn(), $role);
    }

    /**
     * Scope merchants
     */
    public function scopeMerchants($query)
    {
        return $query->where('is_merchant', 1);
    }

    /**
     * Scope customers
     */
    public function scopeCustomers($query)
    {
        return $query->where('is_merchant', 0);
    }
}
