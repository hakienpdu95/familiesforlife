<?php

namespace Modules\Lead\Queries;

use App\Shared\Contracts\QueryInterface;

class ListLeadsQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $orgId,
        public readonly int     $page          = 1,
        public readonly int     $perPage       = 25,
        public readonly string  $sortField     = 'updated_at',
        public readonly string  $sortDir       = 'desc',
        public readonly ?string $search        = null,
        public readonly ?int    $stageId       = null,
        public readonly ?int    $sourceId      = null,
        public readonly ?int    $assignedTo    = null,
        public readonly ?int    $status        = null,
        public readonly ?array  $tagIds        = null,
        public readonly ?int    $minScore      = null,
        public readonly ?string $closingBefore = null,
        public readonly ?string $closingAfter  = null,
        // Permission scope — null = no restriction, int = restrict to assigned_to = userId
        public readonly ?int    $scopeUserId   = null,
    ) {}
}
