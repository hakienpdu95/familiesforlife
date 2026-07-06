<?php

namespace Modules\WorkflowAutomation\Core;

use Carbon\Carbon;
use Modules\WorkflowAutomation\Data\TriggerPayload;
use Modules\WorkflowAutomation\Models\Workflow;

/**
 * Accumulated pipeline context — stores step outputs and resolves {template} tokens.
 *
 * Resolve priority:
 *   {var.KEY}          → workflow_variables[KEY]
 *   {ctx.KEY}          → accumulated[KEY] (dot-notation supported)
 *   {actor.email}      → payload.actorEmail
 *   {extra.FIELD}      → payload.extra[FIELD]
 *   {subject.id}       → payload.subjectId
 *   {input.KEY}        → extra.KEY alias (manual trigger form)
 *   {task.decision}    → task decision from user_task
 *   {task.form.KEY}    → form response from user_task
 *   {now}              → current datetime string
 *   {now:FORMAT}       → Carbon::now()->format(FORMAT)
 */
class RunContext
{
    private array $accumulated = [];

    private function __construct(private array $vars = []) {}

    public static function fromPayload(TriggerPayload $payload): self
    {
        return new self();
    }

    public static function fromArray(array $data): self
    {
        $ctx = new self();
        $ctx->accumulated = $data;
        return $ctx;
    }

    public function put(string $key, mixed $value): void
    {
        data_set($this->accumulated, $key, $value);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->accumulated, $key, $default);
    }

    public function all(): array
    {
        return $this->accumulated;
    }

    public function withVars(array $vars): self
    {
        $clone = clone $this;
        $clone->vars = $vars;
        return $clone;
    }

    /**
     * Resolve all {token} placeholders in a template string.
     */
    public function resolve(string $template, TriggerPayload $payload, ?Workflow $workflow = null): string
    {
        if ($workflow) {
            foreach ($workflow->variablesMap() as $key => $value) {
                $template = str_replace("{var.{$key}}", (string) ($value ?? ''), $template);
            }
        }

        return preg_replace_callback('/\{([^}]+)\}/', function ($m) use ($payload) {
            $token = $m[1];

            // {now} or {now:FORMAT}
            if ($token === 'now') return Carbon::now()->toDateTimeString();
            if (str_starts_with($token, 'now:')) {
                return Carbon::now()->format(substr($token, 4));
            }

            // {ctx.KEY}
            if (str_starts_with($token, 'ctx.')) {
                $val = data_get($this->accumulated, substr($token, 4));
                return is_array($val) ? json_encode($val) : (string) ($val ?? '');
            }

            // {var.KEY}
            if (str_starts_with($token, 'var.')) {
                return (string) ($this->vars[substr($token, 4)] ?? '');
            }

            // {actor.*}
            if ($token === 'actor.email')  return (string) ($payload->actorEmail ?? '');
            if ($token === 'actor.name')   return (string) ($payload->actorName ?? '');
            if ($token === 'actor.role')   return (string) ($payload->actorRole ?? '');
            if ($token === 'actor.id')     return (string) ($payload->actorId ?? '');

            // {extra.KEY} / {input.KEY}
            if (str_starts_with($token, 'extra.') || str_starts_with($token, 'input.')) {
                $key = str_starts_with($token, 'input.') ? substr($token, 6) : substr($token, 6);
                return (string) ($payload->extra[$key] ?? '');
            }

            // {subject.*}
            if ($token === 'subject.id')   return (string) ($payload->subjectId ?? '');
            if ($token === 'subject.type') return (string) ($payload->subjectType ?? '');

            // {task.decision} / {task.form.KEY}
            if ($token === 'task.decision') return (string) $this->get('task.decision', '');
            if ($token === 'task.comment')  return (string) $this->get('task.comment', '');
            if (str_starts_with($token, 'task.form.')) {
                $key = substr($token, 10);
                return (string) data_get($this->get('task.form', []), $key, '');
            }

            return $m[0]; // leave unresolved tokens as-is
        }, $template);
    }
}
