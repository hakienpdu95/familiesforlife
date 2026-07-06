<?php

namespace Modules\WorkflowAutomation\Core;

use Illuminate\Database\Eloquent\Model;
use Modules\WorkflowAutomation\Contracts\ProvidesWorkflowContext;
use Modules\WorkflowAutomation\Data\TriggerPayload;

/**
 * Converts an arbitrary domain event object into a TriggerPayload, driven by the
 * declarative trigger definition in config/workflow_automation.php — with sensible
 * reflection-based defaults so most events need zero mapping configuration.
 *
 * Resolution order:
 *   1. Event implements ProvidesWorkflowContext  → use its own subject + context.
 *   2. Definition names a `subject` property     → use that property as the subject.
 *   3. Otherwise                                 → first public Eloquent model property.
 *
 * Scalar context is taken from the definition's `extra` map (extra_key => event_property),
 * falling back to auto-collecting any scalar public properties of the event.
 */
final class EventPayloadMapper
{
    /**
     * @param array<string,mixed> $def  Trigger definition from config.
     */
    public function fromEvent(string $triggerType, array $def, object $event): TriggerPayload
    {
        $module = $def['module'] ?? 'Core';

        if ($event instanceof ProvidesWorkflowContext) {
            return TriggerPayload::make(
                $triggerType,
                $module,
                $event->workflowSubject(),
                $event->workflowContext(),
            );
        }

        $props   = $this->publicProps($event);
        $subject = $this->resolveSubject($event, $def, $props);
        $extra   = $this->resolveExtra($event, $def, $props);

        return TriggerPayload::make($triggerType, $module, $subject, $extra);
    }

    private function resolveSubject(object $event, array $def, array $props): ?object
    {
        if (!empty($def['subject']) && isset($props[$def['subject']]) && is_object($props[$def['subject']])) {
            return $props[$def['subject']];
        }

        foreach ($props as $value) {
            if ($value instanceof Model) {
                return $value;
            }
        }

        return null;
    }

    private function resolveExtra(object $event, array $def, array $props): array
    {
        // Explicit map wins: extra_key => event property name.
        if (!empty($def['extra']) && is_array($def['extra'])) {
            $extra = [];
            foreach ($def['extra'] as $key => $prop) {
                $extra[$key] = $props[$prop] ?? null;
            }
            return $extra;
        }

        // Auto-collect scalar properties (skip objects/arrays — those are subjects/payloads).
        $extra = [];
        foreach ($props as $name => $value) {
            if (is_scalar($value) || $value === null) {
                $extra[$name] = $value;
            }
        }
        return $extra;
    }

    /** @return array<string,mixed> */
    private function publicProps(object $event): array
    {
        return get_object_vars($event);
    }
}
