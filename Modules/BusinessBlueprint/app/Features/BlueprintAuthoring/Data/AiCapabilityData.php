<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class AiCapabilityData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $blueprint_version_id,

        public readonly ?int $checklist_id = null,

        #[Required, Max(100)]
        public readonly string $capability_code,

        #[Required, Max(255)]
        public readonly string $name,

        public readonly ?string $description   = null,
        public readonly ?int    $ai_agent_id     = null,
        public readonly ?int    $ai_prompt_id     = null,
        public readonly ?string $trigger_event    = null,
        public readonly string  $status           = 'active',
    ) {}
}
