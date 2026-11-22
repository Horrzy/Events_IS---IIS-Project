<?php

use Illuminate\Support\Str;

return [

    // Default session storage driver
    'driver' => env('SESSION_DRIVER', 'file'),

    // Session idle lifetime in minutes
    'lifetime' => env('SESSION_LIFETIME', 30),

    'expire_on_close' => false,

    // Encrypt session data before storing
    'encrypt' => false,

    // Directory for file-based session storage
    'files' => storage_path('framework/sessions'),

    // Database or Redis connection for sessions
    'connection' => env('SESSION_CONNECTION'),

    // Database table for the database session driver
    'table' => 'sessions',

    // Cache store for cache-backed session drivers
    'store' => env('SESSION_STORE'),

    // Probability [numerator, denominator] of sweeping expired sessions
    'lottery' => [2, 100],

    // Session cookie name
    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_session'
    ),

    // Session cookie path
    'path' => '/',

    // Session cookie domain
    'domain' => env('SESSION_DOMAIN'),

    // Send session cookies over HTTPS only
    'secure' => env('SESSION_SECURE_COOKIE'),

    // Block JavaScript access to the session cookie
    'http_only' => true,

    // SameSite policy for CSRF protection
    'same_site' => 'lax',

    // Partition cookies by top-level site
    'partitioned' => false,

];
