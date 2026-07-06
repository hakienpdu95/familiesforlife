<?php

namespace Modules\WorkflowAutomation\Core;

use Modules\WorkflowAutomation\Contracts\TriggerSource;
use Modules\WorkflowAutomation\Data\TriggerPayload;
use Modules\WorkflowAutomation\Models\Workflow;

final class TriggerRegistry
{
    private array $triggers = [];

    public function register(TriggerSource $trigger): void
    {
        $this->triggers[$trigger->type()] = $trigger;
    }

    public function get(string $type): ?TriggerSource
    {
        return $this->triggers[$type] ?? null;
    }

    public function groupedByModule(): array
    {
        $grouped = [];
        foreach ($this->triggers as $trigger) {
            $grouped[$trigger->module()][] = [
                'type'             => $trigger->type(),
                'label'            => $trigger->label(),
                'config_fields'    => $trigger->configFields(),
                'available_fields' => $trigger->availableFields(),
            ];
        }
        return $grouped;
    }

    public function matchingWorkflows(TriggerPayload $payload): \Illuminate\Support\Collection
    {
        $query = Workflow::with('triggerParams')
            ->where('is_active', 1)
            ->where('trigger_type', $payload->triggerType)
            ->orderBy('priority');

        if ($payload->organizationId) {
            $query->where('organization_id', $payload->organizationId);
        }

        return $query->get()->filter(function (Workflow $workflow) use ($payload) {
            $trigger = $this->get($workflow->trigger_type);
            if (!$trigger) return false;
            return $trigger->matches($payload, $workflow->parsedParams());
        });
    }
}
