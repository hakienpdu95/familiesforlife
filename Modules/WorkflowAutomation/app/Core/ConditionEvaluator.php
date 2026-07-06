<?php

namespace Modules\WorkflowAutomation\Core;

use Modules\WorkflowAutomation\Data\TriggerPayload;
use Modules\WorkflowAutomation\Enums\ConditionMatch;
use Modules\WorkflowAutomation\Models\Workflow;
use Modules\WorkflowAutomation\Models\WorkflowCondition;

final class ConditionEvaluator
{
    public function evaluate(Workflow $workflow, TriggerPayload $payload): bool
    {
        $match      = ConditionMatch::from($workflow->condition_match);
        $conditions = $workflow->conditions;

        if ($match === ConditionMatch::None || $conditions->isEmpty()) return true;

        $results = $conditions->map(fn($c) => $this->check($c, $payload));

        return match ($match) {
            ConditionMatch::All => $results->every(fn($r) => $r),
            ConditionMatch::Any => $results->contains(fn($r) => $r),
            default             => true,
        };
    }

    private function check(WorkflowCondition $cond, TriggerPayload $payload): bool
    {
        $actual = $payload->resolve($cond->field);

        $expected = match ($cond->value_type) {
            2       => (int) $cond->value,
            3       => (float) $cond->value,
            4       => in_array(strtolower((string) $cond->value), ['true', '1', 'yes']),
            default => $cond->value,
        };

        return match ($cond->operator) {
            '='            => $actual == $expected,
            '!='           => $actual != $expected,
            '>'            => is_numeric($actual) && $actual > $expected,
            '>='           => is_numeric($actual) && $actual >= $expected,
            '<'            => is_numeric($actual) && $actual < $expected,
            '<='           => is_numeric($actual) && $actual <= $expected,
            'in'           => in_array($actual, explode('|', (string) $cond->value)),
            'not_in'       => !in_array($actual, explode('|', (string) $cond->value)),
            'contains'     => str_contains((string) $actual, (string) $expected),
            'starts_with'  => str_starts_with((string) $actual, (string) $expected),
            'is_empty'     => empty($actual),
            'is_not_empty' => !empty($actual),
            default        => false,
        };
    }
}
