<?php

namespace App\Domain\Commerce\Schedule;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Cleanup Abandoned Carts Task
 *
 * Removes abandoned cart sessions older than configured days.
 */
class CleanupAbandonedCartsTask
{
    /**
     * Days to keep abandoned carts.
     */
    protected int $daysToKeep = 7;

    /**
     * Execute the task.
     */
    public function __invoke(): void
    {
        $cutoffDate = now()->subDays($this->daysToKeep);

        $deleted = DB::table('carts')
            ->where('updated_at', '<', $cutoffDate)
            ->whereNull('user_id')
            ->delete();

        Log::info('Abandoned carts cleanup completed', [
            'deleted' => $deleted,
            'cutoff_date' => $cutoffDate->toDateTimeString(),
        ]);
    }

    /**
     * Get the schedule frequency.
     */
    public static function frequency(): string
    {
        return 'daily';
    }

    /**
     * Get the schedule time.
     */
    public static function at(): string
    {
        return '02:00';
    }
}
