<?php

namespace Modules\Deployment\Services;

use Modules\Survey\Models\SurveyAnswer;
use Modules\Survey\Models\SurveyResponse;

class ReadinessScoreService
{
    private const BANDS = [
        80 => ['label' => 'Sẵn sàng',            'color' => 'success'],
        60 => ['label' => 'Gần sẵn sàng',         'color' => 'info'],
        40 => ['label' => 'Sẵn sàng có hỗ trợ',  'color' => 'warning'],
        0  => ['label' => 'Chưa sẵn sàng',        'color' => 'error'],
    ];

    // Positivity map for Radio/Select option values
    private const OPTION_SCORES = [
        'co'           => 100, 'khong'        => 0,
        'yeu'          => 33,  'mot_phan'     => 50,
        'can_dao_tao'  => 50,  'day_du'       => 100,
        'nhieu'        => 100, 'it'           => 33,
        'ro'           => 100, 'ngay'         => 100,
        '1thang'       => 75,  '3thang'       => 50,
        'chua_biet'    => 25,
    ];

    /** @return array{score: int, band: string, color: string, domains: array, answered: int} */
    public function compute(SurveyResponse $response): array
    {
        // Load ALL answers (numeric + string) with field type, section, options
        $answers = SurveyAnswer::where('response_id', $response->id)
            ->with(['field.section', 'field.options'])
            ->get()
            ->filter(fn($a) => $a->field?->section !== null);

        if ($answers->isEmpty()) {
            return $this->emptyResult();
        }

        // Checkbox fields have multiple rows (one per selected option) — collect them first
        $checkboxMap = $answers
            ->filter(fn($a) => $a->field?->field_type?->value === 6)
            ->groupBy('field_id')
            ->map(fn($g) => $g->pluck('value_string')->filter()->values()->all());

        // Group by section_code; deduplicate field_id (first row = representative answer)
        $byDomain = $answers
            ->unique('field_id')
            ->groupBy(fn($a) => $a->field->section->section_code ?? 'unknown');

        $domainScores = [];
        foreach ($byDomain as $domainCode => $domainAnswers) {
            if ($domainCode === 'unknown') {
                continue;
            }

            $fieldScores = [];
            foreach ($domainAnswers as $answer) {
                $s = $this->scoreAnswer($answer, $checkboxMap[$answer->field_id] ?? null);
                if ($s !== null) {
                    $fieldScores[] = $s;
                }
            }

            if (! empty($fieldScores)) {
                $avg = (int) round(array_sum($fieldScores) / count($fieldScores));
                $domainScores[$domainCode] = [
                    'raw'     => (int) array_sum($fieldScores),
                    'max_raw' => count($fieldScores) * 100,
                    'score'   => $avg,
                    'count'   => count($fieldScores),
                ];
            }
        }

        $totalScore = count($domainScores) > 0
            ? (int) round(array_sum(array_column($domainScores, 'score')) / count($domainScores))
            : 0;

        return [
            'score'    => $totalScore,
            'band'     => $this->band($totalScore),
            'color'    => $this->color($totalScore),
            'domains'  => $domainScores,
            'answered' => $answers->unique('field_id')->count(),
        ];
    }

    private function scoreAnswer(SurveyAnswer $answer, ?array $checkboxValues): ?int
    {
        $type = $answer->field?->field_type?->value;

        return match ($type) {
            // Rating: normalize to 100 using rule_max (default 5)
            7 => $answer->value_number !== null
                    ? (int) round(($answer->value_number / ($answer->field->rule_max ?? 5)) * 100)
                    : null,

            // NPS: 0–10 scale
            12 => $answer->value_number !== null
                    ? (int) round(($answer->value_number / 10) * 100)
                    : null,

            // Number: normalize by rule_max; skip if no max defined
            3 => $this->scoreNumber($answer),

            // Radio / Select: map option value to positivity score
            5, 4 => array_key_exists($answer->value_string ?? '', self::OPTION_SCORES)
                    ? self::OPTION_SCORES[$answer->value_string]
                    : null,

            // Checkbox: fraction of options selected
            6 => $checkboxValues !== null
                    ? $this->scoreCheckbox($answer->field, $checkboxValues)
                    : null,

            default => null,
        };
    }

    private function scoreNumber(SurveyAnswer $answer): ?int
    {
        if ($answer->value_number === null) {
            return null;
        }
        $max = $answer->field?->rule_max;
        if ($max === null || $max <= 0) {
            return null;
        }
        return (int) min(100, round(($answer->value_number / $max) * 100));
    }

    private function scoreCheckbox($field, array $selectedValues): int
    {
        $totalOptions = $field->options->count();
        if ($totalOptions === 0) {
            return 0;
        }
        return (int) min(100, round((count($selectedValues) / $totalOptions) * 100));
    }

    public function band(int $score): string
    {
        foreach (self::BANDS as $threshold => $info) {
            if ($score >= $threshold) {
                return $info['label'];
            }
        }
        return 'Chưa sẵn sàng';
    }

    public function color(int $score): string
    {
        foreach (self::BANDS as $threshold => $info) {
            if ($score >= $threshold) {
                return $info['color'];
            }
        }
        return 'error';
    }

    private function emptyResult(): array
    {
        return ['score' => 0, 'band' => 'Chưa đánh giá', 'color' => 'ghost', 'domains' => [], 'answered' => 0];
    }
}
