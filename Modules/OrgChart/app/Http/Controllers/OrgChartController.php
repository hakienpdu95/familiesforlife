<?php

namespace Modules\OrgChart\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Branch\Models\Branch;
use Modules\OrgChart\Actions\Backend\DestroyOrgChartConfigAction;
use Modules\OrgChart\Actions\Backend\StoreOrgChartConfigAction;
use Modules\OrgChart\Actions\Backend\UpdateOrgChartConfigAction;
use Modules\OrgChart\Data\Requests\StoreOrgChartConfigData;
use Modules\OrgChart\Data\Requests\UpdateOrgChartConfigData;
use Modules\OrgChart\Enums\OrgChartGroupBy;
use Modules\OrgChart\Enums\OrgChartViewType;
use Modules\OrgChart\Models\OrgChartConfig;
use Modules\OrgChart\Queries\GetOrgChartTreeHandler;
use Modules\OrgChart\Queries\GetOrgChartTreeQuery;

class OrgChartController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(OrgChartConfig::class, 'orgChartConfig');
    }

    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            return [Organization::where('id', $userOrgId)->get(['id', 'name']), $userOrgId, true];
        }
        return [Organization::orderBy('name')->get(['id', 'name']), null, false];
    }

    public function index()
    {
        $orgId  = TenantContext::getOrganizationId();
        $total  = OrgChartConfig::withoutTenant()->where('organization_id', $orgId)->count();
        $default = OrgChartConfig::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('is_default', 1)
            ->first();

        $viewTypes = collect(OrgChartViewType::cases())
            ->map(fn ($v) => ['value' => $v->value, 'text' => $v->label()])
            ->all();

        $groupBys = collect(OrgChartGroupBy::cases())
            ->map(fn ($g) => ['value' => $g->value, 'text' => $g->label()])
            ->all();

        return view('orgchart::index', compact('total', 'default', 'viewTypes', 'groupBys'));
    }

    public function show(OrgChartConfig $orgChartConfig, GetOrgChartTreeHandler $handler)
    {
        $orgChartConfig->load('scopeBranch');

        $treeData = $handler->handle(new GetOrgChartTreeQuery($orgChartConfig));

        $orgId = TenantContext::getOrganizationId();
        $configs = OrgChartConfig::withoutTenant()
            ->where('organization_id', $orgId)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'is_default']);

        return view('orgchart::show', compact('orgChartConfig', 'treeData', 'configs'));
    }

    public function create()
    {
        $orgId = TenantContext::getOrganizationId();

        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        // Org-user: load server-side. Super-admin: load via API on org change.
        $branches = $orgLocked
            ? Branch::withoutTenant()
                ->where('organization_id', $orgId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
            : collect();

        $viewTypes = collect(OrgChartViewType::cases())
            ->map(fn ($v) => ['value' => $v->value, 'label' => $v->label()])
            ->all();

        $groupBys = collect(OrgChartGroupBy::cases())
            ->map(fn ($g) => ['value' => $g->value, 'label' => $g->label()])
            ->all();

        return view('orgchart::create', compact(
            'branches', 'viewTypes', 'groupBys',
            'organizations', 'defaultOrgId', 'orgLocked'
        ));
    }

    public function store(Request $request, StoreOrgChartConfigAction $action): RedirectResponse
    {
        $input = $this->normalizeCheckboxes($request->all());
        $data   = StoreOrgChartConfigData::validateAndCreate($input);
        $config = $action->handle($data);

        return redirect()->route('backend.org-charts.show', $config)
            ->with('success', 'Cấu hình sơ đồ "' . $config->name . '" đã được tạo thành công.');
    }

    public function edit(OrgChartConfig $orgChartConfig)
    {
        $orgId   = TenantContext::getOrganizationId();
        $branches = Branch::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $viewTypes = collect(OrgChartViewType::cases())
            ->map(fn ($v) => ['value' => $v->value, 'label' => $v->label()])
            ->all();

        $groupBys = collect(OrgChartGroupBy::cases())
            ->map(fn ($g) => ['value' => $g->value, 'label' => $g->label()])
            ->all();

        return view('orgchart::edit', compact('orgChartConfig', 'branches', 'viewTypes', 'groupBys'));
    }

    public function update(Request $request, OrgChartConfig $orgChartConfig, UpdateOrgChartConfigAction $action): RedirectResponse
    {
        $input = $this->normalizeCheckboxes($request->all());
        $data = UpdateOrgChartConfigData::validateAndCreate($input);
        $action->handle($orgChartConfig, $data);

        return redirect()->route('backend.org-charts.show', $orgChartConfig)
            ->with('success', 'Cập nhật cấu hình sơ đồ thành công.');
    }

    private function normalizeCheckboxes(array $input): array
    {
        $boolFields = [
            'show_avatar', 'show_job_title', 'show_employee_code',
            'show_department', 'show_branch', 'expand_by_default', 'is_default',
        ];
        foreach ($boolFields as $field) {
            $input[$field] = isset($input[$field]) && $input[$field] ? true : false;
        }
        return $input;
    }

    public function destroy(Request $request, OrgChartConfig $orgChartConfig, DestroyOrgChartConfigAction $action): RedirectResponse|JsonResponse
    {
        $name = $action->handle($orgChartConfig);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa cấu hình "' . $name . '".']);
        }

        return redirect()->route('backend.org-charts.index')
            ->with('success', 'Đã xóa cấu hình "' . $name . '".');
    }
}
