<?php

namespace App\Domain\Identity\Listeners;

use App\Domain\Identity\Events\UserRegisteredEvent;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Welcome Email Listener
 *
 * Sends welcome email to newly registered users.
 */
class SendWelcomeEmailListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $backoff = 60;

    /**
     * Handle the event.
     */
    public function handle(UserRegisteredEvent $event): void
    {
        $user = User::find($event->userId);

        if (!$user || !$user->email) {
            Log::warning('SendWelcomeEmail: User or email not found', [
                'user_id' => $event->userId,
            ]);
            return;
        }

        $this->sendWelcomeEmail($user, $event);
    }

    /**
     * Send welcome email based on role
     */
    protected function sendWelcomeEmail(User $user, UserRegisteredEvent $event): void
    {
        Log::info('Welcome email sent', [
            'user_id' => $event->userId,
            'email' => $event->email,
            'role' => $event->role,
            'registration_source' => $event->registrationSource,
        ]);

        // Different welcome emails based on role
        if ($event->isMerchant()) {
            // Mail::to($user->email)->send(new MerchantWelcomeMail($user));
            Log::info('Would send merchant welcome email');
        } else {
            // Mail::to($user->email)->send(new CustomerWelcomeMail($user));
            Log::info('Would send customer welcome email');
        }

        // If referred, could also notify referrer
        if ($event->wasReferred()) {
            $this->notifyReferrer($event->referrerId, $user);
        }
    }

    /**
     * Notify referrer of successful referral
     */
    protected function notifyReferrer(?int $referrerId, User $newUser): void
    {
        if (!$referrerId) {
            return;
        }

        $referrer = User::find($referrerId);

        if ($referrer) {
            Log::info('Would notify referrer of new signup', [
                'referrer_id' => $referrerId,
                'new_user_id' => $newUser->id,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(UserRegisteredEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to send welcome email', [
            'user_id' => $event->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
