<?php

namespace App\Domain\Identity\Exceptions;

use App\Domain\Platform\Exceptions\DomainException;

/**
 * Unauthorized Exception
 *
 * Thrown when user lacks permission for an action.
 */
class UnauthorizedException extends DomainException
{
    protected string $errorCode = 'UNAUTHORIZED';

    public function __construct(
        string $message = 'You are not authorized to perform this action',
        ?string $permission = null,
        ?string $resource = null,
        array $context = []
    ) {
        if ($permission) {
            $context['required_permission'] = $permission;
        }
        if ($resource) {
            $context['resource'] = $resource;
        }

        parent::__construct($message, 403, null, $context);
    }

    /**
     * Create for missing permission
     */
    public static function missingPermission(string $permission): self
    {
        return new self(
            "You do not have the '{$permission}' permission",
            $permission
        );
    }

    /**
     * Create for resource access denied
     */
    public static function forResource(string $resource, int $resourceId): self
    {
        return new self(
            "You cannot access this {$resource}",
            null,
            $resource,
            ['resource_id' => $resourceId]
        );
    }

    /**
     * Create for wrong role
     */
    public static function wrongRole(string $requiredRole, string $currentRole): self
    {
        return new self(
            "This action requires '{$requiredRole}' role",
            null,
            null,
            ['required_role' => $requiredRole, 'current_role' => $currentRole]
        );
    }

    /**
     * Create for merchant-only action
     */
    public static function merchantOnly(): self
    {
        return new self(
            'This action is only available to merchants',
            null,
            null,
            ['required_role' => 'merchant']
        );
    }

    /**
     * Create for operator-only action
     */
    public static function operatorOnly(): self
    {
        return new self(
            'This action is only available to operators',
            null,
            null,
            ['required_role' => 'operator']
        );
    }

    public function getDomain(): string
    {
        return 'Identity';
    }

    public function getUserMessage(): string
    {
        return __('messages.unauthorized');
    }

    public function shouldReport(): bool
    {
        return false;
    }
}
