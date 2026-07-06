<?php

namespace Modules\LeadPipelineStage\Queries;

use App\Shared\Contracts\QueryInterface;

class ListStagesQuery implements QueryInterface
{
    public function __construct(
        public readonly int  $orgId,
        public readonly bool $activeOnly = true,
    ) {}
}
