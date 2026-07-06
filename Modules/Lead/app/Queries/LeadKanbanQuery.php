<?php

namespace Modules\Lead\Queries;

use App\Shared\Contracts\QueryInterface;

class LeadKanbanQuery implements QueryInterface
{
    public function __construct(
        public readonly int  $orgId,
        public readonly ?int $scopeUserId = null,
    ) {}
}
