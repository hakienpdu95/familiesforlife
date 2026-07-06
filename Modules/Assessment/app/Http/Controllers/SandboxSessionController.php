<?php

namespace Modules\Assessment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Modules\Assessment\Models\SandboxEnvironment;
use Modules\Assessment\Models\SandboxSession;
use Modules\Assessment\Models\SandboxTask;
use Modules\Assessment\Models\WorkforceProfile;
use Modules\Assessment\Services\SandboxScoringService;

class SandboxSessionController extends Controller
{
    public function index(Request $request): View
    {
        $user  = $request->user();
        $orgId = TenantContext::getOrganizationId();

        $profile = WorkforceProfile::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('user_id', $user->id)
            ->first();

        $environments = SandboxEnvironment::whereNull('organization_id')
            ->orWhere('organization_id', $orgId)
            ->where('is_active', true)
            ->with('tasks')
            ->orderBy('tier')
            ->get();

        $mySessions = $profile
            ? SandboxSession::withoutTenant()
                ->where('workforce_profile_id', $profile->id)
                ->with('task.environment')
                ->orderByDesc('started_at')
                ->limit(20)
                ->get()
            : collect();

        // Compute live from sessions (more accurate than stored profile fields)
        $completed  = $mySessions->where('status', 'completed');
        $stats = [
            'total'  => $completed->count(),
            'hours'  => round($mySessions->sum('duration_minutes') / 60, 1),
            'avg'    => $completed->avg('final_score') ? round($completed->avg('final_score'), 1) : null,
            'passed' => $completed->where('passed', true)->count(),
        ];

        return view('assessment::sandbox.index', compact(
            'profile', 'environments', 'mySessions', 'stats'
        ));
    }

    /** Bắt đầu hoặc tiếp tục một phiên sandbox cho task */
    public function start(Request $request, SandboxTask $sandboxTask): RedirectResponse
    {
        $user  = $request->user();
        $orgId = TenantContext::getOrganizationId();

        $profile = WorkforceProfile::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('user_id', $user->id)
            ->first();

        // Auto-create an empty profile so sessions can always be linked
        if (! $profile) {
            $profile = WorkforceProfile::create([
                'uuid'            => Str::uuid(),
                'organization_id' => $orgId,
                'user_id'         => $user->id,
            ]);
        }

        // Resume existing in-progress session for this task
        $existing = SandboxSession::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('user_id', $user->id)
            ->where('sandbox_task_id', $sandboxTask->id)
            ->whereIn('status', ['in_progress', 'submitted'])
            ->latest()
            ->first();

        if ($existing) {
            return redirect()->route('backend.sandbox.show', $existing);
        }

        $session = SandboxSession::create([
            'uuid'                 => Str::uuid(),
            'organization_id'     => $orgId,
            'sandbox_task_id'     => $sandboxTask->id,
            'workforce_profile_id'=> $profile?->id,
            'user_id'             => $user->id,
            'status'              => 'in_progress',
            'started_at'          => now(),
        ]);

        return redirect()->route('backend.sandbox.show', $session);
    }

    /** Nộp bài và tự động chấm điểm */
    public function submit(Request $request, SandboxSession $sandboxSession): RedirectResponse
    {
        if ($sandboxSession->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($sandboxSession->status !== 'in_progress') {
            return redirect()->route('backend.sandbox.show', $sandboxSession)
                ->with('error', 'Phiên này không ở trạng thái có thể nộp.');
        }

        $data = $request->validate([
            'submitted_content' => 'nullable|string|max:10000',
            'ai_tools_used'     => 'nullable|string|max:500',
        ]);

        $duration = (int) now()->diffInMinutes($sandboxSession->started_at);

        $sandboxSession->update([
            'status'           => 'submitted',
            'submitted_at'     => now(),
            'duration_minutes' => max(1, $duration),
        ]);

        // Reload task for scoring
        $sandboxSession->load('task');

        $submission = $sandboxSession->submission()->create([
            'sandbox_session_id' => $sandboxSession->id,
            'submitted_content'  => $data['submitted_content'] ?? null,
            'ai_tools_used'      => $data['ai_tools_used'] ?? null,
            'submitted_at'       => now(),
        ]);

        // Auto-score immediately — sandbox is a training tool, not an exam
        (new SandboxScoringService())->autoScore($sandboxSession, $submission);

        return redirect()->route('backend.sandbox.show', $sandboxSession)
            ->with('success', 'Bài đã nộp và được chấm điểm tự động!');
    }

    /** Admin override: chấm điểm thủ công cho bất kỳ session nào */
    public function evaluate(Request $request, SandboxSession $sandboxSession): RedirectResponse
    {
        $this->authorize('assessment.results');

        $data = $request->validate([
            'quality_score'      => 'required|numeric|min:0|max:100',
            'productivity_score' => 'required|numeric|min:0|max:100',
            'ai_adoption_score'  => 'required|numeric|min:0|max:100',
            'feedback'           => 'nullable|string|max:2000',
        ]);

        $final = round(
            $data['quality_score']      * 0.40 +
            $data['productivity_score'] * 0.35 +
            $data['ai_adoption_score']  * 0.25,
        2);

        $sandboxSession->update([
            'quality_score'      => $data['quality_score'],
            'productivity_score' => $data['productivity_score'],
            'ai_adoption_score'  => $data['ai_adoption_score'],
            'final_score'        => $final,
            'passed'             => $final >= 60,
            'feedback'           => $data['feedback'],
            'status'             => 'completed',
            'completed_at'       => $sandboxSession->completed_at ?? now(),
            'evaluated_at'       => now(),
            'evaluator_user_id'  => $request->user()->id,
        ]);

        return redirect()->route('backend.sandbox.show', $sandboxSession)
            ->with('success', 'Đã cập nhật điểm thủ công.');
    }

    public function show(SandboxSession $sandboxSession): View
    {
        if ($sandboxSession->user_id !== request()->user()?->id) {
            $this->authorize('assessment.results');
        }

        $sandboxSession->load(['task.environment', 'submission', 'activities']);
        return view('assessment::sandbox.show', ['session' => $sandboxSession]);
    }
}
