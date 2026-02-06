<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(
        private GeminiService $geminiService
    ) {}

    /**
     * Send a chat message and get AI response
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:10000',
            'history' => 'array',
            'history.*.role' => 'required|string|in:user,assistant',
            'history.*.content' => 'required|string',
        ]);

        $message = $request->input('message');
        $history = $request->input('history', []);

        $result = $this->geminiService->generateContent($message, $history);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['content'],
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
            'history' => 'array',
        ]);

        $message = $request->input('message');
        $history = $request->input('history', []);

        return response()->stream(function () use ($message, $history) {
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');

            try {
                foreach ($this->geminiService->streamContent($message, $history) as $chunk) {
                    echo "data: " . json_encode(['content' => $chunk]) . "\n\n";
                    ob_flush();
                    flush();
                }
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
}
