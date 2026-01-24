<?php

namespace App\Domain\Identity\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Permissions Cast
 *
 * Handles user/operator permissions array.
 */
class PermissionsCast implements CastsAttributes
{
    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $permissions = is_string($value) ? json_decode($value, true) : $value;

        if (!is_array($permissions)) {
            return [];
        }

        return array_unique(array_filter($permissions));
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === []) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        $permissions = array_unique(array_filter($value));

        return json_encode(array_values($permissions));
    }

    /**
     * Check if has permission.
     */
    public static function hasPermission(array $permissions, string $permission): bool
    {
        // Check for wildcard
        if (in_array('*', $permissions)) {
            return true;
        }

        // Check exact match
        if (in_array($permission, $permissions)) {
            return true;
        }

        // Check wildcard patterns (e.g., 'orders.*')
        foreach ($permissions as $p) {
            if (str_ends_with($p, '.*')) {
                $prefix = substr($p, 0, -2);
                if (str_starts_with($permission, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }
}
