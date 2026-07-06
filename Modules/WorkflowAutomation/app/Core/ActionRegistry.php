<?php

namespace Modules\WorkflowAutomation\Core;

use Modules\WorkflowAutomation\Contracts\ActionExecutor;

final class ActionRegistry
{
    private array $executors = [];

    public function register(ActionExecutor $executor): void
    {
        // Support executors that handle multiple action types (v2)
        $types = method_exists($executor, 'supportedTypes')
            ? $executor->supportedTypes()
            : [$executor->type()];

        foreach ($types as $type) {
            $this->executors[$type] = $executor;
        }
    }

    public function get(string $type): ?ActionExecutor
    {
        return $this->executors[$type] ?? null;
    }

    public function groupedByModule(): array
    {
        $grouped = [];
        $seen    = [];

        foreach ($this->executors as $type => $executor) {
            $executorClass = get_class($executor);

            if (method_exists($executor, 'supportedTypes')) {
                // Register each supported type individually for the UI
                foreach ($executor->supportedTypes() as $supportedType) {
                    if (isset($seen[$supportedType])) continue;
                    $seen[$supportedType] = true;
                    $grouped[$executor->module()][] = [
                        'type'          => $supportedType,
                        'label'         => $executor->label() . ' (' . $supportedType . ')',
                        'config_fields' => $executor->stepConfigFields(),
                    ];
                }
            } else {
                if (isset($seen[$type])) continue;
                $seen[$type] = true;
                $grouped[$executor->module()][] = [
                    'type'          => $type,
                    'label'         => $executor->label(),
                    'config_fields' => $executor->stepConfigFields(),
                ];
            }
        }

        return $grouped;
    }
}
