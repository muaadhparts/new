<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // يجدد التوكن
        $schedule->command('nissan:refresh-token')->everyFiveMinutes();

        // تحديث كامل: تنزيل + استيراد + تجميع يومياً الساعة 2:00 صباحاً
        $schedule->command('stock:full-refresh')->dailyAt('02:00')->withoutOverlapping();
    }

    protected $commands = [
        \App\Console\Commands\ClearLog::class,
    ];

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
