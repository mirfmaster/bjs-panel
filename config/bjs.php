<?php

return [
    'username' => env('BJS_USERNAME'),
    'password' => env('BJS_PASSWORD'),
    'base_uri' => env('BJS_BASE_URI', 'https://belanjasosmed.com'),
    'cookie_path' => storage_path('app/bjs-cookies.json'),
    'max_retries' => (int) env('BJS_MAX_RETRIES', 3),
    'retry_delay_ms' => (int) env('BJS_RETRY_DELAY_MS', 5000),
    'session_cache_ttl' => (int) env('BJS_SESSION_CACHE_TTL', 600),
    'cache_keys' => [
        'credentials' => [
            'username' => 'bjs.credentials.username',
            'password' => 'bjs.credentials.password',
        ],
        'session' => [
            'login_toggle' => 'bjs.session.login_toggle',
        ],
        'services' => 'bjs.services',
        'api' => [
            'access_token' => 'bjs.api.access_token',
        ],
    ],
];
