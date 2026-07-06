<?php

namespace Modules\WorkflowAutomation\Executors;

use Modules\WorkflowAutomation\Contracts\ActionExecutor;
use Modules\WorkflowAutomation\Data\ActionResult;
use Modules\WorkflowAutomation\Data\TriggerPayload;
use Modules\WorkflowAutomation\Models\WorkflowStep;

/**
 * Executor for ai.call, ai.classify, ai.summarize, ai.image action types.
 *
 * action_config example:
 *   { "user_prompt": "...", "output_format": "json", "model": "claude-sonnet-4-6" }
 */
class AiCallExecutor implements ActionExecutor
{
    public function type(): string { return 'ai.call'; }
    public function label(): string { return 'AI — Gọi LLM'; }
    public function module(): string { return 'AI'; }
    public function stepConfigFields(): array
    {
        return [
            ['key' => 'user_prompt',   'label' => 'Prompt AI',       'type' => 'textarea', 'required' => true,
             'hint' => 'Hỗ trợ token: {extra.name}, {ctx.score}, {actor.email}, {subject.id}'],
            ['key' => 'output_format', 'label' => 'Định dạng output', 'type' => 'select',
             'options' => [['value' => 'text', 'label' => 'Text tự do'], ['value' => 'json', 'label' => 'JSON (dùng ctx.KEY)']]],
            ['key' => 'model',         'label' => 'Model',            'type' => 'text',
             'hint' => 'Mặc định: claude-sonnet-4-6', 'required' => false],
        ];
    }

    public function supportedTypes(): array
    {
        return ['ai.call', 'ai.classify', 'ai.summarize', 'ai.image'];
    }

    public function execute(WorkflowStep $step, TriggerPayload $payload): ActionResult
    {
        $config = $step->action_config ?? [];
        $prompt = $config['user_prompt'] ?? '';

        if (empty($prompt)) {
            return ActionResult::failure('ai.call: user_prompt is required', 0);
        }

        $start = now();

        try {
            // Integrate with Anthropic SDK / configured AI provider
            // For now: emit log and return success placeholder — real implementation
            // calls the AI provider and parses the response per output_format.
            logger()->info('[WorkflowAutomation] AiCallExecutor', [
                'step_id'     => $step->id,
                'action_type' => $step->action_type,
                'prompt'      => substr($prompt, 0, 200),
            ]);

            $ms = (int) $start->diffInMilliseconds(now());
            return ActionResult::success(['ai_response' => null, 'prompt' => $prompt], $ms);
        } catch (\Throwable $e) {
            $ms = (int) $start->diffInMilliseconds(now());
            return ActionResult::failure($e->getMessage(), $ms);
        }
    }
}
