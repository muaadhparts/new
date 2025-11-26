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

        // تحديث كامل للتويجري (بائع واحد user_id=59): تنزيل + استيراد + تجميع + تحديث يومياً الساعة 2:00 صباحاً
        $schedule->command('stock:manage full-refresh --user_id=59 --margin=1.3 --branch=ATWJRY')
                ->dailyAt('02:00')
                ->withoutOverlapping();

        // ✅ Tryoto: تحديث حالة الشحنات النشطة كل 30 دقيقة
        $schedule->command('shipments:update --limit=50')
                ->everyThirtyMinutes()
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/shipments-cron.log'));

        // ✅ Tryoto: تحديث شامل للشحنات مرتين يومياً (الساعة 8 صباحاً و 6 مساءً)
        $schedule->command('shipments:update --limit=200 --force')
                ->twiceDaily(8, 18)
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/shipments-cron.log'));
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
