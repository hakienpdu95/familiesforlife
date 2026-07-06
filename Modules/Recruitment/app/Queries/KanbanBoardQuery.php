<?php

namespace Modules\Recruitment\Queries;

use App\Shared\Contracts\QueryInterface;

class KanbanBoardQuery implements QueryInterface
{
    public function __construct(
        public readonly int    $orgId,
        public readonly string $jpJobPostUuid,
    ) {}
}
