<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    // public function register(): void
    // {
    //     // Telescope::night();

    //     $this->hideSensitiveRequestDetails();

    //     $isLocal = $this->app->environment('local');

    //     Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
    //         // في البيئة المحلية، سجل كل شيء
    //         if ($isLocal) {
    //             return true;
    //         }

    //         // في الإنتاج، سجل فقط المهم
    //         return $entry->isReportableException() ||
    //                $entry->isFailedRequest() ||
    //                $entry->isFailedJob() ||
    //                $entry->isScheduledTask() ||
    //                $entry->hasMonitoredTag() ||
    //                $this->isSlowQuery($entry) ||
    //                $this->isSlowRequest($entry);
    //     });

    //     // تسجيل الاستعلامات البطيئة تلقائياً
    //     Telescope::tag(function (IncomingEntry $entry) {
    //         $tags = [];

    //         // وسم الاستعلامات البطيئة
    //         if ($entry->type === 'query' && isset($entry->content['time']) && $entry->content['time'] >= 100) {
    //             $tags[] = 'slow-query';
    //             if ($entry->content['time'] >= 500) {
    //                 $tags[] = 'very-slow-query';
    //             }
    //         }

    //         // وسم الطلبات البطيئة
    //         if ($entry->type === 'request' && isset($entry->content['duration']) && $entry->content['duration'] >= 1000) {
    //             $tags[] = 'slow-request';
    //         }

    //         return $tags;
    //     });
    // }

    public function register(): void
    {
        if (!config('telescope.enabled')) {
            return;
        }

        parent::register(); // ⬅️ هذا السطر هو المفتاح

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            if ($isLocal) {
                return true;
            }

            return $entry->isReportableException() ||
                $entry->isFailedRequest() ||
                $entry->isFailedJob() ||
                $entry->isScheduledTask() ||
                $entry->hasMonitoredTag() ||
                $this->isSlowQuery($entry) ||
                $this->isSlowRequest($entry);
        });

        Telescope::tag(function (IncomingEntry $entry) {
            $tags = [];

            if ($entry->type === 'query' && isset($entry->content['time']) && $entry->content['time'] >= 100) {
                $tags[] = 'slow-query';
                if ($entry->content['time'] >= 500) {
                    $tags[] = 'very-slow-query';
                }
            }

            if ($entry->type === 'request' && isset($entry->content['duration']) && $entry->content['duration'] >= 1000) {
                $tags[] = 'slow-request';
            }

            return $tags;
        });
    }

    /**
     * Check if entry is a slow query (>= 100ms)
     */
    protected function isSlowQuery(IncomingEntry $entry): bool
    {
        return $entry->type === 'query'
            && isset($entry->content['time'])
            && $entry->content['time'] >= 100;
    }

    /**
     * Check if entry is a slow request (>= 1000ms)
     */
    protected function isSlowRequest(IncomingEntry $entry): bool
    {
        return $entry->type === 'request'
            && isset($entry->content['duration'])
            && $entry->content['duration'] >= 1000;
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token', 'password', 'password_confirmation']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
            'authorization',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user) {
            // السماح فقط للمدراء بالوصول إلى Telescope
            return $user->is_admin == 1 || in_array($user->email, [
                'admin@muaadh.com',
                'developer@muaadh.com',
            ]);
        });
    }
}
