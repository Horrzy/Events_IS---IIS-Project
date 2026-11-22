<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    // Inputs never flashed to the session on validation errors
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    // Register exception handling callbacks
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
        });
    }
}
