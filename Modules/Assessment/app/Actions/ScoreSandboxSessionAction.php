<?php

namespace Modules\Assessment\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Assessment\Events\SandboxCompleted;
use Modules\Assessment\Models\SandboxSession;

/**
 * Chấm điểm và hoàn thành một sandbox session.
 *
 * Công thức (spec §6.6):
 *   Final Score = Quality×40% + Productivity×35% + AI Adoption×25%
 *
 *   Productivity Score = MIN(100, time_limit / duration × 100)
 *                        0 nếu duration > time_limit × 1.5
 *
 *   AI Adoption Score = (activities_with_ai_tool / total_activities) × 100
 *                       × quality_coefficient (avg quality_note / 10 nếu có)
 *
 * @param SandboxSession $session  Session đã submitted, có thể đã có quality_score từ evaluator
 * @param float|null     $qualityScore  Override quality score nếu evaluator cung cấp; null = giữ giá trị hiện tại
 */
class ScoreSandboxSessionAction
{
    use AsAction;

    public function handle(SandboxSession $session, ?float $qualityScore = null): SandboxSession
    {
        $task = $session->task;

        // 1. Productivity Score
        $productivityScore = $this->calcProductivity($session, $task?->time_limit_minutes);

        // 2. AI Adoption Score
        $aiAdoptionScore = $this->calcAiAdoption($session);

        // 3. Quality Score
        $quality = $qualityScore ?? $session->quality_score ?? 0.0;

        // 4. Final Score
        $finalScore = round($quality * 0.40 + $productivityScore * 0.35 + $aiAdoptionScore * 0.25, 2);
        $passed     = $finalScore >= 60;

        $session->update([
            'quality_score'     => $quality,
            'productivity_score'=> $productivityScore,
            'ai_adoption_score' => $aiAdoptionScore,
            'final_score'       => $finalScore,
            'passed'            => $passed,
            'status'            => 'completed',
            'completed_at'      => now(),
            'evaluated_at'      => now(),
        ]);

        event(new SandboxCompleted($session));

        return $session->fresh();
    }

    private function calcProductivity(SandboxSession $session, ?int $timeLimitMinutes): float
    {
        if (! $timeLimitMinutes || ! $session->duration_minutes) {
            return 50.0; // default khi không có time limit
        }

        // Quá chậm: duration > limit × 1.5 → 0 điểm
        if ($session->duration_minutes > $timeLimitMinutes * 1.5) {
            return 0.0;
        }

        return min(100.0, round(($timeLimitMinutes / $session->duration_minutes) * 100, 2));
    }

    private function calcAiAdoption(SandboxSession $session): float
    {
        $activities = $session->activities;
        $total      = $activities->count();

        if ($total === 0) {
            return 0.0;
        }

        $withAi = $activities->whereNotNull('ai_tool_used')->count();
        $ratio  = $withAi / $total;

        // Hệ số chất lượng AI usage từ quality_note (0–10 → 0–1)
        $notedActivities = $activities->whereNotNull('quality_note');
        $qualityCoef = $notedActivities->count() > 0
            ? $notedActivities->avg('quality_note') / 10.0
            : 1.0; // default: không penalty

        return min(100.0, round($ratio * $qualityCoef * 100, 2));
    }
}
