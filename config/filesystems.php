<?php

return [

    'default' => env('FILESYSTEM_DISK', 'do'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
//            'url' => env('APP_URL') . '/storage',
            'url' => env('SPACES_CDN_ENDPOINT'). '/storage2',
            'visibility' => 'public',
        ],

        'spaces' => [
            'driver' => 's3',
            'key' => env('SPACES_KEY'),
            'secret' => env('SPACES_SECRET'),
            'endpoint' => env('SPACES_ENDPOINT'),
            'region' => env('SPACES_REGION'),
            'bucket' => env('SPACES_BUCKET'),
            'url' => env('SPACES_CDN_ENDPOINT'),
            'visibility' => 'public',
            'throw' => false,
            'use_path_style_endpoint' => true,
            'options' => [
                'verify' => false, 
            ],
        ],



        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],

        'do' => [
            'driver' => 's3',
            'key' => env('DO_ACCESS_KEY_ID'),
            'secret' => env('DO_SECRET_ACCESS_KEY'),
            'endpoint' => env('SPACES_ENDPOINT'),
            'region' => env('DO_DEFAULT_REGION'),
            'bucket' => env('DO_BUCKET'),
            'url' => env('DO_ENDPOINT'),
            'visibility' => 'public',
            'throw' => false,
            'use_path_style_endpoint' => true,
            'options' => [
                'verify' => false,
            ],
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
