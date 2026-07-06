<?php

namespace Modules\Employee\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Employee\Enums\EmployeeStatus;
use Modules\Employee\Models\Employee;

class EmployeeOptionsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);

        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            $orgId = $userOrgId;
        } else {
            $orgId = $request->integer('organization_id') ?: TenantContext::getOrganizationId();
        }

        $q         = $request->input('q', '');
        $excludeId = $request->integer('exclude_id');

        // Mặc định trả employees.id (dùng cho các FK trỏ employees, vd Task/Project/
        // PerformanceReview). value=user_id → trả employees.user_id thay vào, dùng cho
        // các FK trỏ users (vd customers.assigned_to) — chỉ nhân viên có tài khoản mới hợp lệ.
        $useUserId = $request->input('value') === 'user_id';

        $rows = Employee::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', EmployeeStatus::Active->value)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->when($useUserId, fn ($q) => $q->whereNotNull('user_id'))
            ->when($q, fn ($query) => $query->where('full_name', 'like', "%{$q}%")
                ->orWhere('employee_code', 'like', "%{$q}%"))
            ->orderBy('full_name')
            ->limit(30)
            ->get(['id', 'user_id', 'full_name', 'employee_code', 'snap_dept_name']);

        return response()->json($rows->map(fn ($e) => [
            'id'   => $useUserId ? $e->user_id : $e->id,
            'text' => $e->full_name . ' (' . $e->employee_code . ')'
                . ($e->snap_dept_name ? ' — ' . $e->snap_dept_name : ''),
        ]));
    }
}
