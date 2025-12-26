<?php

namespace App\Providers;

use App\Services\ApiCredentialService;
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
            $credentialService = app(ApiCredentialService::class);

            $accessKey = $credentialService->getDigitalOceanKey();
            $secretKey = $credentialService->getDigitalOceanSecret();

            // تحديث الـ config إذا وجدت credentials في قاعدة البيانات
            if ($accessKey) {
                config(['filesystems.disks.do.key' => $accessKey]);
                config(['filesystems.disks.s3.key' => $accessKey]);
            }

            if ($secretKey) {
                config(['filesystems.disks.do.secret' => $secretKey]);
                config(['filesystems.disks.s3.secret' => $secretKey]);
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
