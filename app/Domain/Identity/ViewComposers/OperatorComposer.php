<?php

namespace App\Domain\Identity\ViewComposers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Operator Composer
 *
 * Provides authenticated operator (admin) data to views.
 */
class OperatorComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $operator = Auth::guard('admin')->user();

        if (!$operator) {
            $view->with([
                'isOperator' => false,
                'operatorPermissions' => [],
                'currentOperator' => null,
            ]);
            return;
        }

        $permissions = [];
        if ($operator->role) {
            $permissions = json_decode($operator->role->permissions ?? '[]', true);
        }

        $view->with([
            'isOperator' => true,
            'operatorPermissions' => $permissions,
            'currentOperator' => [
                'id' => $operator->id,
                'name' => $operator->name,
                'email' => $operator->email,
                'role' => $operator->role?->name ?? 'Unknown',
                'photo' => $operator->photo ?? null,
            ],
        ]);
    }

    /**
     * Check if operator has permission.
     */
    public function hasPermission(array $permissions, string $permission): bool
    {
        if (in_array('*', $permissions)) {
            return true;
        }

        // Check for exact match
        if (in_array($permission, $permissions)) {
            return true;
        }

        // Check for wildcard (e.g., "catalog.*" matches "catalog.view")
        $parts = explode('.', $permission);
        if (count($parts) > 1) {
            $wildcard = $parts[0] . '.*';
            if (in_array($wildcard, $permissions)) {
                return true;
            }
        }

        return false;
    }
}
