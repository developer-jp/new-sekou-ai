<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AiModelController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\FeaturePromptController;

// 認証不要のルート
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Feature routes - public (read only)
Route::get('/features', [FeatureController::class, 'index']);
Route::get('/features/{id}', [FeatureController::class, 'show']);

// 認証が必要なルート
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Chat routes
    Route::post('/chat', [ChatController::class, 'chat']);
    Route::post('/chat/stream', [ChatController::class, 'streamChat']);
    Route::post('/chat/stream-with-files', [ChatController::class, 'streamChatWithFiles']);
    
    // AI Models routes
    Route::get('/ai-models', [AiModelController::class, 'index']);
    
    // Conversation routes
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::get('/conversations/{id}', [ConversationController::class, 'show']);
    Route::put('/conversations/{id}', [ConversationController::class, 'update']);
    Route::delete('/conversations/{id}', [ConversationController::class, 'destroy']);

    // Feature routes - protected (write operations, admin only)
    Route::post('/features', [FeatureController::class, 'store']);
    Route::put('/features/{id}', [FeatureController::class, 'update']);
    Route::delete('/features/{id}', [FeatureController::class, 'destroy']);

    // Feature Prompt routes
    Route::post('/features/{featureId}/prompts', [FeaturePromptController::class, 'store']);
    Route::put('/prompts/{id}', [FeaturePromptController::class, 'update']);
    Route::delete('/prompts/{id}', [FeaturePromptController::class, 'destroy']);
});
