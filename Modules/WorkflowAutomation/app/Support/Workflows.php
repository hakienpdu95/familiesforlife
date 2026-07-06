<?php

namespace Modules\WorkflowAutomation\Support;

use Modules\WorkflowAutomation\Core\WorkflowDispatcher;
use Modules\WorkflowAutomation\Data\TriggerPayload;

/**
 * Entry point for firing workflow triggers imperatively from anywhere in the system,
 * without an event class. One line:
 *
 *     Workflows::fire('lead.qualified', $lead, ['score' => 88]);
 *
 * The source module is derived from the trigger's config definition when available,
 * so callers usually only pass the trigger type + subject.
 */
final class Workflows
{
    /**
     * Fire a trigger after the current DB transaction commits (safe default).
     */
    public static function fire(string $triggerType, ?object $subject = null, array $extra = []): void
    {
        WorkflowDispatcher::fireAfterCommit(self::payload($triggerType, $subject, $extra));
    }

    /**
     * Fire immediately, without waiting for a transaction commit.
     */
    public static function fireNow(string $triggerType, ?object $subject = null, array $extra = []): void
    {
        WorkflowDispatcher::fire(self::payload($triggerType, $subject, $extra));
    }

    private static function payload(string $triggerType, ?object $subject, array $extra): TriggerPayload
    {
        // Trigger keys contain dots (e.g. "lead.created"), so dot-notation lookup
        // would misparse them — fetch the whole map and index directly.
        $def    = config('workflow_automation.triggers', [])[$triggerType] ?? [];
        $module = $def['module'] ?? 'Core';

        return TriggerPayload::make($triggerType, $module, $subject, $extra);
    }
}
