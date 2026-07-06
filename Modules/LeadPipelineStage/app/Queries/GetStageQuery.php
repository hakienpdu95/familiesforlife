<?php

namespace Modules\LeadPipelineStage\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\LeadPipelineStage\Models\LeadPipelineStage;

class GetStageQuery implements QueryInterface
{
    public function __construct(
        public readonly LeadPipelineStage $stage,
    ) {}
}
