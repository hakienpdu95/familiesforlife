<?php

namespace Modules\Deployment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Deployment\Actions\LogProgressAction;
use Modules\Deployment\Data\LogProgressData;
use Modules\Deployment\Models\DeploymentProgressLog;
use Modules\Deployment\Models\DeploymentTarget;

class DeploymentProgressController extends Controller
{
    public function index(Request $request): View
    {
        $vertical  = $request->attributes->get('_vertical');
        $targetId  = $request->integer('target_id');

        $targets = DeploymentTarget::where('vertical_code', $vertical->code())
            ->with('targetOrganization')
            ->orderBy('created_at')
            ->get();

        $phases      = $vertical->phases();
        $phaseLabels = $vertical->phaseLabels();

        $logs = collect();
        $currentTarget = null;

        if ($targetId) {
            $currentTarget = $targets->firstWhere('id', $targetId);
            if ($currentTarget) {
                $logs = DeploymentProgressLog::where('deployment_target_id', $targetId)
                    ->with(['loggedBy', 'checklistItem'])
                    ->orderByDesc('logged_at')
                    ->get();
            }
        }

        return view('deployment::progress.index', compact(
            'vertical', 'targets', 'logs', 'currentTarget', 'phases', 'phaseLabels'
        ));
    }

    public function store(Request $request, LogProgressAction $action): RedirectResponse
    {
        $vertical = $request->attributes->get('_vertical');
        $data     = LogProgressData::validateAndCreate($request->all());

        $action->handle($data);

        return back()->with('success', 'Đã ghi nhận tiến độ.');
    }
}
