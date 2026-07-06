<?php

namespace Modules\Assessment\Engine;

use Illuminate\Support\Facades\Log;
use Modules\Assessment\Models\ScoreRule;

/**
 * Tầng 1 — Question Scoring.
 * Mỗi câu trả lời → một feature value Fi đã chuẩn hóa.
 *
 * Hỗ trợ: none | boolean | single_choice | multi_choice | numeric_range
 * Đầu vào aggregation:
 *   - weighted_domain: rawScores keyed by domain_code
 *   - flat_sum / sectioned: rawScores keyed by domain_code or section_code
 */
class FeatureExtractor
{
    /**
     * @param  array<string, array>  $answers  output của AnswerReader::read()
     * @return array{
     *   rawScores: array<string,int>,
     *   signalFlags: array<string,bool>,
     *   questionScores: array<string, array{question_code:string,feature_code:string,raw:int,final:int,selected:string}>
     * }
     */
    public function extract(ScoringConfig $config, array $answers): array
    {
        [$cleanedAnswers, $missingCount, $totalRuled] = $this->cleanAnswers($answers, $config);

        $rawScores      = [];
        $signalFlags    = [];
        $questionScores = [];

        // Init rawScores per domain = 0
        foreach ($config->domains as $domain) {
            $rawScores[$domain->domain_code] = 0;
        }
        // Init rawScores per section = 0 (sectioned mode)
        foreach ($config->sections as $section) {
            $key = $section->section_code ?? (string) $section->id;
            $rawScores[$key] = 0;
        }

        foreach ($config->scoreRules as $rule) {
            /** @var ScoreRule $rule */
            $fieldKey = $rule->field_key;
            $answer   = $cleanedAnswers[$fieldKey] ?? null;
            $type     = $rule->getScoringType();

            if ($type === 'none') {
                continue;
            }

            [$rawScore, $finalScore, $flags, $selectedOptions] = match ($type) {
                'boolean'      => $this->scoreBoolean($rule, $answer),
                'single_choice',
                'multi_choice' => $this->scoreChoice($rule, $answer),
                'numeric_range' => $this->scoreNumericRange($rule, $answer),
                default        => $this->scoreBoolean($rule, $answer),
            };

            // Accumulate into domain or section bucket
            $bucket = $this->resolveBucket($rule, $config);
            if ($bucket !== null) {
                $rawScores[$bucket] = ($rawScores[$bucket] ?? 0) + $finalScore;
            }

            // Signal flags
            foreach ($flags as $flag => $value) {
                if (!isset($signalFlags[$flag])) {
                    $signalFlags[$flag] = $value;
                } elseif ($value === true) {
                    $signalFlags[$flag] = true;
                }
            }

            // Per-question record
            $questionScores[$fieldKey] = [
                'question_code' => $fieldKey,
                'feature_code'  => $rule->getFeatureCode(),
                'raw'           => $rawScore,
                'final'         => $finalScore,
                'selected'      => $selectedOptions,
            ];
        }

        $this->clampDomainScores($rawScores, $config);

        if ($totalRuled > 0 && ($missingCount / $totalRuled) > 0.5) {
            $signalFlags['high_missing_rate'] = true;
        }

        return [
            'rawScores'      => $rawScores,
            'signalFlags'    => $signalFlags,
            'questionScores' => $questionScores,
        ];
    }

    // ── Tầng 1: Boolean ──────────────────────────────────────────────────────

    private function scoreBoolean(ScoreRule $rule, ?array $answer): array
    {
        if ($answer === null || $answer['type'] !== 'boolean') {
            return [0, 0, [], ''];
        }

        $value = (bool) $answer['value'];
        $score = $value ? $rule->score_if_true : $rule->score_if_false;
        $flags = [];

        if ($rule->signal_flag !== null) {
            $flags[$rule->signal_flag] = $value;
        }

        return [$score, $score, $flags, $value ? 'true' : 'false'];
    }

    // ── Tầng 1: Choice (single/multi) ────────────────────────────────────────

    private function scoreChoice(ScoreRule $rule, ?array $answer): array
    {
        if ($answer === null || $answer['type'] !== 'choice') {
            return [0, 0, [], ''];
        }

        $selectedValues = $answer['values'] ?? [];
        $optionsByValue = $rule->options->keyBy('option_value');
        $rawScore       = 0;
        $flags          = [];
        $flagsEmitted   = [];

        foreach ($selectedValues as $selected) {
            $option = $optionsByValue->get($selected);
            if ($option === null) {
                continue;
            }

            $rawScore += $option->score;

            if ($option->signal_flag !== null && !isset($flagsEmitted[$option->signal_flag])) {
                $flags[$option->signal_flag] = true;
                $flagsEmitted[$option->signal_flag] = true;
            }
        }

        // Flags for options NOT selected
        foreach ($rule->options as $option) {
            if ($option->signal_flag !== null && !isset($flags[$option->signal_flag])) {
                $flags[$option->signal_flag] = false;
            }
        }

        // CLAMP (multi_choice)
        $finalScore = $rawScore;
        if ($rule->min_score_cap !== null) {
            $finalScore = max($rule->min_score_cap, $finalScore);
        }
        if ($rule->max_score_cap !== null) {
            $finalScore = min($rule->max_score_cap, $finalScore);
        }

        return [$rawScore, $finalScore, $flags, implode(',', $selectedValues)];
    }

    // ── Tầng 1: Numeric Range ────────────────────────────────────────────────

    private function scoreNumericRange(ScoreRule $rule, ?array $answer): array
    {
        if ($answer === null || !in_array($answer['type'], ['number', 'numeric'], true)) {
            return [0, 0, [], ''];
        }

        $value  = (float) $answer['value'];
        $ranges = $rule->numericRanges->sortBy('sort_order');

        foreach ($ranges as $range) {
            if ($range->matches($value)) {
                $flags = [];
                if ($range->signal_flag !== null) {
                    $flags[$range->signal_flag] = true;
                }
                return [$range->score, $range->score, $flags, (string) $value];
            }
        }

        return [0, 0, [], (string) $value];
    }

    /**
     * Remove noise answers (no matching rule) and count missing fields.
     * Returns [$filteredAnswers, $missingCount, $totalRuledFields].
     */
    private function cleanAnswers(array $answers, ScoringConfig $config): array
    {
        $ruleKeys = $config->scoreRules
            ->filter(fn (ScoreRule $r) => $r->getScoringType() !== 'none')
            ->pluck('field_key')
            ->flip()
            ->all();

        $cleanedAnswers = array_intersect_key($answers, $ruleKeys);

        $missingCount = 0;
        foreach (array_keys($ruleKeys) as $key) {
            if (!isset($answers[$key])) {
                $missingCount++;
            }
        }

        return [$cleanedAnswers, $missingCount, count($ruleKeys)];
    }

    /**
     * Clamp any domain raw score that exceeds max_score * 1.2 (config error guard).
     */
    private function clampDomainScores(array &$rawScores, ScoringConfig $config): void
    {
        foreach ($config->domains as $domain) {
            $code     = $domain->domain_code;
            $maxScore = $domain->max_score;

            if ($maxScore === null || !isset($rawScores[$code])) {
                continue;
            }

            $cap = $maxScore * 1.2;
            if ($rawScores[$code] > $cap) {
                Log::warning('FeatureExtractor: domain score clamped', [
                    'domain'    => $code,
                    'raw_score' => $rawScores[$code],
                    'clamped_to' => $maxScore,
                ]);
                $rawScores[$code] = $maxScore;
            }
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolveBucket(ScoreRule $rule, ScoringConfig $config): ?string
    {
        // Ưu tiên: section_id (sectioned mode)
        if ($rule->section_id !== null && $config->sections->isNotEmpty()) {
            $section = $config->sections->firstWhere('id', $rule->section_id);
            if ($section !== null) {
                return $section->section_code ?? (string) $section->id;
            }
        }

        // Default: domain_code
        return $rule->domain_code ?: null;
    }
}
