<?php

namespace Modules\Organization\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Enums\OrganizationStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Province;
use Modules\Organization\Actions\Backend\DestroyOrganizationAction;
use Modules\Organization\Actions\Backend\StoreOrganizationAction;
use Modules\Organization\Actions\Backend\UpdateOrganizationAction;
use Modules\Organization\Data\Requests\StoreOrganizationData;
use Modules\Organization\Data\Requests\UpdateOrganizationData;
use Modules\Organization\Models\Organization;
use Modules\Organization\Queries\GetOrganizationHandler;
use Modules\Organization\Queries\GetOrganizationQuery;

class OrganizationController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Organization::class, 'organization');
    }

    public function index()
    {
        // Single query merges all stat counts
        $counts = Organization::withoutTenant()
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_active,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_suspended',
                [OrganizationStatus::Active->value, OrganizationStatus::Suspended->value]
            )
            ->first();

        $totalAll       = (int) ($counts->total_all       ?? 0);
        $totalActive    = (int) ($counts->total_active    ?? 0);
        $totalSuspended = (int) ($counts->total_suspended ?? 0);

        $provinces = Province::where('is_active', true)
            ->orderBy('name')
            ->get(['province_code', 'name']);

        $statuses = collect(OrganizationStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        return view('organization::index', compact(
            'totalAll', 'totalActive', 'totalSuspended',
            'provinces', 'statuses'
        ));
    }

    public function create()
    {
        return view('organization::create');
    }

    public function store(Request $request, StoreOrganizationAction $action): RedirectResponse
    {
        $data = StoreOrganizationData::validateAndCreate($request->all());

        $organization = $action->handle($data);

        return redirect()->route('backend.organizations.show', $organization)
            ->with('success', 'Tổ chức "' . $organization->name . '" đã được tạo thành công.');
    }

    public function show(Organization $organization, GetOrganizationHandler $handler)
    {
        $organization = $handler->handle(new GetOrganizationQuery($organization));
        $members = $organization->latestMembers;

        return view('organization::show', compact('organization', 'members'));
    }

    public function edit(Organization $organization)
    {
        $organization->loadCount('members');

        return view('organization::edit', compact('organization'));
    }

    public function update(Request $request, Organization $organization, UpdateOrganizationAction $action): RedirectResponse
    {
        $data = UpdateOrganizationData::validateAndCreate($request->all());

        $action->handle($organization, $data);

        return redirect()->route('backend.organizations.show', $organization)
            ->with('success', 'Cập nhật tổ chức thành công.');
    }

    public function destroy(Request $request, Organization $organization, DestroyOrganizationAction $action): RedirectResponse|JsonResponse
    {
        $name = $action->handle($organization);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa tổ chức "' . $name . '".' ]);
        }

        return redirect()->route('backend.organizations.index')
            ->with('success', 'Đã xóa tổ chức "' . $name . '".');
    }
}
