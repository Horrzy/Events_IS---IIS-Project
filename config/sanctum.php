<?php

use Laravel\Sanctum\Sanctum;

return [

    // Domains allowed to use stateful SPA cookie authentication
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort()
    ))),

    // Guards checked before falling back to bearer tokens
    'guard' => ['web'],

    // Token expiration time in minutes (null disables expiration)
    'expiration' => null,

    // Optional prefix for issued API tokens
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    // Middleware used for stateful Sanctum requests
    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
    ],

];
