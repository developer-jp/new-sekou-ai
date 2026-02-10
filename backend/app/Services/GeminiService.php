<?php

namespace App\Services;

use Gemini;
use Gemini\Client;
use Gemini\Data\Blob;
use Gemini\Data\Content;
use Gemini\Data\GoogleSearch;
use Gemini\Data\Tool;
use Gemini\Enums\MimeType;
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
    public function streamContent(string $message, array $history = [], ?string $systemPrompt = null, bool $useGrounding = false): \Generator
    {
        try {
            $chatHistory = $this->buildHistory($history, $systemPrompt);
            $model = $this->client->generativeModel($this->model);

            if ($useGrounding) {
                $model = $model->withTool(new Tool(googleSearch: new GoogleSearch()));
            }

            $chat = $model->startChat(history: $chatHistory);
            $stream = $chat->streamSendMessage($message);
            $groundingResult = [];

            foreach ($stream as $response) {
                yield ['text' => $response->text()];
                $groundingResult = $this->extractGroundingMetadata($response, $groundingResult);
            }

            if (!empty($groundingResult)) {
                yield ['grounding' => $groundingResult];
            }
        } catch (\Exception $e) {
            Log::error('Gemini streaming error', ['error' => $e->getMessage()]);
            yield ['text' => 'エラーが発生しました: ' . $e->getMessage()];
        }
    }

    /**
     * Generate content with files (images and text) - streaming
     */
    public function streamContentWithFiles(string $message, array $files = [], array $history = [], ?string $systemPrompt = null, bool $useGrounding = false): \Generator
    {
        try {
            $chatHistory = $this->buildHistory($history, $systemPrompt);

            // Add text files content to message
            $textContent = $message;
            foreach ($files as $file) {
                if ($file['type'] === 'text') {
                    $textContent .= "\n\n【ファイル: {$file['filename']}】\n{$file['content']}";
                }
            }

            // Add images as blobs
            $imageBlobs = [];
            foreach ($files as $file) {
                if ($file['type'] === 'image') {
                    $mimeType = $this->getMimeTypeEnum($file['mime_type']);
                    $imageBlobs[] = new Blob(
                        mimeType: $mimeType,
                        data: $file['content']
                    );
                }
            }

            $model = $this->client->generativeModel($this->model);

            if ($useGrounding) {
                $model = $model->withTool(new Tool(googleSearch: new GoogleSearch()));
            }

            $groundingResult = [];

            if (!empty($imageBlobs)) {
                $contentParts = [$textContent];
                foreach ($imageBlobs as $blob) {
                    $contentParts[] = $blob;
                }

                $stream = $model->streamGenerateContent(...$contentParts);

                foreach ($stream as $response) {
                    yield ['text' => $response->text()];
                    $groundingResult = $this->extractGroundingMetadata($response, $groundingResult);
                }
            } else {
                $chat = $model->startChat(history: $chatHistory);
                $stream = $chat->streamSendMessage($textContent);

                foreach ($stream as $response) {
                    yield ['text' => $response->text()];
                    $groundingResult = $this->extractGroundingMetadata($response, $groundingResult);
                }
            }

            if (!empty($groundingResult)) {
                yield ['grounding' => $groundingResult];
            }
        } catch (\Exception $e) {
            Log::error('Gemini multimodal streaming error', ['error' => $e->getMessage()]);
            yield ['text' => 'エラーが発生しました: ' . $e->getMessage()];
        }
    }

    /**
     * Extract grounding metadata from a streaming response chunk
     */
    private function extractGroundingMetadata($response, array $current): array
    {
        if (empty($response->candidates) || !$response->candidates[0]->groundingMetadata) {
            return $current;
        }

        $metadata = $response->candidates[0]->groundingMetadata;
        $result = [];

        // Extract sources from grounding chunks
        if (!empty($metadata->groundingChunks)) {
            $sources = [];
            foreach ($metadata->groundingChunks as $chunk) {
                if ($chunk->web) {
                    $sources[] = [
                        'title' => $chunk->web->title ?? '',
                        'uri' => $chunk->web->uri ?? '',
                    ];
                }
            }
            if (!empty($sources)) {
                $result['sources'] = $sources;
            }
        }

        // Extract web search queries
        if (!empty($metadata->webSearchQueries)) {
            $result['search_queries'] = $metadata->webSearchQueries;
        }

        return !empty($result) ? $result : $current;
    }

    /**
     * Convert MIME type string to Gemini MimeType enum
     */
    private function getMimeTypeEnum(string $mimeType): MimeType
    {
        return match ($mimeType) {
            'image/jpeg' => MimeType::IMAGE_JPEG,
            'image/png' => MimeType::IMAGE_PNG,
            'image/webp' => MimeType::IMAGE_WEBP,
            default => MimeType::IMAGE_JPEG,
        };
    }

    /**
     * Build history array for chat
     */
    private function buildHistory(array $history, ?string $systemPrompt = null): array
    {
        $contents = [];

        // Add system prompt as first user message if provided
        if ($systemPrompt) {
            $contents[] = Content::parse(
                part: "あなたは以下の指示に従って回答してください:\n\n" . $systemPrompt,
                role: Role::USER
            );
            $contents[] = Content::parse(
                part: "はい、指示を理解しました。その指示に従って回答いたします。",
                role: Role::MODEL
            );
        }

        foreach ($history as $item) {
            $role = $item['role'] === 'user' ? Role::USER : Role::MODEL;
            $contents[] = Content::parse(part: $item['content'], role: $role);
        }

        return $contents;
    }
}

