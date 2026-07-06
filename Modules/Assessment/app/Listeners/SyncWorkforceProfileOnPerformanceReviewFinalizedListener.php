<?php

namespace Modules\Assessment\Listeners;

use Modules\Assessment\Models\WorkforceProfile;
use Modules\Assessment\Models\WorkforceProfileHistory;
use Modules\Employee\Models\Employee;
use Modules\PerformanceReview\Events\PerformanceReviewFinalized;
use Modules\PerformanceReview\Models\ReviewCriteria;

class SyncWorkforceProfileOnPerformanceReviewFinalizedListener
{
    public function handle(PerformanceReviewFinalized $event): void
    {
        $review = $event->review;

        // Need employee's user_id to look up workforce profile
        $employee = $review->employee;
        if (! $employee || ! $employee->user_id) {
            return;
        }

        $profile = WorkforceProfile::withoutTenant()
            ->where('organization_id', $review->organization_id)
            ->where('user_id', $employee->user_id)
            ->first();

        if (! $profile) {
            return;
        }

        // Load criteria with tdwcf_domain_code for this review's template
        $criteriaMap = ReviewCriteria::where('template_id', $review->template_id)
            ->whereNotNull('tdwcf_domain_code')
            ->pluck('tdwcf_domain_code', 'criteria_key');

        if ($criteriaMap->isEmpty()) {
            return;
        }

        // Group scores by TDWCF domain (weighted average: score / max_score * 100)
        $domainScores = [];
        $domainCounts = [];

        foreach ($review->scores as $score) {
            $domain = $criteriaMap->get($score->criteria_key);
            if (! $domain || ! $score->max_score) {
                continue;
            }
            $normalizedScore = ($score->score / $score->max_score) * 100;
            $domainScores[$domain] = ($domainScores[$domain] ?? 0) + $normalizedScore;
            $domainCounts[$domain] = ($domainCounts[$domain] ?? 0) + 1;
        }

        if (empty($domainScores)) {
            return;
        }

        // Average per domain
        $updates = [];
        $domainFieldMap = [
            'D1_DIGITAL_LITERACY' => 'score_d1_digital_literacy',
            'D2_DATA_LITERACY'    => 'score_d2_data_literacy',
            'D3_AI_LITERACY'      => 'score_d3_ai_literacy',
            'D4_WORKFLOW'         => 'score_d4_workflow',
            'D5_INNOVATION'       => 'score_d5_innovation',
            'D6_PERFORMANCE'      => 'score_d6_performance',
        ];

        foreach ($domainScores as $domain => $total) {
            $field = $domainFieldMap[$domain] ?? null;
            if ($field) {
                $updates[$field] = round($total / $domainCounts[$domain], 2);
            }
        }

        if (empty($updates)) {
            return;
        }

        $profile->update($updates);

        WorkforceProfileHistory::create([
            'workforce_profile_id' => $profile->id,
            'event_type'           => 'performance_review',
            'source_id'            => $review->id,
            'source_type'          => $review::class,
            'notes'                => "Performance review finalized. TDWCF domains synced: " . implode(', ', array_keys($domainScores)),
            'recorded_at'          => now(),
        ]);

        $profile->refresh();
        $profile->update(['workforce_trust_score' => $profile->recalculateTrustScore()]);
    }
}
