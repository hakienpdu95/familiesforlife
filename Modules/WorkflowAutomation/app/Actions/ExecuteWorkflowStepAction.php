<?php

namespace Modules\WorkflowAutomation\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\WorkflowAutomation\Core\ActionRegistry;
use Modules\WorkflowAutomation\Data\TriggerPayload;
use Modules\WorkflowAutomation\Models\WorkflowStep;

class ExecuteWorkflowStepAction
{
    use AsAction;

    public string $jobQueue   = 'workflows';
    public int    $jobTries   = 3;
    public array  $jobBackoff = [30, 120, 600];

    public function __construct(
        private readonly ActionRegistry $actions,
    ) {}

    public function handle(int $stepId, TriggerPayload $payload, int $executionId = 0): void
    {
        $step = WorkflowStep::find($stepId);
        if (!$step) return;

        $executor = $this->actions->get($step->action_type);
        if (!$executor) {
            logger()->warning('[Workflow] Unknown action type in delayed step', [
                'action_type' => $step->action_type,
            ]);
            $this->updateStepLog($executionId, $stepId, 3, 'Unknown action type', 0);
            return;
        }

        $start  = microtime(true);
        $result = $executor->execute($step, $payload);
        $ms     = (int) ((microtime(true) - $start) * 1000);

        $this->updateStepLog(
            $executionId,
            $stepId,
            $result->success ? 1 : 3,
            $result->errorMessage,
            $ms,
        );

        if (!$result->success) {
            logger()->error('[Workflow] Delayed step failed', [
                'step_id'      => $stepId,
                'execution_id' => $executionId,
                'action_type'  => $step->action_type,
                'error'        => $result->errorMessage,
            ]);
        }
    }

    private function updateStepLog(int $executionId, int $stepId, int $status, ?string $error, int $ms): void
    {
        if ($executionId === 0) return;

        \DB::table('workflow_execution_steps')
            ->where('execution_id', $executionId)
            ->where('step_id', $stepId)
            ->update([
                'status'        => $status,
                'error_message' => $error ? substr($error, 0, 500) : null,
                'duration_ms'   => $ms,
                'executed_at'   => now()->format('Y-m-d H:i:s.v'),
                'attempts'      => \DB::raw('attempts + 1'),
            ]);
    }
}
