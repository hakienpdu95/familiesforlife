<?php
namespace Modules\WorkflowAutomation\Contracts;

use Modules\WorkflowAutomation\Data\TriggerPayload;

interface TriggerSource
{
    public function type(): string;
    public function label(): string;
    public function module(): string;
    public function availableFields(): array;
    public function configFields(): array;
    public function matches(TriggerPayload $payload, array $parsedConfig): bool;
}
