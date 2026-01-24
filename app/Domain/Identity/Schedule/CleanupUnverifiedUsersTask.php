<?php

namespace App\Domain\Identity\Schedule;

use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Cleanup Unverified Users Task
 *
 * Removes or notifies unverified users after grace period.
 */
class CleanupUnverifiedUsersTask
{
    /**
     * Days before sending reminder.
     */
    protected int $reminderDays = 3;

    /**
     * Days before deletion.
     */
    protected int $deletionDays = 30;

    /**
     * Execute the task.
     */
    public function __invoke(): void
    {
        $reminderDate = now()->subDays($this->reminderDays);
        $deletionDate = now()->subDays($this->deletionDays);

        // Delete old unverified users without orders
        $deleted = User::whereNull('email_verified_at')
            ->where('created_at', '<', $deletionDate)
            ->whereDoesntHave('purchases')
            ->delete();

        // Count users needing reminders (for notification in real implementation)
        $needReminder = User::whereNull('email_verified_at')
            ->whereBetween('created_at', [$deletionDate, $reminderDate])
            ->count();

        Log::info('Unverified users cleanup completed', [
            'deleted' => $deleted,
            'pending_reminder' => $needReminder,
            'deletion_threshold_days' => $this->deletionDays,
        ]);
    }

    /**
     * Get the schedule frequency.
     */
    public static function frequency(): string
    {
        return 'daily';
    }
}
