<?php

use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests from the following domains / hosts will receive stateful API
    | authentication cookies. Typically, these should include your local
    | and production domains which access your API via a frontend SPA.
    |
    */

    'stateful' => array_filter(array_map('trim', explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s%s',
        // Стандартные локальные хосты
        'localhost,localhost:3000,localhost:5173,localhost:8080,localhost:8000,',
        '127.0.0.1,127.0.0.1:3000,127.0.0.1:5173,127.0.0.1:8080,127.0.0.1:8000,::1,',
        // Docker хосты
        'app,nginx,web,',
        // Основной домен приложения
        Sanctum::currentApplicationUrlWithPort()
    ))))),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | This array contains the authentication guards that will be checked when
    | Sanctum is trying to authenticate a request. If none of these guards
    | are able to authenticate the request, Sanctum will use the bearer
    | token that's present on an incoming request for authentication.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will be
    | considered expired. This will override any values set in the token's
    | "expires_at" attribute, but first-party sessions are not affected.
    |
    */

    'expiration' => env('SANCTUM_EXPIRATION', 525600), // 1 год по умолчанию

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    |
    | Sanctum can prefix new tokens in order to take advantage of numerous
    | security scanning initiatives maintained by open source platforms
    | that notify developers if they commit tokens into repositories.
    |
    | See: https://docs.github.com/en/code-security/secret-scanning/about-secret-scanning
    |
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', 'sanctum_'),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | When authenticating your first-party SPA with Sanctum you may need to
    | customize some of the middleware Sanctum uses while processing the
    | request. You may change the middleware listed below as required.
    |
    */

    'middleware' => [
        'authenticate_session' => env('SANCTUM_AUTHENTICATE_SESSION', true)
            ? Laravel\Sanctum\Http\Middleware\AuthenticateSession::class
            : null,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Storage
    |--------------------------------------------------------------------------
    |
    | Configure where Sanctum should store personal access tokens.
    | Options: 'database' (default), 'cache', or custom driver.
    |
    */

    'storage' => [
        'driver' => env('SANCTUM_TOKEN_STORAGE', 'database'),

        // Конфигурация для драйвера кэша
        'cache' => [
            'store' => env('SANCTUM_CACHE_STORE', 'memcached'),
            'prefix' => env('SANCTUM_CACHE_PREFIX', 'sanctum_tokens:'),
            'ttl' => env('SANCTUM_CACHE_TTL', 525600), // 1 год в минутах
        ],

        // Конфигурация для базы данных
        'database' => [
            'table' => 'personal_access_tokens',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Name
    |--------------------------------------------------------------------------
    |
    | Default name for new personal access tokens.
    |
    */

    'token_name' => env('SANCTUM_TOKEN_NAME', 'API Token'),

    /*
    |--------------------------------------------------------------------------
    | Authentication Scopes
    |--------------------------------------------------------------------------
    |
    | Define the available scopes for personal access tokens.
    |
    */

    'scopes' => [
        'read' => 'Read access',
        'write' => 'Write access',
        'admin' => 'Administrator access',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Scopes
    |--------------------------------------------------------------------------
    |
    | Default scopes assigned to new tokens when not specified.
    |
    */

    'default_scopes' => ['read'],

    /*
    |--------------------------------------------------------------------------
    | Token Hashing
    |--------------------------------------------------------------------------
    |
    | Enable token hashing for additional security.
    |
    */

    'hash_tokens' => env('SANCTUM_HASH_TOKENS', true),

    /*
    |--------------------------------------------------------------------------
    | Cookie Configuration
    |--------------------------------------------------------------------------
    |
    | Configure Sanctum's authentication cookies.
    |
    */

    'cookie' => [
        'name' => env('SANCTUM_COOKIE_NAME', 'laravel_session'),
        'domain' => env('SANCTUM_COOKIE_DOMAIN', null),
        'secure' => env('SANCTUM_COOKIE_SECURE', null),
        'same_site' => env('SANCTUM_COOKIE_SAME_SITE', 'lax'),
        'path' => env('SANCTUM_COOKIE_PATH', '/'),
        'http_only' => env('SANCTUM_COOKIE_HTTP_ONLY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for authentication endpoints.
    |
    */

    'rate_limiting' => [
        'enabled' => env('SANCTUM_RATE_LIMITING', true),
        'attempts' => env('SANCTUM_RATE_LIMIT_ATTEMPTS', 5),
        'decay_minutes' => env('SANCTUM_RATE_LIMIT_DECAY', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Cleanup
    |--------------------------------------------------------------------------
    |
    | Automatically clean up expired tokens.
    |
    */

    'cleanup' => [
        'enabled' => env('SANCTUM_CLEANUP_ENABLED', true),
        'schedule' => env('SANCTUM_CLEANUP_SCHEDULE', 'daily'), // daily, weekly, monthly
        'older_than_days' => env('SANCTUM_CLEANUP_OLDER_THAN', 30),
    ],

];
