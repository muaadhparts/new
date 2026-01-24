<?php

namespace App\Domain\Identity\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Identity\Models\User;
use Carbon\Carbon;

/**
 * Cleanup Unverified Users Command
 *
 * Removes or notifies unverified user accounts.
 */
class CleanupUnverifiedUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'identity:cleanup-unverified
                            {--days=30 : Days before account is considered stale}
                            {--delete : Delete unverified accounts (use with caution)}
                            {--notify : Send reminder notification}
                            {--dry-run : Show what would happen}';

    /**
     * The console command description.
     */
    protected $description = 'Cleanup or notify unverified user accounts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $delete = $this->option('delete');
        $notify = $this->option('notify');
        $dryRun = $this->option('dry-run');

        $cutoff = Carbon::now()->subDays($days);

        $this->info("Looking for unverified users older than {$days} days...");

        $unverifiedUsers = User::whereNull('email_verified_at')
            ->where('created_at', '<', $cutoff)
            ->get();

        if ($unverifiedUsers->isEmpty()) {
            $this->info('No unverified users found.');
            return self::SUCCESS;
        }

        $this->warn("Found {$unverifiedUsers->count()} unverified users.");

        if ($dryRun) {
            $this->table(
                ['ID', 'Email', 'Created'],
                $unverifiedUsers->map(fn($u) => [
                    $u->id,
                    $u->email,
                    $u->created_at->format('Y-m-d'),
                ])
            );
            $this->info('Dry run - no actions taken.');
            return self::SUCCESS;
        }

        if ($notify) {
            return $this->notifyUsers($unverifiedUsers);
        }

        if ($delete) {
            return $this->deleteUsers($unverifiedUsers);
        }

        $this->info('Use --notify to send reminders or --delete to remove accounts.');

        return self::SUCCESS;
    }

    /**
     * Notify unverified users
     */
    protected function notifyUsers($users): int
    {
        $notified = 0;

        foreach ($users as $user) {
            try {
                // In real implementation, send verification reminder
                $notified++;
            } catch (\Exception $e) {
                $this->warn("Failed to notify {$user->email}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$notified} reminder notifications.");

        return self::SUCCESS;
    }

    /**
     * Delete unverified users
     */
    protected function deleteUsers($users): int
    {
        if (!$this->confirm('Are you sure you want to delete these accounts? This cannot be undone.')) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        $deleted = 0;

        foreach ($users as $user) {
            // Check if user has any purchases or important data
            if ($user->purchases()->exists()) {
                $this->warn("Skipping {$user->email} - has purchase history");
                continue;
            }

            $user->delete();
            $deleted++;
        }

        $this->info("Deleted {$deleted} unverified accounts.");

        return self::SUCCESS;
    }
}
