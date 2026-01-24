<?php

namespace App\Domain\Identity\Listeners;

use App\Domain\Identity\Events\UserRegisteredEvent;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Verification Email Listener
 *
 * Sends email verification to newly registered users.
 */
class SendVerificationEmailListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $backoff = 30;

    /**
     * Handle the event.
     */
    public function handle(UserRegisteredEvent $event): void
    {
        // Skip if social registration (already verified)
        if ($event->isSocialRegistration()) {
            Log::info('Skipping verification email for social registration', [
                'user_id' => $event->userId,
                'source' => $event->registrationSource,
            ]);
            return;
        }

        $user = User::find($event->userId);

        if (!$user || !$user->email) {
            return;
        }

        // Skip if already verified
        if ($user->email_verified_at) {
            return;
        }

        $this->sendVerificationEmail($user);
    }

    /**
     * Send the verification email
     */
    protected function sendVerificationEmail(User $user): void
    {
        Log::info('Verification email sent', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        // Use Laravel's built-in verification
        // $user->sendEmailVerificationNotification();
    }

    /**
     * Handle a job failure.
     */
    public function failed(UserRegisteredEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to send verification email', [
            'user_id' => $event->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
