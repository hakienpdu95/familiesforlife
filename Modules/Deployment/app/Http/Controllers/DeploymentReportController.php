<?php

namespace Modules\Deployment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Deployment\Models\DeploymentIssue;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectMember;
use Rap2hpoutre\FastExcel\FastExcel;
use Spatie\LaravelPdf\Facades\Pdf;

class DeploymentReportController extends Controller
{
    // ── PM Report ─────────────────────────────────────────────────────────────

    public function pm(Request $request)
    {
        $vertical = $request->attributes->get('_vertical');
        $data     = $this->pmData($vertical);

        if ($request->input('format') === 'excel') {
            $rows = $this->pmExcelRows($data);
            return (new FastExcel($rows))->download("pm-report-{$vertical->code()}.xlsx");
        }

        if ($request->input('format') === 'pdf') {
            return Pdf::view('deployment::reports.pm', $data)
                ->name("pm-report-{$vertical->code()}.pdf")
                ->download();
        }

        return view('deployment::reports.pm', $data);
    }

    // ── Province Report ───────────────────────────────────────────────────────

    public function province(Request $request)
    {
        $vertical     = $request->attributes->get('_vertical');
        $provinceCode = $request->input('province_code');

        $data = $this->provinceData($vertical, $provinceCode);

        if ($request->input('format') === 'excel') {
            $rows = $this->provinceExcelRows($data['targets']);
            return (new FastExcel($rows))->download("province-report-{$vertical->code()}.xlsx");
        }

        return view('deployment::reports.province', $data);
    }

    // ── Private builders ──────────────────────────────────────────────────────

    private function pmData($vertical): array
    {
        $code     = $vertical->code();
        $projects = Project::where('vertical_code', $code)
            ->withCount(['members'])
            ->orderByDesc('created_at')
            ->get();

        $projectIds = $projects->pluck('id');
        $targetIds  = DeploymentTarget::where('vertical_code', $code)->pluck('id');

        $targets = DeploymentTarget::where('vertical_code', $code)
            ->with(['targetOrganization', 'assignedEmployee', 'project'])
            ->get()
            ->map(function ($t) {
                $total = $t->checklistItems()->count();
                $done  = $t->checklistItems()->where('is_done', true)->count();
                $t->overall_pct = $total > 0 ? (int) round($done / $total * 100) : 0;
                return $t;
            });

        $openIssues = $targetIds->isNotEmpty()
            ? DeploymentIssue::whereIn('deployment_target_id', $targetIds)
                ->with('target.targetOrganization')
                ->whereIn('status', ['open', 'in_progress'])
                ->orderBy('severity')
                ->get()
            : collect();

        $teamMembers = $projectIds->isNotEmpty()
            ? ProjectMember::whereIn('project_id', $projectIds)
                ->whereNull('left_at')
                ->with(['employee', 'project'])
                ->get()
            : collect();

        return compact('vertical', 'projects', 'targets', 'openIssues', 'teamMembers');
    }

    private function provinceData($vertical, ?string $provinceCode): array
    {
        $query = DeploymentTarget::where('vertical_code', $vertical->code())
            ->with('targetOrganization');

        if ($provinceCode) {
            $query->whereHas('targetOrganization', fn($q) => $q->where('province_code', $provinceCode));
        }

        $targets   = $query->get();
        $phases    = $vertical->phases();
        $lastPhase = end($phases);

        $summary = [
            'total'     => $targets->count(),
            'in_progress' => $targets->filter(fn($t) => ! in_array($t->current_phase, ['draft', $lastPhase]))->count(),
            'completed' => $targets->filter(fn($t) => $t->current_phase === $lastPhase)->count(),
            'draft'     => $targets->where('current_phase', 'draft')->count(),
        ];

        $provinces = DeploymentTarget::where('vertical_code', $vertical->code())
            ->with('targetOrganization')
            ->get()
            ->groupBy(fn($t) => $t->targetOrganization?->province_code ?? 'unknown')
            ->map->count();

        return compact('vertical', 'targets', 'summary', 'phases', 'lastPhase', 'provinces', 'provinceCode');
    }

    private function pmExcelRows(array $data): \Illuminate\Support\Collection
    {
        return $data['targets']->map(fn($t) => [
            'Tên tổ chức'   => $t->targetOrganization?->name ?? '—',
            'Dự án'         => $t->project?->name ?? '—',
            'Phase hiện tại'=> $t->current_phase,
            'Tiến độ (%)'   => $t->overall_pct,
            'Nhân viên PT'  => $t->assignedEmployee?->full_name ?? '—',
            'MST'           => $t->targetOrganization?->tax_code ?? '—',
            'Địa chỉ'       => $t->targetOrganization?->full_address ?? '—',
        ]);
    }

    private function provinceExcelRows(\Illuminate\Support\Collection $targets): \Illuminate\Support\Collection
    {
        return $targets->map(fn($t) => [
            'Tên tổ chức'   => $t->targetOrganization?->name ?? '—',
            'Tỉnh/Thành'    => $t->targetOrganization?->province_code ?? '—',
            'Phase hiện tại'=> $t->current_phase,
            'MST'           => $t->targetOrganization?->tax_code ?? '—',
        ]);
    }
}
