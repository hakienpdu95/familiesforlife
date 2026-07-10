<?php

namespace Modules\Aicem\Features\ExampleLearning\Queries;

use App\Shared\Contracts\QueryInterface;

class ListExampleCandidatesQuery implements QueryInterface
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 25,
        public readonly ?string $status = null,
    ) {}
}
