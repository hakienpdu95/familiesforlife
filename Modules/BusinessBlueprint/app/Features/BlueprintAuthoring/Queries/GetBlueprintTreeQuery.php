<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Queries;

use App\Shared\Contracts\QueryInterface;

class GetBlueprintTreeQuery implements QueryInterface
{
    public function __construct(
        public readonly int $blueprintVersionId,
    ) {}
}
