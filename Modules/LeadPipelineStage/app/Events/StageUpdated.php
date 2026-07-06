<?php

namespace Modules\LeadPipelineStage\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\LeadPipelineStage\Models\LeadPipelineStage;

class StageUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly LeadPipelineStage $stage,
    ) {}
}
