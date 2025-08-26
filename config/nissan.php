<?php

return [
    // fallback فقط في حال لم تتوفر بيانات الاعتماد في قاعدة البيانات
    'fallback_username' => env('NISSAN_USERNAME'),
    'fallback_password' => env('NISSAN_PASSWORD'),

    // إعدادات الاتصال الثابتة
    'sid' => env('NISSAN_SID', 'DYN000000001207CFA'),
    'franchise' => 'NMC',
    'referer' => 'https://microcat-apac.superservice.com/content/microcat-epc/',
    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',
];
