<?php
namespace Modules\WorkflowAutomation\Contracts;

use Modules\WorkflowAutomation\Data\TriggerPayload;
use Modules\WorkflowAutomation\Data\ActionResult;
use Modules\WorkflowAutomation\Models\WorkflowStep;

interface ActionExecutor
{
    public function type(): string;
    public function label(): string;
    public function module(): string;
    public function stepConfigFields(): array;
    public function execute(WorkflowStep $step, TriggerPayload $payload): ActionResult;
}
