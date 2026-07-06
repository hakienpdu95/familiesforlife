<?php

namespace Modules\KcItem\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\KcItem\Actions\Backend\DestroyKcTagAction;
use Modules\KcItem\Actions\Backend\StoreKcTagAction;
use Modules\KcItem\Actions\Backend\UpdateKcTagAction;
use Modules\KcItem\Data\Requests\StoreKcTagData;
use Modules\KcItem\Data\Requests\UpdateKcTagData;
use Modules\KcItem\Models\KcTag;

class KcTagController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(KcTag::class, 'kc_tag');
    }

    public function index()
    {
        $orgId = TenantContext::getOrganizationId();

        $totalAll = KcTag::withoutTenant()
            ->where('organization_id', $orgId)
            ->count();

        return view('kcitem::kc-tag.index', compact('totalAll'));
    }

    public function create()
    {
        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        return view('kcitem::kc-tag.create', compact('organizations', 'defaultOrgId', 'orgLocked'));
    }

    public function store(Request $request, StoreKcTagAction $action): RedirectResponse
    {
        $data  = StoreKcTagData::validateAndCreate($request->all());
        $kcTag = $action->handle($data);

        return redirect()->route('backend.kc-tags.index')
            ->with('success', 'Tag "' . $kcTag->name . '" đã được tạo thành công.');
    }

    public function show(KcTag $kcTag): RedirectResponse
    {
        return redirect()->route('backend.kc-tags.edit', $kcTag);
    }

    public function edit(KcTag $kcTag)
    {
        [$organizations, , $orgLocked] = $this->_resolveOrganizations();

        return view('kcitem::kc-tag.edit', compact('kcTag', 'organizations', 'orgLocked'));
    }

    public function update(Request $request, KcTag $kcTag, UpdateKcTagAction $action): RedirectResponse
    {
        $data = UpdateKcTagData::validateAndCreate($request->all());
        $action->handle($kcTag, $data);

        return redirect()->route('backend.kc-tags.index')
            ->with('success', 'Cập nhật tag thành công.');
    }

    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            return [Organization::where('id', $userOrgId)->get(['id', 'name']), $userOrgId, true];
        }
        return [Organization::orderBy('name')->get(['id', 'name']), null, false];
    }

    public function destroy(Request $request, KcTag $kcTag, DestroyKcTagAction $action): RedirectResponse|JsonResponse
    {
        $name = $action->handle($kcTag);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa tag "' . $name . '".']);
        }

        return redirect()->route('backend.kc-tags.index')
            ->with('success', 'Đã xóa tag "' . $name . '".');
    }
}
