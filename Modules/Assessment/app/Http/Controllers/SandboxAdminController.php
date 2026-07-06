<?php

namespace Modules\Assessment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Assessment\Models\SandboxEnvironment;
use Modules\Assessment\Models\SandboxSession;
use Modules\Assessment\Models\SandboxTask;

class SandboxAdminController extends Controller
{
    // ── Environments list ─────────────────────────────────────────────────────

    public function index(): View
    {
        $this->authorize('assessment.config');

        $orgId       = TenantContext::getOrganizationId();
        $isSuperAdmin = request()->user()?->hasRole('super-admin');

        // Global envs (org_id NULL): visible to all, editable only by super-admin
        $globalEnvs = SandboxEnvironment::whereNull('organization_id')
            ->withCount(['tasks', 'tasks as active_tasks_count' => fn($q) => $q->where('is_active', true)])
            ->orderBy('tier')->orderBy('sort_order')
            ->get();

        // Org-specific envs: only this org's own envs
        $orgEnvs = SandboxEnvironment::where('organization_id', $orgId)
            ->withCount(['tasks', 'tasks as active_tasks_count' => fn($q) => $q->where('is_active', true)])
            ->orderBy('tier')->orderBy('sort_order')
            ->get();

        // Session stats scoped to current org
        $allEnvIds = $globalEnvs->pluck('id')->merge($orgEnvs->pluck('id'));
        $sessionStats = SandboxSession::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'completed')
            ->whereHas('task', fn($q) => $q->whereIn('sandbox_env_id', $allEnvIds))
            ->selectRaw('sandbox_tasks.sandbox_env_id, COUNT(*) as total, AVG(final_score) as avg_score, SUM(passed) as passed_count')
            ->join('sandbox_tasks', 'sandbox_tasks.id', '=', 'sandbox_sessions.sandbox_task_id')
            ->groupBy('sandbox_tasks.sandbox_env_id')
            ->get()
            ->keyBy('sandbox_env_id');

        $globalStats = [
            'envs'     => $globalEnvs->count() + $orgEnvs->count(),
            'tasks'    => $globalEnvs->sum('tasks_count') + $orgEnvs->sum('tasks_count'),
            'sessions' => SandboxSession::withoutTenant()->where('organization_id', $orgId)->where('status', 'completed')->count(),
            'avg'      => SandboxSession::withoutTenant()->where('organization_id', $orgId)->where('status', 'completed')->avg('final_score'),
        ];

        return view('assessment::sandbox.admin.index', compact(
            'globalEnvs', 'orgEnvs', 'sessionStats', 'globalStats', 'isSuperAdmin', 'orgId'
        ));
    }

    // ── Create / Edit Environment ──────────────────────────────────────────────

    public function createEnv(): View
    {
        $this->authorize('assessment.config');

        $isSuperAdmin = request()->user()?->hasRole('super-admin');
        $currentOrg   = TenantContext::resolve();

        // Super-admin gets a full org list to target specific orgs
        $organizations = $isSuperAdmin
            ? Organization::where('is_system', false)->orderBy('name')->get()
            : collect();

        return view('assessment::sandbox.admin.env.create', [
            'isSuperAdmin'  => $isSuperAdmin,
            'currentOrg'    => $currentOrg,
            'organizations' => $organizations,
        ]);
    }

    public function storeEnv(Request $request): RedirectResponse
    {
        $this->authorize('assessment.config');

        $isSuperAdmin = $request->user()?->hasRole('super-admin');

        $rules = [
            'name'        => 'required|string|max:120',
            'env_code'    => 'required|string|max:40|regex:/^[A-Z0-9_]+$/',
            'type'        => 'required|in:office,data,sales,hr,workflow,leadership,custom',
            'tier'        => 'required|integer|min:1|max:5',
            'description' => 'nullable|string|max:500',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
        ];

        if ($isSuperAdmin) {
            $rules['scope']           = 'required|in:global,org';
            $rules['organization_id'] = 'required_if:scope,org|nullable|exists:organizations,id';
        }

        $messages = [
            'name.required'               => 'Vui lòng nhập tên môi trường.',
            'name.max'                    => 'Tên không được vượt quá :max ký tự.',
            'env_code.required'           => 'Vui lòng nhập mã môi trường.',
            'env_code.max'                => 'Mã không được vượt quá :max ký tự.',
            'env_code.regex'              => 'Mã chỉ được dùng CHỮ HOA, số và gạch dưới.',
            'type.required'               => 'Vui lòng chọn loại kỹ năng.',
            'type.in'                     => 'Loại kỹ năng không hợp lệ.',
            'tier.required'               => 'Vui lòng chọn cấp độ.',
            'tier.integer'                => 'Cấp độ phải là số nguyên.',
            'tier.min'                    => 'Cấp độ phải từ :min trở lên.',
            'tier.max'                    => 'Cấp độ không được vượt quá :max.',
            'description.max'             => 'Mô tả không được vượt quá :max ký tự.',
            'sort_order.integer'          => 'Thứ tự phải là số nguyên.',
            'sort_order.min'              => 'Thứ tự không được âm.',
            'scope.required'              => 'Vui lòng chọn phạm vi.',
            'scope.in'                    => 'Phạm vi không hợp lệ.',
            'organization_id.required_if' => 'Vui lòng chọn tổ chức khi phạm vi là "Riêng tổ chức cụ thể".',
            'organization_id.exists'      => 'Tổ chức được chọn không hợp lệ.',
        ];
        $data = $request->validate($rules, $messages);

        // Determine organization_id:
        //   super-admin + scope=global          → NULL (template dùng chung)
        //   super-admin + scope=org + org_id=X  → X  (riêng cho org X)
        //   org-admin                           → luôn là org của họ
        if ($isSuperAdmin) {
            $organizationId = $request->input('scope') === 'global'
                ? null
                : (int) $request->input('organization_id');
        } else {
            $organizationId = TenantContext::getOrganizationId();
        }

        SandboxEnvironment::create([
            'uuid'            => Str::uuid(),
            'organization_id' => $organizationId,
            'name'            => $data['name'],
            'env_code'        => $data['env_code'],
            'type'            => $data['type'],
            'tier'            => $data['tier'],
            'description'     => $data['description'] ?? null,
            'sort_order'      => $data['sort_order'] ?? 0,
            'is_active'       => $request->boolean('is_active', true),
        ]);

        return redirect()->route('backend.sandbox-admin.index')
            ->with('success', 'Đã tạo môi trường sandbox.');
    }

    public function editEnv(SandboxEnvironment $sandboxEnvironment): View
    {
        $this->authorize('assessment.config');
        $this->authorizeEnvAccess($sandboxEnvironment);

        $isSuperAdmin = request()->user()?->hasRole('super-admin');
        $envOrgName   = $sandboxEnvironment->organization_id
            ? (Organization::withoutTenant()->find($sandboxEnvironment->organization_id)?->name ?? 'Tổ chức #'.$sandboxEnvironment->organization_id)
            : null;

        return view('assessment::sandbox.admin.env.edit', [
            'env'          => $sandboxEnvironment,
            'isSuperAdmin' => $isSuperAdmin,
            'envOrgName'   => $envOrgName,
        ]);
    }

    public function updateEnv(Request $request, SandboxEnvironment $sandboxEnvironment): RedirectResponse
    {
        $this->authorize('assessment.config');
        $this->authorizeEnvAccess($sandboxEnvironment);

        $data = $request->validate([
            'name'        => 'required|string|max:120',
            'type'        => 'required|in:office,data,sales,hr,workflow,leadership,custom',
            'tier'        => 'required|integer|min:1|max:5',
            'description' => 'nullable|string|max:500',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
        ], [
            'name.required'      => 'Vui lòng nhập tên môi trường.',
            'name.max'           => 'Tên không được vượt quá :max ký tự.',
            'type.required'      => 'Vui lòng chọn loại kỹ năng.',
            'type.in'            => 'Loại kỹ năng không hợp lệ.',
            'tier.required'      => 'Vui lòng chọn cấp độ.',
            'tier.integer'       => 'Cấp độ phải là số nguyên.',
            'tier.min'           => 'Cấp độ phải từ :min trở lên.',
            'tier.max'           => 'Cấp độ không được vượt quá :max.',
            'description.max'    => 'Mô tả không được vượt quá :max ký tự.',
            'sort_order.integer' => 'Thứ tự phải là số nguyên.',
            'sort_order.min'     => 'Thứ tự không được âm.',
        ]);

        $sandboxEnvironment->update([
            'name'        => $data['name'],
            'type'        => $data['type'],
            'tier'        => $data['tier'],
            'description' => $data['description'] ?? null,
            'sort_order'  => $data['sort_order'] ?? 0,
            'is_active'   => $request->boolean('is_active'),
        ]);

        return redirect()->route('backend.sandbox-admin.index')
            ->with('success', 'Đã cập nhật môi trường.');
    }

    // ── Tasks ─────────────────────────────────────────────────────────────────

    public function tasks(SandboxEnvironment $sandboxEnvironment): View
    {
        $this->authorize('assessment.config');

        $canEdit = $this->canEditEnv($sandboxEnvironment);
        $orgId   = TenantContext::getOrganizationId();

        $tasks = $sandboxEnvironment->tasks()
            ->withCount([
                'sessions',
                'sessions as completed_count' => fn($q) => $q->where('status', 'completed')->where('organization_id', $orgId),
            ])
            ->get();

        return view('assessment::sandbox.admin.tasks', compact('sandboxEnvironment', 'tasks', 'canEdit'));
    }

    public function createTask(SandboxEnvironment $sandboxEnvironment): View
    {
        $this->authorize('assessment.config');
        $this->authorizeEnvAccess($sandboxEnvironment);

        return view('assessment::sandbox.admin.task.create', ['env' => $sandboxEnvironment]);
    }

    public function storeTask(Request $request, SandboxEnvironment $sandboxEnvironment): RedirectResponse
    {
        $this->authorize('assessment.config');
        $this->authorizeEnvAccess($sandboxEnvironment);

        $data = $this->validateTask($request);

        SandboxTask::create([
            'uuid'           => Str::uuid(),
            'sandbox_env_id' => $sandboxEnvironment->id,
            ...$data,
            'is_active'  => $request->boolean('is_active', true),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return redirect()->route('backend.sandbox-admin.tasks', $sandboxEnvironment)
            ->with('success', 'Đã thêm nhiệm vụ.');
    }

    public function editTask(SandboxTask $sandboxTask): View
    {
        $this->authorize('assessment.config');
        $this->authorizeEnvAccess($sandboxTask->environment);

        return view('assessment::sandbox.admin.task.edit', ['env' => $sandboxTask->environment, 'task' => $sandboxTask]);
    }

    public function updateTask(Request $request, SandboxTask $sandboxTask): RedirectResponse
    {
        $this->authorize('assessment.config');
        $this->authorizeEnvAccess($sandboxTask->environment);

        $data = $this->validateTask($request);
        $sandboxTask->update([...$data, 'is_active' => $request->boolean('is_active')]);

        return redirect()->route('backend.sandbox-admin.tasks', $sandboxTask->environment)
            ->with('success', 'Đã cập nhật nhiệm vụ.');
    }

    public function destroyTask(SandboxTask $sandboxTask): RedirectResponse
    {
        $this->authorize('assessment.config');
        $this->authorizeEnvAccess($sandboxTask->environment);

        $env = $sandboxTask->environment;

        if ($sandboxTask->sessions()->exists()) {
            return back()->with('error', 'Không thể xoá: nhiệm vụ đã có phiên thực hành. Hãy tắt kích hoạt thay thế.');
        }

        $sandboxTask->delete();

        return redirect()->route('backend.sandbox-admin.tasks', $env)
            ->with('success', 'Đã xoá nhiệm vụ.');
    }

    // ── Authorization helpers ─────────────────────────────────────────────────

    /**
     * An env can be edited if:
     *   (a) super-admin (edits anything), OR
     *   (b) env.organization_id == current tenant org
     * Global envs (org_id NULL) require super-admin.
     */
    private function canEditEnv(SandboxEnvironment $env): bool
    {
        $user  = request()->user();
        $orgId = TenantContext::getOrganizationId();

        if ($user?->hasRole('super-admin')) {
            return true;
        }

        // org-specific env that belongs to current org
        return $env->organization_id !== null && $env->organization_id === $orgId;
    }

    private function authorizeEnvAccess(SandboxEnvironment $env): void
    {
        if (! $this->canEditEnv($env)) {
            abort(403, 'Bạn không có quyền chỉnh sửa môi trường này. Môi trường hệ thống chỉ super-admin mới chỉnh sửa được.');
        }
    }

    private function validateTask(Request $request): array
    {
        return $request->validate([
            'title'                => 'required|string|max:200',
            'instruction'          => 'required|string|max:3000',
            'expected_output'      => 'nullable|string|max:1000',
            'scoring_rubric'       => 'nullable|string|max:1000',
            'time_limit_minutes'   => 'required|integer|min:5|max:180',
            'ai_tools_allowed'     => 'nullable|string|max:300',
            'target_position_code' => 'nullable|string|max:50',
            'sort_order'           => 'nullable|integer|min:0',
            'is_active'            => 'boolean',
        ], [
            'title.required'              => 'Vui lòng nhập tiêu đề nhiệm vụ.',
            'title.max'                   => 'Tiêu đề không được vượt quá :max ký tự.',
            'instruction.required'        => 'Vui lòng nhập hướng dẫn nhiệm vụ.',
            'instruction.max'             => 'Hướng dẫn không được vượt quá :max ký tự.',
            'expected_output.max'         => 'Kết quả mong đợi không được vượt quá :max ký tự.',
            'scoring_rubric.max'          => 'Tiêu chí chấm điểm không được vượt quá :max ký tự.',
            'time_limit_minutes.required' => 'Vui lòng nhập giới hạn thời gian.',
            'time_limit_minutes.integer'  => 'Giới hạn thời gian phải là số nguyên.',
            'time_limit_minutes.min'      => 'Giới hạn thời gian phải ít nhất :min phút.',
            'time_limit_minutes.max'      => 'Giới hạn thời gian không được vượt quá :max phút.',
            'ai_tools_allowed.max'        => 'Công cụ AI không được vượt quá :max ký tự.',
            'target_position_code.max'    => 'Mã vị trí không được vượt quá :max ký tự.',
            'sort_order.integer'          => 'Thứ tự phải là số nguyên.',
            'sort_order.min'              => 'Thứ tự không được âm.',
        ]);
    }
}
