<?php

namespace Modules\OcopRubric\Features\ScoringSession\Queries;

use App\Shared\Contracts\QueryInterface;

class GetQuickWinsQuery implements QueryInterface
{
    public function __construct(
        public readonly int $sessionId,
        public readonly ?int $limit = 5,
    ) {}
}
