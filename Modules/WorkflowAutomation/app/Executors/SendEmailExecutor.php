<?php

namespace Modules\WorkflowAutomation\Executors;

use Modules\WorkflowAutomation\Contracts\ActionExecutor;
use Modules\WorkflowAutomation\Data\ActionResult;
use Modules\WorkflowAutomation\Data\TriggerPayload;
use Modules\WorkflowAutomation\Mail\WorkflowMail;
use Modules\WorkflowAutomation\Models\WorkflowStep;

class SendEmailExecutor implements ActionExecutor
{
    public function type(): string   { return 'email.send'; }
    public function label(): string  { return 'Gửi email'; }
    public function module(): string { return 'Core'; }

    public function stepConfigFields(): array
    {
        return [
            ['key' => 'email_to',       'label' => 'Gửi đến',       'type' => 'text',
             'hint' => 'Template: {actor.email} hoặc địa chỉ cố định'],
            ['key' => 'email_subject',  'label' => 'Tiêu đề',       'type' => 'text',
             'hint' => 'Template: Kết quả {extra.band_code}'],
            ['key' => 'email_template', 'label' => 'Blade template', 'type' => 'text'],
        ];
    }

    public function execute(WorkflowStep $step, TriggerPayload $payload): ActionResult
    {
        $start  = microtime(true);
        $config = $step->action_config ?? [];
        try {
            $to = $payload->render($config['email_to'] ?? $step->email_to ?? '');
            if (empty($to)) return ActionResult::fail('email_to empty after render');

            $emails = array_filter(
                array_map('trim', explode(',', $to)),
                fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL),
            );
            if (empty($emails)) return ActionResult::fail("No valid email: {$to}");

            $subject  = $payload->render($config['email_subject'] ?? $step->email_subject ?? '');
            $template = $config['email_template'] ?? $step->email_template ?? 'workflowautomation::emails.generic';

            \Mail::to($emails)->queue(new WorkflowMail($subject, $template, $payload));

            return ActionResult::ok((int) ((microtime(true) - $start) * 1000));
        } catch (\Throwable $e) {
            return ActionResult::fail($e->getMessage(), (int) ((microtime(true) - $start) * 1000));
        }
    }
}
