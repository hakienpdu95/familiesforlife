<?php

namespace Modules\WorkflowAutomation\Executors;

use Modules\WorkflowAutomation\Contracts\ActionExecutor;
use Modules\WorkflowAutomation\Data\ActionResult;
use Modules\WorkflowAutomation\Data\TriggerPayload;
use Modules\WorkflowAutomation\Models\WorkflowStep;
use Modules\WorkflowAutomation\Notifications\WorkflowNotification;

class SendNotificationExecutor implements ActionExecutor
{
    public function type(): string   { return 'notification.send'; }
    public function label(): string  { return 'Gửi thông báo nội bộ'; }
    public function module(): string { return 'Core'; }

    public function stepConfigFields(): array
    {
        return [
            ['key' => 'notif_target', 'label' => 'Gửi đến',  'type' => 'text',
             'hint' => 'actor | admin | user:{id} | role:sales'],
            ['key' => 'notif_title',  'label' => 'Tiêu đề',  'type' => 'text'],
            ['key' => 'notif_body',   'label' => 'Nội dung', 'type' => 'textarea',
             'hint' => 'Template: {actor.email} đạt {extra.overall_score}%'],
        ];
    }

    public function execute(WorkflowStep $step, TriggerPayload $payload): ActionResult
    {
        $start  = microtime(true);
        $config = $step->action_config ?? [];
        try {
            $target = $config['notif_target'] ?? $step->notif_target ?? 'admin';
            $title  = $payload->render($config['notif_title'] ?? $step->notif_title ?? '');
            $body   = $payload->render($config['notif_body']  ?? $step->notif_body  ?? '');

            $users = $this->resolveTargetUsers($target, $payload);
            foreach ($users as $user) {
                if ($user) {
                    $user->notify(new WorkflowNotification($title, $body));
                }
            }

            return ActionResult::ok(
                (int) ((microtime(true) - $start) * 1000),
                ['recipients' => count(array_filter($users))],
            );
        } catch (\Throwable $e) {
            return ActionResult::fail($e->getMessage(), (int) ((microtime(true) - $start) * 1000));
        }
    }

    private function resolveTargetUsers(string $target, TriggerPayload $payload): array
    {
        return match(true) {
            $target === 'actor' && $payload->actorId
                => [\App\Models\User::find($payload->actorId)],
            $target === 'admin'
                => \App\Models\User::role('system_admin')->get()->all(),
            str_starts_with($target, 'user:')
                => [\App\Models\User::find((int) substr($target, 5))],
            str_starts_with($target, 'role:')
                => \App\Models\User::role(substr($target, 5))->get()->all(),
            default => [],
        };
    }
}
