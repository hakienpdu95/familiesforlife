<?php

namespace Modules\PerformanceReview\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;
use Modules\Employee\Enums\EmployeeStatus;
use Modules\PerformanceReview\Actions\Backend\DestroyPerformanceReviewAction;
use Modules\PerformanceReview\Actions\Backend\FinalizeReviewAction;
use Modules\PerformanceReview\Actions\Backend\StorePerformanceReviewAction;
use Modules\PerformanceReview\Actions\Backend\UpdatePerformanceReviewAction;
use Modules\PerformanceReview\Data\Requests\StorePerformanceReviewData;
use Modules\PerformanceReview\Data\Requests\UpdatePerformanceReviewData;
use Modules\PerformanceReview\Enums\ReviewStatus;
use Modules\PerformanceReview\Models\PerformanceReview;
use Modules\PerformanceReview\Models\ReviewTemplate;

class PerformanceReviewController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(PerformanceReview::class, 'performance_review');
    }

    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            return [Organization::where('id', $userOrgId)->get(['id', 'name']), $userOrgId, true];
        }
        return [Organization::orderBy('name')->get(['id', 'name']), null, false];
    }

    public function index()
    {
        $orgId = TenantContext::getOrganizationId();

        $counts = PerformanceReview::withoutTenant()
            ->where('organization_id', $orgId)
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_draft,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_submitted,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_finalized',
                ['draft', 'submitted', 'finalized']
            )
            ->first();

        $statuses = collect(ReviewStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $templates = ReviewTemplate::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($t) => ['value' => $t->id, 'text' => $t->name])
            ->all();

        return view('performancereview::index', [
            'totalAll'       => (int) ($counts->total_all ?? 0),
            'totalDraft'     => (int) ($counts->total_draft ?? 0),
            'totalSubmitted' => (int) ($counts->total_submitted ?? 0),
            'totalFinalized' => (int) ($counts->total_finalized ?? 0),
            'statuses'       => $statuses,
            'templates'      => $templates,
        ]);
    }

    public function create()
    {
        $orgId = TenantContext::getOrganizationId();

        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        $templates = ReviewTemplate::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('name')
            ->with('criteria')
            ->get();

        // Resolve only on validation-failed re-render
        $selectedEmployee = old('employee_id') ? Employee::withoutTenant()->find(old('employee_id'), ['id', 'full_name', 'employee_code']) : null;
        $selectedReviewer = old('reviewer_id') ? Employee::withoutTenant()->find(old('reviewer_id'), ['id', 'full_name', 'employee_code']) : null;

        return view('performancereview::create', compact(
            'templates', 'organizations', 'defaultOrgId', 'orgLocked',
            'selectedEmployee', 'selectedReviewer'
        ));
    }

    public function store(Request $request, StorePerformanceReviewAction $action): RedirectResponse
    {
        $data   = StorePerformanceReviewData::validateAndCreate($request->all());
        $review = $action->handle($data);

        return redirect()->route('backend.performance-reviews.show', $review)
            ->with('success', 'Đánh giá hiệu suất đã được tạo thành công.');
    }

    public function show(PerformanceReview $performanceReview)
    {
        $performanceReview->load([
            'employee.branch', 'employee.department', 'employee.jobTitle',
            'reviewer',
            'template.criteria',
            'scores',
        ]);

        return view('performancereview::show', ['review' => $performanceReview]);
    }

    public function edit(PerformanceReview $performanceReview)
    {
        [$organizations, , $orgLocked] = $this->_resolveOrganizations();

        $orgId = TenantContext::getOrganizationId();

        $templates = ReviewTemplate::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('name')
            ->with('criteria')
            ->get();

        $performanceReview->load(['scores', 'template.criteria', 'reviewer']);

        // Override with old() values only when validation fails on re-render
        $selectedReviewer = old('reviewer_id')
            ? Employee::withoutTenant()->find(old('reviewer_id'), ['id', 'full_name', 'employee_code'])
            : null;

        return view('performancereview::edit', [
            'review'           => $performanceReview,
            'templates'        => $templates,
            'organizations'    => $organizations,
            'orgLocked'        => $orgLocked,
            'selectedReviewer' => $selectedReviewer,
        ]);
    }

    public function update(Request $request, PerformanceReview $performanceReview, UpdatePerformanceReviewAction $action): RedirectResponse
    {
        $data = UpdatePerformanceReviewData::validateAndCreate($request->all());
        $action->handle($performanceReview, $data);

        return redirect()->route('backend.performance-reviews.show', $performanceReview)
            ->with('success', 'Cập nhật đánh giá thành công.');
    }

    public function destroy(Request $request, PerformanceReview $performanceReview, DestroyPerformanceReviewAction $action): RedirectResponse|JsonResponse
    {
        $label = $action->handle($performanceReview);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa đánh giá "' . $label . '".' ]);
        }

        return redirect()->route('backend.performance-reviews.index')
            ->with('success', 'Đã xóa đánh giá "' . $label . '".');
    }

    public function finalize(PerformanceReview $performanceReview, FinalizeReviewAction $action): RedirectResponse
    {
        $this->authorize('finalize', $performanceReview);

        $action->handle($performanceReview);

        return redirect()->route('backend.performance-reviews.show', $performanceReview)
            ->with('success', 'Đánh giá đã được hoàn tất. Điểm tổng đã được tính.');
    }

    public function submit(PerformanceReview $performanceReview): RedirectResponse
    {
        $this->authorize('update', $performanceReview);

        $performanceReview->update(['status' => 'submitted']);

        return redirect()->route('backend.performance-reviews.show', $performanceReview)
            ->with('success', 'Đã nộp đánh giá.');
    }
}
