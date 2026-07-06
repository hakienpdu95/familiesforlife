<?php

namespace Modules\WorkflowAutomation\Contracts;

/**
 * Implement this on a domain event (or any object dispatched to the workflow engine)
 * to take full control of how it is mapped into a TriggerPayload — instead of relying
 * on the generic reflection-based mapping in EventPayloadMapper.
 */
interface ProvidesWorkflowContext
{
    /**
     * Return the workflow subject — typically the primary Eloquent model this event
     * concerns. May be null for subject-less triggers.
     */
    public function workflowSubject(): ?object;

    /**
     * Extra, queryable context exposed to conditions/templates under `extra.<key>`.
     *
     * @return array<string, scalar|null>
     */
    public function workflowContext(): array;
}
