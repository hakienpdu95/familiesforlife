<?php

namespace Modules\Assessment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Assessment\Enums\CampaignStatus;
use Modules\Assessment\Enums\ParticipationStatus;
use Modules\Assessment\Jobs\CreateCampaignPassportEntryJob;
use Modules\Assessment\Models\CampaignParticipation;
use Modules\Assessment\Models\CampaignParticipationScore;
use Modules\Assessment\Models\OpenAssessmentCampaign;
use Modules\Assessment\Models\SandboxSession;
use Modules\Assessment\Models\SandboxSubmission;
use Modules\Assessment\Models\SandboxTask;
use Modules\Assessment\Services\CampaignEligibilityService;

class CampaignController extends Controller
{
    public function __construct(private readonly CampaignEligibilityService $eligibility) {}

    /**
     * GET /campaigns — Danh sách campaign đang open
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $campaigns = OpenAssessmentCampaign::where('status', CampaignStatus::Open->value)
            ->where(function ($q) {
                $q->whereNull('open_from')->orWhere('open_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('open_until')->orWhere('open_until', '>', now());
            })
            ->with(['organization', 'domainRequirements'])
            ->withCount('participations')
            ->orderByDesc('created_at')
            ->paginate(12);

        $myParticipationCampaignIds = CampaignParticipation::where('user_id', $user->id)
            ->pluck('campaign_id')
            ->flip();

        return view('assessment::campaigns.index', compact('campaigns', 'user', 'myParticipationCampaignIds'));
    }

    /**
     * GET /campaigns/{campaign} — Chi tiết campaign
     */
    public function show(Request $request, OpenAssessmentCampaign $campaign): View
    {
        $user = $request->user();

        $campaign->load(['organization', 'domainRequirements', 'sandboxTasks.task']);

        $myParticipation = CampaignParticipation::where('campaign_id', $campaign->id)
            ->where('user_id', $user->id)
            ->first();

        // Only run eligibility check when the user hasn't joined yet
        $eligibility = $myParticipation ? null : $this->eligibility->check($user, $campaign);

        return view('assessment::campaigns.show', compact('campaign', 'user', 'myParticipation', 'eligibility'));
    }

    /**
     * POST /campaigns/{campaign}/join
     */
    public function join(Request $request, OpenAssessmentCampaign $campaign): RedirectResponse
    {
        $user = $request->user();

        $existing = CampaignParticipation::where('campaign_id', $campaign->id)
            ->where('user_id', $user->id)->exists();

        if ($existing) {
            return redirect()->route('campaigns.workspace', $campaign->uuid)
                ->with('info', 'Bạn đã tham gia campaign này rồi.');
        }

        $eligibility = $this->eligibility->check($user, $campaign);

        if (!$eligibility->canJoin) {
            return back()->withErrors(['join' => $eligibility->block->message]);
        }

        DB::transaction(function () use ($campaign, $user) {
            CampaignParticipation::create([
                'campaign_id' => $campaign->id,
                'user_id'     => $user->id,
                'joined_at'   => now(),
                'status'      => ParticipationStatus::InProgress->value,
            ]);
            OpenAssessmentCampaign::where('id', $campaign->id)->increment('participants_count');
        });

        $redirect = redirect()->route('campaigns.workspace', $campaign->uuid)
            ->with('success', 'Đã tham gia campaign. Hoàn thành các task sandbox để nộp bài.');

        // Carry cross-org advisory into the workspace as a one-time info notice
        foreach ($eligibility->advisories as $advisory) {
            $redirect->with('info', $advisory->message);
            break; // only first advisory surfaced as session flash
        }

        return $redirect;
    }

    /**
     * GET /campaigns/{campaign}/workspace
     */
    public function workspace(Request $request, OpenAssessmentCampaign $campaign): View
    {
        $user = $request->user();

        $participation = CampaignParticipation::where('campaign_id', $campaign->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $campaign->load(['sandboxTasks.task', 'organization']);

        $taskIds = $campaign->sandboxTasks->pluck('sandbox_task_id');
        $sessions = SandboxSession::withoutTenant()
            ->where('user_id', $user->id)
            ->whereIn('sandbox_task_id', $taskIds)
            ->get()
            ->keyBy('sandbox_task_id');

        $requiredTasksDone = $campaign->sandboxTasks
            ->where('is_required', true)
            ->every(fn($ct) => isset($sessions[$ct->sandbox_task_id])
                && in_array($sessions[$ct->sandbox_task_id]->status, ['completed', 'submitted']));

        return view('assessment::campaigns.workspace', compact(
            'campaign', 'participation', 'user', 'sessions', 'requiredTasksDone'
        ));
    }

    /**
     * POST /campaigns/{campaign}/submit
     */
    public function submit(Request $request, OpenAssessmentCampaign $campaign): RedirectResponse
    {
        $user = $request->user();

        $participation = CampaignParticipation::where('campaign_id', $campaign->id)
            ->where('user_id', $user->id)
            ->where('status', ParticipationStatus::InProgress->value)
            ->firstOrFail();

        $taskIds = $campaign->sandboxTasks()->pluck('sandbox_task_id');

        $sessions = SandboxSession::withoutTenant()
            ->where('user_id', $user->id)
            ->whereIn('sandbox_task_id', $taskIds)
            ->whereIn('status', ['completed', 'submitted'])
            ->get();

        $requiredTaskIds  = $campaign->sandboxTasks()->where('is_required', true)->pluck('sandbox_task_id');
        $completedTaskIds = $sessions->pluck('sandbox_task_id');

        if ($requiredTaskIds->diff($completedTaskIds)->isNotEmpty()) {
            return back()->withErrors(['submit' => 'Bạn chưa hoàn thành tất cả task bắt buộc.']);
        }

        $avgScore      = round((float) ($sessions->avg('final_score') ?? 0), 2);
        $tdwcfScore    = $avgScore;
        $maturityLevel = $this->scoreToMaturityLevel($tdwcfScore);

        DB::transaction(function () use ($participation, $tdwcfScore, $maturityLevel, $avgScore) {
            $participation->update([
                'status'                => ParticipationStatus::Completed->value,
                'completed_at'          => now(),
                'result_tdwcf_score'    => $tdwcfScore,
                'result_maturity_level' => $maturityLevel,
                'result_sandbox_avg'    => $avgScore,
            ]);

            // Simplified domain breakdown from sandbox average
            $domainMap = [
                'D1' => min(100, round($tdwcfScore * 0.90, 2)),
                'D3' => min(100, round($tdwcfScore * 1.05, 2)),
                'D4' => min(100, round($tdwcfScore, 2)),
            ];

            foreach ($domainMap as $code => $score) {
                CampaignParticipationScore::upsert(
                    [['participation_id' => $participation->id, 'domain_code' => $code, 'score' => $score]],
                    ['participation_id', 'domain_code'],
                    ['score']
                );
            }
        });

        CreateCampaignPassportEntryJob::dispatchSync($participation->id);

        return redirect()->route('passport.index')
            ->with('success', "Nộp bài thành công! Điểm TDWCF: {$tdwcfScore}. Kết quả đã được lưu vào Competency Passport.");
    }

    /**
     * PATCH /campaigns/{campaign}/decline — Từ chối lời mời org
     */
    public function decline(Request $request, OpenAssessmentCampaign $campaign): RedirectResponse
    {
        $user = $request->user();

        CampaignParticipation::where('campaign_id', $campaign->id)
            ->where('user_id', $user->id)
            ->update(['status' => ParticipationStatus::Declined->value]);

        return redirect()->route('campaigns.index')->with('info', 'Đã từ chối lời mời.');
    }

    /**
     * GET /campaigns/{campaign}/tasks/{task}
     */
    public function taskView(Request $request, OpenAssessmentCampaign $campaign, SandboxTask $task): View
    {
        $user = $request->user();

        $participation = CampaignParticipation::where('campaign_id', $campaign->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $campaignTask = $campaign->sandboxTasks()->where('sandbox_task_id', $task->id)->firstOrFail();

        $session = SandboxSession::withoutTenant()
            ->where('user_id', $user->id)
            ->where('sandbox_task_id', $task->id)
            ->latest()
            ->first();

        return view('assessment::campaigns.task', compact(
            'campaign', 'task', 'campaignTask', 'participation', 'session', 'user'
        ));
    }

    /**
     * POST /campaigns/{campaign}/tasks/{task}/start
     */
    public function taskStart(Request $request, OpenAssessmentCampaign $campaign, SandboxTask $task): RedirectResponse
    {
        $user = $request->user();

        CampaignParticipation::where('campaign_id', $campaign->id)
            ->where('user_id', $user->id)
            ->where('status', ParticipationStatus::InProgress->value)
            ->firstOrFail();

        $campaign->sandboxTasks()->where('sandbox_task_id', $task->id)->firstOrFail();

        // Idempotent — don't create a second session
        $existing = SandboxSession::withoutTenant()
            ->where('user_id', $user->id)
            ->where('sandbox_task_id', $task->id)
            ->first();

        if ($existing) {
            return redirect()->route('campaigns.task', [$campaign->uuid, $task->id]);
        }

        SandboxSession::create([
            'uuid'                 => (string) Str::uuid(),
            'organization_id'      => $user->organization_id ?? $campaign->organization_id,
            'workforce_profile_id' => null,
            'user_id'              => $user->id,
            'sandbox_task_id'      => $task->id,
            'status'               => 'in_progress',
            'started_at'           => now(),
        ]);

        return redirect()->route('campaigns.task', [$campaign->uuid, $task->id])
            ->with('info', 'Task đã bắt đầu. Đọc hướng dẫn và nộp bài khi xong.');
    }

    /**
     * POST /campaigns/{campaign}/tasks/{task}/complete
     */
    public function taskComplete(Request $request, OpenAssessmentCampaign $campaign, SandboxTask $task): RedirectResponse
    {
        $user = $request->user();

        CampaignParticipation::where('campaign_id', $campaign->id)
            ->where('user_id', $user->id)
            ->where('status', ParticipationStatus::InProgress->value)
            ->firstOrFail();

        $session = SandboxSession::withoutTenant()
            ->where('user_id', $user->id)
            ->where('sandbox_task_id', $task->id)
            ->where('status', 'in_progress')
            ->firstOrFail();

        $request->validate([
            'submitted_content' => ['required', 'string', 'min:20', 'max:10000'],
            'ai_tools_used'     => ['nullable', 'string', 'max:500'],
        ]);

        $durationMinutes = $session->started_at
            ? (int) $session->started_at->diffInMinutes(now())
            : null;

        DB::transaction(function () use ($session, $request, $durationMinutes) {
            SandboxSubmission::create([
                'sandbox_session_id' => $session->id,
                'submitted_content'  => $request->input('submitted_content'),
                'ai_tools_used'      => $request->input('ai_tools_used'),
                'submitted_at'       => now(),
            ]);

            // Baseline auto-score — org reviewer adjusts in campaign admin
            $session->update([
                'status'             => 'completed',
                'completed_at'       => now(),
                'submitted_at'       => now(),
                'duration_minutes'   => $durationMinutes,
                'quality_score'      => 70.0,
                'productivity_score' => 70.0,
                'ai_adoption_score'  => 70.0,
                'final_score'        => 70.0,
                'passed'             => true,
            ]);
        });

        return redirect()->route('campaigns.workspace', $campaign->uuid)
            ->with('success', 'Đã nộp "' . $task->title . '". Tiếp tục làm các task còn lại hoặc nộp bài campaign.');
    }

    // ── Private helpers ──────────────────────────────────────────────

    private function scoreToMaturityLevel(float $score): string
    {
        return match (true) {
            $score >= 85 => 'DIGITAL_LEADER',
            $score >= 70 => 'DIGITAL_PROFESSIONAL',
            $score >= 50 => 'DIGITAL_PRACTITIONER',
            $score >= 30 => 'DIGITAL_AWARE',
            default      => 'DIGITAL_BEGINNER',
        };
    }
}
