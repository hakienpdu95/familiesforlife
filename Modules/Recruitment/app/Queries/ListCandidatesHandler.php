<?php

namespace Modules\Recruitment\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Recruitment\Models\RcCandidate;

class ListCandidatesHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['full_name', 'email', 'status', 'source', 'created_at', 'years_experience'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListCandidatesQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField
            : 'created_at';

        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = RcCandidate::query()
            ->select('rc_candidates.*')
            ->withCount('applications');

        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('rc_candidates.full_name', 'like', $term)
                    ->orWhere('rc_candidates.email', 'like', $term)
                    ->orWhere('rc_candidates.phone', 'like', $term)
                    ->orWhere('rc_candidates.current_title', 'like', $term);
            });
        }

        if ($query->status !== null && $query->status !== '') {
            $q->where('rc_candidates.status', $query->status);
        }

        if ($query->source !== null && $query->source !== '') {
            $q->where('rc_candidates.source', $query->source);
        }

        if ($query->dateFrom !== null && $query->dateFrom !== '') {
            $q->whereDate('rc_candidates.created_at', '>=', $query->dateFrom);
        }

        if ($query->dateTo !== null && $query->dateTo !== '') {
            $q->whereDate('rc_candidates.created_at', '<=', $query->dateTo);
        }

        $q->orderBy('rc_candidates.' . $sortField, $sortDir);

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
