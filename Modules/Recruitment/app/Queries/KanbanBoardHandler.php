<?php

namespace Modules\Recruitment\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Collection;
use Modules\Recruitment\Models\RcApplication;
use Modules\Recruitment\Models\RcPipelineStage;

class KanbanBoardHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var KanbanBoardQuery $query */

        $stages = RcPipelineStage::withoutTenant()
            ->where('org_id', $query->orgId)
            ->where('is_active', true)
            ->withCount([
                'applications as candidate_count' => function ($q) use ($query): void {
                    $q->where('jp_job_post_id', $query->jpJobPostUuid)
                      ->where('status', 'active');
                },
            ])
            ->orderBy('sort_order')
            ->get();

        return $stages;
    }
}
