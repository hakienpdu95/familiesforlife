<?php

namespace Modules\AiCopilot\Actions;

use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\AiCopilot\Exceptions\FeatureNotAvailableException;
use Modules\AiCopilot\Exceptions\QuotaExceededException;
use Modules\AiCopilot\Jobs\ProcessAiRequestJob;
use Modules\AiCopilot\Models\AiRequest;

class ExecuteAiTaskAction
{
    use AsAction;

    /**
     * @param  string     $agentSlug   e.g. 'sop.step_draft'
     * @param  array      $variables   e.g. ['step_title' => '...']
     * @param  Model|null $subject     Polymorphic subject (SopStep, KpiGoal…)
     * @param  bool       $forceSync   Force synchronous dispatch regardless of agent setting
     */
    public function handle(
        string  $agentSlug,
        array   $variables,
        ?Model  $subject    = null,
        bool    $forceSync  = false,
    ): AiRequest {
        $org   = TenantContext::resolve();
        $user  = auth()->user();

        $this->checkGates($org);

        $agent  = \Modules\AiCopilot\Models\AiAgent::findBySlug($agentSlug, $org->id);
        $prompt = $agent->defaultPrompt();

        $rendered = $this->renderTemplate($prompt->user_template, $variables);

        $aiRequest = AiRequest::create([
            'uuid'            => Str::uuid()->toString(),
            'organization_id' => $org->id,
            'user_id'         => $user->id,
            'agent_id'        => $agent->id,
            'prompt_id'       => $prompt->id,
            'subject_type'    => $subject ? get_class($subject) : null,
            'subject_id'      => $subject?->getKey(),
            'rendered_prompt' => $rendered,
            'input_variables' => $variables,
            'provider'        => $agent->provider,
            'model'           => $agent->model,
            'status'          => 'pending',
            'queued_at'       => now(),
        ]);

        if ($agent->sync_mode || $forceSync) {
            ProcessAiRequestJob::dispatchSync($aiRequest->id);
        } else {
            ProcessAiRequestJob::dispatch($aiRequest->id)
                ->onQueue('ai')
                ->afterCommit();
        }

        return $aiRequest->fresh();
    }

    private function checkGates(Organization $org): void
    {
        if (!org_can('module.ai')) {
            throw new FeatureNotAvailableException('module.ai');
        }

        if (org_quota('quota.ai_requests') <= 0) {
            throw new QuotaExceededException('quota.ai_requests');
        }

        if (org_quota('quota.ai_tokens') <= 0) {
            throw new QuotaExceededException('quota.ai_tokens');
        }
    }

    private function renderTemplate(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace("{{{$key}}}", e((string) $value), $template);
        }
        return $template;
    }
}
