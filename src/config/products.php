<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Product Service Configuration
    |--------------------------------------------------------------------------
    |
    | Настройки сервиса товаров
    |
    */

    'cache' => [
        'enabled' => env('PRODUCT_CACHE_ENABLED', true),
        'ttl' => env('PRODUCT_CACHE_TTL', 3600), // секунды
        'prefix' => 'products:',
    ],

    'search' => [
        'driver' => env('PRODUCT_SEARCH_DRIVER', 'like'), // like или fulltext
        'min_length' => 3, // минимальная длина поискового запроса
        'stop_words' => ['и', 'в', 'на', 'с', 'по', 'для'], // стоп-слова для русского языка
    ],

    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
        'available_per_page' => [10, 15, 25, 50, 100],
    ],

    'sorting' => [
        'default' => 'newest',
        'available' => [
            'price_asc' => ['field' => 'price', 'direction' => 'asc'],
            'price_desc' => ['field' => 'price', 'direction' => 'desc'],
            'rating_desc' => ['field' => 'rating', 'direction' => 'desc'],
            'newest' => ['field' => 'created_at', 'direction' => 'desc'],
        ],
    ],

    'validation' => [
        'price' => [
            'min' => 0,
            'max' => 9999999.99,
        ],
        'rating' => [
            'min' => 0,
            'max' => 5,
        ],
    ],

    'performance' => [
        'slow_query_threshold' => 1000, // миллисекунды
        'max_results' => 1000, // максимальное количество возвращаемых товаров
        'query_timeout' => 30, // секунды
    ],
];
