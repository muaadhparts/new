<?php

namespace App\Providers;

use App\Domain\Merchant\Services\ApiCredentialService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

/**
 * DigitalOceanServiceProvider
 *
 * يحمل credentials الـ DigitalOcean Spaces من قاعدة البيانات المشفرة
 * ويعين الـ config بشكل ديناميكي
 */
class DigitalOceanServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // لا نشغل هذا في console commands للتثبيت
        if ($this->app->runningInConsole() && !$this->isFilesystemNeeded()) {
            return;
        }

        try {
            // استخدام Cache لتجنب query في كل request
            $credentials = Cache::remember('do_credentials', 3600, function () {
                $credentialService = app(ApiCredentialService::class);
                return [
                    'key' => $credentialService->getDigitalOceanKey(),
                    'secret' => $credentialService->getDigitalOceanSecret(),
                ];
            });

            // تحديث الـ config إذا وجدت credentials
            if (!empty($credentials['key'])) {
                config(['filesystems.disks.do.key' => $credentials['key']]);
                config(['filesystems.disks.s3.key' => $credentials['key']]);
            }

            if (!empty($credentials['secret'])) {
                config(['filesystems.disks.do.secret' => $credentials['secret']]);
                config(['filesystems.disks.s3.secret' => $credentials['secret']]);
            }

        } catch (\Exception $e) {
            // في حالة الخطأ (مثل عدم وجود الجدول) نستخدم القيم الافتراضية من env
            // لا نوقف التطبيق
        }
    }

    /**
     * تحقق إذا كان الأمر يحتاج filesystem
     */
    protected function isFilesystemNeeded(): bool
    {
        $command = $_SERVER['argv'][1] ?? '';

        // أوامر لا تحتاج filesystem
        $excludedCommands = [
            'migrate',
            'migrate:fresh',
            'migrate:install',
            'db:seed',
            'key:generate',
            'config:cache',
            'config:clear',
            'cache:clear',
            'route:cache',
            'route:clear',
            'view:cache',
            'view:clear',
            'optimize',
            'optimize:clear',
        ];

        return !in_array($command, $excludedCommands);
    }
}
