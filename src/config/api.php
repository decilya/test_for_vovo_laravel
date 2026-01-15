<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */

    'name' => env('API_NAME', 'Comment System API'),
    'version' => env('API_VERSION', 'v1'),
    'url' => env('API_URL', env('APP_URL') . '/api'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limit' => [
        'enabled' => env('API_RATE_LIMIT_ENABLED', true),
        'requests_per_minute' => env('API_RATE_LIMIT_REQUESTS_PER_MINUTE', 60),
        'max_attempts' => env('API_RATE_LIMIT_MAX_ATTEMPTS', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    */

    'cors' => [
        'allowed_origins' => explode(',', env('API_CORS_ALLOWED_ORIGINS', '*')),
        'allowed_methods' => explode(',', env('API_CORS_ALLOWED_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS')),
        'allowed_headers' => explode(',', env('API_CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,X-Requested-With')),
        'exposed_headers' => explode(',', env('API_CORS_EXPOSED_HEADERS', '')),
        'max_age' => env('API_CORS_MAX_AGE', 0),
        'supports_credentials' => env('API_CORS_SUPPORTS_CREDENTIALS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'default_per_page' => env('API_DEFAULT_PER_PAGE', 15),
        'max_per_page' => env('API_MAX_PER_PAGE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'enabled' => env('API_CACHE_ENABLED', true),
        'ttl' => env('API_CACHE_TTL', 3600), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */

    'security' => [
        'force_https' => env('API_FORCE_HTTPS', false),
        'hide_server_header' => env('API_HIDE_SERVER_HEADER', true),
        'hide_powered_by_header' => env('API_HIDE_POWERED_BY_HEADER', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */

    'logging' => [
        'enabled' => env('API_LOGGING_ENABLED', true),
        'level' => env('API_LOGGING_LEVEL', 'info'),
        'channel' => env('API_LOGGING_CHANNEL', 'api'),
    ],
];
