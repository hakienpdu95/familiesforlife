<?php

namespace Modules\WorkflowAutomation\Concerns;

use Modules\WorkflowAutomation\Support\Workflows;

/**
 * Drop-in convenience for models that do NOT already emit domain events but still want
 * to drive workflows. Declare a map of lifecycle hooks to trigger types:
 *
 *     use FiresWorkflows;
 *     protected array $workflowTriggers = [
 *         'created' => 'task.created',
 *         'updated' => 'task.updated',
 *         'deleted' => 'task.deleted',
 *     ];
 *
 * Supported hooks: created, updated, deleted, restored. Each fires after commit with the
 * model itself as the subject. Prefer dedicated domain events + the config `event` binding
 * when they already exist; this trait is for models that have none.
 */
trait FiresWorkflows
{
    public static function bootFiresWorkflows(): void
    {
        foreach (['created', 'updated', 'deleted', 'restored'] as $hook) {
            static::registerModelEvent($hook, function ($model) use ($hook) {
                $type = $model->workflowTriggers[$hook] ?? null;
                if ($type) {
                    Workflows::fire($type, $model);
                }
            });
        }
    }
}
