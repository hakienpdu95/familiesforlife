<?php

namespace Modules\WorkflowAutomation\Listeners;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\WorkflowAutomation\Core\EventPayloadMapper;
use Modules\WorkflowAutomation\Core\WorkflowDispatcher;

/**
 * Binds every configured trigger that declares an `event` class to the application
 * event bus. When such an event fires, it is mapped generically into a TriggerPayload
 * and dispatched to the workflow engine — no per-event Listener class required.
 *
 * Registered once from WorkflowAutomationServiceProvider::boot().
 */
class WorkflowEventSubscriber
{
    public function __construct(
        private readonly EventPayloadMapper $mapper,
    ) {}

    public function subscribe(Dispatcher $events): void
    {
        $triggers = config('workflow_automation.triggers', []);

        foreach ($triggers as $type => $def) {
            $eventClass = $def['event'] ?? null;
            if (!$eventClass || !class_exists($eventClass)) {
                continue; // module absent or no event binding — skip silently
            }

            $events->listen($eventClass, function (object $event) use ($type, $def) {
                $payload = $this->mapper->fromEvent($type, $def, $event);
                WorkflowDispatcher::fireAfterCommit($payload);
            });
        }
    }
}
