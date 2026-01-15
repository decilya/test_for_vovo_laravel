<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Encryption Keys
    |--------------------------------------------------------------------------
    |
    | Passport uses encryption keys while generating secure access tokens for
    | your application. By default, the keys are stored as local files but
    | can be set via environment variables when that is more convenient.
    |
    */

    'private_key' => env('PASSPORT_PRIVATE_KEY'),
    'public_key' => env('PASSPORT_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Client UUIDs
    |--------------------------------------------------------------------------
    |
    | By default, Passport uses auto-incrementing primary keys when assigning
    | IDs to clients. However, if Passport is installed using the provided
    | --uuids switch, this will be set to "true" and UUIDs will be used.
    |
    */

    'client_uuids' => false,

    /*
    |--------------------------------------------------------------------------
    | Personal Access Client
    |--------------------------------------------------------------------------
    |
    | If you enable client hashing, you should set the personal access client
    | ID and unhashed secret within your environment file. The values will
    | get used while issuing fresh personal access tokens to your users.
    |
    */

    'personal_access_client' => [
        'id' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_ID'),
        'secret' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Passport Storage Driver
    |--------------------------------------------------------------------------
    |
    | This configuration value allows you to customize the storage options
    | for Passport, such as the database connection that should be used
    | by Passport's internal database models which store tokens, etc.
    |
    */

    'storage' => [
        'database' => [
            'connection' => env('DB_CONNECTION', 'mysql'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Lifetimes
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of minutes until certain tokens expire.
    | These defaults are based on convention and may be adjusted as needed.
    |
    */

    'tokens' => [
        'access_token' => [
            'expire' => env('PASSPORT_ACCESS_TOKEN_EXPIRE', 60 * 24 * 30), // 30 дней
        ],
        'refresh_token' => [
            'expire' => env('PASSPORT_REFRESH_TOKEN_EXPIRE', 60 * 24 * 60), // 60 дней
        ],
        'personal_access_token' => [
            'expire' => env('PASSPORT_PERSONAL_ACCESS_TOKEN_EXPIRE', 60 * 24 * 365), // 1 год
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    |
    | Define the scopes that are available for your application. Passport
    | uses these scopes to validate requested scopes from clients.
    |
    */

    'scopes' => [
        'read-posts' => 'Read posts',
        'write-posts' => 'Write posts',
        'delete-posts' => 'Delete posts',
        'read-comments' => 'Read comments',
        'write-comments' => 'Write comments',
        'delete-comments' => 'Delete comments',
        'manage-users' => 'Manage users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Scopes
    |--------------------------------------------------------------------------
    |
    | Define the default scopes that will be applied to all tokens.
    |
    */

    'default_scopes' => [
        'read-posts',
        'read-comments',
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Types
    |--------------------------------------------------------------------------
    |
    | Define the token types that are available for your application.
    |
    */

    'token_types' => [
        'Bearer' => 'Bearer Token',
        'Mac' => 'MAC Token',
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Encryption
    |--------------------------------------------------------------------------
    |
    | Determine if Passport should encrypt tokens stored in the database.
    |
    */

    'encrypt_tokens' => env('PASSPORT_ENCRYPT_TOKENS', false),

    /*
    |--------------------------------------------------------------------------
    | Hashing Client Secrets
    |--------------------------------------------------------------------------
    |
    | Determine if Passport should hash client secrets.
    |
    */

    'hash_client_secrets' => env('PASSPORT_HASH_CLIENT_SECRETS', true),

    /*
    |--------------------------------------------------------------------------
    | Authorization View
    |--------------------------------------------------------------------------
    |
    | Set the view that will be rendered for the authorization prompt.
    |
    */

    'authorization_view' => env('PASSPORT_AUTHORIZATION_VIEW', 'passport::authorize'),

    /*
    |--------------------------------------------------------------------------
    | Unserializes Cookies
    |--------------------------------------------------------------------------
    |
    | Determine if Passport should unserializes cookies.
    |
    */

    'unserializes_cookies' => env('PASSPORT_UNSERIALIZES_COOKIES', false),
];
