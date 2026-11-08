<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Signing Algorithm
    |--------------------------------------------------------------------------
    |
    | Asymmetric signing is required so that other services can verify tokens
    | with the public key only. Do not change this to HS256 in production.
    |
    */

    'algo' => env('JWT_ALGO', 'RS256'),

    /*
    |--------------------------------------------------------------------------
    | Keys
    |--------------------------------------------------------------------------
    |
    | Either provide the PEM contents directly via JWT_PRIVATE_KEY /
    | JWT_PUBLIC_KEY, or leave them empty and point the path settings at the
    | key files (defaults: storage/jwt/private.pem and public.pem).
    |
    */

    'private_key' => env('JWT_PRIVATE_KEY'),

    'public_key' => env('JWT_PUBLIC_KEY'),

    'private_key_path' => env('JWT_PRIVATE_KEY_PATH', storage_path('jwt/private.pem')),

    'public_key_path' => env('JWT_PUBLIC_KEY_PATH', storage_path('jwt/public.pem')),

    /*
    |--------------------------------------------------------------------------
    | Token Lifetimes
    |--------------------------------------------------------------------------
    |
    | Access token lifetime and refresh token lifetime, both in minutes.
    |
    */

    'ttl' => (int) env('JWT_TTL', 15),

    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 20160),

    /*
    |--------------------------------------------------------------------------
    | Issuer, Audience, Clock Skew
    |--------------------------------------------------------------------------
    */

    'issuer' => rtrim((string) env('JWT_ISSUER', env('APP_URL', 'http://localhost')), '/'),

    'audience' => env('JWT_AUDIENCE', 'curasync-api'),

    'clock_skew' => (int) env('JWT_CLOCK_SKEW', 30),

];
