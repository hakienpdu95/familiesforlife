<?php

namespace Modules\Deployment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Deployment\Actions\CreateVerticalProjectAction;
use Modules\Deployment\Data\CreateVerticalProjectData;
use Modules\Project\Models\Project;

class DeploymentProjectController extends Controller
{
    public function index(Request $request): View
    {
        $vertical = $request->attributes->get('_vertical');

        $projects = Project::where('vertical_code', $vertical->code())
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('deployment::projects.index', compact('vertical', 'projects'));
    }

    public function create(Request $request): View
    {
        $vertical = $request->attributes->get('_vertical');

        return view('deployment::projects.create', compact('vertical'));
    }

    public function store(Request $request, CreateVerticalProjectAction $action): RedirectResponse
    {
        $vertical = $request->attributes->get('_vertical');
        $data     = CreateVerticalProjectData::validateAndCreate($request->all());
        $project  = $action->handle($data, $vertical);

        return redirect()
            ->route('deployment.projects.index', ['vertical' => $vertical->code()])
            ->with('success', "Dự án \"{$project->name}\" đã được tạo.");
    }
}
