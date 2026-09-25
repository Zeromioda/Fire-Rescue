<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin client for the station's AI provider (OpenAI-compatible chat API).
 */
class AiService
{
    public function isConfigured(): bool
    {
        return filled(config('services.ai.api_key'));
    }

    /**
     * Send a system + user prompt and return the model's reply decoded as a JSON array.
     * Returns null when the AI is not configured, the call fails, or the reply is not valid JSON.
     */
    public function json(string $system, string $user, int $maxTokens = 1500): ?array
    {
        if (! $this->isConfigured()) {
            Log::warning('AI request skipped: AI_API_KEY is not set.');
            return null;
        }

        try {
            $response = Http::timeout((int) config('services.ai.timeout', 60))
                ->withToken(config('services.ai.api_key'))
                ->acceptJson()
                ->post(rtrim(config('services.ai.base_url'), '/').'/chat/completions', [
                    'model' => config('services.ai.model'),
                    'temperature' => 0.2,
                    'max_completion_tokens' => $maxTokens,
                    'reasoning_effort' => 'low',
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                ]);

            if (! $response->successful()) {
                Log::error('AI API error', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $content = trim((string) $response->json('choices.0.message.content', ''));
            $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content);
            $decoded = json_decode($content, true);

            if (! is_array($decoded)) {
                Log::warning('AI response was not valid JSON.', ['raw_response' => $content]);
                return null;
            }

            return $decoded;
        } catch (\Throwable $e) {
            Log::error('AI request failed', ['message' => $e->getMessage()]);
            return null;
        }
    }
}
