<?php

namespace Modules\WorkflowAutomation\Triggers;

use Modules\WorkflowAutomation\Contracts\TriggerSource;
use Modules\WorkflowAutomation\Data\TriggerPayload;

class ManualTrigger implements TriggerSource
{
    public function type(): string   { return 'manual'; }
    public function label(): string  { return 'Kích hoạt thủ công'; }
    public function module(): string { return 'Core'; }

    public function availableFields(): array { return []; }
    public function configFields(): array    { return []; }

    public function matches(TriggerPayload $payload, array $parsedConfig): bool
    {
        return true;
    }
}
