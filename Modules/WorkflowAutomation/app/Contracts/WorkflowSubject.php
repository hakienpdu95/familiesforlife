<?php
namespace Modules\WorkflowAutomation\Contracts;

use Modules\WorkflowAutomation\Data\TriggerPayload;

interface WorkflowSubject
{
    public static function workflowSubjectType(): string;
    public static function workflowUpdatableFields(): array;
    public static function resolveFromPayload(TriggerPayload $payload): ?static;
}
