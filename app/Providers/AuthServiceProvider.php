<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    // Model-to-policy mappings
    protected $policies = [];

    // Register authentication and authorization services
    public function boot(): void
    {
    }
}
