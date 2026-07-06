<?php

namespace Modules\OcopRubric\Features\ScoringSession\Queries;

use App\Shared\Contracts\QueryInterface;

class GetNextCriterionQuery implements QueryInterface
{
    public function __construct(public readonly int $sessionId) {}
}
