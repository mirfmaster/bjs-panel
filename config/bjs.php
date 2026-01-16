<?php

return [
    'username' => env('BJS_USERNAME'),
    'password' => env('BJS_PASSWORD'),
    'base_uri' => env('BJS_BASE_URI', 'https://belanjasosmed.com'),
    'cookie_path' => storage_path('app/bjs-cookies.json'),
    'max_failed_attempts' => (int) env('BJS_MAX_FAILED_ATTEMPTS', 3),
    'cache_keys' => [
        'credentials' => [
            'username' => 'bjs.credentials.username',
            'password' => 'bjs.credentials.password',
        ],
        'session' => [
            'login_toggle' => 'bjs.session.login_toggle',
            'failed_attempts' => 'bjs.session.failed_attempts',
        ],
    ],
];
