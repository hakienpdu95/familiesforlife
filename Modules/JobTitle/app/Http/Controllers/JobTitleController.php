<?php

namespace Modules\JobTitle\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\JobTitle\Actions\Backend\DestroyJobTitleAction;
use Modules\JobTitle\Actions\Backend\StoreJobTitleAction;
use Modules\JobTitle\Actions\Backend\UpdateJobTitleAction;
use Modules\JobTitle\Data\Requests\StoreJobTitleData;
use Modules\JobTitle\Data\Requests\UpdateJobTitleData;
use Modules\JobTitle\Enums\JobTitleCategory;
use Modules\JobTitle\Models\JobTitle;

class JobTitleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(JobTitle::class, 'job_title');
    }

    public function index()
    {
        $orgId = TenantContext::getOrganizationId();

        $counts = JobTitle::withoutTenant()
            ->where('organization_id', $orgId)
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as total_active,
                 SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as total_inactive,
                 SUM(CASE WHEN is_system = 1 THEN 1 ELSE 0 END) as total_system'
            )
            ->first();

        $totalAll      = (int) ($counts->total_all      ?? 0);
        $totalActive   = (int) ($counts->total_active   ?? 0);
        $totalInactive = (int) ($counts->total_inactive ?? 0);
        $totalSystem   = (int) ($counts->total_system   ?? 0);

        $categories = collect(JobTitleCategory::cases())
            ->map(fn ($c) => ['value' => $c->value, 'text' => $c->label()])
            ->all();

        $statuses = [
            ['value' => '1', 'text' => 'Đang dùng'],
            ['value' => '0', 'text' => 'Vô hiệu'],
        ];

        return view('jobtitle::index', compact(
            'totalAll', 'totalActive', 'totalInactive', 'totalSystem',
            'categories', 'statuses'
        ));
    }

    public function options(Request $request): JsonResponse
    {
        $this->authorize('viewAny', JobTitle::class);

        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            $orgId = $userOrgId;
        } else {
            $orgId = $request->integer('organization_id') ?: TenantContext::getOrganizationId();
        }

        $rows = JobTitle::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('level')
            ->get(['id', 'name', 'level']);

        return response()->json($rows->map(fn ($jt) => [
            'id'   => $jt->id,
            'text' => $jt->name . ' (Lv.' . $jt->level . ')',
        ]));
    }

    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            return [Organization::where('id', $userOrgId)->get(['id', 'name']), $userOrgId, true];
        }
        return [Organization::orderBy('name')->get(['id', 'name']), null, false];
    }

    public function create()
    {
        $categories = collect(JobTitleCategory::cases())
            ->map(fn ($c) => ['value' => $c->value, 'text' => $c->label()])
            ->all();

        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        return view('jobtitle::create', compact('categories', 'organizations', 'defaultOrgId', 'orgLocked'));
    }

    public function store(Request $request, StoreJobTitleAction $action): RedirectResponse
    {
        $data     = StoreJobTitleData::validateAndCreate($request->all());
        $jobTitle = $action->handle($data);

        return redirect()->route('backend.job-titles.show', $jobTitle)
            ->with('success', 'Chức danh "' . $jobTitle->name . '" đã được tạo thành công.');
    }

    public function show(JobTitle $jobTitle)
    {
        return view('jobtitle::show', compact('jobTitle'));
    }

    public function edit(JobTitle $jobTitle)
    {
        $categories = collect(JobTitleCategory::cases())
            ->map(fn ($c) => ['value' => $c->value, 'text' => $c->label()])
            ->all();

        [$organizations, , $orgLocked] = $this->_resolveOrganizations();

        return view('jobtitle::edit', compact('jobTitle', 'categories', 'organizations', 'orgLocked'));
    }

    public function update(Request $request, JobTitle $jobTitle, UpdateJobTitleAction $action): RedirectResponse
    {
        $data = UpdateJobTitleData::validateAndCreate($request->all());
        $action->handle($jobTitle, $data);

        return redirect()->route('backend.job-titles.show', $jobTitle)
            ->with('success', 'Cập nhật chức danh thành công.');
    }

    public function destroy(Request $request, JobTitle $jobTitle, DestroyJobTitleAction $action): RedirectResponse|JsonResponse
    {
        $name = $action->handle($jobTitle);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa chức danh "' . $name . '".' ]);
        }

        return redirect()->route('backend.job-titles.index')
            ->with('success', 'Đã xóa chức danh "' . $name . '".');
    }
}
