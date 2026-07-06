<?php

namespace Modules\Assessment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Assessment\Models\Assessment;

class AssessmentController extends Controller
{
    public function index(): View
    {
        $this->authorize('assessment.view');

        $user  = auth()->user();
        $query = Assessment::withoutTenant();

        // Super-admin không thuộc org nào → xem tất cả assessments trên platform.
        // Org-user → chỉ xem global (org_id NULL) + org của họ.
        if ($user->organization_id !== null) {
            $orgId = TenantContext::isSet() ? TenantContext::getOrganizationId() : $user->organization_id;
            $query->where(function ($q) use ($orgId) {
                $q->whereNull('organization_id')
                  ->orWhere('organization_id', $orgId);
            });
        }

        $assessments = $query->orderBy('name')->paginate(20);

        return view('assessment::assessments.index', compact('assessments'));
    }

    public function show(Assessment $assessment): View
    {
        $this->authorize('assessment.view');
        return view('assessment::assessments.show', compact('assessment'));
    }

    public function create(): View
    {
        $this->authorize('assessment.config');
        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();
        return view('assessment::assessments.create', compact('organizations', 'defaultOrgId', 'orgLocked'));
    }

    public function store(Request $request)
    {
        $this->authorize('assessment.config');

        $data = $request->validate([
            'organization_id'    => 'required|integer|exists:organizations,id',
            'name'               => 'required|string|max:255',
            'aggregation_model'  => 'required|in:weighted_domain,flat_sum,sectioned',
            'classification_type'=> 'required|in:score_band,pass_fail,persona_match,none',
            'has_scoring'        => 'boolean',
            'source_type'        => 'required|in:lead_scoring,standalone',
            'source_ref'         => 'nullable|string|max:255',
        ], [
            'organization_id.required' => 'Vui lòng chọn tổ chức.',
            'organization_id.exists'   => 'Tổ chức không hợp lệ.',
        ]);

        $data['assessment_code'] = $this->generateUniqueCode($data['name']);
        $data['has_scoring']     = $request->boolean('has_scoring');

        Assessment::create($data);

        return redirect()->route('assessments.index')->with('success', 'Đã tạo assessment.');
    }

    public function edit(Assessment $assessment): View
    {
        $this->authorize('assessment.config');
        [$organizations, , $orgLocked] = $this->_resolveOrganizations();
        return view('assessment::assessments.edit', compact('assessment', 'organizations', 'orgLocked'));
    }

    public function update(Request $request, Assessment $assessment)
    {
        $this->authorize('assessment.config');

        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'aggregation_model'  => 'required|in:weighted_domain,flat_sum,sectioned',
            'classification_type'=> 'required|in:score_band,pass_fail,persona_match,none',
            'has_scoring'        => 'boolean',
            'is_active'          => 'boolean',
        ]);

        $data['has_scoring'] = $request->boolean('has_scoring');
        $data['is_active']   = $request->boolean('is_active');

        $assessment->update($data);

        return redirect()->route('assessments.show', $assessment)->with('success', 'Đã cập nhật.');
    }

    public function destroy(Assessment $assessment)
    {
        $this->authorize('assessment.config');
        $assessment->delete();
        return redirect()->route('assessments.index')->with('success', 'Đã xóa.');
    }

    /**
     * DN user (organization_id != null) → chỉ thấy org của họ, field bị locked.
     * Admin (organization_id = null)    → thấy tất cả org, chọn tự do qua TomSelect.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: int|null, 2: bool}
     */
    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;

        if ($userOrgId) {
            return [
                Organization::where('id', $userOrgId)->get(['id', 'name']),
                $userOrgId,
                true,
            ];
        }

        return [
            Organization::orderBy('name')->get(['id', 'name']),
            null,
            false,
        ];
    }

    private function generateUniqueCode(string $name): string
    {
        $base = Str::slug($name);
        do {
            $hash = substr(str_replace('-', '', Str::uuid()->toString()), 0, 8);
            $code = "{$base}-{$hash}";
        } while (Assessment::withoutTenant()->where('assessment_code', $code)->exists());

        return $code;
    }
}
