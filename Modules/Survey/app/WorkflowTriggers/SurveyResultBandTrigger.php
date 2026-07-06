<?php

namespace Modules\Survey\WorkflowTriggers;

use Modules\WorkflowAutomation\Contracts\TriggerSource;
use Modules\WorkflowAutomation\Data\TriggerPayload;

class SurveyResultBandTrigger implements TriggerSource
{
    public function type(): string   { return 'survey.result_calculated'; }
    public function label(): string  { return 'Kết quả survey được tính điểm'; }
    public function module(): string { return 'Survey'; }

    public function availableFields(): array
    {
        return [
            ['key' => 'extra.survey_id',      'label' => 'Survey ID',       'type' => 'integer'],
            ['key' => 'extra.band_code',       'label' => 'Band Code',       'type' => 'string'],
            ['key' => 'extra.overall_score',   'label' => 'Overall Score',   'type' => 'float'],
            ['key' => 'extra.weight_version',  'label' => 'Weight Version',  'type' => 'integer'],
        ];
    }

    public function configFields(): array
    {
        return [
            ['key' => 'band_code', 'label' => 'Band Code cần match', 'type' => 'text',
             'hint' => 'Để trống = match tất cả band. Ví dụ: AI_READY'],
        ];
    }

    public function matches(TriggerPayload $payload, array $parsedConfig): bool
    {
        if ($payload->triggerType !== $this->type()) {
            return false;
        }

        $requiredBand = $parsedConfig['band_code'] ?? null;
        if ($requiredBand === null || $requiredBand === '') {
            return true;
        }

        return ($payload->extra['band_code'] ?? null) === $requiredBand;
    }
}
