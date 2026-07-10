<?php

namespace Modules\Aicem\Features\KnowledgeBase\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Aicem\Models\AicemKnowledgeDocument;

class ListKnowledgeDocumentsHandler implements QueryHandlerInterface
{
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

        return $q->orderBy('type')->orderBy('priority')->orderByDesc('created_at')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
