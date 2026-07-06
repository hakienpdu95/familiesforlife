<?php

namespace Modules\WorkflowAutomation\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\WorkflowAutomation\Core\ActionRegistry;
use Modules\WorkflowAutomation\Core\ConditionEvaluator;
use Modules\WorkflowAutomation\Core\CooldownGuard;
use Modules\WorkflowAutomation\Core\RunContext;
use Modules\WorkflowAutomation\Data\TriggerPayload;
use Modules\WorkflowAutomation\Enums\StepStatus;
use Modules\WorkflowAutomation\Enums\StepType;
use Modules\WorkflowAutomation\Enums\WorkflowStatus;
use Modules\WorkflowAutomation\Models\Workflow;
use Modules\WorkflowAutomation\Models\WorkflowStep;
use Modules\WorkflowAutomation\Models\WorkflowStepGroup;

class ExecuteWorkflowAction
{
    use AsAction;

    public string $jobQueue   = 'workflows';
    public int    $jobTries   = 3;
    public array  $jobBackoff = [10, 60, 300];
    public int    $jobTimeout = 120;

    public function __construct(
        private readonly ConditionEvaluator $evaluator,
        private readonly CooldownGuard      $cooldown,
        private readonly ActionRegistry     $actions,
    ) {}

    public function handle(int $workflowId, TriggerPayload $payload, string $runId): void
    {
        if (\DB::table('workflow_executions')->where('run_id', $runId)->exists()) {
            return;
        }

        $workflow = Workflow::with(['conditions', 'stepGroups', 'steps', 'variables'])->find($workflowId);
        if (!$workflow || !$workflow->is_active) return;

        $startedAt = now();

        if (!$this->cooldown->allow($workflow, $payload)) {
            $this->persist($workflow, $payload, $runId, WorkflowStatus::Skip, 'cooldown', null, [], $startedAt);
            return;
        }

        $condPass = $this->evaluator->evaluate($workflow, $payload);
        if (!$condPass) {
            $this->persist($workflow, $payload, $runId, WorkflowStatus::Skip, 'condition_failed', false, [], $startedAt);
            return;
        }

        // Create execution row first so user_task records can FK to it
        $execId = $this->insertExecution($workflow, $payload, $runId, WorkflowStatus::Scheduled, $startedAt);

        $ctx = RunContext::fromPayload($payload)->withVars($workflow->variablesMap());

        [$stepLogs, $finalStatus] = $this->runAllGroups($workflow, $payload, $ctx, $execId);

        $totalMs = (int) $startedAt->diffInMilliseconds(now());
        $this->finalizeExecution($execId, $workflow, $finalStatus, $stepLogs, $ctx, $totalMs);

        ActivityLogger::info('WorkflowAutomation', 'workflow_executed', null, [
            'workflow_id'  => $workflow->id,
            'workflow_name'=> $workflow->name,
            'run_id'       => $runId,
            'status'       => $finalStatus->value,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Group execution
    // ─────────────────────────────────────────────────────────────

    private function runAllGroups(
        Workflow       $workflow,
        TriggerPayload $payload,
        RunContext     $ctx,
        int            $execId,
    ): array {
        $stepLogs     = [];
        $halted       = false;
        $waiting      = false;
        $finalStatus  = WorkflowStatus::Pass;

        // Partition: steps with a group vs ungrouped steps (treated as sequential)
        $groups       = $workflow->stepGroups->sortBy('sort_order');
        $ungrouped    = $workflow->steps->whereNull('group_id')->sortBy('sort_order');

        if ($groups->isEmpty() && $ungrouped->isNotEmpty()) {
            // No groups defined — run all steps sequentially (v1 compatibility)
            [$logs, $halted, $waiting] = $this->runSequential($ungrouped, $payload, $ctx, $execId);
            $stepLogs = array_merge($stepLogs, $logs);
        } else {
            foreach ($groups as $group) {
                if ($halted || $waiting) break;

                $groupSteps = $workflow->steps
                    ->where('group_id', $group->id)
                    ->sortBy('sort_order');

                if ($groupSteps->isEmpty()) continue;

                // Delayed group — schedule and stop synchronous execution
                if (($group->delay_minutes ?? 0) > 0) {
                    foreach ($groupSteps as $step) {
                        $stepLogs[] = $this->stepLog($step, StepStatus::Scheduled, null, 0, null, 'scheduled');
                        ExecuteWorkflowStepAction::dispatch($step->id, $payload, $execId)
                            ->delay(now()->addMinutes($group->delay_minutes))
                            ->onQueue('workflows');
                    }
                    continue;
                }

                $mode = $group->execute_mode ?? WorkflowStepGroup::MODE_SEQUENTIAL;

                [$logs, $groupHalted, $groupWaiting] = match ($mode) {
                    WorkflowStepGroup::MODE_PARALLEL     => $this->runParallel($groupSteps, $payload, $ctx, $execId),
                    WorkflowStepGroup::MODE_PARALLEL_ANY => $this->runParallelAny($groupSteps, $payload, $ctx, $execId),
                    default                              => $this->runSequential($groupSteps, $payload, $ctx, $execId),
                };

                $stepLogs = array_merge($stepLogs, $logs);
                if ($groupHalted) $halted = true;
                if ($groupWaiting) $waiting = true;
            }

            // Ungrouped steps run after all groups (sequentially)
            if (!$halted && !$waiting && $ungrouped->isNotEmpty()) {
                [$logs, $h, $w] = $this->runSequential($ungrouped, $payload, $ctx, $execId);
                $stepLogs = array_merge($stepLogs, $logs);
                if ($h) $halted = true;
                if ($w) $waiting = true;
            }
        }

        // Determine final status
        $success   = count(array_filter($stepLogs, fn($s) => $s['status'] === StepStatus::Success->value));
        $failed    = count(array_filter($stepLogs, fn($s) => $s['status'] === StepStatus::Failed->value));
        $scheduled = count(array_filter($stepLogs, fn($s) => $s['status'] === StepStatus::Scheduled->value));

        $finalStatus = match(true) {
            $waiting                                                          => WorkflowStatus::WaitingApproval,
            $halted                                                           => WorkflowStatus::Halted,
            $scheduled > 0 && $success === 0 && $failed === 0                => WorkflowStatus::Scheduled,
            $failed === 0                                                     => WorkflowStatus::Pass,
            $success === 0 && $scheduled === 0                               => WorkflowStatus::Fail,
            default                                                           => WorkflowStatus::Partial,
        };

        return [$stepLogs, $finalStatus];
    }

    // ─────────────────────────────────────────────────────────────
    // Execution modes
    // ─────────────────────────────────────────────────────────────

    private function runSequential($steps, TriggerPayload $payload, RunContext $ctx, int $execId): array
    {
        $logs    = [];
        $halted  = false;
        $waiting = false;

        foreach ($steps as $step) {
            if ($halted) {
                $logs[] = $this->stepLog($step, StepStatus::Halted, null, 0, null, 'halted_upstream');
                continue;
            }

            [$log, $stepHalted, $stepWaiting] = $this->executeStep($step, $payload, $ctx, $execId);
            $logs[] = $log;

            if ($stepHalted) $halted = true;
            if ($stepWaiting) {
                $waiting = true;
                break;
            }
        }

        return [$logs, $halted, $waiting];
    }

    private function runParallel($steps, TriggerPayload $payload, RunContext $ctx, int $execId): array
    {
        $logs    = [];
        $halted  = false;
        $waiting = false;

        // Parallel: run all steps, collect all results (no halt propagation between siblings)
        foreach ($steps as $step) {
            [$log, , $stepWaiting] = $this->executeStep($step, $payload, $ctx, $execId);
            $logs[] = $log;
            if ($stepWaiting) $waiting = true;
        }

        // Halt only if ALL siblings failed AND one has halt_on_fail
        $allFailed    = !empty($logs) && count(array_filter($logs, fn($l) => $l['status'] === StepStatus::Failed->value)) === count($logs);
        $anyHaltOnFail = $steps->contains(fn($s) => $s->halt_on_fail);
        if ($allFailed && $anyHaltOnFail) $halted = true;

        return [$logs, $halted, $waiting];
    }

    private function runParallelAny($steps, TriggerPayload $payload, RunContext $ctx, int $execId): array
    {
        $logs    = [];
        $waiting = false;

        foreach ($steps as $step) {
            [$log, , $stepWaiting] = $this->executeStep($step, $payload, $ctx, $execId);
            $logs[] = $log;

            if ($stepWaiting) { $waiting = true; break; }

            // Stop on first success
            if ($log['status'] === StepStatus::Success->value) {
                // Mark remaining siblings as skipped
                foreach ($steps->slice($steps->search($step) + 1) as $remaining) {
                    $logs[] = $this->stepLog($remaining, StepStatus::Skipped, null, 0, null, 'parallel_skipped');
                }
                break;
            }
        }

        return [$logs, false, $waiting];
    }

    // ─────────────────────────────────────────────────────────────
    // Single step execution
    // ─────────────────────────────────────────────────────────────

    private function executeStep(WorkflowStep $step, TriggerPayload $payload, RunContext $ctx, int $execId): array
    {
        $start = now();

        // 1. Evaluate per-step condition_config
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

        // 2. Handle user_task steps (Human-in-the-Loop)
        $stepTypeEnum = StepType::tryFrom($step->step_type ?? 1) ?? StepType::Automated;

        if ($stepTypeEnum === StepType::UserTask) {
            $executor = $this->actions->get($step->action_type);
            if ($executor) {
                // Inject execution_id so the executor can FK the user_task record
                $payload = new TriggerPayload(
                    ...(array_merge((array) $payload, ['extra' => array_merge($payload->extra, ['__execution_id' => $execId])]))
                );
                $executor->execute($step, $payload);
            }

            $ms  = (int) $start->diffInMilliseconds(now());
            $log = $this->stepLog($step, StepStatus::Waiting, null, $ms, $condResult, null, null);
            return [$log, false, true]; // waiting=true → pause execution
        }

        // 3. Handle automated steps
        $executor = $this->actions->get($step->action_type);
        if (!$executor) {
            $ms  = (int) $start->diffInMilliseconds(now());
            $log = $this->stepLog($step, StepStatus::Failed, "Unknown action: {$step->action_type}", $ms, $condResult);
            return [$log, (bool) $step->halt_on_fail, false];
        }

        $result = $executor->execute($step, $payload);
        $ms     = (int) $start->diffInMilliseconds(now());

        if ($result->success) {
            // 4. Store output in RunContext
            if ($step->step_output_key && $result->output !== null) {
                $ctx->put($step->step_output_key, $result->output);
            }
            $log = $this->stepLog($step, StepStatus::Success, null, $ms, $condResult, null, $result->output);
            return [$log, false, false];
        }

        // 5. Failure — check halt_on_fail
        $log = $this->stepLog($step, StepStatus::Failed, $result->errorMessage, $ms, $condResult);
        return [$log, (bool) $step->halt_on_fail, false];
    }

    // ─────────────────────────────────────────────────────────────
    // Per-step condition evaluation (§9.1)
    // ─────────────────────────────────────────────────────────────

    private function evaluateStepCondition(array $config, RunContext $ctx, TriggerPayload $payload): bool
    {
        $match      = strtoupper($config['match'] ?? 'ALL');
        $conditions = $config['conditions'] ?? [];

        if (empty($conditions)) return true;

        $results = array_map(fn($c) => $this->checkStepCondition($c, $ctx, $payload), $conditions);

        return match($match) {
            'ANY'   => in_array(true, $results, true),
            default => !in_array(false, $results, true), // ALL
        };
    }

    private function checkStepCondition(array $cond, RunContext $ctx, TriggerPayload $payload): bool
    {
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

        return match ($operator) {
            '='            => $actual == $expected,
            '!='           => $actual != $expected,
            '>'            => is_numeric($actual) && $actual > $expected,
            '>='           => is_numeric($actual) && $actual >= $expected,
            '<'            => is_numeric($actual) && $actual < $expected,
            '<='           => is_numeric($actual) && $actual <= $expected,
            'in'           => in_array($actual, (array) $expected),
            'not_in'       => !in_array($actual, (array) $expected),
            'contains'     => str_contains((string) $actual, (string) $expected),
            'starts_with'  => str_starts_with((string) $actual, (string) $expected),
            'is_empty'     => empty($actual),
            'is_not_empty' => !empty($actual),
            default        => false,
        };
    }

    // ─────────────────────────────────────────────────────────────
    // Persistence helpers
    // ─────────────────────────────────────────────────────────────

    private function insertExecution(
        Workflow       $workflow,
        TriggerPayload $payload,
        string         $runId,
        WorkflowStatus $status,
        \Carbon\Carbon $startedAt,
    ): int {
        $workflow->increment('run_count');
        $workflow->update(['last_run_at' => now(), 'last_run_status' => $status->value]);

        return \DB::table('workflow_executions')->insertGetId([
            'workflow_id'      => $workflow->id,
            'organization_id'  => $payload->organizationId,
            'run_id'           => $runId,
            'trigger_type'     => $payload->triggerType,
            'source_module'    => $payload->sourceModule,
            'subject_type'     => $payload->subjectType,
            'subject_id'       => $payload->subjectId,
            'actor_id'         => $payload->actorId,
            'context'          => json_encode($payload->toContext(), JSON_UNESCAPED_UNICODE),
            'status'           => $status->value,
            'skip_reason'      => null,
            'condition_result' => null,
            'steps_total'      => 0,
            'steps_success'    => 0,
            'steps_failed'     => 0,
            'steps_scheduled'  => 0,
            'steps_skipped'    => 0,
            'steps_halted'     => 0,
            'steps_waiting'    => 0,
            'duration_ms'      => 0,
            'triggered_at'     => $payload->firedAt->format('Y-m-d H:i:s.v'),
            'executed_at'      => $startedAt->format('Y-m-d H:i:s.v'),
            'finished_at'      => null,
            'run_context'      => null,
            'created_at'       => now(),
        ]);
    }

    private function finalizeExecution(
        int            $execId,
        Workflow       $workflow,
        WorkflowStatus $status,
        array          $stepLogs,
        RunContext     $ctx,
        int            $totalMs,
    ): void {
        $success   = count(array_filter($stepLogs, fn($s) => $s['status'] === StepStatus::Success->value));
        $failed    = count(array_filter($stepLogs, fn($s) => $s['status'] === StepStatus::Failed->value));
        $scheduled = count(array_filter($stepLogs, fn($s) => $s['status'] === StepStatus::Scheduled->value));
        $skipped   = count(array_filter($stepLogs, fn($s) => $s['status'] === StepStatus::Skipped->value));
        $halted    = count(array_filter($stepLogs, fn($s) => $s['status'] === StepStatus::Halted->value));
        $waiting   = count(array_filter($stepLogs, fn($s) => $s['status'] === StepStatus::Waiting->value));

        \DB::table('workflow_executions')->where('id', $execId)->update([
            'status'          => $status->value,
            'steps_total'     => count($stepLogs),
            'steps_success'   => $success,
            'steps_failed'    => $failed,
            'steps_scheduled' => $scheduled,
            'steps_skipped'   => $skipped,
            'steps_halted'    => $halted,
            'steps_waiting'   => $waiting,
            'duration_ms'     => $totalMs,
            'run_context'     => json_encode($ctx->all(), JSON_UNESCAPED_UNICODE),
            'finished_at'     => now()->format('Y-m-d H:i:s.v'),
        ]);

        if (!empty($stepLogs)) {
            foreach ($stepLogs as &$log) {
                $log['execution_id'] = $execId;
            }
            \DB::table('workflow_execution_steps')->insert($stepLogs);
        }

        $workflow->update(['last_run_status' => $status->value]);
    }

    private function persist(
        Workflow       $workflow,
        TriggerPayload $payload,
        string         $runId,
        WorkflowStatus $status,
        ?string        $skipReason,
        ?bool          $conditionResult,
        array          $stepLogs,
        \Carbon\Carbon $startedAt,
        int            $totalMs   = 0,
        int            $scheduled = 0,
    ): void {
        $workflow->increment('run_count');
        $workflow->update(['last_run_at' => now(), 'last_run_status' => $status->value]);

        $execId = \DB::table('workflow_executions')->insertGetId([
            'workflow_id'      => $workflow->id,
            'organization_id'  => $payload->organizationId,
            'run_id'           => $runId,
            'trigger_type'     => $payload->triggerType,
            'source_module'    => $payload->sourceModule,
            'subject_type'     => $payload->subjectType,
            'subject_id'       => $payload->subjectId,
            'actor_id'         => $payload->actorId,
            'context'          => json_encode($payload->toContext(), JSON_UNESCAPED_UNICODE),
            'status'           => $status->value,
            'skip_reason'      => $skipReason,
            'condition_result' => $conditionResult,
            'steps_total'      => count($stepLogs),
            'steps_success'    => count(array_filter($stepLogs, fn($s) => $s['status'] === StepStatus::Success->value)),
            'steps_failed'     => count(array_filter($stepLogs, fn($s) => $s['status'] === StepStatus::Failed->value)),
            'steps_scheduled'  => $scheduled,
            'steps_skipped'    => 0,
            'steps_halted'     => 0,
            'steps_waiting'    => 0,
            'duration_ms'      => $totalMs,
            'triggered_at'     => $payload->firedAt->format('Y-m-d H:i:s.v'),
            'executed_at'      => $startedAt->format('Y-m-d H:i:s.v'),
            'finished_at'      => now()->format('Y-m-d H:i:s.v'),
            'run_context'      => null,
            'created_at'       => now(),
        ]);

        if ($execId && !empty($stepLogs)) {
            foreach ($stepLogs as &$log) {
                $log['execution_id'] = $execId;
            }
            \DB::table('workflow_execution_steps')->insert($stepLogs);
        }
    }

    private function stepLog(
        WorkflowStep $step,
        StepStatus   $status,
        ?string      $error,
        int          $ms,
        ?bool        $conditionResult = null,
        ?string      $skipReason      = null,
        ?array       $outputData      = null,
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
        logger()->error('[Workflow] ExecuteWorkflowAction permanently failed', [
            'error' => $e->getMessage(),
        ]);
    }
}
