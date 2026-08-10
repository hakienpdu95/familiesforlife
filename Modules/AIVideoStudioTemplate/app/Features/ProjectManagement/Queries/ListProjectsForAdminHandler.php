<?php

namespace Modules\AIVideoStudioTemplate\Features\ProjectManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioProject;

/** spec/AIVideoStudioTemplate_Technical_Specification.md §8 — filter theo status + tìm theo name ở trang index (Tabulator, remote pagination/sort). */
class ListProjectsForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['name', 'status', 'shots_count', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListProjectsForAdminQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'created_at';
        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        return AiVideoStudioProject::query()
            ->withCount('shots')
            ->with('createdBy:id,name')
            ->when($query->status, fn ($q) => $q->where('status', $query->status))
            ->when($query->search, fn ($q) => $q->where('name', 'like', "%{$query->search}%"))
            ->orderBy($sortField, $sortDir)
            ->orderBy('id', $sortDir)
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
