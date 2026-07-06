<?php

namespace Modules\Assessment\Listeners;

use Modules\Assessment\Actions\CheckCertificationEligibilityAction;
use Modules\Assessment\Events\SandboxCompleted;
use Modules\Assessment\Models\WorkforceProfileHistory;
use Modules\Assessment\Services\CareerLevelService;

class UpdateWorkforceProfileOnSandboxListener
{
    public function __construct(
        private readonly CheckCertificationEligibilityAction $checkCert,
    ) {}

    public function handle(SandboxCompleted $event): void
    {
        $session = $event->session;
        $profile = $session->profile;

        if (! $profile) {
            return;
        }

        // Tính thống kê sandbox từ tất cả completed sessions của profile
        $completedSessions = $profile->fresh()
            ->hasMany(\Modules\Assessment\Models\SandboxSession::class, 'workforce_profile_id')
            ->where('status', 'completed')
            ->whereNotNull('final_score')
            ->get(['final_score', 'duration_minutes', 'completed_at']);

        $sessionsTotal = $completedSessions->count();
        $hoursTotal    = (int) ceil(($completedSessions->sum('duration_minutes') ?? 0) / 60);
        $scoreAvg      = $sessionsTotal > 0
            ? round($completedSessions->avg('final_score'), 2)
            : null;
        $lastCompletedAt = $completedSessions->max('completed_at');

        $profile->update([
            'sandbox_sessions_total'   => $sessionsTotal,
            'sandbox_hours_total'      => $hoursTotal,
            'sandbox_score_avg'        => $scoreAvg,
            'sandbox_last_completed_at'=> $lastCompletedAt,
        ]);

        WorkforceProfileHistory::create([
            'workforce_profile_id' => $profile->id,
            'event_type'           => 'sandbox',
            'source_id'            => $session->id,
            'source_type'          => $session::class,
            'notes'                => "Sandbox completed. Score: {$session->final_score}. Passed: " . ($session->passed ? 'yes' : 'no'),
            'recorded_at'          => now(),
        ]);

        // Cập nhật trust score
        $profile->refresh();
        $profile->update(['workforce_trust_score' => $profile->recalculateTrustScore()]);

        // Kiểm tra điều kiện cấp chứng nhận
        $this->checkCert->handle($profile->fresh());

        // Sau khi cert có thể đã được cấp, kiểm tra thăng cấp độ nghề nghiệp
        app(CareerLevelService::class)->checkAndAdvance($profile->fresh());
    }
}
