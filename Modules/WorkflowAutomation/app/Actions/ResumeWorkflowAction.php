<?php

namespace Modules\WorkflowAutomation\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\WorkflowAutomation\Core\ActionRegistry;
use Modules\WorkflowAutomation\Core\RunContext;
use Modules\WorkflowAutomation\Enums\StepStatus;
use Modules\WorkflowAutomation\Enums\StepType;
use Modules\WorkflowAutomation\Enums\WorkflowStatus;
use Modules\WorkflowAutomation\Models\WorkflowExecution;
use Modules\WorkflowAutomation\Models\WorkflowStep;
use Modules\WorkflowAutomation\Models\WorkflowUserTask;

/**
 * Resume a workflow execution that was paused at a user_task step.
 *
 * Called by WorkflowUserTaskController after user responds to a task:
 *   ResumeWorkflowAction::dispatch($executionId, $decision, $formResponse, $completedBy);
 *
 * Flow (§9.2):
 *   1. Load execution + workflow + remaining steps
 *   2. Rebuild RunContext from execution.run_context
 *   3. Inject task.decision / task.form / task.comment into RunContext
 *   4. Run steps that come after the waiting step
 *   5. Finalize execution status
 */
class ResumeWorkflowAction
{
    use AsAction;

    public string $jobQueue   = 'workflows';
    public int    $jobTries   = 3;
    public array  $jobBackoff = [10, 60, 300];
    public int    $jobTimeout = 120;

    public function __construct(
        private readonly ActionRegistry $actions,
    ) {}

    public function handle(
        int     $executionId,
        string  $decision,
        ?array  $formResponse,
        int     $completedBy,
        ?string $comment = null,
    ): void {
        $execution = WorkflowExecution::with(['workflow.steps', 'workflow.stepGroups', 'workflow.variables'])
            ->find($executionId);

        if (!$execution) return;

        // Only resume from WaitingApproval
        if ($execution->status !== WorkflowStatus::WaitingApproval->value) return;

        $workflow = $execution->workflow;
        if (!$workflow) return;

        // Rebuild RunContext from persisted state
        $ctx = RunContext::fromArray((array) json_decode($execution->run_context ?? '{}', true))
            ->withVars($workflow->variablesMap());

        // Inject task response into context (§9.2)
        $ctx->put('task.decision', $decision);
        $ctx->put('task.comment',  $comment);
        $ctx->put('task.form',     $formResponse ?? []);

        // Find the step_id of the waiting step from execution_steps
        $waitingStepId = \DB::table('workflow_execution_steps')
            ->where('execution_id', $executionId)
            ->where('status', StepStatus::Waiting->value)
            ->orderByDesc('id')
            ->value('step_id');

        // Determine steps to run: all steps that come AFTER the waiting step (by sort_order)
        $waitingStep = $waitingStepId ? WorkflowStep::find($waitingStepId) : null;
        $waitingOrder = $waitingStep?->sort_order ?? 0;

        $remainingSteps = $workflow->steps
            ->where('sort_order', '>', $waitingOrder)
            ->sortBy('sort_order');

        // Build the payload from the saved execution context
        $payloadContext = (array) json_decode($execution->context ?? '{}', true);
        $payload = \Modules\WorkflowAutomation\Data\TriggerPayload::make(
            triggerType:  $execution->trigger_type,
            sourceModule: $execution->source_module ?? 'Core',
            overrides: [
                'organizationId'    => $execution->organization_id,
                'actorId'           => $execution->actor_id,
                'actorEmail'        => $payloadContext['actor_email'] ?? null,
                'actorName'         => $payloadContext['actor_name'] ?? null,
                'subjectType'       => $execution->subject_type,
                'subjectId'         => $execution->subject_id,
                'subjectLabel'      => $payloadContext['subject_label'] ?? null,
                'extra'             => $payloadContext['extra'] ?? [],
                'subjectAttributes' => $payloadContext['subject_attributes'] ?? [],
            ],
        );

        $startedAt = now();
        $newLogs   = [];
        $halted    = false;
        $waiting   = false;

        foreach ($remainingSteps as $step) {
            if ($halted) {
                $newLogs[] = $this->stepLog($step, StepStatus::Halted, null, 0, null, 'halted_upstream');
                continue;
            }

            [$log, $stepHalted, $stepWaiting] = $this->executeStep($step, $payload, $ctx, $executionId);
            $newLogs[] = $log;

            if ($stepHalted) $halted = true;
            if ($stepWaiting) { $waiting = true; break; }
        }

        // Persist new step logs
        foreach ($newLogs as &$log) {
            $log['execution_id'] = $executionId;
        }
        if (!empty($newLogs)) {
            \DB::table('workflow_execution_steps')->insert($newLogs);
        }

        // Recompute final counts from ALL steps (including those logged before pause)
        $allLogs = \DB::table('workflow_execution_steps')
            ->where('execution_id', $executionId)
            ->get();

        $success   = $allLogs->where('status', StepStatus::Success->value)->count();
        $failed    = $allLogs->where('status', StepStatus::Failed->value)->count();
        $scheduled = $allLogs->where('status', StepStatus::Scheduled->value)->count();
        $skipped   = $allLogs->where('status', StepStatus::Skipped->value)->count();
        $haltedCnt = $allLogs->where('status', StepStatus::Halted->value)->count();
        $waitingCnt= $allLogs->where('status', StepStatus::Waiting->value)->count();

        $finalStatus = match(true) {
            $waiting                                              => WorkflowStatus::WaitingApproval,
            $halted                                               => WorkflowStatus::Halted,
            $failed === 0                                         => WorkflowStatus::Pass,
            $success === 0 && $scheduled === 0                   => WorkflowStatus::Fail,
            default                                               => WorkflowStatus::Partial,
        };

        \DB::table('workflow_executions')->where('id', $executionId)->update([
            'status'          => $finalStatus->value,
            'steps_total'     => $allLogs->count(),
            'steps_success'   => $success,
            'steps_failed'    => $failed,
            'steps_scheduled' => $scheduled,
            'steps_skipped'   => $skipped,
            'steps_halted'    => $haltedCnt,
            'steps_waiting'   => $waitingCnt,
            'run_context'     => json_encode($ctx->all(), JSON_UNESCAPED_UNICODE),
            'finished_at'     => $waiting ? null : now()->format('Y-m-d H:i:s.v'),
        ]);
    }

    private function executeStep(WorkflowStep $step, $payload, RunContext $ctx, int $execId): array
    {
        $start = now();

        // Per-step condition
        if ($step->condition_config) {
            $condResult = $this->evaluateStepCondition($step->condition_config, $ctx, $payload);
            if (!$condResult) {
                return [
                    $this->stepLog($step, StepStatus::Skipped, null, 0, false, 'condition_failed'),
                    false, false,
                ];
            }
        } else {
            $condResult = null;
        }

        $stepTypeEnum = StepType::tryFrom($step->step_type ?? 1) ?? StepType::Automated;

        if ($stepTypeEnum === StepType::UserTask) {
            $executor = $this->actions->get($step->action_type);
            if ($executor) {
                $payload = new \Modules\WorkflowAutomation\Data\TriggerPayload(
                    ...(array_merge((array) $payload, ['extra' => array_merge($payload->extra, ['__execution_id' => $execId])]))
                );
                $executor->execute($step, $payload);
            }
            $ms = (int) $start->diffInMilliseconds(now());
            return [$this->stepLog($step, StepStatus::Waiting, null, $ms, $condResult), false, true];
        }

        $executor = $this->actions->get($step->action_type);
        if (!$executor) {
            $ms = (int) $start->diffInMilliseconds(now());
            return [$this->stepLog($step, StepStatus::Failed, "Unknown action: {$step->action_type}", $ms, $condResult), (bool) $step->halt_on_fail, false];
        }

        $result = $executor->execute($step, $payload);
        $ms     = (int) $start->diffInMilliseconds(now());

        if ($result->success) {
            if ($step->step_output_key && $result->output !== null) {
                $ctx->put($step->step_output_key, $result->output);
            }
            return [$this->stepLog($step, StepStatus::Success, null, $ms, $condResult, null, $result->output), false, false];
        }

        return [$this->stepLog($step, StepStatus::Failed, $result->errorMessage, $ms, $condResult), (bool) $step->halt_on_fail, false];
    }

    private function evaluateStepCondition(array $config, RunContext $ctx, $payload): bool
    {
        $match      = strtoupper($config['match'] ?? 'ALL');
        $conditions = $config['conditions'] ?? [];

        if (empty($conditions)) return true;

        $results = array_map(function ($cond) use ($ctx, $payload) {
            $field    = $cond['field'] ?? '';
            $operator = $cond['operator'] ?? '=';
            $expected = $cond['value'] ?? null;
            $type     = $cond['type'] ?? 'string';

            $actual = match(true) {
                str_starts_with($field, 'ctx.')  => $ctx->get(substr($field, 4)),
                str_starts_with($field, 'task.') => $ctx->get($field),
                str_starts_with($field, 'var.')  => $ctx->get($field),
                default                          => $payload->resolve($field),
            };

            $expected = match($type) {
                'integer' => (int) $expected,
                'float'   => (float) $expected,
                'boolean' => in_array(strtolower((string) $expected), ['true', '1', 'yes'], true),
                default   => $expected,
            };

            return match($operator) {
                '='            => $actual == $expected,
                '!='           => $actual != $expected,
                '>'            => is_numeric($actual) && $actual > $expected,
                '>='           => is_numeric($actual) && $actual >= $expected,
                '<'            => is_numeric($actual) && $actual < $expected,
                '<='           => is_numeric($actual) && $actual <= $expected,
                'is_empty'     => empty($actual),
                'is_not_empty' => !empty($actual),
                'contains'     => str_contains((string) $actual, (string) $expected),
                default        => false,
            };
        }, $conditions);

        return $match === 'ANY' ? in_array(true, $results, true) : !in_array(false, $results, true);
    }

    private function stepLog(
        WorkflowStep $step,
        StepStatus   $status,
        ?string      $error          = null,
        int          $ms             = 0,
        ?bool        $conditionResult = null,
        ?string      $skipReason     = null,
        ?array       $outputData     = null,
    ): array {
        return [
            'execution_id'     => 0,
            'step_id'          => $step->id,
            'sort_order'       => $step->sort_order,
            'action_type'      => $step->action_type,
            'status'           => $status->value,
            'error_message'    => $error ? substr($error, 0, 500) : null,
            'duration_ms'      => $ms,
            'attempts'         => 1,
            'condition_result' => $conditionResult,
            'skip_reason'      => $skipReason,
            'output_data'      => $outputData ? json_encode($outputData, JSON_UNESCAPED_UNICODE) : null,
            'executed_at'      => now()->format('Y-m-d H:i:s.v'),
            'created_at'       => now(),
        ];
    }

    public function jobFailed(\Throwable $e): void
    {
        logger()->error('[Workflow] ResumeWorkflowAction permanently failed', [
            'error' => $e->getMessage(),
        ]);
    }
}
