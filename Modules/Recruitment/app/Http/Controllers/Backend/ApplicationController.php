<?php

namespace Modules\Recruitment\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Recruitment\Actions\Backend\MoveApplicationAction;
use Modules\Recruitment\Actions\Backend\RejectApplicationAction;
use Modules\Recruitment\Actions\Backend\StoreApplicationAction;
use Modules\Recruitment\Data\Requests\MoveApplicationData;
use Modules\Recruitment\Data\Requests\StoreApplicationData;
use Modules\Recruitment\Enums\ApplicationStatus;
use Modules\Recruitment\Enums\CandidateSource;
use Modules\Recruitment\Models\RcApplication;
use Modules\Recruitment\Models\RcCandidate;
use Modules\Recruitment\Models\RcPipelineStage;

class ApplicationController extends Controller
{
    public function create(Request $request): View
    {
        $this->authorize('create', RcApplication::class);

        $candidate   = RcCandidate::findOrFail($request->query('candidate_id'));
        $stages      = RcPipelineStage::query()->active()->ordered()->get(['id', 'name', 'stage_type']);
        $sources     = collect(CandidateSource::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        return view('recruitment::applications.create', compact('candidate', 'stages', 'sources'));
    }

    public function store(Request $request, StoreApplicationAction $action): RedirectResponse
    {
        $this->authorize('create', RcApplication::class);

        $data = StoreApplicationData::validateAndCreate($request->all());
        $application = $action->handle($data);

        return redirect()
            ->route('backend.recruitment.applications.show', $application)
            ->with('success', 'Đã tạo đơn ứng tuyển');
    }

    public function show(RcApplication $application): View
    {
        $this->authorize('view', $application);

        $application->load([
            'candidate',
            'currentStage',
            'assignedTo',
            'stageLogs.stage',
            'stageLogs.actionedBy',
            'answers',
            'interviews.stage',
            'interviews.panelists.user',
            'interviews.evaluations',
            'offers',
        ]);

        $stages = RcPipelineStage::query()->active()->ordered()->get(['id', 'name', 'stage_type', 'color_hex']);

        return view('recruitment::applications.show', compact('application', 'stages'));
    }

    public function move(Request $request, RcApplication $application, MoveApplicationAction $action): JsonResponse
    {
        $this->authorize('update', $application);

        $data = MoveApplicationData::validateAndCreate($request->all());
        $updated = $action->handle($application, $data);

        return response()->json([
            'message'     => 'Đã chuyển stage thành công',
            'stage_name'  => $updated->currentStage?->name,
            'status'      => $updated->status?->value,
        ]);
    }

    public function reject(Request $request, RcApplication $application, RejectApplicationAction $action): JsonResponse
    {
        $this->authorize('update', $application);

        $updated = $action->handle($application, $request->input('reason'));

        return response()->json([
            'message' => 'Đã từ chối ứng viên',
            'status'  => $updated->status?->value,
        ]);
    }

    public function assign(Request $request, RcApplication $application): JsonResponse
    {
        $this->authorize('update', $application);

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $application->update(['assigned_to' => $validated['user_id']]);

        return response()->json(['message' => 'Đã gán recruiter']);
    }
}
