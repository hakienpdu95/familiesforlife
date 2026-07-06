<?php

namespace Modules\OcopRubric\Features\RubricAuthoring\Queries;

use App\Shared\Contracts\QueryInterface;

class GetRubricTreeQuery implements QueryInterface
{
    public function __construct(public readonly int $rubricVersionId) {}
}
