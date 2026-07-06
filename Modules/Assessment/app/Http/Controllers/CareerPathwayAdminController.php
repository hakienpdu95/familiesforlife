<?php

namespace Modules\Assessment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Assessment\Models\CareerPathwayStep;
use Modules\Assessment\Models\CertificationDefinition;
use Modules\Assessment\Models\SandboxEnvironment;

class CareerPathwayAdminController extends Controller
{
    private const LEVELS = [
        'DIGITAL_BEGINNER',
        'DIGITAL_AWARE',
        'DIGITAL_PRACTITIONER',
        'DIGITAL_PROFESSIONAL',
        'DIGITAL_LEADER',
    ];

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $this->authorize('assessment.config');

        $orgId        = TenantContext::getOrganizationId();
        $isSuperAdmin = request()->user()?->hasRole('super-admin');

        $globalSteps = CareerPathwayStep::whereNull('organization_id')
            ->where('is_active', true)
            ->orderBy('step_order')
            ->get();

        $orgSteps = CareerPathwayStep::where('organization_id', $orgId)
            ->orderBy('step_order')
            ->get();

        return view('assessment::career-pathway.admin.index', compact(
            'globalSteps', 'orgSteps', 'isSuperAdmin'
        ));
    }

    // ── Create / Store ────────────────────────────────────────────────────────

    public function create(): View
    {
        $this->authorize('assessment.config');

        $isSuperAdmin  = request()->user()?->hasRole('super-admin');
        $currentOrg    = TenantContext::resolve();
        $organizations = $isSuperAdmin
            ? Organization::where('is_system', false)->orderBy('name')->get()
            : collect();

        [$envCodes, $certCodes] = $this->loadSelectors();

        return view('assessment::career-pathway.admin.create', [
            'levels'        => self::LEVELS,
            'envCodes'      => $envCodes,
            'certCodes'     => $certCodes,
            'isSuperAdmin'  => $isSuperAdmin,
            'currentOrg'    => $currentOrg,
            'organizations' => $organizations,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('assessment.config');

        $isSuperAdmin = $request->user()?->hasRole('super-admin');
        $data = $this->validateStep($request, $isSuperAdmin);

        if ($isSuperAdmin) {
            $organizationId = $request->input('scope') === 'global'
                ? null
                : (int) $request->input('organization_id');
        } else {
            $organizationId = TenantContext::getOrganizationId();
        }

        CareerPathwayStep::create([
            'organization_id'              => $organizationId,
            'from_level'                   => $data['from_level'],
            'to_level'                     => $data['to_level'],
            'step_order'                   => $data['step_order'] ?? 0,
            'title'                        => $data['title'],
            'description'                  => $data['description'] ?? null,
            'required_cert_code'           => $data['required_cert_code'] ?? null,
            'recommended_kc_tag'           => $data['recommended_kc_tag'] ?? null,
            'recommended_sandbox_env_code' => $data['recommended_sandbox_env_code'] ?? null,
            'estimated_weeks'              => $data['estimated_weeks'] ?? null,
            'is_active'                    => $request->boolean('is_active', true),
        ]);

        return redirect()->route('backend.career-pathway-admin.index')
            ->with('success', 'Đã tạo bước lộ trình.');
    }

    // ── Edit / Update ─────────────────────────────────────────────────────────

    public function edit(CareerPathwayStep $careerPathwayStep): View
    {
        $this->authorize('assessment.config');
        $this->authorizeStepAccess($careerPathwayStep);

        $stepOrgName = $careerPathwayStep->organization_id
            ? (Organization::withoutTenant()->find($careerPathwayStep->organization_id)?->name ?? 'Không xác định')
            : null;

        [$envCodes, $certCodes] = $this->loadSelectors();

        return view('assessment::career-pathway.admin.edit', [
            'step'        => $careerPathwayStep,
            'levels'      => self::LEVELS,
            'envCodes'    => $envCodes,
            'certCodes'   => $certCodes,
            'stepOrgName' => $stepOrgName,
        ]);
    }

    public function update(Request $request, CareerPathwayStep $careerPathwayStep): RedirectResponse
    {
        $this->authorize('assessment.config');
        $this->authorizeStepAccess($careerPathwayStep);

        $isSuperAdmin = $request->user()?->hasRole('super-admin');
        $data = $this->validateStep($request, $isSuperAdmin);

        $careerPathwayStep->update([
            'from_level'                   => $data['from_level'],
            'to_level'                     => $data['to_level'],
            'step_order'                   => $data['step_order'] ?? 0,
            'title'                        => $data['title'],
            'description'                  => $data['description'] ?? null,
            'required_cert_code'           => $data['required_cert_code'] ?? null,
            'recommended_kc_tag'           => $data['recommended_kc_tag'] ?? null,
            'recommended_sandbox_env_code' => $data['recommended_sandbox_env_code'] ?? null,
            'estimated_weeks'              => $data['estimated_weeks'] ?? null,
            'is_active'                    => $request->boolean('is_active'),
        ]);

        return redirect()->route('backend.career-pathway-admin.index')
            ->with('success', 'Đã cập nhật bước lộ trình.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function destroy(CareerPathwayStep $careerPathwayStep): RedirectResponse
    {
        $this->authorize('assessment.config');
        $this->authorizeStepAccess($careerPathwayStep);

        $careerPathwayStep->delete();

        return redirect()->route('backend.career-pathway-admin.index')
            ->with('success', 'Đã xoá bước lộ trình.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function canEditStep(CareerPathwayStep $step): bool
    {
        $user  = request()->user();
        $orgId = TenantContext::getOrganizationId();

        if ($user?->hasRole('super-admin')) {
            return true;
        }

        return $step->organization_id !== null && $step->organization_id === $orgId;
    }

    private function authorizeStepAccess(CareerPathwayStep $step): void
    {
        if (! $this->canEditStep($step)) {
            abort(403, 'Chỉ super-admin mới chỉnh sửa được bước lộ trình hệ thống.');
        }
    }

    private function validateStep(Request $request, bool $isSuperAdmin): array
    {
        $rules = [
            'from_level'                   => 'required|in:'.implode(',', self::LEVELS),
            'to_level'                     => 'required|in:'.implode(',', self::LEVELS),
            'step_order'                   => 'nullable|integer|min:0',
            'title'                        => 'required|string|max:200',
            'description'                  => 'nullable|string|max:1000',
            'required_cert_code'           => 'nullable|string|max:50',
            'recommended_kc_tag'           => 'nullable|string|max:100',
            'recommended_sandbox_env_code' => 'nullable|string|max:50',
            'estimated_weeks'              => 'nullable|integer|min:1|max:52',
            'is_active'                    => 'boolean',
        ];

        if ($isSuperAdmin && ! request()->route('careerPathwayStep')) {
            $rules['scope']           = 'required|in:global,org';
            $rules['organization_id'] = 'required_if:scope,org|nullable|exists:organizations,id';
        }

        $messages = [
            'organization_id.required_if' => 'Vui lòng chọn tổ chức khi phạm vi là "Riêng tổ chức cụ thể".',
            'organization_id.exists'      => 'Tổ chức được chọn không hợp lệ.',
        ];

        return $request->validate($rules, $messages);
    }

    private function loadSelectors(): array
    {
        $orgId = TenantContext::getOrganizationId();

        $envCodes = SandboxEnvironment::where(function ($q) use ($orgId) {
                $q->whereNull('organization_id')->orWhere('organization_id', $orgId);
            })
            ->where('is_active', true)
            ->orderBy('tier')->orderBy('sort_order')
            ->pluck('name', 'env_code');

        $certCodes = CertificationDefinition::where(function ($q) use ($orgId) {
                $q->whereNull('organization_id')->orWhere('organization_id', $orgId);
            })
            ->where('is_active', true)
            ->orderBy('level_order')
            ->pluck('name', 'cert_code');

        return [$envCodes, $certCodes];
    }
}
