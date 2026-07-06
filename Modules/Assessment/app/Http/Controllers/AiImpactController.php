<?php

namespace Modules\Assessment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Assessment\Enums\ImpactCategory;
use Modules\Employee\Models\Employee;
use Modules\KpiGoal\Models\AiImpactSnapshot;

class AiImpactController extends Controller
{
    // ── List (Part 3: filters, Part 4: chart data) ────────────────────────────

    public function index(Request $request): View
    {
        $this->authorize('assessment.results');

        $orgId = TenantContext::getOrganizationId();

        $query = AiImpactSnapshot::where('organization_id', $orgId)
            ->with('employee:id,full_name,snap_job_title');

        // Part 3: filters
        if ($cat = $request->input('category')) {
            $query->where('impact_category', $cat);
        }
        if ($empId = $request->input('employee_id')) {
            $query->where('employee_id', $empId);
        }
        if ($from = $request->input('period_from')) {
            $query->where('period_start', '>=', $from);
        }
        if ($to = $request->input('period_to')) {
            $query->where('period_end', '<=', $to);
        }

        $snapshots = $query->orderByDesc('period_start')->paginate(20)->withQueryString();

        $stats = [
            'avgImprovement' => AiImpactSnapshot::where('organization_id', $orgId)->whereNotNull('improvement_pct')->avg('improvement_pct'),
            'avgRoi'         => AiImpactSnapshot::where('organization_id', $orgId)->whereNotNull('roi_pct')->where('roi_pct', '>', 0)->avg('roi_pct'),
            'thisWeek'       => AiImpactSnapshot::where('organization_id', $orgId)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];

        // Part 4: chart data — avg improvement per category
        $chartData = AiImpactSnapshot::where('organization_id', $orgId)
            ->selectRaw('impact_category, AVG(improvement_pct) as avg_improvement, COUNT(*) as cnt')
            ->groupBy('impact_category')
            ->get()
            ->keyBy('impact_category');

        $employees = Employee::where('organization_id', $orgId)->orderBy('full_name')->get(['id', 'full_name']);

        return view('assessment::ai-impact.index', compact('snapshots', 'stats', 'chartData', 'employees'));
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create(): View
    {
        $this->authorize('assessment.results');

        $isSuperAdmin  = request()->user()?->hasRole('super-admin');
        $currentOrg    = TenantContext::resolve();
        $organizations = $isSuperAdmin
            ? Organization::where('is_system', false)->orderBy('name')->get()
            : collect();
        $employees = $isSuperAdmin
            ? Employee::orderBy('full_name')->get(['id', 'full_name'])
            : Employee::where('organization_id', TenantContext::getOrganizationId())
                ->orderBy('full_name')->get(['id', 'full_name']);
        $categories = ImpactCategory::cases();

        return view('assessment::ai-impact.create', [
            'categories'    => $categories,
            'employees'     => $employees,
            'isSuperAdmin'  => $isSuperAdmin,
            'currentOrg'    => $currentOrg,
            'organizations' => $organizations,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('assessment.results');

        $isSuperAdmin = $request->user()?->hasRole('super-admin');
        $data = $this->validateSnapshot($request);
        $data['organization_id'] = $isSuperAdmin
            ? (int) $request->input('organization_id')
            : TenantContext::getOrganizationId();
        $data['created_by'] = $request->user()->id;

        AiImpactSnapshot::create($data);

        return redirect()->route('backend.ai-impact.index')
            ->with('success', 'Đã lưu chỉ số tác động AI thành công.');
    }

    // ── Edit / Update (Part 1) ────────────────────────────────────────────────

    public function edit(AiImpactSnapshot $aiImpactSnapshot): View
    {
        $this->authorize('assessment.results');

        $snapOrgName = $aiImpactSnapshot->organization_id
            ? (Organization::withoutTenant()->find($aiImpactSnapshot->organization_id)?->name ?? 'Không xác định')
            : null;
        $categories = ImpactCategory::cases();
        $employees  = Employee::where('organization_id', $aiImpactSnapshot->organization_id ?? TenantContext::getOrganizationId())
            ->orderBy('full_name')->get(['id', 'full_name']);

        return view('assessment::ai-impact.edit', [
            'snap'        => $aiImpactSnapshot,
            'categories'  => $categories,
            'employees'   => $employees,
            'snapOrgName' => $snapOrgName,
        ]);
    }

    public function update(Request $request, AiImpactSnapshot $aiImpactSnapshot): RedirectResponse
    {
        $this->authorize('assessment.results');

        $data = $this->validateSnapshot($request);
        $aiImpactSnapshot->update($data);

        return redirect()->route('backend.ai-impact.index')
            ->with('success', 'Đã cập nhật bản ghi tác động AI.');
    }

    // ── Delete (Part 1) ───────────────────────────────────────────────────────

    public function destroy(AiImpactSnapshot $aiImpactSnapshot): RedirectResponse
    {
        $this->authorize('assessment.results');

        $aiImpactSnapshot->delete();

        return back()->with('success', 'Đã xoá bản ghi.');
    }

    // ── Per-employee view (Part 2 + Part 4 chart) ─────────────────────────────

    public function employee(Request $request, Employee $employee): View
    {
        $this->authorize('assessment.results');

        $orgId = TenantContext::getOrganizationId();

        $snapshots = AiImpactSnapshot::withoutTenant()
            ->where('employee_id', $employee->id)
            ->where('organization_id', $orgId)
            ->orderByDesc('period_start')
            ->get();

        // Category breakdown
        $byCategory = $snapshots->groupBy('impact_category')->map(fn($g) => [
            'count'       => $g->count(),
            'avg_improve' => round($g->avg('improvement_pct') ?? 0, 1),
            'avg_roi'     => round($g->avg('roi_pct') ?? 0, 1),
        ]);

        // AII trend: group by month (use period_start), recalculate per month window
        $trend = $snapshots
            ->groupBy(fn($s) => $s->period_start->format('Y-m'))
            ->map(function ($group, $month) {
                $productivity = $group->where('impact_category', 'productivity')->avg('improvement_pct') ?? 0;
                $quality      = $group->where('impact_category', 'quality')->avg('improvement_pct') ?? 0;
                $timeSaving   = $group->where('impact_type', 'time_saving')->avg('improvement_pct') ?? 0;
                return [
                    'month' => $month,
                    'aii'   => round($productivity * 0.40 + $quality * 0.30 + $timeSaving * 0.30, 2),
                    'count' => $group->count(),
                ];
            })
            ->sortKeys()
            ->values();

        $currentAii = $snapshots->isEmpty() ? 0 : (function () use ($snapshots) {
            $productivity = $snapshots->where('impact_category', 'productivity')->avg('improvement_pct') ?? 0;
            $quality      = $snapshots->where('impact_category', 'quality')->avg('improvement_pct') ?? 0;
            $timeSaving   = $snapshots->where('impact_type', 'time_saving')->avg('improvement_pct') ?? 0;
            return round($productivity * 0.40 + $quality * 0.30 + $timeSaving * 0.30, 2);
        })();

        return view('assessment::ai-impact.employee', compact(
            'employee', 'snapshots', 'byCategory', 'trend', 'currentAii'
        ));
    }

    // ── Bulk CSV import (Part 5) ──────────────────────────────────────────────

    public function importForm(): View
    {
        $this->authorize('assessment.results');
        return view('assessment::ai-impact.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('assessment.results');

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $orgId = TenantContext::getOrganizationId();
        $path  = $request->file('csv_file')->getRealPath();
        $fh    = fopen($path, 'r');

        // Skip header row
        $header = fgetcsv($fh);

        $validCategories = array_map(fn($c) => $c->value, ImpactCategory::cases());
        $employeeCache   = [];
        $created = 0;
        $errors  = [];
        $row     = 1;

        while (($cols = fgetcsv($fh)) !== false) {
            $row++;
            if (count($cols) < 6) {
                $errors[] = "Dòng {$row}: không đủ cột (cần ≥ 6).";
                continue;
            }

            [$empCode, $category, $impactType, $periodStart, $periodEnd, $baseline] = $cols;
            $achieved    = $cols[6] ?? null;
            $investCost  = $cols[7] ?? 0;
            $benefitVal  = $cols[8] ?? 0;
            $notes       = $cols[9] ?? null;

            // Resolve employee
            $empCode = trim($empCode);
            if (! isset($employeeCache[$empCode])) {
                $employeeCache[$empCode] = Employee::withoutGlobalScopes()
                    ->where('organization_id', $orgId)
                    ->where('employee_code', $empCode)
                    ->value('id');
            }
            $employeeId = $employeeCache[$empCode];
            if (! $employeeId) {
                $errors[] = "Dong {$row}: khong tim thay nhan vien [{$empCode}].";
                continue;
            }

            $category = trim($category);
            if (! in_array($category, $validCategories)) {
                $errors[] = "Dong {$row}: danh muc [{$category}] khong hop le.";
                continue;
            }

            $baselineNum = (float) $baseline;
            $achievedNum = $achieved !== null ? (float) $achieved : $baselineNum;

            AiImpactSnapshot::create([
                'organization_id' => $orgId,
                'employee_id'     => $employeeId,
                'impact_category' => $category,
                'impact_type'     => trim($impactType),
                'period_start'    => trim($periodStart),
                'period_end'      => trim($periodEnd),
                'baseline_value'  => $baselineNum,
                'achieved_value'  => $achievedNum,
                'investment_cost' => (float) $investCost,
                'benefit_value'   => (float) $benefitVal,
                'notes'           => $notes ? trim($notes) : null,
                'created_by'      => $request->user()->id,
            ]);

            $created++;
        }

        fclose($fh);

        $msg = "Đã nhập {$created} bản ghi.";
        if ($errors) {
            $msg .= ' Lỗi: ' . implode(' | ', array_slice($errors, 0, 5));
        }

        return redirect()->route('backend.ai-impact.index')
            ->with($errors ? 'info' : 'success', $msg);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validateSnapshot(Request $request): array
    {
        $rules = [
            'impact_category' => 'required|string',
            'impact_type'     => 'required|string|max:100',
            'period_start'    => 'required|date',
            'period_end'      => 'required|date|after_or_equal:period_start',
            'baseline_value'  => 'required|numeric|min:0',
            'achieved_value'  => 'required|numeric|min:0',
            'investment_cost' => 'nullable|numeric|min:0',
            'benefit_value'   => 'nullable|numeric|min:0',
            'employee_id'     => 'nullable|exists:employees,id',
            'notes'           => 'nullable|string|max:1000',
        ];

        $isSuperAdmin = $request->user()?->hasRole('super-admin');
        if ($isSuperAdmin && ! $request->route('aiImpactSnapshot')) {
            $rules['organization_id'] = 'required|exists:organizations,id';
        }

        $messages = [
            'impact_category.required'        => 'Vui lòng chọn danh mục tác động.',
            'impact_type.required'            => 'Vui lòng nhập chỉ số đo lường.',
            'impact_type.max'                 => 'Chỉ số đo lường không được vượt quá 100 ký tự.',
            'period_start.required'           => 'Vui lòng chọn kỳ bắt đầu.',
            'period_start.date'               => 'Kỳ bắt đầu không đúng định dạng ngày.',
            'period_end.required'             => 'Vui lòng chọn kỳ kết thúc.',
            'period_end.date'                 => 'Kỳ kết thúc không đúng định dạng ngày.',
            'period_end.after_or_equal'       => 'Kỳ kết thúc phải sau hoặc bằng kỳ bắt đầu.',
            'baseline_value.required'         => 'Vui lòng nhập giá trị trước AI.',
            'baseline_value.numeric'          => 'Giá trị trước AI phải là số.',
            'baseline_value.min'              => 'Giá trị trước AI không được âm.',
            'achieved_value.required'         => 'Vui lòng nhập giá trị sau AI.',
            'achieved_value.numeric'          => 'Giá trị sau AI phải là số.',
            'achieved_value.min'              => 'Giá trị sau AI không được âm.',
            'investment_cost.numeric'         => 'Chi phí đầu tư phải là số.',
            'investment_cost.min'             => 'Chi phí đầu tư không được âm.',
            'benefit_value.numeric'           => 'Giá trị lợi ích phải là số.',
            'benefit_value.min'               => 'Giá trị lợi ích không được âm.',
            'employee_id.exists'              => 'Nhân viên được chọn không hợp lệ.',
            'notes.max'                       => 'Ghi chú không được vượt quá 1000 ký tự.',
            'organization_id.required'        => 'Vui lòng chọn tổ chức.',
            'organization_id.exists'          => 'Tổ chức được chọn không hợp lệ.',
        ];

        return $request->validate($rules, $messages);
    }
}
