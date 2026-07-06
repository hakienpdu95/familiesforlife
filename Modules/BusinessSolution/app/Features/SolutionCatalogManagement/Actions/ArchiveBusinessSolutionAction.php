<?php

namespace Modules\BusinessSolution\Features\SolutionCatalogManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\BusinessSolution\Enums\BusinessSolutionStatus;
use Modules\BusinessSolution\Models\BusinessSolution;

class ArchiveBusinessSolutionAction
{
    use AsAction;

    public function handle(BusinessSolution $businessSolution): BusinessSolution
    {
        $businessSolution->update(['status' => BusinessSolutionStatus::Archived->value]);

        return $businessSolution->fresh();
    }
}
