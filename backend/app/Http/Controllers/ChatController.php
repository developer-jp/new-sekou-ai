<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\GeminiService;
use App\Services\FileExtractionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(
        private GeminiService $geminiService,
        private FileExtractionService $fileExtractionService
    ) {}

    /**
     * Send a chat message and get AI response
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:10000',
            'conversation_id' => 'nullable|exists:conversations,id',
            'history' => 'array',
            'history.*.role' => 'required|string|in:user,assistant',
            'history.*.content' => 'required|string',
        ]);

        $user = $request->user();
        $message = $request->input('message');
        $history = $request->input('history', []);
        $conversationId = $request->input('conversation_id');

        // Get or create conversation
        $conversation = $this->getOrCreateConversation($user, $conversationId, $message);

        // Save user message
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $message,
        ]);

        $result = $this->geminiService->generateContent($message, $history);

        if ($result['success']) {
            // Save assistant message
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $result['content'],
            ]);

            // Update conversation last_message_at
            $conversation->update(['last_message_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => $result['content'],
                'conversation_id' => $conversation->id,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Unknown error occurred',
        ], 500);
    }

    /**
     * Stream a chat response using SSE
     */
    public function streamChat(Request $request): StreamedResponse
    {
        $request->validate([
            'message' => 'required|string|max:10000',
            'conversation_id' => 'nullable|exists:conversations,id',
            'history' => 'array',
            'system_prompt' => 'nullable|string|max:10000',
        ]);

        $user = $request->user();
        $message = $request->input('message');
        $history = $request->input('history', []);
        $conversationId = $request->input('conversation_id');
        $systemPrompt = $request->input('system_prompt');

        // Get or create conversation
        $conversation = $this->getOrCreateConversation($user, $conversationId, $message);

        // Save user message
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $message,
        ]);

        return response()->stream(function () use ($message, $history, $conversation, $systemPrompt) {
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');

            // Send conversation_id first
            echo "data: " . json_encode(['conversation_id' => $conversation->id]) . "\n\n";
            ob_flush();
            flush();

            $fullContent = '';

            try {
                foreach ($this->geminiService->streamContent($message, $history, $systemPrompt) as $chunk) {
                    $fullContent .= $chunk;
                    echo "data: " . json_encode(['content' => $chunk]) . "\n\n";
                    ob_flush();
                    flush();
                }

                // Save assistant message after streaming completes
                Message::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => $fullContent,
                ]);

                // Update conversation last_message_at
                $conversation->update(['last_message_at' => now()]);

                echo "data: [DONE]\n\n";
                ob_flush();
                flush();
            } catch (\Exception $e) {
                echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
                ob_flush();
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Stream a chat response with file uploads using SSE
     */
    public function streamChatWithFiles(Request $request): StreamedResponse
    {
        $request->validate([
            'message' => 'required|string|max:10000',
            'conversation_id' => 'nullable',
            'history' => 'nullable|string', // JSON string for multipart
            'system_prompt' => 'nullable|string|max:10000',
            'files.*' => 'file|max:10240', // 10MB max per file
        ]);

        $user = $request->user();
        $message = $request->input('message');
        $history = json_decode($request->input('history', '[]'), true) ?: [];
        $conversationId = $request->input('conversation_id') ? (int) $request->input('conversation_id') : null;
        $systemPrompt = $request->input('system_prompt');
        $uploadedFiles = $request->file('files', []);

        // Process uploaded files
        $processedFiles = [];
        $fileNames = [];
        foreach ($uploadedFiles as $file) {
            if (!$this->fileExtractionService->isSupported($file)) {
                continue;
            }
            try {
                $processedFiles[] = $this->fileExtractionService->extractContent($file);
                $fileNames[] = $file->getClientOriginalName();
            } catch (\Exception $e) {
                // Log and skip failed files
                \Log::warning('File extraction failed', ['file' => $file->getClientOriginalName(), 'error' => $e->getMessage()]);
            }
        }

        // Get or create conversation
        $conversation = $this->getOrCreateConversation($user, $conversationId, $message);

        // Save user message (with file info)
        $messageContent = $message;
        if (!empty($fileNames)) {
            $messageContent .= "\n\n📎 添付ファイル: " . implode(', ', $fileNames);
        }
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $messageContent,
        ]);

        return response()->stream(function () use ($message, $processedFiles, $history, $conversation, $systemPrompt) {
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');

            // Send conversation_id first
            echo "data: " . json_encode(['conversation_id' => $conversation->id]) . "\n\n";
            ob_flush();
            flush();

            $fullContent = '';

            try {
                foreach ($this->geminiService->streamContentWithFiles($message, $processedFiles, $history, $systemPrompt) as $chunk) {
                    $fullContent .= $chunk;
                    echo "data: " . json_encode(['content' => $chunk]) . "\n\n";
                    ob_flush();
                    flush();
                }

                // Save assistant message after streaming completes
                Message::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => $fullContent,
                ]);

                // Update conversation last_message_at
                $conversation->update(['last_message_at' => now()]);

                echo "data: [DONE]\n\n";
                ob_flush();
                flush();
            } catch (\Exception $e) {
                echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
                ob_flush();
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Get existing conversation or create new one
     */
    private function getOrCreateConversation($user, ?int $conversationId, string $message): Conversation
    {
        if ($conversationId) {
            return Conversation::forUser($user->id)->findOrFail($conversationId);
        }

        // Create new conversation with auto-generated title
        $title = mb_substr($message, 0, 50);
        if (mb_strlen($message) > 50) {
            $title .= '...';
        }

        // Get actual first AI model ID from database
        $defaultModelId = \App\Models\AiModel::active()->ordered()->value('id') ?? 1;

        return Conversation::create([
            'user_id' => $user->id,
            'ai_model_id' => $defaultModelId,
            'title' => $title,
            'last_message_at' => now(),
        ]);
    }
}

