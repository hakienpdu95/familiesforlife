<?php

namespace Modules\Aicem\Features\ExampleLearning\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Aicem\Models\AicemExampleCandidate;

class ListExampleCandidatesHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListExampleCandidatesQuery $query */
        $q = AicemExampleCandidate::query()->with(['reviewer:id,name']);

        $status = $query->status ?? 'pending';
        if ($status !== 'all') {
            $q->where('status', $status);
        }

        return $q->orderByDesc('created_at')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
