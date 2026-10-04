<?php

namespace Modules\OcopSubject\Features\OcopSubjectManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class ListOcopSubjectsForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $organizationType = null,
        public readonly ?string $provinceCode = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly string $sortField = 'created_at',
        public readonly string $sortDir = 'desc',
    ) {}
}
