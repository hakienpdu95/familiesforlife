<?php

namespace Modules\OrganizationSolution\Features\SolutionActivation\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OrganizationSolution\Enums\OrganizationSolutionStatus;
use Modules\OrganizationSolution\Models\OrganizationSolution;

class SuspendOrganizationSolutionAction
{
    use AsAction;

    public function handle(OrganizationSolution $orgSolution): OrganizationSolution
    {
        $orgSolution->update(['status' => OrganizationSolutionStatus::Suspended->value]);

        return $orgSolution->fresh();
    }
}
