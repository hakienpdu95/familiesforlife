<?php

namespace Modules\Aicem\Features\KnowledgeBase\Queries;

use App\Shared\Contracts\QueryInterface;

class ListKnowledgeDocumentsQuery implements QueryInterface
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 25,
        public readonly ?string $search = null,
        public readonly ?string $type = null,
        public readonly ?string $subjectType = null,
    ) {}
}
