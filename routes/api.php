<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// API routes loaded under the "api" middleware group
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
