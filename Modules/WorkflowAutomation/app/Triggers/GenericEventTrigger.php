<?php

namespace Modules\WorkflowAutomation\Triggers;

use Modules\WorkflowAutomation\Contracts\TriggerSource;
use Modules\WorkflowAutomation\Data\TriggerPayload;

/**
 * A config-driven TriggerSource. One instance is registered per entry in
 * config('workflow_automation.triggers'), so adding a new trigger to the builder
 * requires only a config entry — no bespoke class.
 *
 * `matches()` performs generic equality filtering: for every configFields key that the
 * workflow has a non-empty value for, the corresponding payload field must equal it.
 * The payload field checked is `extra.<key>` (falling back to `subject.attr.<key>`),
 * which covers the common "only fire when band_code = advanced" style of filter.
 */
class GenericEventTrigger implements TriggerSource
{
    public function __construct(
        private readonly string $type,
        private readonly array  $def,
    ) {}

    public function type(): string   { return $this->type; }
    public function label(): string  { return $this->def['label']  ?? $this->type; }
    public function module(): string { return $this->def['module'] ?? 'Core'; }

    public function availableFields(): array { return $this->def['fields'] ?? []; }
    public function configFields(): array    { return $this->def['config'] ?? []; }

    public function matches(TriggerPayload $payload, array $parsedConfig): bool
    {
        if ($payload->triggerType !== $this->type) {
            return false;
        }

        foreach ($this->configFields() as $field) {
            $key      = $field['key'] ?? null;
            $required = $key !== null ? ($parsedConfig[$key] ?? null) : null;

            if ($required === null || $required === '') {
                continue; // empty filter = match all
            }

            $actual = $payload->resolve('extra.' . $key);
            if ($actual === null) {
                $actual = $payload->resolve('subject.attr.' . $key);
            }

            if ((string) $actual !== (string) $required) {
                return false;
            }
        }

        return true;
    }
}
