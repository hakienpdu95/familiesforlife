<?php

namespace Modules\Assessment\Services;

use Modules\Assessment\Events\SandboxCompleted;
use Modules\Assessment\Models\SandboxSession;
use Modules\Assessment\Models\SandboxSubmission;

/**
 * Auto-scores a sandbox session immediately after submission.
 *
 * Three dimensions:
 *   quality_score     (40%) — submission content depth
 *   productivity_score(35%) — time efficiency vs task limit
 *   ai_adoption_score (25%) — breadth of AI tool usage
 *
 * Manual override by evaluators (assessment.results) is always available.
 */
class SandboxScoringService
{
    private const PASS_THRESHOLD = 60.0;

    public function autoScore(SandboxSession $session, SandboxSubmission $submission): void
    {
        $quality      = $this->scoreQuality($submission);
        $productivity = $this->scoreProductivity($session);
        $aiAdoption   = $this->scoreAiAdoption($submission);

        $final = round($quality * 0.40 + $productivity * 0.35 + $aiAdoption * 0.25, 2);

        $session->update([
            'quality_score'      => $quality,
            'productivity_score' => $productivity,
            'ai_adoption_score'  => $aiAdoption,
            'final_score'        => $final,
            'passed'             => $final >= self::PASS_THRESHOLD,
            'status'             => 'completed',
            'completed_at'       => now(),
            'evaluated_at'       => now(),
            'evaluator_user_id'  => null, // null = auto-scored
            'feedback'           => $this->generateFeedback($quality, $productivity, $aiAdoption, $final),
        ]);

        // Fire event — listener chain handles: stats sync → cert check → career level advance
        event(new SandboxCompleted($session->fresh()));
    }

    // ── Quality: depth of submitted content ──────────────────────────────────

    private function scoreQuality(SandboxSubmission $submission): float
    {
        $content = trim($submission->submitted_content ?? '');
        $len = mb_strlen($content);

        if ($len === 0)  return 20.0;
        if ($len < 80)   return 40.0;
        if ($len < 200)  return 60.0;
        if ($len < 500)  return 72.0;
        if ($len < 1000) return 82.0;
        return 88.0;
    }

    // ── Productivity: time used vs task time limit ────────────────────────────

    private function scoreProductivity(SandboxSession $session): float
    {
        $limit   = $session->task?->time_limit_minutes;
        $elapsed = $session->duration_minutes;

        // No time limit set for this task → use a generous baseline
        if (! $limit || $limit <= 0) {
            return $elapsed && $elapsed <= 30 ? 90.0 : 78.0;
        }

        // No duration tracked (edge case) → neutral
        if (! $elapsed || $elapsed <= 0) {
            return 72.0;
        }

        $ratio = $elapsed / $limit;

        if ($ratio <= 0.50) return 100.0;
        if ($ratio <= 0.70) return 95.0;
        if ($ratio <= 0.90) return 85.0;
        if ($ratio <= 1.10) return 75.0;
        if ($ratio <= 1.50) return 58.0;
        return 40.0;
    }

    // ── AI Adoption: tools declared in submission ─────────────────────────────

    private function scoreAiAdoption(SandboxSubmission $submission): float
    {
        $tools = $submission->usedAiTools();
        $count = count($tools);

        if ($count === 0) return 20.0;
        if ($count === 1) return 65.0;
        if ($count === 2) return 80.0;
        return 90.0;
    }

    // ── Auto-generated feedback summary ───────────────────────────────────────

    private function generateFeedback(float $q, float $p, float $ai, float $final): string
    {
        $parts = [];

        if ($q < 60)  $parts[] = 'Cần bổ sung nội dung bài làm chi tiết hơn.';
        if ($p < 60)  $parts[] = 'Lần sau hãy chú ý quản lý thời gian tốt hơn.';
        if ($ai < 60) $parts[] = 'Hãy thử dùng thêm công cụ AI để tăng năng suất.';

        if ($final >= 85) return 'Xuất sắc! Bạn đã hoàn thành tốt nhiệm vụ. ' . implode(' ', $parts);
        if ($final >= 70) return 'Tốt! Kết quả đáng ghi nhận. ' . implode(' ', $parts);
        if ($final >= 60) return 'Đạt yêu cầu. ' . implode(' ', $parts);
        return 'Chưa đạt. ' . implode(' ', $parts) . ' Hãy thử lại để cải thiện điểm.';
    }
}
