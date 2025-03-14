<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
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

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
