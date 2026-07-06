<?php

namespace Modules\PerformanceReview\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\PerformanceReview\Models\ReviewCriteria;
use Modules\PerformanceReview\Models\ReviewTemplate;
use Modules\PerformanceReview\Models\PerformanceReview as PerformanceReviewModel;

class ReviewTemplateController extends Controller
{
    /** [organizations, defaultOrgId, orgLocked] — giống Department module */
    private function resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            return [Organization::where('id', $userOrgId)->get(['id', 'name']), $userOrgId, true];
        }
        return [Organization::orderBy('name')->get(['id', 'name']), null, false];
    }

    private function resolveOrgId(?Request $request = null): int
    {
        $orgId = auth()->user()->organization_id
            ?? ($request ? $request->integer('organization_id') ?: null : null)
            ?? TenantContext::getOrganizationId();

        abort_unless((bool) $orgId, 422, 'Không xác định được tổ chức hiện tại.');

        return (int) $orgId;
    }

    public function options(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PerformanceReviewModel::class);

        $orgId = $this->resolveOrgId($request);

        $templates = ReviewTemplate::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('name')
            ->with('criteria')
            ->get();

        return response()->json($templates->map(fn ($t) => [
            'id'       => $t->id,
            'text'     => $t->name,
            'criteria' => $t->criteria->map(fn ($c) => [
                'criteria_key'  => $c->criteria_key,
                'criteria_name' => $c->criteria_name,
                'weight'        => $c->weight,
                'max_score'     => $c->max_score,
                'description'   => $c->description,
            ])->values()->all(),
        ]));
    }

    public function index()
    {
        $this->authorize('viewAny', \Modules\PerformanceReview\Models\PerformanceReview::class);

        $userOrgId = auth()->user()->organization_id;

        $counts = ReviewTemplate::withoutTenant()
            ->when($userOrgId, fn ($q) => $q->where('organization_id', $userOrgId))
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as total_active,
                 SUM(CASE WHEN is_locked = 1 THEN 1 ELSE 0 END) as total_locked,
                 SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as total_inactive'
            )
            ->first();

        $totalAll      = (int) ($counts->total_all      ?? 0);
        $totalActive   = (int) ($counts->total_active   ?? 0);
        $totalLocked   = (int) ($counts->total_locked   ?? 0);
        $totalInactive = (int) ($counts->total_inactive ?? 0);

        $periodTypes = collect(\Modules\PerformanceReview\Enums\PeriodType::cases())
            ->map(fn ($pt) => ['value' => $pt->value, 'text' => $pt->label()])
            ->all();

        return view('performancereview::templates.index', compact(
            'totalAll', 'totalActive', 'totalLocked', 'totalInactive', 'periodTypes'
        ));
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \Modules\PerformanceReview\Models\PerformanceReview::class);

        $validated = $request->validate([
            'page'        => ['nullable', 'integer', 'min:1'],
            'size'        => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'      => ['nullable', 'string', 'max:200'],
            'period_type' => ['nullable', 'string'],
            'is_active'   => ['nullable', 'string', 'in:1,0'],
            'date_from'   => ['nullable', 'date_format:Y-m-d'],
            'date_to'     => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $allowSort = ['name', 'period_type', 'rating_scale', 'criteria_count', 'created_at'];
        $sortField = (is_array($sortRaw) && in_array($sortRaw['field'] ?? '', $allowSort))
            ? $sortRaw['field'] : 'created_at';
        $sortDir   = (is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc') ? 'asc' : 'desc';

        $page    = max(1, (int) ($validated['page'] ?? 1));
        $perPage = min(100, max(5, (int) ($validated['size'] ?? 25)));

        $userOrgId = auth()->user()->organization_id;

        $query = ReviewTemplate::withoutTenant()
            ->when($userOrgId, fn ($q) => $q->where('organization_id', $userOrgId))
            ->withCount('criteria')
            ->when($validated['search'] ?? null, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($validated['period_type'] ?? null, fn ($q, $v) => $q->where('period_type', $v))
            ->when(isset($validated['is_active']) && $validated['is_active'] !== '',
                fn ($q) => $q->where('is_active', (bool) $validated['is_active']))
            ->when($validated['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($validated['date_to'] ?? null,   fn ($q, $v) => $q->whereDate('created_at', '<=', $v));

        if ($sortField === 'criteria_count') {
            $query->orderBy('criteria_count', $sortDir);
        } else {
            $query->orderBy($sortField, $sortDir);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $data = $paginator->map(function (ReviewTemplate $tpl) {
            $badges = [];
            if ($tpl->is_system) $badges[] = ['label' => 'Hệ thống', 'class' => 'badge-ghost'];
            if ($tpl->is_locked) $badges[] = ['label' => 'Đã khóa',  'class' => 'badge-warning'];
            if (! $tpl->is_active) $badges[] = ['label' => 'Ẩn',     'class' => 'badge-error'];

            return [
                'id'             => $tpl->id,
                'name'           => $tpl->name,
                'period_label'   => $tpl->period_type->label(),
                'rating_scale'   => $tpl->rating_scale,
                'criteria_count' => $tpl->criteria_count,
                'is_locked'      => $tpl->is_locked,
                'badges'         => $badges,
                'created_at'     => $tpl->created_at?->format('d/m/Y'),
                'show_url'       => route('backend.review-templates.show', $tpl),
                'delete_url'     => route('backend.review-templates.destroy', $tpl),
                'can_delete'     => ! $tpl->is_locked,
            ];
        })->values()->all();

        return response()->json([
            'data'      => $data,
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', \Modules\PerformanceReview\Models\PerformanceReview::class);

        [$organizations, $defaultOrgId, $orgLocked] = $this->resolveOrganizations();

        return view('performancereview::templates.create', compact('organizations', 'defaultOrgId', 'orgLocked'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', \Modules\PerformanceReview\Models\PerformanceReview::class);

        $orgId = $this->resolveOrgId($request);

        $validated = $request->validate([
            'organization_id'   => ['required', 'integer', 'exists:organizations,id'],
            'name'              => ['required', 'string', 'max:255'],
            'period_type'       => ['required', 'string', Rule::in(['monthly','quarterly','semi_annual','annual','probation','custom'])],
            'apply_to_function' => ['nullable', 'string', 'max:50'],
            'rating_scale'      => ['required', 'integer', Rule::in([5, 10])],
            'criteria'          => ['required', 'array', 'min:1'],
            'criteria.*.criteria_key'  => ['required', 'string', 'max:100'],
            'criteria.*.criteria_name' => ['required', 'string', 'max:255'],
            'criteria.*.weight'        => ['required', 'numeric', 'min:0.01', 'max:100'],
            'criteria.*.max_score'     => ['required', 'integer', 'min:1', 'max:10'],
            'criteria.*.description'   => ['nullable', 'string'],
        ]);

        // Validate total weight = 100
        $totalWeight = collect($validated['criteria'])->sum('weight');
        if (abs($totalWeight - 100) > 0.01) {
            return back()->withInput()->withErrors(['criteria' => 'Tổng trọng số các tiêu chí phải bằng 100. Hiện tại: ' . $totalWeight]);
        }

        $template = ReviewTemplate::create([
            'uuid'              => Str::uuid(),
            'organization_id'   => $orgId,
            'created_by'        => auth()->id(),
            'name'              => $validated['name'],
            'period_type'       => $validated['period_type'],
            'apply_to_function' => $validated['apply_to_function'],
            'rating_scale'      => $validated['rating_scale'],
            'is_system'         => false,
            'is_locked'         => false,
            'is_active'         => true,
        ]);

        foreach ($validated['criteria'] as $i => $c) {
            ReviewCriteria::create([
                'template_id'   => $template->id,
                'criteria_key'  => Str::slug($c['criteria_key'], '_'),
                'criteria_name' => $c['criteria_name'],
                'weight'        => $c['weight'],
                'max_score'     => $c['max_score'],
                'description'   => $c['description'] ?? null,
                'sort_order'    => $i,
            ]);
        }

        return redirect()->route('backend.review-templates.index')
            ->with('success', 'Mẫu đánh giá "' . $template->name . '" đã được tạo.');
    }

    public function show(ReviewTemplate $reviewTemplate)
    {
        $this->authorize('viewAny', \Modules\PerformanceReview\Models\PerformanceReview::class);

        $reviewTemplate->load('criteria');

        return view('performancereview::templates.show', ['template' => $reviewTemplate]);
    }

    public function destroy(Request $request, ReviewTemplate $reviewTemplate): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', \Modules\PerformanceReview\Models\PerformanceReview::class);

        if ($reviewTemplate->is_locked) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Mẫu này đang bị khóa, không thể xóa.'], 422);
            }
            return back()->withErrors(['template' => 'Mẫu này đang bị khóa, không thể xóa.']);
        }

        $name = $reviewTemplate->name;
        $reviewTemplate->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa mẫu "' . $name . '".' ]);
        }

        return redirect()->route('backend.review-templates.index')
            ->with('success', 'Đã xóa mẫu "' . $name . '".');
    }
}
