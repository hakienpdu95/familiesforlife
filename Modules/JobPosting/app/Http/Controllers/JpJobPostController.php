<?php

namespace Modules\JobPosting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Department\Models\Department;
use Modules\JobPosting\Actions\Backend\ArchiveJpJobPostAction;
use Modules\JobPosting\Actions\Backend\CloseJpJobPostAction;
use Modules\JobPosting\Actions\Backend\DestroyJpJobPostAction;
use Modules\JobPosting\Actions\Backend\DuplicateJpJobPostAction;
use Modules\JobPosting\Actions\Backend\PauseJpJobPostAction;
use Modules\JobPosting\Actions\Backend\PublishJpJobPostAction;
use Modules\JobPosting\Actions\Backend\StoreJpJobPostAction;
use Modules\JobPosting\Actions\Backend\SubmitReviewJpJobPostAction;
use Modules\JobPosting\Actions\Backend\SyncMarketplaceAction;
use Modules\JobPosting\Actions\Backend\UpdateJpJobPostAction;
use Modules\JobPosting\Data\Requests\StoreJpJobPostData;
use Modules\JobPosting\Data\Requests\UpdateJpJobPostData;
use Modules\JobPosting\Enums\EmploymentType;
use Modules\JobPosting\Enums\ExperienceLevel;
use Modules\JobPosting\Enums\Industry;
use Modules\JobPosting\Enums\JobPostStatus;
use Modules\JobPosting\Enums\MktSyncStatus;
use Modules\JobPosting\Enums\SalaryType;
use Modules\JobPosting\Enums\Visibility;
use Modules\JobPosting\Enums\WorkArrangement;
use Modules\JobPosting\Models\JpBenefitMaster;
use Modules\JobPosting\Models\JpJobPost;
use Modules\JobPosting\Models\JpSkillMaster;
use Modules\JobTitle\Models\JobTitle;

class JpJobPostController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(JpJobPost::class, 'job_post');
    }

    public function index()
    {
        $orgId = TenantContext::getOrganizationId();

        $counts = JpJobPost::withoutTenant()
            ->where('organization_id', $orgId)
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_published,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_draft,
                 SUM(CASE WHEN status IN (?,?) THEN 1 ELSE 0 END) as total_closed,
                 SUM(CASE WHEN status = ? THEN application_count ELSE 0 END) as total_applications,
                 SUM(CASE WHEN status = ? THEN view_count ELSE 0 END) as total_views,
                 SUM(CASE WHEN mkt_sync_status = ? THEN 1 ELSE 0 END) as out_of_sync_count',
                [
                    JobPostStatus::Published->value,
                    JobPostStatus::Draft->value,
                    JobPostStatus::Closed->value,
                    JobPostStatus::Archived->value,
                    JobPostStatus::Published->value,
                    JobPostStatus::Published->value,
                    MktSyncStatus::OutOfSync->value,
                ]
            )
            ->first();

        $expiringSoon = JpJobPost::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', JobPostStatus::Published->value)
            ->whereNotNull('expire_at')
            ->where('expire_at', '>', now())
            ->where('expire_at', '<=', now()->addDays(7))
            ->count();

        $statuses = collect(JobPostStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $employmentTypes = collect(EmploymentType::cases())
            ->map(fn ($e) => ['value' => $e->value, 'text' => $e->label()])
            ->all();

        $workArrangements = collect(WorkArrangement::cases())
            ->map(fn ($w) => ['value' => $w->value, 'text' => $w->label()])
            ->all();

        $experienceLevels = collect(ExperienceLevel::cases())
            ->map(fn ($e) => ['value' => $e->value, 'text' => $e->label()])
            ->all();

        $industries = collect(Industry::cases())
            ->map(fn ($i) => ['value' => $i->value, 'text' => $i->label()])
            ->all();

        $departments = Department::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($d) => ['value' => $d->id, 'text' => $d->name])
            ->all();

        return view('job-posting::index', compact(
            'counts', 'expiringSoon',
            'statuses', 'employmentTypes', 'workArrangements',
            'experienceLevels', 'industries', 'departments'
        ));
    }

    public function create()
    {
        $orgId = TenantContext::getOrganizationId();

        $departments = Department::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $jobTitles = JobTitle::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $skills = JpSkillMaster::withoutTenant()
            ->where(fn ($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'category']);

        $benefits = JpBenefitMaster::withoutTenant()
            ->where(fn ($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'category', 'icon']);

        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        return view('job-posting::create', compact(
            'departments', 'jobTitles', 'skills', 'benefits',
            'organizations', 'defaultOrgId', 'orgLocked'
        ) + $this->enumOptions());
    }

    public function store(Request $request, StoreJpJobPostAction $action): RedirectResponse
    {
        $data = StoreJpJobPostData::validateAndCreate($request->all());
        $post = $action->handle($data);

        return redirect()->route('backend.job-posts.show', $post)
            ->with('success', 'Tin tuyển dụng "' . $post->title . '" đã được tạo.');
    }

    public function show(JpJobPost $jobPost)
    {
        $jobPost->load(['department', 'jobTitle', 'owner', 'createdBy', 'histories.changedBy']);

        return view('job-posting::show', compact('jobPost'));
    }

    public function edit(JpJobPost $jobPost)
    {
        $orgId = TenantContext::getOrganizationId();

        $departments = Department::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $jobTitles = JobTitle::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        [$organizations, , $orgLocked] = $this->_resolveOrganizations();

        return view('job-posting::edit', compact('jobPost', 'departments', 'jobTitles', 'organizations', 'orgLocked') + $this->enumOptions());
    }

    public function update(Request $request, JpJobPost $jobPost, UpdateJpJobPostAction $action): RedirectResponse
    {
        $data = UpdateJpJobPostData::validateAndCreate($request->all());
        $action->handle($jobPost, $data);

        return redirect()->route('backend.job-posts.show', $jobPost)
            ->with('success', 'Cập nhật tin tuyển dụng thành công.');
    }

    public function destroy(Request $request, JpJobPost $jobPost, DestroyJpJobPostAction $action): RedirectResponse|JsonResponse
    {
        $title = $action->handle($jobPost);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa tin "' . $title . '".']);
        }

        return redirect()->route('backend.job-posts.index')
            ->with('success', 'Đã xóa tin "' . $title . '".');
    }

    public function publish(Request $request, JpJobPost $jobPost, PublishJpJobPostAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('publish', $jobPost);
        $action->handle($jobPost);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã publish tin tuyển dụng.']);
        }

        return redirect()->route('backend.job-posts.show', $jobPost)
            ->with('success', 'Đã publish tin tuyển dụng.');
    }

    public function close(Request $request, JpJobPost $jobPost, CloseJpJobPostAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('close', $jobPost);
        $action->handle($jobPost, $request->input('note'));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã đóng tin tuyển dụng.']);
        }

        return redirect()->route('backend.job-posts.show', $jobPost)
            ->with('success', 'Đã đóng tin tuyển dụng.');
    }

    public function submitReview(Request $request, JpJobPost $jobPost, SubmitReviewJpJobPostAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $jobPost);
        $action->handle($jobPost);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã gửi tin để duyệt.']);
        }

        return redirect()->route('backend.job-posts.show', $jobPost)
            ->with('success', 'Đã gửi tin để duyệt.');
    }

    public function pause(Request $request, JpJobPost $jobPost, PauseJpJobPostAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('close', $jobPost);
        $action->handle($jobPost, $request->input('note'));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã tạm dừng tin tuyển dụng.']);
        }

        return redirect()->route('backend.job-posts.show', $jobPost)
            ->with('success', 'Đã tạm dừng tin tuyển dụng.');
    }

    public function archive(Request $request, JpJobPost $jobPost, ArchiveJpJobPostAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('close', $jobPost);
        $action->handle($jobPost);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã lưu trữ tin tuyển dụng.']);
        }

        return redirect()->route('backend.job-posts.show', $jobPost)
            ->with('success', 'Đã lưu trữ tin tuyển dụng.');
    }

    public function duplicate(Request $request, JpJobPost $jobPost, DuplicateJpJobPostAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('create', JpJobPost::class);
        $newPost = $action->handle($jobPost);

        if ($request->expectsJson()) {
            return response()->json([
                'message'  => 'Đã nhân bản tin tuyển dụng.',
                'redirect' => route('backend.job-posts.show', $newPost),
            ]);
        }

        return redirect()->route('backend.job-posts.show', $newPost)
            ->with('success', 'Đã nhân bản tin "' . $jobPost->title . '".');
    }

    public function syncMarketplace(Request $request, JpJobPost $jobPost, SyncMarketplaceAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('publish', $jobPost);
        $action->handle($jobPost);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã đồng bộ ra Marketplace.']);
        }

        return redirect()->route('backend.job-posts.show', $jobPost)
            ->with('success', 'Đã đồng bộ ra Marketplace.');
    }

    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            return [Organization::where('id', $userOrgId)->get(['id', 'name']), $userOrgId, true];
        }
        return [Organization::orderBy('name')->get(['id', 'name']), null, false];
    }

    private function enumOptions(): array
    {
        return [
            'employmentTypes'  => collect(EmploymentType::cases())->map(fn ($e) => ['value' => $e->value, 'text' => $e->label()])->all(),
            'workArrangements' => collect(WorkArrangement::cases())->map(fn ($w) => ['value' => $w->value, 'text' => $w->label()])->all(),
            'experienceLevels' => collect(ExperienceLevel::cases())->map(fn ($e) => ['value' => $e->value, 'text' => $e->label()])->all(),
            'industries'       => collect(Industry::cases())->map(fn ($i) => ['value' => $i->value, 'text' => $i->label()])->all(),
            'salaryTypes'      => collect(SalaryType::cases())->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])->all(),
            'visibilities'     => collect(Visibility::cases())->map(fn ($v) => ['value' => $v->value, 'text' => $v->label()])->all(),
        ];
    }
}
