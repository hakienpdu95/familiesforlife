<?php

namespace Modules\Assessment\Engine;

use Modules\Assessment\Models\ScoreRule;

class RuleEngine
{
    /**
     * Iterate tất cả score_rules, match với answers → tính raw_score per domain + emit signal_flags.
     *
     * @param array<string, array>              $answers     output của AnswerReader::read()
     * @return array{rawScores: array<string,int>, signalFlags: array<string,bool>}
     */
    public function calculate(ScoringConfig $config, array $answers): array
    {
        $rawScores   = [];
        $signalFlags = [];

        // Init rawScores per domain = 0
        foreach ($config->domains as $domain) {
            $rawScores[$domain->domain_code] = 0;
        }

        foreach ($config->scoreRules as $rule) {
            /** @var ScoreRule $rule */
            $answer = $answers[$rule->field_key] ?? null;

            match ($rule->condition_type) {
                'boolean'      => $this->applyBoolean($rule, $answer, $rawScores, $signalFlags),
                'single_choice',
                'multi_choice' => $this->applyChoice($rule, $answer, $rawScores, $signalFlags),
                default        => null,
            };
        }

        return ['rawScores' => $rawScores, 'signalFlags' => $signalFlags];
    }

    private function applyBoolean(ScoreRule $rule, ?array $answer, array &$rawScores, array &$signalFlags): void
    {
        if ($answer === null || $answer['type'] !== 'boolean') {
            return;
        }

        $value = $answer['value'];
        $score = $value ? $rule->score_if_true : $rule->score_if_false;
        $rawScores[$rule->domain_code] += $score;

        if ($rule->signal_flag !== null) {
            $signalFlags[$rule->signal_flag] = $value;
        }
    }

    private function applyChoice(ScoreRule $rule, ?array $answer, array &$rawScores, array &$signalFlags): void
    {
        if ($answer === null || $answer['type'] !== 'choice') {
            return;
        }

        $selectedValues = $answer['values'] ?? [];
        $optionsByValue = $rule->options->keyBy('option_value');

        // Đánh dấu flags đã được emit từ rule này để tránh override
        $flagsEmitted = [];

        foreach ($selectedValues as $selected) {
            $option = $optionsByValue->get($selected);
            if ($option === null) {
                continue;
            }

            $rawScores[$rule->domain_code] += $option->score;

            if ($option->signal_flag !== null && !isset($flagsEmitted[$option->signal_flag])) {
                // Flag được set TRUE khi option này được chọn
                $signalFlags[$option->signal_flag] = true;
                $flagsEmitted[$option->signal_flag] = true;
            }
        }

        // Flag false nếu option có flag nhưng không được chọn (chỉ khi chưa được set true)
        foreach ($rule->options as $option) {
            if ($option->signal_flag !== null && !isset($signalFlags[$option->signal_flag])) {
                $signalFlags[$option->signal_flag] = false;
            }
        }
    }
}
