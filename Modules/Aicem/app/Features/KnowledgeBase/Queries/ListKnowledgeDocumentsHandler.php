<?php

namespace Modules\Aicem\Features\KnowledgeBase\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Aicem\Models\AicemKnowledgeDocument;

class ListKnowledgeDocumentsHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['title', 'type', 'priority', 'current_version', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListKnowledgeDocumentsQuery $query */
        $q = AicemKnowledgeDocument::query()->with('creator:id,name');

        if ($query->search) {
            $q->where('title', 'like', '%' . $query->search . '%');
        }

        if ($query->type) {
            $q->where('type', $query->type);
        }

        if ($query->subjectType) {
            $q->where('subject_type', $query->subjectType);
        }

        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'created_at';
        $sortDir   = $query->sortDir === 'asc' ? 'asc' : 'desc';

        if ($sortField === 'created_at') {
            // Mặc định — giữ nguyên hành vi cũ: nhóm theo loại rồi ưu tiên (priority) trước khi mới nhất.
            $q->orderBy('type')->orderBy('priority')->orderByDesc('created_at');
        } else {
            $q->orderBy($sortField, $sortDir)->orderBy('id');
        }

        return $q->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
