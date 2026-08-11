<?php

namespace Modules\Heritage\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryInterface;

class ListPublishedHeritageSitesQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $provinceCode = null,
        public readonly int $page = 1,
        public readonly int $perPage = 12,
    ) {}
}
