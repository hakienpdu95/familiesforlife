<?php

namespace Modules\WorkflowAutomation\Executors;

use Modules\WorkflowAutomation\Contracts\ActionExecutor;
use Modules\WorkflowAutomation\Data\ActionResult;
use Modules\WorkflowAutomation\Data\TriggerPayload;
use Modules\WorkflowAutomation\Models\WorkflowStep;

/**
 * Executor for flow.log — writes a debug log entry without side effects.
 *
 * action_config:
 *   message  string  Message to log (supports template tokens)
 *   level    string  'debug' | 'info' | 'warning' (default: info)
 */
class FlowLogExecutor implements ActionExecutor
{
    public function type(): string { return 'flow.log'; }
    public function label(): string { return 'Flow Log — Ghi log'; }
    public function module(): string { return 'Control'; }
    public function stepConfigFields(): array
    {
        return [
            ['key' => 'message', 'label' => 'Thông điệp log', 'type' => 'text', 'required' => true,
             'hint' => 'Hỗ trợ token: {ctx.score}, {actor.name}, {subject.id}'],
            ['key' => 'level',   'label' => 'Mức độ',         'type' => 'select',
             'options' => [
                 ['value' => 'info',    'label' => 'Info'],
                 ['value' => 'debug',   'label' => 'Debug'],
                 ['value' => 'warning', 'label' => 'Warning'],
             ]],
        ];
    }

    public function supportedTypes(): array
    {
        return ['flow.log'];
    }

    public function execute(WorkflowStep $step, TriggerPayload $payload): ActionResult
    {
        $start   = now();
        $config  = $step->action_config ?? [];
        $message = $config['message'] ?? "(no message)";
        $level   = in_array($config['level'] ?? 'info', ['debug', 'info', 'warning'], true)
                   ? ($config['level'] ?? 'info')
                   : 'info';

        logger()->{$level}("[WorkflowAutomation] flow.log — step #{$step->id}", [
            'workflow_id' => $step->workflow_id,
            'step_key'    => $step->step_key,
            'message'     => $message,
        ]);

        return ActionResult::ok((int) $start->diffInMilliseconds(now()), ['message' => $message]);
    }
}
