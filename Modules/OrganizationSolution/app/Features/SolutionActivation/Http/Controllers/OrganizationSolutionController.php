<?php

namespace Modules\OrganizationSolution\Features\SolutionActivation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\BusinessBlueprint\Enums\BlueprintVersionStatus;
use Modules\BusinessBlueprint\Models\Blueprint;
use Modules\BusinessSolution\Models\BusinessSolution;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\ActivateBusinessSolutionAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\ArchiveOrganizationSolutionAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\SuspendOrganizationSolutionAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Data\ActivateBusinessSolutionData;
use Modules\OrganizationSolution\Features\SolutionActivation\Exceptions\BlueprintVersionNotPublishedException;
use Modules\OrganizationSolution\Features\SolutionActivation\Queries\ListOrganizationSolutionsHandler;
use Modules\OrganizationSolution\Features\SolutionActivation\Queries\ListOrganizationSolutionsQuery;
use Modules\OrganizationSolution\Models\OrganizationSolution;

class OrganizationSolutionController extends Controller
{
    public function index(Request $request, ListOrganizationSolutionsHandler $handler): View
    {
        $organizationSolutions = $handler->handle(new ListOrganizationSolutionsQuery(
            status: $request->string('status')->value() ?: null,
        ));

        // "Version" kích hoạt được là blueprint_versions (Module BusinessBlueprint) — KHÁC
        // với business_solution_versions (catalog version của chính Module BusinessSolution).
        $publishedSolutions = BusinessSolution::query()
            ->where('status', 'published')
            ->get()
            ->map(function (BusinessSolution $solution) {
                $solution->setRelation(
                    'publishedBlueprintVersions',
                    Blueprint::where('business_solution_id', $solution->id)
                        ->with(['versions' => fn ($q) => $q->where('status', BlueprintVersionStatus::Published->value)])
                        ->get()
                        ->flatMap->versions
                );

                return $solution;
            })
            ->filter(fn (BusinessSolution $solution) => $solution->publishedBlueprintVersions->isNotEmpty())
            ->values();

        return view('organizationsolution::index', compact('organizationSolutions', 'publishedSolutions'));
    }

    public function activate(Request $request, ActivateBusinessSolutionAction $action): RedirectResponse
    {
        $data = ActivateBusinessSolutionData::from($request->validate([
            'business_solution_id' => 'required|integer|exists:business_solutions,id',
            'blueprint_version_id'  => 'required|integer|exists:blueprint_versions,id',
            'name'                  => 'required|string|max:255',
        ]));

        try {
            $orgSolution = $action->handle($data);
        } catch (BlueprintVersionNotPublishedException $e) {
            return back()->withErrors(['blueprint_version_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('organization_solutions.wizard.capabilities.form', $orgSolution)
            ->with('success', "Đã kích hoạt \"{$orgSolution->name}\" (draft) — tiếp tục cấu hình.");
    }

    public function suspend(OrganizationSolution $organizationSolution, SuspendOrganizationSolutionAction $action): RedirectResponse
    {
        $action->handle($organizationSolution);

        return redirect()->route('organization_solutions.index')
            ->with('success', "Đã tạm ngưng \"{$organizationSolution->name}\".");
    }

    public function archive(OrganizationSolution $organizationSolution, ArchiveOrganizationSolutionAction $action): RedirectResponse
    {
        $action->handle($organizationSolution);

        return redirect()->route('organization_solutions.index')
            ->with('success', "Đã lưu trữ \"{$organizationSolution->name}\".");
    }
}
