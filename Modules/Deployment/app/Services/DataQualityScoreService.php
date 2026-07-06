<?php

namespace Modules\Deployment\Services;

use Modules\Deployment\Enums\IssueSeverity;
use Modules\Deployment\Models\DeploymentTarget;

class DataQualityScoreService
{
    private const DEDUCTIONS = [
        'critical' => 20,
        'high'     => 10,
        'medium'   => 5,
        'low'      => 2,
    ];

    public function score(DeploymentTarget $target): array
    {
        $issues = $target->issues()
            ->whereIn('status', ['open', 'in_progress'])
            ->get(['severity']);

        $deductions = 0;
        $breakdown  = [];

        foreach ($issues as $issue) {
            $sev = $issue->severity instanceof IssueSeverity
                ? $issue->severity->value
                : (string) $issue->severity;

            $pts = self::DEDUCTIONS[$sev] ?? 0;
            $deductions += $pts;
            $breakdown[$sev] = ($breakdown[$sev] ?? 0) + 1;
        }

        $score = max(0, 100 - $deductions);

        return [
            'score'      => $score,
            'deductions' => $deductions,
            'breakdown'  => $breakdown,
            'band'       => $this->band($score),
            'color'      => $this->color($score),
        ];
    }

    private function band(int $score): string
    {
        return match (true) {
            $score >= 90 => 'Xuất sắc',
            $score >= 75 => 'Tốt',
            $score >= 60 => 'Trung bình',
            $score >= 40 => 'Cần cải thiện',
            default      => 'Nghiêm trọng',
        };
    }

    private function color(int $score): string
    {
        return match (true) {
            $score >= 90 => 'success',
            $score >= 75 => 'info',
            $score >= 60 => 'warning',
            default      => 'error',
        };
    }
}
