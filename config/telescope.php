<?php

use Laravel\Telescope\Http\Middleware\Authorize;
use Laravel\Telescope\Watchers;

return [

    /*
    |--------------------------------------------------------------------------
    | Telescope Master Switch
    |--------------------------------------------------------------------------
    |
    | This option may be used to disable all Telescope watchers regardless
    | of their individual configuration, which simply provides a single
    | and convenient way to enable or disable Telescope data storage.
    |
    */

    'enabled' => env('TELESCOPE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Telescope Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Telescope will be accessible from. If the
    | setting is null, Telescope will reside under the same domain as the
    | application. Otherwise, this value will be used as the subdomain.
    |
    */

    'domain' => env('TELESCOPE_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Telescope Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Telescope will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('TELESCOPE_PATH', 'telescope'),

    /*
    |--------------------------------------------------------------------------
    | Telescope Storage Driver
    |--------------------------------------------------------------------------
    |
    | This configuration options determines the storage driver that will
    | be used to store Telescope's data. In addition, you may set any
    | custom options as needed by the particular driver you choose.
    |
    */

    'driver' => env('TELESCOPE_DRIVER', 'database'),

    'storage' => [
        'database' => [
            'connection' => env('DB_CONNECTION', 'mysql'),
            'chunk' => 1000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Telescope Queue
    |--------------------------------------------------------------------------
    |
    | This configuration options determines the queue connection and queue
    | which will be used to process ProcessPendingUpdate jobs. This can
    | be changed if you would prefer to use a non-default connection.
    |
    */

    'queue' => [
        'connection' => env('TELESCOPE_QUEUE_CONNECTION'),
        'queue' => env('TELESCOPE_QUEUE'),
        'delay' => env('TELESCOPE_QUEUE_DELAY', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telescope Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Telescope route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => [
        'web',
        Authorize::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed / Ignored Paths & Commands
    |--------------------------------------------------------------------------
    |
    | The following array lists the URI paths and Artisan commands that will
    | not be watched by Telescope. In addition to this list, some Laravel
    | commands, like migrations and queue commands, are always ignored.
    |
    */

    'only_paths' => [
        // 'api/*'
    ],

    'ignore_paths' => [
        'livewire*',
        'nova-api*',
        'pulse*',
        '_boost*',
    ],

    'ignore_commands' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Telescope Watchers
    |--------------------------------------------------------------------------
    |
    | The following array lists the "watchers" that will be registered with
    | Telescope. The watchers gather the application's profile data when
    | a request or task is executed. Feel free to customize this list.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | PRODUCTION OPTIMIZED WATCHERS
    |--------------------------------------------------------------------------
    | Only critical watchers enabled:
    | - Exceptions (always)
    | - Slow Queries (>100ms only)
    | - Failed Requests (4xx/5xx only)
    | - Failed Jobs
    | - Logs (error level only)
    |
    | All verbose logging disabled for performance.
    */
    'watchers' => [
        // DISABLED - Not essential
        Watchers\BatchWatcher::class => false,
        Watchers\CacheWatcher::class => false,
        Watchers\ClientRequestWatcher::class => false,
        Watchers\CommandWatcher::class => false,
        Watchers\DumpWatcher::class => false,
        Watchers\EventWatcher::class => false,
        Watchers\GateWatcher::class => false,
        Watchers\MailWatcher::class => false,
        Watchers\ModelWatcher::class => false,
        Watchers\NotificationWatcher::class => false,
        Watchers\RedisWatcher::class => false,
        Watchers\ScheduleWatcher::class => false,
        Watchers\ViewWatcher::class => false,

        // ENABLED - Critical for debugging
        Watchers\ExceptionWatcher::class => true,

        // ENABLED - Track failed jobs only
        Watchers\JobWatcher::class => env('TELESCOPE_JOB_WATCHER', true),

        // ENABLED - Error logs only (no info/debug)
        Watchers\LogWatcher::class => [
            'enabled' => env('TELESCOPE_LOG_WATCHER', true),
            'level' => 'error', // Only error, critical, alert, emergency
        ],

        // ENABLED - Slow queries only (>100ms)
        Watchers\QueryWatcher::class => [
            'enabled' => env('TELESCOPE_QUERY_WATCHER', true),
            'ignore_packages' => true,
            'ignore_paths' => [],
            'slow' => 100, // Only log queries >= 100ms
        ],

        // ENABLED - Track failed/slow requests only
        Watchers\RequestWatcher::class => [
            'enabled' => env('TELESCOPE_REQUEST_WATCHER', true),
            'size_limit' => env('TELESCOPE_RESPONSE_SIZE_LIMIT', 32),
            'ignore_http_methods' => ['OPTIONS', 'HEAD'],
            'ignore_status_codes' => [200, 201, 204, 301, 302, 304], // Ignore success responses
        ],
    ],
];
