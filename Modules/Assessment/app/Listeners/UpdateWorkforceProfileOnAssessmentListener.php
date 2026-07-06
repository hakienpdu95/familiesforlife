<?php

namespace Modules\Assessment\Listeners;

use Modules\Assessment\Events\AssessmentCompleted;
use Modules\Assessment\Models\ResultDomainScore;
use Modules\Assessment\Models\WorkforceProfile;
use Modules\Assessment\Models\WorkforceProfileHistory;
use Modules\Employee\Models\Employee;

class UpdateWorkforceProfileOnAssessmentListener
{
    public function handle(AssessmentCompleted $event): void
    {
        if ($event->result->assessment_code !== 'TDWCF') {
            return;
        }

        $result = $event->result;

        // Chỉ xử lý khi subject là Employee
        if ($result->subject_type !== Employee::class) {
            return;
        }

        $employee = Employee::withoutTenant()->find($result->subject_id);
        if (! $employee || ! $employee->organization_id) {
            return;
        }

        // Lấy điểm từng domain từ result_domain_scores
        $domainScores = ResultDomainScore::where('result_id', $result->id)
            ->pluck('normalized_score', 'domain_code');

        $d1 = $domainScores['D1_DIGITAL_LITERACY'] ?? null;
        $d2 = $domainScores['D2_DATA_LITERACY']    ?? null;
        $d3 = $domainScores['D3_AI_LITERACY']      ?? null;
        $d4 = $domainScores['D4_WORKFLOW']          ?? null;
        $d5 = $domainScores['D5_INNOVATION']        ?? null;
        $d6 = $domainScores['D6_PERFORMANCE']       ?? null;

        $aiReadiness = ($d3 !== null && $d4 !== null) ? round(($d3 + $d4) / 2, 2) : null;
        $digitalScore = ($d1 !== null && $d2 !== null && $d3 !== null)
            ? round(($d1 + $d2 + $d3) / 3, 2) : null;
        $productivityScore = ($d4 !== null && $d6 !== null)
            ? round(($d4 + $d6) / 2, 2) : null;

        /** @var WorkforceProfile $profile */
        $profile = WorkforceProfile::withoutTenant()
            ->firstOrCreate(
                ['organization_id' => $employee->organization_id, 'user_id' => $employee->user_id ?? 0],
                ['employee_id' => $employee->id, 'uuid' => (string) \Illuminate\Support\Str::uuid()]
            );

        $scoreBefore  = $profile->tdwcf_score;
        $levelBefore  = $profile->tdwcf_maturity_level;
        $growthScore  = ($scoreBefore !== null && $result->overall_score !== null)
            ? round($result->overall_score - $scoreBefore, 2) : null;

        $profile->update([
            'tdwcf_score'               => $result->overall_score,
            'tdwcf_maturity_level'      => $result->maturity_level,
            'tdwcf_assessed_at'         => $result->calculated_at ?? now(),
            'score_d1_digital_literacy' => $d1,
            'score_d2_data_literacy'    => $d2,
            'score_d3_ai_literacy'      => $d3,
            'score_d4_workflow'         => $d4,
            'score_d5_innovation'       => $d5,
            'score_d6_performance'      => $d6,
            'digital_score'             => $digitalScore,
            'ai_score'                  => $d3,
            'productivity_score'        => $productivityScore,
            'innovation_score'          => $d5,
            'growth_score'              => $growthScore,
            'ai_readiness_score'        => $aiReadiness,
        ]);

        // Ghi lịch sử
        WorkforceProfileHistory::create([
            'workforce_profile_id' => $profile->id,
            'event_type'           => 'assessment',
            'source_id'            => $result->id,
            'source_type'          => $result::class,
            'tdwcf_score_before'   => $scoreBefore,
            'tdwcf_score_after'    => $result->overall_score,
            'maturity_level_before' => $levelBefore,
            'maturity_level_after'  => $result->maturity_level,
            'change_delta'         => $growthScore,
            'recorded_at'          => now(),
        ]);

        // Cập nhật workforce_trust_score sau khi profile đã được update
        $profile->refresh();
        $trustScore = $profile->recalculateTrustScore();
        $profile->update(['workforce_trust_score' => $trustScore]);
    }
}
