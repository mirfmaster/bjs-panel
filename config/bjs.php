<?php

return [
    'username' => env('BJS_USERNAME'),
    'password' => env('BJS_PASSWORD'),
    'base_uri' => env('BJS_BASE_URI', 'https://belanjasosmed.com'),
    'cookie_path' => storage_path('app/bjs-cookies.json'),
    'max_retries' => (int) env('BJS_MAX_RETRIES', 3),
    'retry_delay_ms' => (int) env('BJS_RETRY_DELAY_MS', 5000),
    'cache_keys' => [
        'credentials' => [
            'username' => 'bjs.credentials.username',
            'password' => 'bjs.credentials.password',
        ],
        'session' => [
            'login_toggle' => 'bjs.session.login_toggle',
        ],
    ],
];
