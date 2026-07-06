<?php

namespace Modules\WorkflowAutomation\Executors;

use Illuminate\Support\Str;
use Modules\WorkflowAutomation\Contracts\ActionExecutor;
use Modules\WorkflowAutomation\Data\ActionResult;
use Modules\WorkflowAutomation\Data\TriggerPayload;
use Modules\WorkflowAutomation\Models\WorkflowStep;
use Modules\WorkflowAutomation\Models\WorkflowUserTask;

/**
 * Executor for user_task.approve, user_task.form, user_task.review.
 * Creates a workflow_user_tasks record and signals the engine to pause.
 *
 * action_config keys:
 *   assignee          string  "role:cfo" | "user:{actor.id}"
 *   title             string  Task title (supports template tokens)
 *   description       string  Task description
 *   allowed_decisions array   ["approve","reject"]
 *   due_hours         int     Deadline in hours from now
 *   on_timeout        string  "fail" | "continue" | "escalate"
 *   form_fields       array   Field definitions for user_task.form
 *   output_context_key string Key to store form response in RunContext
 */
class UserTaskExecutor implements ActionExecutor
{
    public function type(): string { return 'user_task.approve'; }
    public function label(): string { return 'User Task — Phê duyệt'; }
    public function module(): string { return 'UserTask'; }
    public function stepConfigFields(): array
    {
        return [
            ['key' => 'assignee',          'label' => 'Người/nhóm phê duyệt', 'type' => 'text', 'required' => true,
             'hint' => '"role:cfo" hoặc "user:{actor.id}" hoặc "role:sales_manager"'],
            ['key' => 'title',             'label' => 'Tiêu đề task',          'type' => 'text', 'required' => true,
             'hint' => 'Hỗ trợ token: {subject.id}, {ctx.score}, {extra.name}'],
            ['key' => 'description',       'label' => 'Mô tả chi tiết',        'type' => 'textarea'],
            ['key' => 'allowed_decisions', 'label' => 'Lựa chọn cho phép',    'type' => 'text',
             'hint' => 'Dùng | phân cách. VD: approve|reject|more_info'],
            ['key' => 'due_hours',         'label' => 'Hạn xử lý (giờ)',      'type' => 'number',
             'hint' => 'VD: 24 (1 ngày), 48 (2 ngày), 0 = không hạn'],
            ['key' => 'on_timeout',        'label' => 'Khi quá hạn',          'type' => 'select',
             'options' => [
                 ['value' => 'fail',     'label' => 'Đánh dấu thất bại'],
                 ['value' => 'continue', 'label' => 'Tự động tiếp tục (SLA)'],
                 ['value' => 'escalate', 'label' => 'Escalate lên cấp trên'],
             ]],
        ];
    }

    public function supportedTypes(): array
    {
        return ['user_task.approve', 'user_task.form', 'user_task.review'];
    }

    public function execute(WorkflowStep $step, TriggerPayload $payload): ActionResult
    {
        $config = $step->action_config ?? [];

        $assigneeId   = null;
        $assigneeRole = null;
        $assigneeStr  = $config['assignee'] ?? '';

        if (str_starts_with($assigneeStr, 'role:')) {
            $assigneeRole = substr($assigneeStr, 5);
        } elseif (str_starts_with($assigneeStr, 'user:')) {
            $userId = substr($assigneeStr, 5);
            // Resolve {actor.id} template if needed
            $assigneeId = is_numeric($userId) ? (int) $userId : $payload->actorId;
        }

        $dueAt = isset($config['due_hours'])
            ? now()->addHours((int) $config['due_hours'])
            : null;

        $formConfig = null;
        if (($step->action_type === 'user_task.form') && !empty($config['form_fields'])) {
            $formConfig = $config['form_fields'];
        }

        $allowed = $config['allowed_decisions'] ?? ['approve', 'reject'];

        WorkflowUserTask::create([
            'task_token'        => (string) Str::uuid(),
            'execution_id'      => $payload->extra['__execution_id'] ?? 0,
            'step_id'           => $step->id,
            'workflow_id'       => $step->workflow_id,
            'organization_id'   => $payload->organizationId,
            'assignee_id'       => $assigneeId,
            'assignee_role'     => $assigneeRole,
            'title'             => $config['title'] ?? 'Nhiệm vụ chờ phê duyệt',
            'description'       => $config['description'] ?? null,
            'context_snapshot'  => $payload->toContext(),
            'form_config'       => $formConfig,
            'allowed_decisions' => $allowed,
            'due_at'            => $dueAt,
            'on_timeout'        => $config['on_timeout'] ?? 'fail',
            'status'            => WorkflowUserTask::STATUS_PENDING,
            'created_at'        => now(),
        ]);

        // Signal engine to pause — engine detects step_type=2 and sets execution to waiting
        return ActionResult::success(null, 0);
    }
}
