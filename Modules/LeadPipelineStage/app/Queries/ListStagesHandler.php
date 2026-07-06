<?php

namespace Modules\LeadPipelineStage\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Collection;
use Modules\LeadPipelineStage\Models\LeadPipelineStage;

class ListStagesHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListStagesQuery $query */
        $q = LeadPipelineStage::query()
            ->where(function ($q) use ($query) {
                $q->where('organization_id', $query->orgId)
                  ->orWhere('is_global', true);
            })
            ->orderBy('sort_order');

        if ($query->activeOnly) {
            $q->where('is_active', true);
        }

        return $q->get();
    }
}
