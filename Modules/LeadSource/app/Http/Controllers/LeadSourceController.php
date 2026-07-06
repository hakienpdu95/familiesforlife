<?php

namespace Modules\LeadSource\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\LeadSource\Actions\CreateSourceAction;
use Modules\LeadSource\Actions\DeleteSourceAction;
use Modules\LeadSource\Actions\ToggleSourceAction;
use Modules\LeadSource\Actions\UpdateSourceAction;
use Modules\LeadSource\Data\Requests\CreateSourceData;
use Modules\LeadSource\Data\Requests\UpdateSourceData;
use Modules\LeadSource\Models\LeadSource;
use Modules\LeadSource\Queries\ListSourcesHandler;
use Modules\LeadSource\Queries\ListSourcesQuery;

class LeadSourceController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(LeadSource::class, 'source');
    }

    public function index(ListSourcesHandler $handler): View
    {
        $orgId   = $this->orgId();
        $sources = $handler->handle(new ListSourcesQuery($orgId, activeOnly: false));

        return view('lead-source::sources.index', compact('sources'));
    }

    public function create(): View
    {
        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        return view('lead-source::sources.create', compact('organizations', 'defaultOrgId', 'orgLocked'));
    }

    public function store(Request $request, CreateSourceAction $action): RedirectResponse
    {
        $data = CreateSourceData::validateAndCreate($request->all());
        $action->handle($data);

        return redirect()->route('lead-source.index')
            ->with('success', 'Đã thêm nguồn cơ hội mới.');
    }

    public function edit(LeadSource $source): View
    {
        [$organizations, , $orgLocked] = $this->_resolveOrganizations();

        return view('lead-source::sources.edit', compact('source', 'organizations', 'orgLocked'));
    }

    public function update(
        Request $request,
        LeadSource $source,
        UpdateSourceAction $action,
    ): RedirectResponse {
        $data = UpdateSourceData::validateAndCreate($request->all());
        $action->handle($source, $data);

        return redirect()->route('lead-source.index')
            ->with('success', 'Đã cập nhật nguồn cơ hội.');
    }

    public function destroy(LeadSource $source, DeleteSourceAction $action): RedirectResponse
    {
        $action->handle($source);

        return redirect()->route('lead-source.index')
            ->with('success', 'Đã xóa nguồn cơ hội.');
    }

    public function toggle(LeadSource $source, ToggleSourceAction $action): RedirectResponse
    {
        $this->authorize('update', $source);
        $action->handle($source);

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
