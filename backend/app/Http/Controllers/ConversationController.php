<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    /**
     * List user's conversations
     */
    public function index(Request $request): JsonResponse
    {
        $conversations = Conversation::forUser($request->user()->id)
            ->active()
            ->recent()
            ->select(['id', 'title', 'last_message_at', 'created_at'])
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Get a single conversation with messages
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $conversation = Conversation::forUser($request->user()->id)
            ->with(['messages:id,conversation_id,role,content,metadata,created_at'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
        ]);
    }

    /**
     * Create a new conversation
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'ai_model_id' => 'nullable|exists:ai_models,id',
        ]);

        $conversation = Conversation::create([
            'user_id' => $request->user()->id,
            'ai_model_id' => $request->input('ai_model_id', 1),
            'title' => $request->input('title'),
            'last_message_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
        ], 201);
    }

    /**
     * Update conversation
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'is_archived' => 'nullable|boolean',
            'is_favorite' => 'nullable|boolean',
        ]);

        $conversation = Conversation::forUser($request->user()->id)->findOrFail($id);
        
        $conversation->update($request->only(['title', 'is_archived', 'is_favorite']));

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
        ]);
    }

    /**
     * Delete conversation
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $conversation = Conversation::forUser($request->user()->id)->findOrFail($id);
        $conversation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversation deleted',
        ]);
    }
}
