<?php

namespace App\Jobs;

use App\Models\Agent\AgentCostRecord;
use App\Models\Agent\AgentExecution;
use App\Models\Agent\AgentProfile;
use App\Models\Agent\AgentToken;
use App\Services\Agent\LocalModelService;
use App\Services\Agent\OpenRouterService;
use App\Services\Agent\SkillRegistry;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AgentSkillJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(
        private readonly int $agentProfileId,
        private readonly string $skillSlug,
        private readonly array $input,
        private readonly ?int $triggeredBy,
        private readonly string $triggerType,
        private readonly ?int $executionId = null,
    ) {}

    public function handle(
        SkillRegistry $registry,
        LocalModelService $localModel,
        OpenRouterService $openRouter,
    ): void {
        $startTime = microtime(true);

        // Find or create execution record
        $execution = $this->executionId
            ? AgentExecution::find($this->executionId)
            : AgentExecution::create([
                'agent_profile_id' => $this->agentProfileId,
                'skill_slug' => $this->skillSlug,
                'triggered_by' => $this->triggeredBy,
                'trigger_type' => $this->triggerType,
                'input' => $this->input,
                'status' => 'queued',
            ]);

        if (! $execution) {
            return;
        }

        $execution->update(['status' => 'running']);

        // Rate-limit enforcement for token-triggered executions (soft enforcement)
        if ($this->triggerType === 'token' && $execution->triggered_by) {
            $token = AgentToken::find($execution->triggered_by);

            if ($token) {
                $cacheKey = "agent_token_rate:{$token->id}";
                $count = (int) Cache::get($cacheKey, 0);

                if ($count >= $token->rate_limit_per_minute) {
                    Log::warning("AgentToken {$token->id} exceeded rate limit ({$token->rate_limit_per_minute}/min). Proceeding anyway (soft enforcement).");
                }

                Cache::put($cacheKey, $count + 1, 60);
            }
        }

        try {
            $agent = AgentProfile::findOrFail($this->agentProfileId);
            $skill = $registry->find($this->skillSlug);

            if (! $skill) {
                throw new \RuntimeException("Skill '{$this->skillSlug}' not found.");
            }

            // Build the prompt
            $prompt = $this->buildPrompt($skill, $this->input);
            $config = $agent->provider_config ?? [];
            $model = $config['model'] ?? $this->defaultModel($skill, $agent->agent_type);
            $maxTokens = $config['max_tokens'] ?? ($skill['estimated_tokens'] ?? 1500) * 2;

            // Call the appropriate provider
            $result = match ($agent->agent_type) {
                'openrouter' => $openRouter->chat($model, [['role' => 'user', 'content' => $prompt]], ['max_tokens' => $maxTokens]),
                'local' => $localModel->generate($model, $prompt, ['max_tokens' => $maxTokens]),
                default => $localModel->generate($model, $prompt, ['max_tokens' => $maxTokens]),
            };

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Try to parse JSON from output
            $output = $this->parseOutput($result['text']);

            $execution->update([
                'status' => 'completed',
                'output' => $output,
                'tokens_used' => $result['tokens_used'] ?? null,
                'model_used' => $result['model_used'] ?? $model,
                'duration_ms' => $durationMs,
            ]);

            Log::info("AgentSkillJob completed: execution={$execution->id} skill={$this->skillSlug} tokens={$result['tokens_used']}");

            // Dispatch execution completion webhook
            try {
                app(WebhookService::class)->dispatch('agent.execution.completed', [
                    'execution_id' => $execution->id,
                    'agent_profile_id' => $this->agentProfileId,
                    'skill_slug' => $this->skillSlug,
                    'status' => 'completed',
                    'tokens_used' => $result['tokens_used'] ?? null,
                    'model_used' => $result['model_used'] ?? $model,
                    'duration_ms' => $durationMs,
                    'completed_at' => now()->toIso8601String(),
                ]);
            } catch (\Throwable $webhookError) {
                Log::error("AgentSkillJob webhook dispatch failed: execution={$execution->id}: ".$webhookError->getMessage());
            }

            $tokensUsed = $result['tokens_used'] ?? 0;
            $modelUsed = $result['model_used'] ?? $model;
            $tier = $skill['model_tier'] ?? 'standard';
            $costPerToken = match ($tier) {
                'fast' => 0.10 / 1_000_000,
                'powerful' => 3.00 / 1_000_000,
                default => 0.50 / 1_000_000,
            };
            $estimatedCost = $tokensUsed * $costPerToken;

            AgentCostRecord::create([
                'agent_profile_id' => $this->agentProfileId,
                'skill_slug' => $this->skillSlug,
                'model_used' => $modelUsed,
                'tokens_input' => (int) ($tokensUsed * 0.6),
                'tokens_output' => (int) ($tokensUsed * 0.4),
                'estimated_cost' => round($estimatedCost, 6),
                'currency' => 'USD',
                'recorded_at' => now(),
                'date_bucket' => now()->toDateString(),
                'created_at' => now(),
            ]);

        } catch (\Throwable $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $execution->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ]);
            Log::error("AgentSkillJob failed: execution={$execution->id} skill={$this->skillSlug}: ".$e->getMessage());
            throw $e;
        }
    }

    private function buildPrompt(array $skill, array $input): string
    {
        $instructions = $skill['instructions'] ?? '';
        $inputJson = json_encode($input, JSON_PRETTY_PRINT);

        return <<<PROMPT
{$instructions}

## Input Data

```json
{$inputJson}
```

Respond with valid JSON only. Do not include any explanation outside the JSON.
PROMPT;
    }

    private function defaultModel(array $skill, string $agentType): string
    {
        $tier = $skill['model_tier'] ?? 'standard';

        if ($agentType === 'openrouter') {
            return match ($tier) {
                'fast' => 'meta-llama/llama-3.1-8b-instruct:free',
                'standard' => 'meta-llama/llama-3.1-70b-instruct',
                'powerful' => 'anthropic/claude-3.5-sonnet',
                default => 'meta-llama/llama-3.1-8b-instruct:free',
            };
        }

        // Local (Ollama)
        return match ($tier) {
            'fast' => 'llama3.2:3b',
            'standard' => 'llama3.1:8b',
            'powerful' => 'llama3.1:70b',
            default => 'llama3.1:8b',
        };
    }

    private function parseOutput(string $text): array
    {
        $text = trim($text);

        // Try to extract JSON block
        if (preg_match('/```(?:json)?\s*(\{.*?\}|\[.*?\])\s*```/s', $text, $m)) {
            $text = $m[1];
        }

        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return is_array($decoded) ? $decoded : ['result' => $decoded];
        }

        return ['raw_output' => $text];
    }

    public function failed(\Throwable $e): void
    {
        Log::error('AgentSkillJob permanently failed: '.$e->getMessage());

        if ($this->executionId) {
            AgentExecution::where('id', $this->executionId)->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
