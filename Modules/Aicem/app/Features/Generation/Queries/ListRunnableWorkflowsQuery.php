<?php

namespace Modules\Aicem\Features\Generation\Queries;

use App\Shared\Contracts\QueryInterface;

class ListRunnableWorkflowsQuery implements QueryInterface
{
    public function __construct(
        public readonly string $subjectType,
        public readonly int $subjectId,
    ) {}
}
