<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AiModelController;

// 認証不要のルート
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 認証が必要なルート
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Chat routes
    Route::post('/chat', [ChatController::class, 'chat']);
    Route::post('/chat/stream', [ChatController::class, 'streamChat']);
    
    // AI Models routes
    Route::get('/ai-models', [AiModelController::class, 'index']);
});
