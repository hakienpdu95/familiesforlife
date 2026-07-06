<?php

namespace Modules\OrganizationSolution\Features\SolutionActivation\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OrganizationSolution\Enums\OrganizationSolutionStatus;
use Modules\OrganizationSolution\Models\OrganizationSolution;

class ArchiveOrganizationSolutionAction
{
    use AsAction;

    public function handle(OrganizationSolution $orgSolution): OrganizationSolution
    {
        $orgSolution->update(['status' => OrganizationSolutionStatus::Archived->value]);

        return $orgSolution->fresh();
    }
}
