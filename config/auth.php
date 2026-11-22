<?php

return [

    // Default authentication guard and password broker
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    // Authentication guards
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    // User providers
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],

    // Password reset settings
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    // Password confirmation timeout in seconds
    'password_timeout' => 10800,

];
