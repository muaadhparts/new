<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'muaadh' => [
        'ocean' => 'https://MUAADH.com/verify/'
    ],

    'tryoto' => [
        'sandbox' => env('TRYOTO_SANDBOX', false),
        'test' => [
            'url'   => env('TRYOTO_TEST_URL', 'https://staging-api.tryoto.com'),
            'token' => env('TRYOTO_TEST_REFRESH_TOKEN'),
        ],
        'live' => [
            'url'   => env('TRYOTO_URL', 'https://api.tryoto.com'),
            'token' => env('TRYOTO_REFRESH_TOKEN'),
        ],
        // اختياري: لو كان لديك Webhook من OTO مستقبلاً
        'webhook' => [
            'secret' => env('TRYOTO_WEBHOOK_SECRET'),
        ],
    ],

    // النسخة القديمة (مرجعية فقط):
    // 'tryoto' => [
    //     'cache_name' => 'oto_api_token',
    //     'cache_time' => 58, // time in minutes
    //     'sandbox' => env('TRYOTO_SANDBOX', false),
    //     'test' => [
    //         'url' => env('TRYOTO_TEST_URL', 'https://staging-api.tryoto.com'),
    //         'token' => env('TRYOTO_TEST_REFRESH_TOKEN', 'xxxx'),
    //     ],
    //     'live' => [
    //         'url' => env('TRYOTO_URL', 'https://api.tryoto.com'),
    //         'token' => env('TRYOTO_REFRESH_TOKEN', 'xxxxxxxxxxxxxxxxx'),
    //     ],
    // ],

    'google_maps' => [
        'key' => env('GOOGLE_MAPS_KEY'),
    ],

];
