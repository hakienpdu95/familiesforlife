<?php

namespace Modules\Deployment\Http\Controllers;

use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Deployment\Actions\DeployOrganizationSolutionAction;
use Modules\Deployment\Models\Deployment;
use Modules\OrganizationSolution\Models\OrganizationSolution;

class DeploymentEngineController extends Controller
{
    public function deploy(OrganizationSolution $organizationSolution, DeployOrganizationSolutionAction $action): RedirectResponse
    {
        try {
            $deployment = $action->handle($organizationSolution);
        } catch (DomainException $e) {
            return back()->withErrors(['organization_solution' => $e->getMessage()]);
        }

        return redirect()->route('deployments.logs', $deployment)
            ->with('success', "Deploy \"{$organizationSolution->name}\" hoàn tất.");
    }

    public function logs(Deployment $deployment): View
    {
        $deployment->loadMissing('logs', 'organizationSolution', 'blueprintVersion', 'project');

        return view('deployment::engine.logs', compact('deployment'));
    }

    public function snapshots(Deployment $deployment): View
    {
        $deployment->loadMissing('snapshots.configItems', 'organizationSolution');

        return view('deployment::engine.snapshots', compact('deployment'));
    }
}
