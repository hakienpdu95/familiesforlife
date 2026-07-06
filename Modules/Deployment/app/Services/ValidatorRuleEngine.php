<?php

namespace Modules\Deployment\Services;

use Modules\Deployment\Enums\IssueSeverity;
use Modules\Deployment\Enums\IssueStatus;
use Modules\Deployment\Models\DeploymentIssue;
use Modules\Deployment\Models\DeploymentTarget;

/**
 * Rule-based validator that scans a DeploymentTarget and creates DeploymentIssue
 * records for any violations found. Does not call any AI API.
 */
class ValidatorRuleEngine
{
    /** @return DeploymentIssue[] newly created issues */
    public function run(DeploymentTarget $target): array
    {
        $created = [];

        foreach ($this->rules() as $rule) {
            $violation = $rule($target);
            if ($violation !== null) {
                $created[] = $this->createIssue($target, $violation);
            }
        }

        return $created;
    }

    // ── Rules ────────────────────────────────────────────────────────────────

    /** @return array<callable> */
    private function rules(): array
    {
        return [
            $this->ruleRequiredChecklistOverdue(...),
            $this->ruleNoProgressInDays(...),
            $this->ruleStuckOnFirstPhase(...),
            $this->ruleNoAssignedEmployee(...),
        ];
    }

    private function ruleRequiredChecklistOverdue(DeploymentTarget $target): ?array
    {
        $pending = $target->checklistItems()
            ->where('phase', $target->current_phase)
            ->where('is_required', true)
            ->where('is_done', false)
            ->count();

        if ($pending === 0) return null;

        return [
            'title'    => "Còn {$pending} mục checklist bắt buộc chưa hoàn thành",
            'desc'     => "Phase [{$target->current_phase}] có {$pending} mục bắt buộc chưa tick trong deployment target này.",
            'severity' => $pending >= 3 ? IssueSeverity::High : IssueSeverity::Medium,
            'key'      => 'required_checklist_pending',
        ];
    }

    private function ruleNoProgressInDays(DeploymentTarget $target): ?array
    {
        $lastLog = $target->progressLogs()->first(); // orderByDesc logged_at

        if (! $lastLog) {
            // No logs at all and not in draft phase
            if ($target->current_phase === 'draft') return null;

            return [
                'title'    => 'Chưa có nhật ký tiến độ nào',
                'desc'     => 'Target đã rời khỏi phase draft nhưng chưa có bất kỳ bản ghi tiến độ nào.',
                'severity' => IssueSeverity::Low,
                'key'      => 'no_progress_log',
            ];
        }

        $daysSince = (int) now()->diffInDays($lastLog->logged_at);
        if ($daysSince < 7) return null;

        return [
            'title'    => "Không có hoạt động trong {$daysSince} ngày",
            'desc'     => "Lần cập nhật tiến độ cuối cùng là {$daysSince} ngày trước.",
            'severity' => $daysSince >= 14 ? IssueSeverity::High : IssueSeverity::Medium,
            'key'      => 'no_recent_progress',
        ];
    }

    private function ruleStuckOnFirstPhase(DeploymentTarget $target): ?array
    {
        // Only flag if target was created > 30 days ago and still on first phase
        if ($target->current_phase !== 'draft') return null;

        $age = (int) now()->diffInDays($target->created_at);
        if ($age < 30) return null;

        return [
            'title'    => "Target vẫn ở phase Draft sau {$age} ngày",
            'desc'     => "Target được tạo {$age} ngày trước nhưng chưa tiến hành triển khai.",
            'severity' => IssueSeverity::Medium,
            'key'      => 'stuck_draft',
        ];
    }

    private function ruleNoAssignedEmployee(DeploymentTarget $target): ?array
    {
        if ($target->assigned_employee_id || $target->current_phase === 'draft') {
            return null;
        }

        return [
            'title'    => 'Target chưa được phân công nhân viên phụ trách',
            'desc'     => 'Không có nhân viên nào được giao phụ trách target đang triển khai này.',
            'severity' => IssueSeverity::Low,
            'key'      => 'no_assignee',
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function createIssue(DeploymentTarget $target, array $v): DeploymentIssue
    {
        // Skip if same rule already has an open issue
        $existing = DeploymentIssue::withoutTenant()
            ->where('deployment_target_id', $target->id)
            ->where('status', IssueStatus::Open->value)
            ->whereRaw("title LIKE ?", ["%{$v['key']}%"])
            ->exists();

        if ($existing) {
            // Return a representative empty model (not persisted)
            return new DeploymentIssue(['title' => $v['title'], 'severity' => $v['severity']]);
        }

        return DeploymentIssue::create([
            'organization_id'      => $target->organization_id,
            'deployment_target_id' => $target->id,
            'project_id'           => $target->project_id,
            'title'                => "[{$v['key']}] {$v['title']}",
            'description'          => $v['desc'],
            'severity'             => $v['severity']->value,
            'status'               => IssueStatus::Open->value,
            'created_by'           => auth()->id() ?? 1,
        ]);
    }
}
