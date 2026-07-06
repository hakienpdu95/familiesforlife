<?php

namespace Modules\WorkflowAutomation\Executors;

use Modules\WorkflowAutomation\Contracts\ActionExecutor;
use Modules\WorkflowAutomation\Data\ActionResult;
use Modules\WorkflowAutomation\Data\TriggerPayload;
use Modules\WorkflowAutomation\Models\WorkflowStep;

class CallWebhookExecutor implements ActionExecutor
{
    public function type(): string   { return 'webhook.call'; }
    public function label(): string  { return 'Gọi webhook ngoài'; }
    public function module(): string { return 'Core'; }

    public function stepConfigFields(): array
    {
        return [
            ['key' => 'webhook_url',    'label' => 'URL',         'type' => 'url'],
            ['key' => 'webhook_method', 'label' => 'Method',      'type' => 'select',
             'options' => [
                 ['value' => 2, 'label' => 'POST'],
                 ['value' => 3, 'label' => 'PUT'],
                 ['value' => 1, 'label' => 'GET'],
             ]],
            ['key' => 'webhook_secret', 'label' => 'HMAC secret', 'type' => 'password', 'required' => false],
        ];
    }

    public function execute(WorkflowStep $step, TriggerPayload $payload): ActionResult
    {
        $start  = microtime(true);
        $config = $step->action_config ?? [];
        try {
            $url = $payload->render($config['webhook_url'] ?? $step->webhook_url ?? '');
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return ActionResult::fail("Invalid URL: {$url}");
            }

            $body = json_encode([
                'workflow_trigger' => $payload->triggerType,
                'source_module'    => $payload->sourceModule,
                'organization_id'  => $payload->organizationId,
                'subject_type'     => $payload->subjectType,
                'subject_id'       => $payload->subjectId,
                'actor_email'      => $payload->actorEmail,
                'extra'            => $payload->extra,
                'fired_at'         => $payload->firedAt->format('c'),
            ]);

            $headers = $step->headers->pluck('header_value', 'header_key')->all();
            $secret  = $config['webhook_secret'] ?? $step->webhook_secret;
            if ($secret) {
                $headers['X-Workflow-Signature'] = hash_hmac('sha256', $body, $secret);
            }

            $webhookMethod = $config['webhook_method'] ?? $step->webhook_method;
            $method   = match ((int) $webhookMethod) {
                2       => 'POST',
                3       => 'PUT',
                4       => 'PATCH',
                default => 'GET',
            };

            $response = \Http::withHeaders($headers)
                ->timeout(config('workflow_automation.webhook_timeout', 15))
                ->retry(config('workflow_automation.webhook_max_retries', 2), 500)
                ->{strtolower($method)}($url, json_decode($body, true));

            $ms = (int) ((microtime(true) - $start) * 1000);

            if (!$response->successful()) {
                return ActionResult::fail(
                    "HTTP {$response->status()}: " . substr($response->body(), 0, 200),
                    $ms,
                );
            }

            return ActionResult::ok($ms, ['http_status' => $response->status()]);
        } catch (\Throwable $e) {
            return ActionResult::fail($e->getMessage(), (int) ((microtime(true) - $start) * 1000));
        }
    }
}
