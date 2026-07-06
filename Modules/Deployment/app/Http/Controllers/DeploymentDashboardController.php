<?php

namespace Modules\Deployment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Deployment\Enums\IssueStatus;
use Modules\Deployment\Models\DeploymentIssue;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectMember;

class DeploymentDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $vertical = $request->attributes->get('_vertical');
        $code     = $vertical->code();

        // ── KPI cards ────────────────────────────────────────────────────────
        $targets   = DeploymentTarget::where('vertical_code', $code)->get();
        $targetIds = $targets->pluck('id');
        $phases      = $vertical->phases();
        $phaseLabels = $vertical->phaseLabels();
        $lastPhase   = end($phases);

        $totalTargets   = $targets->count();
        $inProgress     = $targets->filter(fn($t) => $t->current_phase !== 'draft' && $t->current_phase !== $lastPhase)->count();
        $completed      = $targets->filter(fn($t) => $t->current_phase === $lastPhase)->count();
        $openIssueCount = $targetIds->isNotEmpty()
            ? DeploymentIssue::whereIn('deployment_target_id', $targetIds)->where('status', IssueStatus::Open->value)->count()
            : 0;

        // ── Phase breakdown ───────────────────────────────────────────────────
        $byPhase = $targets->groupBy('current_phase')->map->count();

        // ── Top targets with progress ─────────────────────────────────────────
        $topTargets = DeploymentTarget::where('vertical_code', $code)
            ->with(['targetOrganization', 'checklistItems', 'progressLogs' => fn($q) => $q->latest('logged_at')->limit(1)])
            ->where('current_phase', '!=', 'draft')
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get()
            ->map(function ($t) {
                $total          = $t->checklistItems->count();
                $done           = $t->checklistItems->where('is_done', true)->count();
                $t->overall_pct = $total > 0 ? (int) round($done / $total * 100) : 0;
                return $t;
            });

        // ── Recent open issues ────────────────────────────────────────────────
        $recentIssues = $targetIds->isNotEmpty()
            ? DeploymentIssue::whereIn('deployment_target_id', $targetIds)
                ->with('target.targetOrganization')
                ->whereIn('status', [IssueStatus::Open->value, IssueStatus::InProgress->value])
                ->orderBy('severity')
                ->orderByDesc('created_at')
                ->limit(6)
                ->get()
            : collect();

        // ── Nhân sự widget ────────────────────────────────────────────────────
        $projectIds  = Project::where('vertical_code', $code)->pluck('id');
        $teamMembers = $projectIds->isNotEmpty()
            ? ProjectMember::whereIn('project_id', $projectIds)
                ->whereNull('left_at')
                ->with(['employee', 'project'])
                ->get()
                ->groupBy('employee_id')
            : collect();

        // ── Projects summary ──────────────────────────────────────────────────
        $projects = Project::where('vertical_code', $code)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'name', 'code', 'status']);

        return view('deployment::dashboard.index', compact(
            'vertical',
            'totalTargets', 'inProgress', 'completed', 'openIssueCount',
            'byPhase', 'phases', 'phaseLabels',
            'topTargets',
            'recentIssues',
            'teamMembers',
            'projects',
        ));
    }
}
