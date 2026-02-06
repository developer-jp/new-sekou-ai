<?php

namespace App\Services;

use Gemini;
use Gemini\Client;
use Gemini\Data\Content;
use Gemini\Enums\Role;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private Client $client;
    private string $model = 'gemini-2.0-flash';

    public function __construct()
    {
        $apiKey = config('services.gemini.api_key');
        $this->client = Gemini::client($apiKey);
    }

    /**
     * Generate content using Gemini API
     */
    public function generateContent(string $message, array $history = []): array
    {
        try {
            $chat = $this->client->generativeModel($this->model)->startChat(
                history: $this->buildHistory($history)
            );

            $response = $chat->sendMessage($message);

            $text = $response->text();

            return [
                'success' => true,
                'content' => $text
            ];
        } catch (\Exception $e) {
            Log::error('Gemini service error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate content with streaming
     */
    public function streamContent(string $message, array $history = []): \Generator
    {
        try {
            $chat = $this->client->generativeModel($this->model)->startChat(
                history: $this->buildHistory($history)
            );

            $stream = $chat->streamSendMessage($message);

            foreach ($stream as $response) {
                yield $response->text();
            }
        } catch (\Exception $e) {
            Log::error('Gemini streaming error', ['error' => $e->getMessage()]);
            yield 'エラーが発生しました: ' . $e->getMessage();
        }
    }

    /**
     * Build history array for chat
     */
    private function buildHistory(array $history): array
    {
        $contents = [];

        foreach ($history as $item) {
            $role = $item['role'] === 'user' ? Role::USER : Role::MODEL;
            $contents[] = Content::parse(part: $item['content'], role: $role);
        }

        return $contents;
    }
}
