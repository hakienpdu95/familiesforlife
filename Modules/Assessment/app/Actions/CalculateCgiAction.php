<?php

namespace Modules\Assessment\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Assessment\Models\WorkforceProfile;
use Modules\Assessment\Models\WorkforceProfileHistory;

/**
 * Competency Growth Index (CGI)
 *
 * Formula:
 *   CGI = (tdwcf_score_current - tdwcf_score_initial) / tdwcf_score_initial × 100
 *
 * Lấy điểm ban đầu từ bản ghi lịch sử assessment đầu tiên của profile.
 * CGI từng lần cũng được lưu trong workforce_profile_histories.change_delta.
 */
class CalculateCgiAction
{
    use AsAction;

    public function handle(WorkforceProfile $profile): ?float
    {
        if ($profile->tdwcf_score === null) {
            return null;
        }

        $firstHistory = WorkforceProfileHistory::where('workforce_profile_id', $profile->id)
            ->where('event_type', 'assessment')
            ->whereNotNull('tdwcf_score_before')
            ->orderBy('recorded_at')
            ->first();

        $initialScore = $firstHistory?->tdwcf_score_before;

        if ($initialScore === null || $initialScore == 0) {
            return null;
        }

        return round(($profile->tdwcf_score - $initialScore) / $initialScore * 100, 2);
    }
}
