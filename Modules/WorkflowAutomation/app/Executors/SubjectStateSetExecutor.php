<?php

namespace Modules\WorkflowAutomation\Executors;

use Modules\WorkflowAutomation\Contracts\ActionExecutor;
use Modules\WorkflowAutomation\Data\ActionResult;
use Modules\WorkflowAutomation\Data\TriggerPayload;
use Modules\WorkflowAutomation\Models\WorkflowStep;
use Modules\WorkflowAutomation\Services\WorkflowEntityStateService;

/**
 * Executor for subject.state_set — directly sets entity state via the State Machine service.
 *
 * action_config:
 *   model   string  Entity type (e.g. 'Lead', 'Invoice')
 *   state   string  Target state_key
 */
class SubjectStateSetExecutor implements ActionExecutor
{
    public function __construct(private readonly WorkflowEntityStateService $stateService) {}

    public function type(): string { return 'subject.state_set'; }
    public function label(): string { return 'Subject — Set State'; }
    public function module(): string { return 'Subject'; }
    public function stepConfigFields(): array
    {
        return [
            ['key' => 'model', 'label' => 'Loại đối tượng', 'type' => 'text', 'required' => true,
             'hint' => 'VD: Lead, Invoice, Employee (tên class model)'],
            ['key' => 'state', 'label' => 'Trạng thái đích', 'type' => 'text', 'required' => true,
             'hint' => 'VD: qualified, approved, terminated (state_key)'],
        ];
    }

    public function supportedTypes(): array
    {
        return ['subject.state_set'];
    }

    public function execute(WorkflowStep $step, TriggerPayload $payload): ActionResult
    {
        $start  = now();
        $config = $step->action_config ?? [];

        $entityType = $config['model'] ?? $payload->subjectType;
        $stateKey   = $config['state'] ?? null;

        if (!$entityType || !$stateKey || !$payload->subjectId) {
            return ActionResult::fail('subject.state_set: model, state, and subject_id are required', 0);
        }

        try {
            $this->stateService->setStateDirectly(
                entityType: $entityType,
                entityId:   $payload->subjectId,
                stateKey:   $stateKey,
                actorId:    $payload->actorId,
                orgId:      $payload->organizationId,
            );

            $ms = (int) $start->diffInMilliseconds(now());
            return ActionResult::ok($ms, ['state' => $stateKey]);
        } catch (\Throwable $e) {
            $ms = (int) $start->diffInMilliseconds(now());
            return ActionResult::fail($e->getMessage(), $ms);
        }
    }
}
