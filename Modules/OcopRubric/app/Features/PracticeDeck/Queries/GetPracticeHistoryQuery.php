<?php

namespace Modules\OcopRubric\Features\PracticeDeck\Queries;

use App\Shared\Contracts\QueryInterface;

class GetPracticeHistoryQuery implements QueryInterface
{
    public function __construct(
        public readonly ?int $productId = null,
        public readonly ?int $userId = null,
        public readonly ?string $mode = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
