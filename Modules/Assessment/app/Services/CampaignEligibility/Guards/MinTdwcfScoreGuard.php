<?php

namespace Modules\Assessment\Services\CampaignEligibility\Guards;

use App\Models\User;
use Modules\Assessment\Models\OpenAssessmentCampaign;
use Modules\Assessment\Models\PassportEntry;
use Modules\Assessment\Models\WorkforceProfile;
use Modules\Assessment\Services\CampaignEligibility\BlockResult;
use Modules\Assessment\Services\CampaignEligibility\Contracts\CampaignGuardContract;

class MinTdwcfScoreGuard implements CampaignGuardContract
{
    public function check(User $user, OpenAssessmentCampaign $campaign): ?BlockResult
    {
        if ($campaign->min_tdwcf_score === null) {
            return null;
        }

        $score = $this->resolveScore($user);

        // No score on record → benefit of the doubt (let them join and discover their level)
        if ($score === null) {
            return null;
        }

        if ($score < $campaign->min_tdwcf_score) {
            return new BlockResult(
                "Cần TDWCF Score ≥ {$campaign->min_tdwcf_score} để tham gia (điểm hiện tại: {$score}).",
                route('passport.index'),
                'Xem Passport',
            );
        }

        return null;
    }

    private function resolveScore(User $user): ?float
    {
        // Prefer live score from current org profile
        if ($user->current_org_id) {
            $score = WorkforceProfile::withoutTenant()
                ->where('user_id', $user->id)
                ->where('organization_id', $user->current_org_id)
                ->value('tdwcf_score');

            if ($score !== null) {
                return (float) $score;
            }
        }

        // Fallback: latest passport snapshot
        $score = PassportEntry::where('user_id', $user->id)
            ->orderByDesc('snapshot_at')
            ->value('tdwcf_score');

        return $score !== null ? (float) $score : null;
    }
}
