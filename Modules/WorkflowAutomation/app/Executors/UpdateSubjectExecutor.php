<?php

namespace Modules\WorkflowAutomation\Executors;

use Modules\WorkflowAutomation\Contracts\ActionExecutor;
use Modules\WorkflowAutomation\Core\SubjectRegistry;
use Modules\WorkflowAutomation\Data\ActionResult;
use Modules\WorkflowAutomation\Data\TriggerPayload;
use Modules\WorkflowAutomation\Models\WorkflowStep;

class UpdateSubjectExecutor implements ActionExecutor
{
    public function __construct(
        private readonly SubjectRegistry $subjects,
    ) {}

    public function type(): string   { return 'subject.update'; }
    public function label(): string  { return 'Cập nhật dữ liệu'; }
    public function module(): string { return 'Core'; }

    public function stepConfigFields(): array
    {
        $subjectOptions = collect($this->subjects->all())
            ->map(fn($s, $type) => ['value' => $type, 'label' => $s['label']])
            ->values()
            ->all();

        return [
            ['key' => 'update_model', 'label' => 'Đối tượng', 'type' => 'select',
             'options_dynamic' => $subjectOptions],
            ['key' => 'update_field', 'label' => 'Field',     'type' => 'select',
             'options_from_model' => true],
            ['key' => 'update_value', 'label' => 'Giá trị',  'type' => 'text',
             'hint' => 'Template: {extra.band_code} hoặc giá trị cố định'],
        ];
    }

    public function execute(WorkflowStep $step, TriggerPayload $payload): ActionResult
    {
        $start      = microtime(true);
        $ac         = $step->action_config ?? [];
        try {
            $subjectType = $ac['update_model'] ?? $step->update_model;
            $config      = $this->subjects->get($subjectType);
            if (!$config) return ActionResult::fail("Unknown subject type: {$subjectType}");

            $model = $this->subjects->resolve($subjectType, $payload);
            if (!$model) return ActionResult::fail("Cannot resolve {$subjectType} from payload");

            $field         = $ac['update_field'] ?? $step->update_field;
            $allowedFields = array_column($config['updatableFields'], 'field');
            if (!in_array($field, $allowedFields)) {
                return ActionResult::fail("Field '{$field}' not updatable on {$subjectType}");
            }

            $value = $payload->render($ac['update_value'] ?? $step->update_value ?? '');
            $model->update([$field => $value]);

            return ActionResult::ok(
                (int) ((microtime(true) - $start) * 1000),
                ['updated' => "{$subjectType}.{$field} = {$value}"],
            );
        } catch (\Throwable $e) {
            return ActionResult::fail($e->getMessage(), (int) ((microtime(true) - $start) * 1000));
        }
    }
}
