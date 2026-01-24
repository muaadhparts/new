<?php

namespace App\Domain\Identity\Subscribers;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * User Event Subscriber
 *
 * Handles all user-related events in one place.
 */
class UserEventSubscriber
{
    /**
     * Handle user registered events.
     */
    public function handleUserRegistered($event): void
    {
        Log::channel('users')->info('User registered', [
            'user_id' => $event->user->id ?? null,
            'email' => $event->user->email ?? null,
            'role' => $event->user->role ?? null,
        ]);
    }

    /**
     * Handle user verified events.
     */
    public function handleUserVerified($event): void
    {
        Log::channel('users')->info('User verified', [
            'user_id' => $event->user->id ?? null,
        ]);
    }

    /**
     * Handle user logged in events.
     */
    public function handleUserLoggedIn($event): void
    {
        Log::channel('users')->debug('User logged in', [
            'user_id' => $event->user->id ?? null,
            'ip' => $event->ip ?? null,
        ]);
    }

    /**
     * Handle user password changed events.
     */
    public function handlePasswordChanged($event): void
    {
        Log::channel('users')->info('User password changed', [
            'user_id' => $event->user->id ?? null,
        ]);
    }

    /**
     * Handle user profile updated events.
     */
    public function handleProfileUpdated($event): void
    {
        Log::channel('users')->info('User profile updated', [
            'user_id' => $event->user->id ?? null,
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            'App\Domain\Identity\Events\UserRegistered' => 'handleUserRegistered',
            'App\Domain\Identity\Events\UserVerified' => 'handleUserVerified',
            'App\Domain\Identity\Events\UserLoggedIn' => 'handleUserLoggedIn',
            'App\Domain\Identity\Events\PasswordChanged' => 'handlePasswordChanged',
            'App\Domain\Identity\Events\ProfileUpdated' => 'handleProfileUpdated',
        ];
    }
}
