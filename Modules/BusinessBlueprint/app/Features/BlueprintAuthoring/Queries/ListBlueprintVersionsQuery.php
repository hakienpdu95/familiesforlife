<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Queries;

use App\Shared\Contracts\QueryInterface;

class ListBlueprintVersionsQuery implements QueryInterface
{
    public function __construct(
        public readonly int $blueprintId,
    ) {}
}
