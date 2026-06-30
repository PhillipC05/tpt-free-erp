<?php

namespace App\Services\Agent;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterService
{
    private string $apiKey;

    private string $baseUrl = 'https://openrouter.ai/api/v1';

    private string $siteUrl;

    private string $siteName;

    public function __construct()
    {
        $this->apiKey = config('ai.openrouter_api_key', '');
        $this->siteUrl = config('app.url', 'http://localhost');
        $this->siteName = config('app.name', 'TPT Free ERP');
    }

    public function chat(string $model, array $messages, array $options = []): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('OpenRouter API key not configured. Set AI_OPENROUTER_API_KEY in .env');
        }

        $startTime = microtime(true);

        $payload = array_filter([
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $options['max_tokens'] ?? 2000,
            'temperature' => $options['temperature'] ?? 0.3,
        ]);

        $response = Http::timeout(120)
            ->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'HTTP-Referer' => $this->siteUrl,
                'X-Title' => $this->siteName,
            ])
            ->post("{$this->baseUrl}/chat/completions", $payload);

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        if (! $response->successful()) {
            throw new \RuntimeException("OpenRouter error {$response->status()}: ".$response->body());
        }

        $body = $response->json();
        $choice = $body['choices'][0] ?? null;

        return [
            'text' => $choice['message']['content'] ?? '',
            'tokens_used' => ($body['usage']['total_tokens'] ?? 0),
            'model_used' => $body['model'] ?? $model,
            'duration_ms' => $durationMs,
            'provider' => 'openrouter',
        ];
    }

    public function listModels(): array
    {
        if (empty($this->apiKey)) {
            return [];
        }

        try {
            $response = Http::timeout(15)
                ->withToken($this->apiKey)
                ->get("{$this->baseUrl}/models");

            if ($response->successful()) {
                return $response->json('data', []);
            }
        } catch (\Throwable $e) {
            Log::warning('Could not list OpenRouter models: '.$e->getMessage());
        }

        return [];
    }
}
