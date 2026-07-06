<?php

namespace Modules\Assessment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Assessment\Events\CertificationIssued;
use Modules\Assessment\Models\CertificationDefinition;
use Modules\Assessment\Models\WorkforceCertification;
use Modules\Assessment\Models\WorkforceProfile;
use Modules\Assessment\Services\CertificationQrService;

class CertificationAdminController extends Controller
{
    // ── Definitions index ─────────────────────────────────────────────────────

    public function index(): View
    {
        $this->authorize('assessment.config');

        $orgId        = TenantContext::getOrganizationId();
        $isSuperAdmin = request()->user()?->hasRole('super-admin');

        $globalDefs = CertificationDefinition::whereNull('organization_id')
            ->orderBy('cert_type_code')->orderBy('level_order')
            ->get();

        $orgDefs = CertificationDefinition::where('organization_id', $orgId)
            ->orderBy('cert_type_code')->orderBy('level_order')
            ->get();

        $stats = [
            'total_active'  => WorkforceCertification::where('organization_id', $orgId)->where('status', 'active')->count(),
            'total_issued'  => WorkforceCertification::where('organization_id', $orgId)->count(),
            'expiring_soon' => WorkforceCertification::where('organization_id', $orgId)
                ->where('status', 'active')
                ->where('expires_at', '<=', now()->addDays(30))
                ->count(),
        ];

        return view('assessment::certifications.admin.index', compact(
            'globalDefs', 'orgDefs', 'stats', 'isSuperAdmin'
        ));
    }

    // ── Definition CRUD ───────────────────────────────────────────────────────

    public function createDef(): View
    {
        $this->authorize('assessment.config');

        $isSuperAdmin  = request()->user()?->hasRole('super-admin');
        $currentOrg    = TenantContext::resolve();
        $organizations = $isSuperAdmin
            ? Organization::where('is_system', false)->orderBy('name')->get()
            : collect();

        return view('assessment::certifications.admin.definition.create', [
            'isSuperAdmin'  => $isSuperAdmin,
            'currentOrg'    => $currentOrg,
            'organizations' => $organizations,
        ]);
    }

    public function storeDef(Request $request): RedirectResponse
    {
        $this->authorize('assessment.config');

        $isSuperAdmin = $request->user()?->hasRole('super-admin');
        $data = $this->validateDef($request, $isSuperAdmin);

        if ($isSuperAdmin) {
            $organizationId = $request->input('scope') === 'global'
                ? null
                : (int) $request->input('organization_id');
        } else {
            $organizationId = TenantContext::getOrganizationId();
        }

        CertificationDefinition::create([
            'uuid'                        => Str::uuid(),
            'organization_id'             => $organizationId,
            'cert_code'                   => strtoupper($data['cert_code']),
            'cert_type_code'              => strtoupper($data['cert_type_code']),
            'name'                        => $data['name'],
            'level_code'                  => $data['level_code'],
            'level_order'                 => $this->levelOrder($data['level_code']),
            'description'                 => $data['description'] ?? null,
            'validity_months'             => $data['validity_months'] ?? 24,
            'min_workforce_score'         => $data['min_workforce_score'] ?? null,
            'min_kpi_achievement_pct'     => $data['min_kpi_achievement_pct'] ?? null,
            'min_sandbox_hours'           => $data['min_sandbox_hours'] ?? null,
            'min_sandbox_score'           => $data['min_sandbox_score'] ?? null,
            'requires_impact_score'       => $request->boolean('requires_impact_score'),
            'requires_portfolio_approval' => $request->boolean('requires_portfolio_approval'),
            'is_active'                   => $request->boolean('is_active', true),
        ]);

        return redirect()->route('backend.certs-admin.index')
            ->with('success', 'Đã tạo định nghĩa chứng nhận.');
    }

    public function editDef(CertificationDefinition $certificationDefinition): View
    {
        $this->authorize('assessment.config');
        $this->authorizeDefAccess($certificationDefinition);

        $isSuperAdmin = request()->user()?->hasRole('super-admin');
        $defOrgName   = $certificationDefinition->organization_id
            ? (Organization::withoutTenant()->find($certificationDefinition->organization_id)?->name
                ?? 'Tổ chức #'.$certificationDefinition->organization_id)
            : null;

        return view('assessment::certifications.admin.definition.edit', [
            'def'          => $certificationDefinition,
            'isSuperAdmin' => $isSuperAdmin,
            'defOrgName'   => $defOrgName,
        ]);
    }

    public function updateDef(Request $request, CertificationDefinition $certificationDefinition): RedirectResponse
    {
        $this->authorize('assessment.config');
        $this->authorizeDefAccess($certificationDefinition);

        $isSuperAdmin = $request->user()?->hasRole('super-admin');
        $data = $this->validateDef($request, $isSuperAdmin, editing: true);

        $certificationDefinition->update([
            'name'                        => $data['name'],
            'description'                 => $data['description'] ?? null,
            'validity_months'             => $data['validity_months'] ?? 24,
            'min_workforce_score'         => $data['min_workforce_score'] ?? null,
            'min_kpi_achievement_pct'     => $data['min_kpi_achievement_pct'] ?? null,
            'min_sandbox_hours'           => $data['min_sandbox_hours'] ?? null,
            'min_sandbox_score'           => $data['min_sandbox_score'] ?? null,
            'requires_impact_score'       => $request->boolean('requires_impact_score'),
            'requires_portfolio_approval' => $request->boolean('requires_portfolio_approval'),
            'is_active'                   => $request->boolean('is_active'),
        ]);

        return redirect()->route('backend.certs-admin.index')
            ->with('success', 'Đã cập nhật định nghĩa chứng nhận.');
    }

    public function destroyDef(CertificationDefinition $certificationDefinition): RedirectResponse
    {
        $this->authorize('assessment.config');
        $this->authorizeDefAccess($certificationDefinition);

        if ($certificationDefinition->certifications()->exists()) {
            return back()->with('error', 'Không thể xoá: đã có chứng nhận được cấp theo định nghĩa này.');
        }

        $certificationDefinition->delete();

        return redirect()->route('backend.certs-admin.index')
            ->with('success', 'Đã xoá định nghĩa chứng nhận.');
    }

    // ── Issued certifications list ────────────────────────────────────────────

    public function issued(Request $request): View
    {
        $this->authorize('assessment.results');

        $orgId = TenantContext::getOrganizationId();

        $certs = WorkforceCertification::where('organization_id', $orgId)
            ->with(['definition', 'profile.employee'])
            ->orderByDesc('issued_at')
            ->paginate(30);

        return view('assessment::certifications.admin.issued', compact('certs'));
    }

    // ── Manual issue ──────────────────────────────────────────────────────────

    public function issueForm(): View
    {
        $this->authorize('assessment.results');

        $orgId = TenantContext::getOrganizationId();

        $profiles = WorkforceProfile::where('organization_id', $orgId)
            ->with('employee')
            ->orderBy('id')
            ->get();

        $definitions = CertificationDefinition::where(function ($q) use ($orgId) {
                $q->whereNull('organization_id')->orWhere('organization_id', $orgId);
            })
            ->where('is_active', true)
            ->orderBy('cert_type_code')->orderBy('level_order')
            ->get();

        return view('assessment::certifications.admin.issue-form', compact('profiles', 'definitions'));
    }

    public function issue(Request $request): RedirectResponse
    {
        $this->authorize('assessment.results');

        $data = $request->validate([
            'workforce_profile_id' => 'required|exists:workforce_profiles,id',
            'cert_definition_id'   => 'required|exists:certification_definitions,id',
            'notes'                => 'nullable|string|max:500',
        ], [
            'workforce_profile_id.required' => 'Vui lòng chọn nhân viên.',
            'workforce_profile_id.exists'   => 'Nhân viên được chọn không hợp lệ.',
            'cert_definition_id.required'   => 'Vui lòng chọn định nghĩa chứng nhận.',
            'cert_definition_id.exists'     => 'Định nghĩa chứng nhận không hợp lệ.',
            'notes.max'                     => 'Ghi chú không được vượt quá :max ký tự.',
        ]);

        $orgId   = TenantContext::getOrganizationId();
        $profile = WorkforceProfile::withoutTenant()->findOrFail($data['workforce_profile_id']);
        $def     = CertificationDefinition::findOrFail($data['cert_definition_id']);

        // Prevent duplicates
        $exists = WorkforceCertification::withoutTenant()
            ->where('workforce_profile_id', $profile->id)
            ->where('cert_definition_id', $def->id)
            ->where('status', 'active')
            ->exists();

        if ($exists) {
            return back()->with('error', 'Nhân viên đã có chứng nhận này ở trạng thái active.');
        }

        $validityMonths = $def->validity_months ?? 24;
        $compositeScore = round(
            ($profile->tdwcf_score       ?? 0) * 0.30 +
            ($profile->sandbox_score_avg ?? 0) * 0.25 +
            ($profile->impact_score      ?? 0) * 0.25,
            2
        );

        $cert = WorkforceCertification::create([
            'uuid'                     => Str::uuid(),
            'organization_id'          => $orgId,
            'workforce_profile_id'     => $profile->id,
            'cert_definition_id'       => $def->id,
            'assessment_score_at_issue'=> $profile->tdwcf_score,
            'sandbox_score_at_issue'   => $profile->sandbox_score_avg,
            'impact_score_at_issue'    => $profile->impact_score,
            'composite_score_at_issue' => $compositeScore,
            'status'                   => 'active',
            'issued_at'                => now(),
            'expires_at'               => now()->addMonths($validityMonths),
            'certificate_number'       => strtoupper('CERT-'.Str::random(10)),
            'issued_by'                => $request->user()->id,
            'human_reviewer_id'        => $request->user()->id,
            'reviewed_at'              => now(),
        ]);

        app(CertificationQrService::class)->generateAndStore($cert);

        event(new CertificationIssued($cert, $profile));

        return redirect()->route('backend.certs-admin.issued')
            ->with('success', 'Đã cấp chứng nhận thủ công cho nhân viên.');
    }

    // ── Revoke ────────────────────────────────────────────────────────────────

    public function revoke(Request $request, WorkforceCertification $workforceCertification): RedirectResponse
    {
        $this->authorize('assessment.results');

        $data = $request->validate([
            'revoked_reason' => 'required|string|max:300',
        ], [
            'revoked_reason.required' => 'Vui lòng nhập lý do thu hồi.',
            'revoked_reason.max'      => 'Lý do thu hồi không được vượt quá :max ký tự.',
        ]);

        if ($workforceCertification->status === 'revoked') {
            return back()->with('error', 'Chứng nhận đã bị thu hồi trước đó.');
        }

        $workforceCertification->update([
            'status'         => 'revoked',
            'revoked_at'     => now(),
            'revoked_reason' => $data['revoked_reason'],
        ]);

        return back()->with('success', 'Đã thu hồi chứng nhận.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function canEditDef(CertificationDefinition $def): bool
    {
        $user  = request()->user();
        $orgId = TenantContext::getOrganizationId();

        if ($user?->hasRole('super-admin')) return true;
        return $def->organization_id !== null && $def->organization_id === $orgId;
    }

    private function authorizeDefAccess(CertificationDefinition $def): void
    {
        if (! $this->canEditDef($def)) {
            abort(403, 'Định nghĩa chứng nhận hệ thống chỉ super-admin mới chỉnh sửa được.');
        }
    }

    private function levelOrder(string $level): int
    {
        return ['FOUNDATION' => 1, 'PRACTITIONER' => 2, 'PROFESSIONAL' => 3, 'LEADER' => 4][$level] ?? 1;
    }

    private function validateDef(Request $request, bool $isSuperAdmin, bool $editing = false): array
    {
        $rules = [
            'name'                        => 'required|string|max:200',
            'description'                 => 'nullable|string|max:500',
            'validity_months'             => 'required|integer|min:1|max:120',
            'min_workforce_score'         => 'nullable|numeric|min:0|max:100',
            'min_kpi_achievement_pct'     => 'nullable|numeric|min:0|max:100',
            'min_sandbox_hours'           => 'nullable|integer|min:0',
            'min_sandbox_score'           => 'nullable|numeric|min:0|max:100',
            'requires_impact_score'       => 'boolean',
            'requires_portfolio_approval' => 'boolean',
            'is_active'                   => 'boolean',
        ];

        if (! $editing) {
            $rules['cert_code']      = 'required|string|max:50|regex:/^[A-Z0-9_]+$/|unique:certification_definitions,cert_code';
            $rules['cert_type_code'] = 'required|string|max:30|regex:/^[A-Z0-9_]+$/';
            $rules['level_code']     = 'required|in:FOUNDATION,PRACTITIONER,PROFESSIONAL,LEADER';
        }

        if ($isSuperAdmin && ! $editing) {
            $rules['scope']           = 'required|in:global,org';
            $rules['organization_id'] = 'required_if:scope,org|nullable|exists:organizations,id';
        }

        $messages = [
            'name.required'                    => 'Vui lòng nhập tên chứng nhận.',
            'name.max'                         => 'Tên không được vượt quá :max ký tự.',
            'validity_months.required'         => 'Vui lòng nhập thời hạn hiệu lực.',
            'validity_months.integer'          => 'Thời hạn hiệu lực phải là số nguyên.',
            'validity_months.min'              => 'Thời hạn hiệu lực phải ít nhất :min tháng.',
            'validity_months.max'              => 'Thời hạn hiệu lực không được vượt quá :max tháng.',
            'min_workforce_score.numeric'      => 'Điểm TDWCF tối thiểu phải là số.',
            'min_workforce_score.min'          => 'Điểm TDWCF không được âm.',
            'min_workforce_score.max'          => 'Điểm TDWCF không được vượt quá :max.',
            'min_kpi_achievement_pct.numeric'  => 'KPI tối thiểu phải là số.',
            'min_kpi_achievement_pct.max'      => 'KPI tối thiểu không được vượt quá :max%.',
            'min_sandbox_hours.integer'        => 'Số giờ sandbox phải là số nguyên.',
            'min_sandbox_hours.min'            => 'Số giờ sandbox không được âm.',
            'min_sandbox_score.numeric'        => 'Điểm sandbox tối thiểu phải là số.',
            'min_sandbox_score.max'            => 'Điểm sandbox không được vượt quá :max.',
            'cert_code.required'               => 'Vui lòng nhập cert code.',
            'cert_code.max'                    => 'Cert code không được vượt quá :max ký tự.',
            'cert_code.regex'                  => 'Cert code chỉ được dùng CHỮ HOA, số và gạch dưới.',
            'cert_code.unique'                 => 'Cert code này đã tồn tại trong hệ thống.',
            'cert_type_code.required'          => 'Vui lòng nhập type code.',
            'cert_type_code.max'               => 'Type code không được vượt quá :max ký tự.',
            'cert_type_code.regex'             => 'Type code chỉ được dùng CHỮ HOA, số và gạch dưới.',
            'level_code.required'              => 'Vui lòng chọn cấp độ.',
            'level_code.in'                    => 'Cấp độ không hợp lệ.',
            'scope.required'                   => 'Vui lòng chọn phạm vi.',
            'scope.in'                         => 'Phạm vi không hợp lệ.',
            'organization_id.required_if'      => 'Vui lòng chọn tổ chức khi phạm vi là "Riêng tổ chức cụ thể".',
            'organization_id.exists'           => 'Tổ chức được chọn không hợp lệ.',
        ];

        return $request->validate($rules, $messages);
    }
}
