<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\PromptFrameworkStudio\Models\GeneratedPrompt;

/** spec/PromptFrameworkStudio_Technical_Specification.md §4.3 — nguồn dữ liệu cho Tabulator. */
class ListGeneratedPromptsForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['label', 'framework_key', 'updated_at', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListGeneratedPromptsForAdminQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'updated_at';
        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        return GeneratedPrompt::query()
            ->with(['createdBy:id,name'])
            ->when($query->frameworkKey, fn ($q) => $q->where('framework_key', $query->frameworkKey))
            // tận dụng index('label') (§3.1) — tìm chuỗi con theo tên người dùng tự đặt.
            ->when($query->search, fn ($q) => $q->where('label', 'like', "%{$query->search}%"))
            ->orderBy($sortField, $sortDir)
            ->orderBy('id', 'desc')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
