<?php

namespace App\Domain\Identity\Listeners;

use App\Domain\Identity\Events\UserLoginEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Log User Activity Listener
 *
 * Logs user login activity for security and analytics.
 */
class LogUserActivityListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /**
     * Handle the event.
     */
    public function handle(UserLoginEvent $event): void
    {
        $this->logLoginActivity($event);
        $this->updateLastLogin($event);
        $this->checkSuspiciousActivity($event);
    }

    /**
     * Log the login activity
     */
    protected function logLoginActivity(UserLoginEvent $event): void
    {
        // Could use a dedicated activity_logs table
        Log::info('User login recorded', [
            'user_id' => $event->userId,
            'login_method' => $event->loginMethod,
            'ip_address' => $event->ipAddress,
            'user_agent' => $event->userAgent,
            'remember_me' => $event->rememberMe,
            'occurred_at' => $event->occurredAt->format('Y-m-d H:i:s'),
        ]);

        // DB::table('user_activity_logs')->insert([
        //     'user_id' => $event->userId,
        //     'activity_type' => 'login',
        //     'login_method' => $event->loginMethod,
        //     'ip_address' => $event->ipAddress,
        //     'user_agent' => $event->userAgent,
        //     'created_at' => now(),
        // ]);
    }

    /**
     * Update user's last login timestamp
     */
    protected function updateLastLogin(UserLoginEvent $event): void
    {
        DB::table('users')
            ->where('id', $event->userId)
            ->update([
                'last_login_at' => now(),
                'last_login_ip' => $event->ipAddress,
            ]);
    }

    /**
     * Check for suspicious login activity
     */
    protected function checkSuspiciousActivity(UserLoginEvent $event): void
    {
        // Could implement:
        // - New device detection
        // - Unusual location detection
        // - Multiple failed attempts before success
        // - Login from new IP

        // For now, just log
        Log::debug('Login activity check completed', [
            'user_id' => $event->userId,
            'ip' => $event->ipAddress,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(UserLoginEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to log user activity', [
            'user_id' => $event->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
