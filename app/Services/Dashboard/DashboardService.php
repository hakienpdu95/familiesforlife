<?php

namespace App\Services\Dashboard;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\ActivityLog\Models\ActivityLog;
use Modules\Employee\Enums\EmployeeStatus;
use Modules\Employee\Models\Employee;
use Modules\Leave\Enums\LeaveRequestStatus;
use Modules\Leave\Models\LeaveRequest;
use Modules\Lead\Enums\LeadStatus;
use Modules\Lead\Models\Lead;
use Modules\Recruitment\Models\RcApplication;
use Modules\Task\Enums\TaskStatus;
use Modules\Task\Models\Task;
use Modules\WorkflowAutomation\Models\WorkflowExecution;
use Modules\WorkflowAutomation\Models\WorkflowUserTask;

class DashboardService
{
    public function getData(User $user): array
    {
        $orgId       = TenantContext::getOrganizationId();
        $primaryRole = $user->getRoleNames()->first() ?? RoleEnum::VIEWER->value;

        return [
            'kpi_cards'       => $this->kpiCards($user, $orgId, $primaryRole),
            'action_feed'     => $this->actionFeed($user, $orgId),
            'recent_activity' => $this->recentActivity($orgId),
            'primary_role'    => $primaryRole,
        ];
    }

    // ── KPI Cards ─────────────────────────────────────────────────────────────

    private function kpiCards(User $user, ?int $orgId, string $role): array
    {
        $cards = [];

        // ── Always: pending workflow approvals for me ─────────────────────
        $myApprovals = $this->countMyPendingApprovals($user, $orgId);
        $cards[] = [
            'id'      => 'workflow_approvals',
            'label'   => 'Chờ tôi phê duyệt',
            'value'   => $myApprovals,
            'icon'    => 'approval',
            'color'   => $myApprovals > 0 ? 'warning' : 'ghost',
            'urgent'  => $myApprovals > 0,
            'link'    => route('workflow.tasks.my'),
            'hint'    => 'Workflow user task đang chờ',
        ];

        // ── Always: my overdue tasks ──────────────────────────────────────
        $myOverdue = $this->countMyOverdueTasks($user, $orgId);
        $cards[] = [
            'id'      => 'tasks_overdue_mine',
            'label'   => 'Task quá hạn của tôi',
            'value'   => $myOverdue,
            'icon'    => 'task_overdue',
            'color'   => $myOverdue > 0 ? 'error' : 'ghost',
            'urgent'  => $myOverdue > 0,
            'link'    => route('backend.tasks.index'),
            'hint'    => 'Cần xử lý ngay',
        ];

        // ── Role-specific cards ───────────────────────────────────────────
        if ($this->isFull($role)) {
            // CEO / Admin / Ops / AI_OP
            $cards[] = $this->cardEmployeesActive($orgId);
            $cards[] = $this->cardLeadsActive($orgId);
            $cards[] = $this->cardWorkflowToday($orgId);
            $cards[] = $this->cardLeavePending($orgId);
        } elseif ($role === RoleEnum::HR->value) {
            $cards[] = $this->cardEmployeesActive($orgId);
            $cards[] = $this->cardLeavePending($orgId);
            $cards[] = $this->cardRecruitmentActive($orgId);
        } elseif ($role === RoleEnum::SALES->value) {
            $cards[] = $this->cardMyLeads($user, $orgId);
            $cards[] = $this->cardLeadsConvertedThisMonth($orgId);
        } elseif ($role === RoleEnum::MARKETING->value) {
            $cards[] = $this->cardLeadsActive($orgId);
        }

        return $cards;
    }

    private function isFull(string $role): bool
    {
        return in_array($role, [
            RoleEnum::CEO->value,
            RoleEnum::ADMIN->value,
            RoleEnum::OPS->value,
            RoleEnum::AI_OP->value,
        ]);
    }

    // ── Card builders ─────────────────────────────────────────────────────────

    private function cardEmployeesActive(?int $orgId): array
    {
        $count = Employee::where('organization_id', $orgId)
            ->whereIn('status', [
                EmployeeStatus::Active->value,
                EmployeeStatus::Probation->value,
                EmployeeStatus::OnLeave->value,
            ])
            ->count();

        return [
            'id'     => 'employees_active',
            'label'  => 'Nhân viên hoạt động',
            'value'  => $count,
            'icon'   => 'employees',
            'color'  => 'success',
            'urgent' => false,
            'link'   => route('backend.employees.index'),
            'hint'   => 'Active + Thử việc + Đang nghỉ phép',
        ];
    }

    private function cardLeadsActive(?int $orgId): array
    {
        $count = Lead::where('organization_id', $orgId)
            ->where('status', LeadStatus::Active->value)
            ->count();

        return [
            'id'     => 'leads_active',
            'label'  => 'Lead đang theo dõi',
            'value'  => $count,
            'icon'   => 'leads',
            'color'  => 'info',
            'urgent' => false,
            'link'   => route('lead.index'),
            'hint'   => 'Trạng thái Active',
        ];
    }

    private function cardMyLeads(User $user, ?int $orgId): array
    {
        $count = Lead::where('organization_id', $orgId)
            ->where('assigned_to', $user->id)
            ->where('status', LeadStatus::Active->value)
            ->count();

        return [
            'id'     => 'my_leads',
            'label'  => 'Lead của tôi',
            'value'  => $count,
            'icon'   => 'leads',
            'color'  => 'info',
            'urgent' => false,
            'link'   => route('lead.index'),
            'hint'   => 'Lead được giao cho bạn',
        ];
    }

    private function cardLeadsConvertedThisMonth(?int $orgId): array
    {
        $count = Lead::where('organization_id', $orgId)
            ->where('status', LeadStatus::Converted->value)
            ->whereBetween('updated_at', [now()->startOfMonth(), now()])
            ->count();

        return [
            'id'     => 'leads_converted',
            'label'  => 'Lead chuyển đổi tháng này',
            'value'  => $count,
            'icon'   => 'leads_won',
            'color'  => 'success',
            'urgent' => false,
            'link'   => route('lead.index'),
            'hint'   => Carbon::now()->format('M Y'),
        ];
    }

    private function cardWorkflowToday(?int $orgId): array
    {
        $count = WorkflowExecution::where('organization_id', $orgId)
            ->whereDate('created_at', today())
            ->count();

        $failed = WorkflowExecution::where('organization_id', $orgId)
            ->whereDate('created_at', today())
            ->where('status', 3) // Fail
            ->count();

        return [
            'id'     => 'workflow_today',
            'label'  => 'Workflow chạy hôm nay',
            'value'  => $count,
            'icon'   => 'workflow',
            'color'  => $failed > 0 ? 'warning' : 'secondary',
            'urgent' => $failed > 0,
            'link'   => route('workflows.index'),
            'hint'   => $failed > 0 ? "{$failed} lỗi cần kiểm tra" : 'Tất cả thành công',
        ];
    }

    private function cardLeavePending(?int $orgId): array
    {
        $count = LeaveRequest::where('organization_id', $orgId)
            ->where('status', LeaveRequestStatus::Pending->value)
            ->count();

        return [
            'id'     => 'leave_pending',
            'label'  => 'Đơn nghỉ phép chờ duyệt',
            'value'  => $count,
            'icon'   => 'leave',
            'color'  => $count > 0 ? 'warning' : 'ghost',
            'urgent' => $count > 0,
            'link'   => route('backend.leave.requests.index'),
            'hint'   => 'Cần phê duyệt',
        ];
    }

    private function cardRecruitmentActive(?int $orgId): array
    {
        $count = RcApplication::where('organization_id', $orgId)
            ->where('status', 'active')
            ->count();

        return [
            'id'     => 'recruitment_active',
            'label'  => 'Ứng tuyển đang xử lý',
            'value'  => $count,
            'icon'   => 'recruitment',
            'color'  => 'info',
            'urgent' => false,
            'link'   => route('backend.recruitment.candidates'),
            'hint'   => 'Chờ đánh giá / phỏng vấn',
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function countMyPendingApprovals(User $user, ?int $orgId): int
    {
        $roles = $user->getRoleNames()->toArray();

        return WorkflowUserTask::where('organization_id', $orgId)
            ->where('status', WorkflowUserTask::STATUS_PENDING)
            ->where(function ($q) use ($user, $roles) {
                $q->where('assignee_id', $user->id)
                  ->orWhereIn('assignee_role', $roles);
            })
            ->count();
    }

    private function countMyOverdueTasks(User $user, ?int $orgId): int
    {
        $employeeId = Employee::where('organization_id', $orgId)
            ->where('user_id', $user->id)
            ->value('id');

        return Task::where('organization_id', $orgId)
            ->where(function ($q) use ($user, $employeeId) {
                $q->where('created_by', $user->id);
                if ($employeeId) {
                    $q->orWhere('employee_id', $employeeId);
                }
            })
            ->whereNotNull('due_date')
            ->where('due_date', '<', today())
            ->whereNotIn('status', [TaskStatus::Done->value, TaskStatus::Cancelled->value])
            ->count();
    }

    // ── Action Feed ───────────────────────────────────────────────────────────

    private function actionFeed(User $user, ?int $orgId): array
    {
        $items = [];

        // Pending workflow approvals
        $roles    = $user->getRoleNames()->toArray();
        $approvals = WorkflowUserTask::where('organization_id', $orgId)
            ->where('status', WorkflowUserTask::STATUS_PENDING)
            ->where(function ($q) use ($user, $roles) {
                $q->where('assignee_id', $user->id)
                  ->orWhereIn('assignee_role', $roles);
            })
            ->with('execution.workflow')
            ->orderBy('due_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        foreach ($approvals as $task) {
            $dueAt   = $task->due_at ? Carbon::parse($task->due_at) : null;
            $overdue = $dueAt && $dueAt->isPast();
            $items[] = [
                'type'       => 'workflow_approval',
                'title'      => $task->title,
                'subtitle'   => 'Workflow: ' . ($task->execution->workflow->name ?? '—'),
                'urgent'     => $overdue,
                'badge'      => $overdue ? 'Quá hạn' : ($dueAt ? $dueAt->diffForHumans() : 'Đang chờ'),
                'badge_color'=> $overdue ? 'error' : 'warning',
                'link'       => route('workflow.tasks.show', $task->task_token),
                'created_at' => $task->created_at,
            ];
        }

        // Overdue tasks
        $employeeId = Employee::where('organization_id', $orgId)
            ->where('user_id', $user->id)
            ->value('id');

        $overdueTasks = Task::where('organization_id', $orgId)
            ->where(function ($q) use ($user, $employeeId) {
                $q->where('created_by', $user->id);
                if ($employeeId) {
                    $q->orWhere('employee_id', $employeeId);
                }
            })
            ->whereNotNull('due_date')
            ->where('due_date', '<', today())
            ->whereNotIn('status', [TaskStatus::Done->value, TaskStatus::Cancelled->value])
            ->with('project:id,name')
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        foreach ($overdueTasks as $task) {
            $daysLate = (int) Carbon::parse($task->due_date)->diffInDays(today());
            $items[] = [
                'type'       => 'task_overdue',
                'title'      => $task->title,
                'subtitle'   => $task->project ? $task->project->name : 'Không thuộc dự án',
                'urgent'     => $daysLate > 2,
                'badge'      => "Trễ {$daysLate} ngày",
                'badge_color'=> $daysLate > 2 ? 'error' : 'warning',
                'link'       => route('backend.tasks.index'),
                'created_at' => $task->created_at,
            ];
        }

        // Sort: urgent first, then by created_at
        usort($items, fn ($a, $b) => $b['urgent'] <=> $a['urgent']
            ?: $a['created_at']->timestamp <=> $b['created_at']->timestamp);

        return array_slice($items, 0, 8);
    }

    // ── Recent Activity ───────────────────────────────────────────────────────

    private function recentActivity(?int $orgId): Collection
    {
        return ActivityLog::where(function ($q) use ($orgId) {
                $q->where('organization_id', $orgId)
                  ->orWhereNull('organization_id');
            })
            ->with('causer:id,name')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'description', 'subject_type', 'event', 'causer_id', 'causer_type', 'created_at', 'organization_id']);
    }
}
