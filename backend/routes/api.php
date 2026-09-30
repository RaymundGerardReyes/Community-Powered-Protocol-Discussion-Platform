<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\ProtocolController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\ThreadController;
use App\Http\Controllers\Api\V1\VoteController;
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

// Thread public endpoints
Route::get('/protocols/{protocol}/threads', [ThreadController::class, 'index']);
Route::get('/threads/{id}', [ThreadController::class, 'show']);
Route::get('/threads/{thread}/comments', [CommentController::class, 'index']);

// Review public endpoints
Route::get('/protocols/{protocol}/reviews', [ReviewController::class, 'index']);

// Search sidecar status & reindex endpoints
Route::get('/search/status', [SearchController::class, 'status']);
Route::post('/search/reindex', [SearchController::class, 'reindex']);

// Authenticated mutations
Route::middleware('auth:sanctum')->group(function () {
    // Protocols
    Route::post('/protocols', [ProtocolController::class, 'store']);
    Route::put('/protocols/{protocol}', [ProtocolController::class, 'update']);
    Route::delete('/protocols/{protocol}', [ProtocolController::class, 'destroy']);

    // Threads
    Route::post('/protocols/{protocol}/threads', [ThreadController::class, 'store']);
    Route::put('/threads/{thread}', [ThreadController::class, 'update']);
    Route::delete('/threads/{thread}', [ThreadController::class, 'destroy']);

    // Comments
    Route::post('/threads/{thread}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    // Reviews
    Route::post('/protocols/{protocol}/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

    // Votes (polymorphic)
    Route::post('/votes', [VoteController::class, 'store']);
});
