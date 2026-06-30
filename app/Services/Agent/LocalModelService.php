<?php

namespace App\Services\Agent;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocalModelService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('ai.ollama_base_url', 'http://localhost:11434');
    }

    public function generate(string $model, string $prompt, array $options = []): array
    {
        $startTime = microtime(true);

        $payload = [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => array_merge([
                'temperature' => 0.3,
                'num_predict' => $options['max_tokens'] ?? 2000,
            ], $options),
        ];

        try {
            $response = Http::timeout(120)
                ->post("{$this->baseUrl}/api/generate", $payload);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            if (! $response->successful()) {
                throw new \RuntimeException("Ollama API error {$response->status()}: ".$response->body());
            }

            $body = $response->json();

            return [
                'text' => $body['response'] ?? '',
                'tokens_used' => ($body['prompt_eval_count'] ?? 0) + ($body['eval_count'] ?? 0),
                'model_used' => $model,
                'duration_ms' => $durationMs,
                'provider' => 'local',
            ];
        } catch (\Throwable $e) {
            Log::error('LocalModelService error: '.$e->getMessage());
            throw $e;
        }
    }

    public function chat(string $model, array $messages, array $options = []): array
    {
        $startTime = microtime(true);

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'stream' => false,
            'options' => [
                'temperature' => $options['temperature'] ?? 0.3,
                'num_predict' => $options['max_tokens'] ?? 2000,
            ],
        ];

        $response = Http::timeout(120)
            ->post("{$this->baseUrl}/api/chat", $payload);

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        if (! $response->successful()) {
            throw new \RuntimeException('Ollama chat error: '.$response->body());
        }

        $body = $response->json();

        return [
            'text' => $body['message']['content'] ?? '',
            'tokens_used' => ($body['prompt_eval_count'] ?? 0) + ($body['eval_count'] ?? 0),
            'model_used' => $model,
            'duration_ms' => $durationMs,
            'provider' => 'local',
        ];
    }

    public function listModels(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/api/tags");
            if ($response->successful()) {
                return $response->json('models', []);
            }
        } catch (\Throwable $e) {
            Log::warning('Could not list Ollama models: '.$e->getMessage());
        }

        return [];
    }
}
