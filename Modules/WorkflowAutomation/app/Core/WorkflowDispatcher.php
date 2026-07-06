<?php

namespace Modules\WorkflowAutomation\Core;

use Modules\WorkflowAutomation\Actions\ExecuteWorkflowAction;
use Modules\WorkflowAutomation\Data\TriggerPayload;

final class WorkflowDispatcher
{
    public static function fire(TriggerPayload $payload): void
    {
        try {
            $registry  = app(TriggerRegistry::class);
            $workflows = $registry->matchingWorkflows($payload);

            foreach ($workflows as $workflow) {
                $runId = (string) \Str::uuid();
                ExecuteWorkflowAction::dispatch($workflow->id, $payload, $runId)
                    ->onQueue(config('workflow_automation.queue', 'workflows'));
            }
        } catch (\Throwable $e) {
            logger()->error('[Workflow] Dispatcher failed', [
                'trigger' => $payload->triggerType,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public static function fireAfterCommit(TriggerPayload $payload): void
    {
        \DB::afterCommit(fn() => self::fire($payload));
    }
}
