<?php

namespace Modules\Recruitment\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Recruitment\Models\RcApplication;

class ListApplicationsHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['applied_at', 'status', 'apply_source'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListApplicationsQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField
            : 'applied_at';

        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = RcApplication::query()
            ->select('rc_applications.*')
            ->with([
                'candidate:id,full_name,email,current_title,years_experience',
                'currentStage:id,name,color_hex,stage_type',
                'assignedTo:id,name',
            ]);

        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->whereHas('candidate', function (Builder $sub) use ($term): void {
                $sub->where('full_name', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        if ($query->status !== null && $query->status !== '') {
            $q->where('rc_applications.status', $query->status);
        }

        if ($query->stageId !== null && $query->stageId !== '') {
            $q->where('rc_applications.current_stage_id', $query->stageId);
        }

        if ($query->jpJobPostId !== null && $query->jpJobPostId !== '') {
            $q->where('rc_applications.jp_job_post_id', $query->jpJobPostId);
        }

        if ($query->assignedTo !== null && $query->assignedTo !== '') {
            $q->where('rc_applications.assigned_to', $query->assignedTo);
        }

        if ($query->dateFrom !== null && $query->dateFrom !== '') {
            $q->whereDate('rc_applications.applied_at', '>=', $query->dateFrom);
        }

        if ($query->dateTo !== null && $query->dateTo !== '') {
            $q->whereDate('rc_applications.applied_at', '<=', $query->dateTo);
        }

        $q->orderBy('rc_applications.' . $sortField, $sortDir);

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
