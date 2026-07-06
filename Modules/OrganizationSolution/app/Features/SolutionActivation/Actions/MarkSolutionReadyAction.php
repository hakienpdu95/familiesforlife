<?php

namespace Modules\OrganizationSolution\Features\SolutionActivation\Actions;

use DomainException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OrganizationSolution\Enums\OrganizationSolutionStatus;
use Modules\OrganizationSolution\Features\SolutionActivation\Queries\ValidatePreDeployHandler;
use Modules\OrganizationSolution\Features\SolutionActivation\Queries\ValidatePreDeployQuery;
use Modules\OrganizationSolution\Models\OrganizationSolution;

/**
 * Bước 8 — Review (spec §3.6): chạy ValidatePreDeployAction (7 điều kiện A07 §14);
 * nếu pass → status: configuring → ready.
 */
class MarkSolutionReadyAction
{
    use AsAction;

    public function __construct(private readonly ValidatePreDeployHandler $validator) {}

    public function handle(OrganizationSolution $orgSolution): OrganizationSolution
    {
        $result = $this->validator->handle(new ValidatePreDeployQuery($orgSolution->id));

        if (! $result['ready']) {
            $failed = collect($result['criteria'])->reject(fn ($c) => $c['passed'])->pluck('label');
            throw new DomainException('Chưa đạt điều kiện Pre-Deploy: ' . $failed->implode(' | '));
        }

        $orgSolution->update(['status' => OrganizationSolutionStatus::Ready->value]);

        return $orgSolution->fresh();
    }
}
