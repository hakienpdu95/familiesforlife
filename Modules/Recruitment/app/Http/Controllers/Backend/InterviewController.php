<?php

namespace Modules\Recruitment\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Recruitment\Actions\Backend\ScheduleInterviewAction;
use Modules\Recruitment\Enums\InterviewStatus;
use Modules\Recruitment\Enums\InterviewType;
use Modules\Recruitment\Enums\PanelistRole;
use Modules\Recruitment\Models\RcApplication;
use Modules\Recruitment\Models\RcInterview;
use Modules\Recruitment\Models\RcPipelineStage;

class InterviewController extends Controller
{
    public function create(Request $request): View
    {
        $this->authorize('create', RcApplication::class);

        $application = RcApplication::with(['candidate', 'currentStage'])->findOrFail($request->query('application_id'));

        $stages      = RcPipelineStage::query()->active()->ordered()->get(['id', 'name', 'stage_type']);
        $orgId       = TenantContext::getOrganizationId();
        $users       = User::query()
            ->where('organization_id', $orgId)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $interviewTypes = collect(InterviewType::cases())->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])->all();
        $panelistRoles  = collect(PanelistRole::cases())->map(fn ($r) => ['value' => $r->value, 'text' => $r->label()])->all();

        return view('recruitment::interviews.create', compact(
            'application', 'stages', 'users', 'interviewTypes', 'panelistRoles'
        ));
    }

    public function store(Request $request, RcApplication $application, ScheduleInterviewAction $action): RedirectResponse
    {
        $this->authorize('update', $application);

        $validated = $request->validate([
            'stage_id'         => ['required', 'integer', 'exists:rc_pipeline_stages,id'],
            'interview_type'   => ['required', 'string', 'in:' . implode(',', array_column(InterviewType::cases(), 'value'))],
            'title'            => ['nullable', 'string', 'max:200'],
            'scheduled_at'     => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'location'         => ['nullable', 'string', 'max:300'],
            'meeting_url'      => ['nullable', 'url', 'max:2000'],
            'meeting_id'       => ['nullable', 'string', 'max:100'],
            'interviewer_note' => ['nullable', 'string'],
            'panelists'        => ['nullable', 'array'],
            'panelists.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'panelists.*.role'    => ['required', 'string', 'in:' . implode(',', array_column(PanelistRole::cases(), 'value'))],
        ]);

        $interview = $action->handle($application, $validated);

        return redirect()
            ->route('backend.recruitment.interviews.show', $interview)
            ->with('success', 'Đã tạo lịch phỏng vấn');
    }

    public function show(RcInterview $interview): View
    {
        $this->authorize('view', $interview->application);

        $interview->load([
            'application.candidate',
            'application.currentStage',
            'stage',
            'panelists.user',
            'evaluations.evaluator',
            'evaluations.criteria',
            'createdBy',
        ]);

        $statuses = collect(InterviewStatus::cases())->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])->all();

        return view('recruitment::interviews.show', compact('interview', 'statuses'));
    }

    public function updateStatus(Request $request, RcInterview $interview): JsonResponse
    {
        $this->authorize('update', $interview->application);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', array_column(InterviewStatus::cases(), 'value'))],
        ]);

        $interview->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Đã cập nhật trạng thái phỏng vấn',
            'status'  => $interview->fresh()->status?->value,
            'label'   => $interview->fresh()->status?->label(),
        ]);
    }

    public function mySchedule(): View
    {
        $userId = auth()->id();

        $panelAssignments = \Modules\Recruitment\Models\RcInterviewPanelist::query()
            ->with(['interview.application.candidate', 'interview.stage', 'interview.evaluations'])
            ->where('user_id', $userId)
            ->whereHas('interview', fn ($q) => $q->whereIn('status', ['scheduled', 'confirmed']))
            ->join('rc_interviews', 'rc_interviews.id', '=', 'rc_interview_panelists.interview_id')
            ->orderBy('rc_interviews.scheduled_at', 'asc')
            ->select('rc_interview_panelists.*')
            ->get();

        return view('recruitment::interviews.my-schedule', compact('panelAssignments'));
    }
}
