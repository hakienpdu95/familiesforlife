<?php

namespace Modules\LeadPipelineStage\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\LeadPipelineStage\Models\LeadPipelineStage;

class GetStageHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LeadPipelineStage
    {
        /** @var GetStageQuery $query */
        return $query->stage->loadCount('leads');
    }
}
