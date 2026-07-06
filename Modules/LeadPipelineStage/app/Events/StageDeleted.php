<?php

namespace Modules\LeadPipelineStage\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StageDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int  $stageId,
        public readonly ?int $organizationId,
    ) {}
}
