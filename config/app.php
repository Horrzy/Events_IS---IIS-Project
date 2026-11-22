<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    // Application name
    'name' => env('APP_NAME', 'Laravel'),

    // Current application environment
    'env' => env('APP_ENV', 'production'),

    // Enable or disable detailed debug error pages
    'debug' => (bool) env('APP_DEBUG', false),

    // Base application URL used for link generation
    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    // Default timezone for date and time functions
    'timezone' => 'UTC',

    // Default translation locale
    'locale' => 'cs',

    // Fallback locale when the default translation is missing
    'fallback_locale' => 'cs',

    // Locale used by Faker for test and seed data
    'faker_locale' => 'cs_CZ',

    // Encryption key and cipher
    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    // Maintenance mode driver ("file" or "cache")
    'maintenance' => [
        'driver' => 'file',
        // 'store' => 'redis',
    ],

    // Autoloaded service providers
    'providers' => ServiceProvider::defaultProviders()->merge([
        // Application service providers
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
    ])->toArray(),

    // Registered class aliases
    'aliases' => Facade::defaultAliases()->merge([
        // 'Example' => App\Facades\Example::class,
    ])->toArray(),

];
