<?php

namespace Modules\Deployment\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\Deployment\Services\ReadinessScoreService;
use Modules\Survey\Models\SurveyAnswer;
use Modules\Survey\Models\SurveyField;
use Modules\Survey\Models\SurveyFieldOption;
use Modules\Survey\Models\SurveyResponse;

class SubmitReadinessAction
{
    use AsAction;

    /**
     * @param  array<int|string, mixed>  $answers       [field_id => value] for scalar fields
     * @param  array<int|string, array>  $checkboxAnswers  [field_id => [option_value, ...]]
     */
    public function handle(
        DeploymentTarget $target,
        array $answers,
        array $checkboxAnswers = []
    ): array {
        if (! $target->readiness_response_id) {
            (new CloneSurveyAction)->handle($target);
            $target->refresh();
        }

        $response = SurveyResponse::findOrFail($target->readiness_response_id);
        $surveyId = $response->survey_id;

        // Load all fields for this survey keyed by id (to know types)
        $fields = SurveyField::where('survey_id', $surveyId)
            ->with('options')
            ->get()
            ->keyBy('id');

        return DB::transaction(function () use ($response, $target, $answers, $checkboxAnswers, $fields) {
            SurveyAnswer::where('response_id', $response->id)->delete();

            // Scalar answers (Rating, NPS, Number, Radio, Select)
            foreach ($answers as $fieldId => $value) {
                $field = $fields->get((int) $fieldId);
                if (! $field) {
                    continue;
                }

                $type = $field->field_type->value;

                if (in_array($type, [7, 12])) {
                    // Rating / NPS — store as value_number
                    $max    = $type === 12 ? 10 : ($field->rule_max ?? 5);
                    $parsed = max(0, min($max, (int) $value));
                    SurveyAnswer::create([
                        'response_id'  => $response->id,
                        'field_id'     => $field->id,
                        'value_number' => $parsed,
                    ]);
                } elseif ($type === 3) {
                    // Number — store as value_number
                    $min    = $field->rule_min ?? 0;
                    $max    = $field->rule_max ?? PHP_INT_MAX;
                    $parsed = max($min, min($max, (float) $value));
                    SurveyAnswer::create([
                        'response_id'  => $response->id,
                        'field_id'     => $field->id,
                        'value_number' => $parsed,
                    ]);
                } elseif (in_array($type, [5, 4])) {
                    // Radio / Select — store as value_string + option_id
                    if ($value === null || $value === '') {
                        continue;
                    }
                    $opt = $field->options->firstWhere('option_value', $value);
                    SurveyAnswer::create([
                        'response_id'  => $response->id,
                        'field_id'     => $field->id,
                        'option_id'    => $opt?->id,
                        'value_string' => (string) $value,
                    ]);
                } else {
                    // Text / other — store as value_string
                    if ($value === null || $value === '') {
                        continue;
                    }
                    SurveyAnswer::create([
                        'response_id'  => $response->id,
                        'field_id'     => $field->id,
                        'value_string' => (string) $value,
                    ]);
                }
            }

            // Checkbox answers (multiple rows per field)
            foreach ($checkboxAnswers as $fieldId => $selectedValues) {
                $field = $fields->get((int) $fieldId);
                if (! $field || $field->field_type->value !== 6) {
                    continue;
                }
                foreach ((array) $selectedValues as $optVal) {
                    $opt = $field->options->firstWhere('option_value', $optVal);
                    SurveyAnswer::create([
                        'response_id'  => $response->id,
                        'field_id'     => $field->id,
                        'option_id'    => $opt?->id,
                        'value_string' => (string) $optVal,
                    ]);
                }
            }

            $response->update([
                'status'       => 1,
                'submitted_at' => now(),
            ]);

            $result = (new ReadinessScoreService)->compute($response->fresh());
            $target->update(['readiness_score' => $result['score']]);

            return $result;
        });
    }
}
