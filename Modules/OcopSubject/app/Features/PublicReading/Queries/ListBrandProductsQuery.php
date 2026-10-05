<?php

namespace Modules\OcopSubject\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryInterface;

class ListBrandProductsQuery implements QueryInterface
{
    public const SORTS = [
        'newest' => 'Mới nhất',
        'star' => 'Hạng sao cao',
        'name' => 'Tên A-Z',
    ];

    public function __construct(
        public readonly int $ocopSubjectId,
        public readonly ?string $search = null,
        public readonly array $stars = [],
        public readonly string $sort = 'newest',
        public readonly int $page = 1,
        public readonly int $perPage = 12,
    ) {}
}
