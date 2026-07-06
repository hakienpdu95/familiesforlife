<?php

namespace Modules\LeadPipelineStage\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\LeadPipelineStage\Actions\CreateStageAction;
use Modules\LeadPipelineStage\Actions\DeleteStageAction;
use Modules\LeadPipelineStage\Actions\ToggleStageAction;
use Modules\LeadPipelineStage\Actions\UpdateStageAction;
use Modules\LeadPipelineStage\Data\Requests\CreateStageData;
use Modules\LeadPipelineStage\Data\Requests\UpdateStageData;
use Modules\LeadPipelineStage\Models\LeadPipelineStage;
use Modules\LeadPipelineStage\Queries\ListStagesHandler;
use Modules\LeadPipelineStage\Queries\ListStagesQuery;

class LeadPipelineStageController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(LeadPipelineStage::class, 'stage');
    }

    public function index(ListStagesHandler $handler): View
    {
        $orgId  = $this->orgId();
        $stages = $handler->handle(new ListStagesQuery($orgId, activeOnly: false));

        return view('lead-pipeline-stage::stages.index', compact('stages'));
    }

    public function create(): View
    {
        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        return view('lead-pipeline-stage::stages.create', compact('organizations', 'defaultOrgId', 'orgLocked'));
    }

    public function store(Request $request, CreateStageAction $action): RedirectResponse
    {
        $data = CreateStageData::validateAndCreate($request->all());
        $action->handle($data);

        return redirect()->route('lead-pipeline-stage.index')
            ->with('success', 'Đã thêm tình trạng mới.');
    }

    public function edit(LeadPipelineStage $stage): View
    {
        [$organizations, , $orgLocked] = $this->_resolveOrganizations();

        return view('lead-pipeline-stage::stages.edit', compact('stage', 'organizations', 'orgLocked'));
    }

    public function update(
        Request $request,
        LeadPipelineStage $stage,
        UpdateStageAction $action,
    ): RedirectResponse {
        $data = UpdateStageData::validateAndCreate($request->all());
        $action->handle($stage, $data);

        return redirect()->route('lead-pipeline-stage.index')
            ->with('success', 'Đã cập nhật tình trạng.');
    }

    public function destroy(LeadPipelineStage $stage, DeleteStageAction $action): RedirectResponse
    {
        $action->handle($stage);

        return redirect()->route('lead-pipeline-stage.index')
            ->with('success', 'Đã xóa tình trạng.');
    }

    public function toggle(LeadPipelineStage $stage, ToggleStageAction $action): RedirectResponse
    {
        $this->authorize('update', $stage);
        $action->handle($stage);

        return back()->with('success', 'Đã cập nhật trạng thái.');
    }

    private function orgId(): int
    {
        return TenantContext::getOrganizationId() ?? abort(403, 'No organization context.');
    }

    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            return [Organization::where('id', $userOrgId)->get(['id', 'name']), $userOrgId, true];
        }
        return [Organization::orderBy('name')->get(['id', 'name']), null, false];
    }
}
