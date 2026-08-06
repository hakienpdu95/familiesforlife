<?php

namespace Modules\N8n\Features\LogViewing\Queries;

use App\Shared\Contracts\QueryInterface;

class ListN8nOutboundLogsQuery implements QueryInterface
{
    public function __construct(
        public readonly ?int $connectionId = null,
        public readonly ?bool $success = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly string $sortField = 'requested_at',
        public readonly string $sortDir = 'desc',
    ) {}
}
