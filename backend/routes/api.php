<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProtocolController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| Versioned REST API endpoints for the Community-Powered Protocol Platform.
|
*/

// Authentication endpoints
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// Protocol public endpoints
Route::get('/protocols', [ProtocolController::class, 'index']);
Route::get('/protocols/{slug}', [ProtocolController::class, 'show']);

// Protocol authenticated endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/protocols', [ProtocolController::class, 'store']);
    Route::put('/protocols/{protocol}', [ProtocolController::class, 'update']);
    Route::delete('/protocols/{protocol}', [ProtocolController::class, 'destroy']);
});
