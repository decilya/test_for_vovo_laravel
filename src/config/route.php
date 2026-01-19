<?php

use App\Http\Controllers\Api\AuthController;

return [
    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Конфигурация маршрутов приложения
    |
    */

    'api' => [
        'version' => 'v1',
        'prefix' => 'api',
        'middleware' => ['api'],
        'rate_limit' => [
            'enabled' => env('API_RATE_LIMIT_ENABLED', true),
            'max_attempts' => env('API_RATE_LIMIT_ATTEMPTS', 60),
            'decay_minutes' => env('API_RATE_LIMIT_DECAY', 1),
        ],
    ],

    'web' => [
        'middleware' => ['web'],
        'rate_limit' => [
            'enabled' => env('WEB_RATE_LIMIT_ENABLED', false),
        ],
    ],

    'admin' => [
        'prefix' => 'admin',
        'middleware' => ['auth', 'admin'],
        'name_prefix' => 'admin.',
    ],

    'caching' => [
        'enabled' => env('ROUTE_CACHING_ENABLED', true),
        'duration' => env('ROUTE_CACHE_DURATION', 3600), // секунды
    ],

    'bindings' => [
        // Привязка параметров маршрута к моделям
        'product' => \App\Models\Product::class,
        'category' => \App\Models\Category::class,
    ],
];
