<?php

namespace App\Domain\Identity\Subscribers;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Auth Event Subscriber
 *
 * Handles all authentication-related events in one place.
 */
class AuthEventSubscriber
{
    /**
     * Handle login events.
     */
    public function handleLogin($event): void
    {
        Log::channel('auth')->info('User logged in', [
            'user_id' => $event->user->id ?? null,
            'guard' => $event->guard ?? 'web',
        ]);
    }

    /**
     * Handle logout events.
     */
    public function handleLogout($event): void
    {
        Log::channel('auth')->info('User logged out', [
            'user_id' => $event->user->id ?? null,
        ]);
    }

    /**
     * Handle failed login events.
     */
    public function handleFailed($event): void
    {
        Log::channel('auth')->warning('Login failed', [
            'email' => $event->credentials['email'] ?? null,
            'guard' => $event->guard ?? 'web',
        ]);
    }

    /**
     * Handle lockout events.
     */
    public function handleLockout($event): void
    {
        Log::channel('auth')->warning('User locked out', [
            'ip' => $event->request->ip() ?? null,
            'email' => $event->request->input('email') ?? null,
        ]);
    }

    /**
     * Handle password reset events.
     */
    public function handlePasswordReset($event): void
    {
        Log::channel('auth')->info('Password reset', [
            'user_id' => $event->user->id ?? null,
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            'Illuminate\Auth\Events\Login' => 'handleLogin',
            'Illuminate\Auth\Events\Logout' => 'handleLogout',
            'Illuminate\Auth\Events\Failed' => 'handleFailed',
            'Illuminate\Auth\Events\Lockout' => 'handleLockout',
            'Illuminate\Auth\Events\PasswordReset' => 'handlePasswordReset',
        ];
    }
}
