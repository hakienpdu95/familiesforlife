<?php

namespace Modules\AiCopilot\Actions;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\AiCopilot\Exceptions\QuotaExceededException;
use Modules\AiCopilot\Jobs\ProcessAiRequestJob;
use Modules\AiCopilot\Models\AiRequest;

class RetryAiRequestAction
{
    use AsAction;

    public function handle(AiRequest $original): AiRequest
    {
        if (!$original->isFailed()) {
            throw new \RuntimeException('Only failed requests can be retried.');
        }

        if (org_quota('quota.ai_requests') <= 0) {
            throw new QuotaExceededException('quota.ai_requests');
        }

        // Create a new AiRequest (original is immutable after failure)
        $retried = AiRequest::create([
            'uuid'             => Str::uuid(),
            'organization_id'  => $original->organization_id,
            'user_id'          => auth()->id() ?? $original->user_id,
            'agent_id'         => $original->agent_id,
            'prompt_id'        => $original->prompt_id,
            'subject_type'     => $original->subject_type,
            'subject_id'       => $original->subject_id,
            'rendered_prompt'  => $original->rendered_prompt,
            'input_variables'  => $original->input_variables,
            'provider'         => $original->provider,
            'model'            => $original->model,
            'status'           => 'pending',
            'queued_at'        => now(),
        ]);

        ProcessAiRequestJob::dispatch($retried->id)
            ->onQueue('ai')
            ->afterCommit();

        ActivityLogger::info('ai_copilot', 'request_retried', $retried, [
            'original_uuid' => $original->uuid,
            'agent_id'      => $original->agent_id,
        ]);

        return $retried;
    }
}
