<?php

namespace Modules\AiCopilot\Jobs;

use App\Foundation\Jobs\TenantAwareJob;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\AiCopilot\Actions\RecordAiUsageAction;
use Modules\AiCopilot\Drivers\DTOs\AiCompletionRequest;
use Modules\AiCopilot\Models\AiRequest;
use Modules\AiCopilot\Services\AiDriverManager;

class ProcessAiRequestJob extends TenantAwareJob
{
    public int $tries   = 2;
    public int $timeout = 60;

    public function __construct(private readonly int $aiRequestId)
    {
        parent::__construct();
        $this->onQueue('ai');
    }

    public function handle(AiDriverManager $manager): void
    {
        $this->withTenant(function () use ($manager) {
            $aiRequest = AiRequest::findOrFail($this->aiRequestId);

            if ($aiRequest->status !== 'pending') {
                return;
            }

            $aiRequest->update(['status' => 'processing', 'started_at' => now()]);

            try {
                $agent  = $aiRequest->agent;
                $prompt = $aiRequest->prompt ?? $agent->defaultPrompt();
                $driver = $manager->driver($agent->provider);

                $result = $driver->complete(new AiCompletionRequest(
                    model:        $agent->model,
                    systemPrompt: $prompt->system_prompt,
                    userMessage:  $aiRequest->rendered_prompt,
                    temperature:  (float) $agent->temperature,
                    maxTokens:    (int) $agent->max_tokens,
                    timeoutSec:   (int) $agent->timeout_seconds,
                ));

                $aiRequest->update([
                    'status'        => 'done',
                    'ai_output'     => $result->content,
                    'finish_reason' => $result->finishReason,
                    'input_tokens'  => $result->inputTokens,
                    'output_tokens' => $result->outputTokens,
                    'total_tokens'  => $result->totalTokens,
                    'cost_usd'      => $result->costUsd,
                    'duration_ms'   => $result->durationMs,
                    'completed_at'  => now(),
                ]);

                RecordAiUsageAction::run($aiRequest);

                ActivityLogger::info('ai_copilot', 'task_executed', $aiRequest, [
                    'agent_slug'   => $agent->slug,
                    'total_tokens' => $result->totalTokens,
                    'duration_ms'  => $result->durationMs,
                ]);

            } catch (\Throwable $e) {
                $aiRequest->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at'  => now(),
                ]);

                ActivityLogger::error('ai_copilot', 'request_failed', $aiRequest, [
                    'agent_slug' => $aiRequest->agent?->slug ?? '?',
                    'error'      => $e->getMessage(),
                ]);

                if ($this->attempts() >= $this->tries) {
                    throw $e;
                }
            }
        });
    }

    public function failed(\Throwable $e): void
    {
        // failed() runs outside withTenant() — bypass global scope
        AiRequest::withoutTenant()
            ->where('id', $this->aiRequestId)
            ->where('status', 'processing')
            ->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
    }
}
