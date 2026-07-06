<?php

namespace Modules\KpiGoal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Department\Models\Department;
use Modules\Employee\Models\Employee;
use Modules\KpiGoal\Actions\Backend\ApproveKpiGoalAction;
use Modules\KpiGoal\Actions\Backend\CloseKpiCycleAction;
use Modules\KpiGoal\Actions\Backend\StoreKpiGoalAction;
use Modules\KpiGoal\Actions\Backend\UpdateKpiGoalAction;
use Modules\KpiGoal\Actions\Backend\UpdateKpiProgressAction;
use Modules\KpiGoal\Data\Requests\StoreKpiGoalData;
use Modules\KpiGoal\Data\Requests\UpdateKpiGoalData;
use Modules\KpiGoal\Enums\KpiDirection;
use Modules\KpiGoal\Enums\KpiGoalStatus;
use Modules\KpiGoal\Enums\KpiGoalType;
use Modules\KpiGoal\Models\KpiGoal;
use Modules\KpiGoal\Models\KpiSnapshot;
use Modules\KpiGoal\Queries\KpiLeaderboardHandler;
use Modules\KpiGoal\Queries\KpiLeaderboardQuery;
use Modules\KpiGoal\Queries\ListKpiGoalsHandler;
use Modules\KpiGoal\Queries\ListKpiGoalsQuery;

class KpiGoalController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', KpiGoal::class);

        $query = new ListKpiGoalsQuery(
            employee_id: $request->integer('employee_id') ?: null,
            cycle_label: $request->input('cycle_label'),
            status:      $request->input('status'),
            goal_type:   $request->input('goal_type'),
        );

        $goals = (new ListKpiGoalsHandler)->handle($query)->paginate(20)->withQueryString();

        $userOrgId = auth()->user()->organization_id;
        $statuses  = collect(KpiGoalStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])->all();

        $empQuery = Employee::withoutTenant()->working()->orderBy('full_name');
        if ($userOrgId) $empQuery->where('organization_id', $userOrgId);
        $employees = $empQuery->get(['id', 'full_name', 'employee_code']);

        $cycleQuery = KpiGoal::withoutTenant()->distinct()->orderByDesc('cycle_label');
        if ($userOrgId) $cycleQuery->where('organization_id', $userOrgId);
        $cycleLabels = $cycleQuery->pluck('cycle_label');

        return view('kpigoal::goals.index', compact('goals', 'statuses', 'employees', 'cycleLabels'));
    }

    public function create()
    {
        $this->authorize('create', KpiGoal::class);

        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        // Pre-load employees for locked users; admin loads dynamically via JS
        $employees = collect();
        if ($orgLocked) {
            $employees = Employee::withoutTenant()
                ->where('organization_id', auth()->user()->organization_id)
                ->working()
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'employee_code']);
        }

        $directions = collect(KpiDirection::cases())
            ->map(fn ($d) => ['value' => $d->value, 'text' => $d->label()])->all();

        $goalTypes = collect(KpiGoalType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])->all();

        return view('kpigoal::goals.create', compact(
            'organizations', 'defaultOrgId', 'orgLocked',
            'employees', 'directions', 'goalTypes'
        ));
    }

    public function apiEmployees(Request $request): JsonResponse
    {
        $orgId = (int) $request->input('org_id');
        if (!$orgId) {
            return response()->json([]);
        }

        $employees = Employee::withoutTenant()
            ->where('organization_id', $orgId)
            ->working()
            ->when($request->input('q'), fn ($q, $search) =>
                $q->where('full_name', 'like', "%{$search}%")
            )
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'employee_code'])
            ->map(fn ($e) => [
                'id'   => $e->id,
                'text' => $e->full_name . ' (' . $e->employee_code . ')',
            ])
            ->values();

        return response()->json($employees);
    }

    public function store(Request $request, StoreKpiGoalAction $action): RedirectResponse
    {
        $this->authorize('create', KpiGoal::class);

        $data = StoreKpiGoalData::validateAndCreate($request->all());
        $goal = $action->handle($data);

        return redirect()->route('backend.kpi.goals.show', $goal)
            ->with('success', 'Mục tiêu KPI đã được tạo.');
    }

    public function show(KpiGoal $goal)
    {
        $this->authorize('view', $goal);
        $goal->load(['employee.department', 'approvedBy', 'parentGoal', 'childGoals', 'snapshot', 'createdBy']);

        return view('kpigoal::goals.show', compact('goal'));
    }

    public function edit(KpiGoal $goal)
    {
        $this->authorize('update', $goal);

        $directions = collect(KpiDirection::cases())
            ->map(fn ($d) => ['value' => $d->value, 'text' => $d->label()])->all();

        $orgName = Organization::find($goal->organization_id)?->name ?? '';

        return view('kpigoal::goals.edit', compact('goal', 'directions', 'orgName'));
    }

    public function update(Request $request, KpiGoal $goal, UpdateKpiGoalAction $action): RedirectResponse
    {
        $this->authorize('update', $goal);

        try {
            $data = UpdateKpiGoalData::validateAndCreate($request->all());
            $action->handle($goal, $data);
            return redirect()->route('backend.kpi.goals.show', $goal)->with('success', 'Đã cập nhật mục tiêu.');
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['action' => $e->getMessage()]);
        }
    }

    public function approve(KpiGoal $goal, ApproveKpiGoalAction $action): RedirectResponse
    {
        $this->authorize('approve', $goal);

        $approver = Employee::withoutTenant()
            ->where('organization_id', $goal->organization_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        try {
            $action->handle($goal, $approver);
            return back()->with('success', 'Mục tiêu đã được duyệt và đang theo dõi.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }
    }

    public function updateProgress(Request $request, KpiGoal $goal, UpdateKpiProgressAction $action): RedirectResponse
    {
        $this->authorize('updateProgress', $goal);

        $request->validate(['current_value' => 'required|numeric']);

        try {
            $action->handle($goal, (float) $request->input('current_value'));
            return back()->with('success', 'Tiến độ đã được cập nhật.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }
    }

    public function closeCycle(Request $request, CloseKpiCycleAction $action): RedirectResponse
    {
        $this->authorize('closeCycle', KpiGoal::class);

        $request->validate([
            'employee_id' => 'required|integer',
            'cycle_label' => 'required|string',
        ]);

        try {
            $result = $action->handle(
                (int) $request->input('employee_id'),
                $request->input('cycle_label'),
            );

            return redirect()->route('backend.kpi.leaderboard')
                ->with('success', sprintf(
                    'Đã chốt kỳ %s. Tổng điểm: %.1f/100 (%.2f/5.0)',
                    $request->input('cycle_label'),
                    $result['total_score'],
                    $result['kpi_score_5'],
                ));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }
    }

    public function leaderboard(Request $request)
    {
        $this->authorize('viewLeaderboard', KpiGoal::class);

        $userOrgId = auth()->user()->organization_id;

        $snapshotQuery = KpiSnapshot::whereHas('employee', fn ($q) =>
            $userOrgId
                ? $q->where('organization_id', $userOrgId)
                : $q
        );
        $cycleLabels = $snapshotQuery->distinct()->orderByDesc('cycle_label')->pluck('cycle_label');

        $cycleLabel   = $request->input('cycle_label', $cycleLabels->first());
        $departmentId = $request->integer('department_id') ?: null;

        $rows = collect();
        if ($cycleLabel) {
            $rows = (new KpiLeaderboardHandler)->handle(
                new KpiLeaderboardQuery($cycleLabel, $departmentId)
            );
        }

        $deptQuery = Department::withoutTenant()->where('status', 'active')->orderBy('name');
        if ($userOrgId) $deptQuery->where('organization_id', $userOrgId);
        $departments = $deptQuery->get(['id', 'name']);

        return view('kpigoal::goals.leaderboard', compact('rows', 'cycleLabels', 'cycleLabel', 'departments', 'departmentId'));
    }

    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            return [Organization::where('id', $userOrgId)->get(['id', 'name']), $userOrgId, true];
        }
        return [Organization::orderBy('name')->get(['id', 'name']), null, false];
    }
}
