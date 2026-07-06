<?php

namespace Modules\SolutionCatalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\BusinessSolution\Models\BusinessSolution;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\ActivateBusinessSolutionAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Data\ActivateBusinessSolutionData;
use Modules\OrganizationSolution\Features\SolutionActivation\Exceptions\BlueprintVersionNotPublishedException;
use Modules\OrganizationSolution\Models\OrganizationSolution;

/**
 * Nút "Kích hoạt" gọi thẳng OrganizationSolution::ActivateBusinessSolutionAction
 * (Phần 3, spec §5.2) — module này không có Action/Model riêng.
 */
class SolutionActivationController extends Controller
{
    public function activate(BusinessSolution $businessSolution, ActivateBusinessSolutionAction $action): RedirectResponse
    {
        if (OrganizationSolution::where('business_solution_id', $businessSolution->id)->exists()) {
            return back()->withErrors(['business_solution' => 'Tổ chức của bạn đã kích hoạt Business Solution này rồi.']);
        }

        $blueprint = $businessSolution->blueprints()->where('status', 'published')->first();

        if (! $blueprint || ! $blueprint->current_version_id) {
            return back()->withErrors(['business_solution' => 'Business Solution này chưa có Blueprint published — chưa thể kích hoạt.']);
        }

        try {
            $orgSolution = $action->handle(ActivateBusinessSolutionData::from([
                'business_solution_id' => $businessSolution->id,
                'blueprint_version_id'  => $blueprint->current_version_id,
                'name'                  => $businessSolution->name,
            ]));
        } catch (BlueprintVersionNotPublishedException $e) {
            return back()->withErrors(['business_solution' => $e->getMessage()]);
        }

        return redirect()
            ->route('organization_solutions.wizard.capabilities.form', $orgSolution)
            ->with('success', "Đã kích hoạt \"{$businessSolution->name}\" — tiếp tục cấu hình.");
    }
}
