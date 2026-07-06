<?php

namespace Modules\Survey\WorkflowTriggers;

use Modules\WorkflowAutomation\Contracts\TriggerSource;
use Modules\WorkflowAutomation\Data\TriggerPayload;

class SurveySubmittedTrigger implements TriggerSource
{
    public function type(): string   { return 'survey.submitted'; }
    public function label(): string  { return 'Survey được submit'; }
    public function module(): string { return 'Survey'; }

    public function availableFields(): array
    {
        return [
            ['key' => 'extra.survey_id',      'label' => 'Survey ID',       'type' => 'integer'],
            ['key' => 'extra.survey_slug',     'label' => 'Survey Slug',     'type' => 'string'],
            ['key' => 'extra.respondent_ref',  'label' => 'Respondent Ref',  'type' => 'string'],
        ];
    }

    public function configFields(): array { return []; }

    public function matches(TriggerPayload $payload, array $parsedConfig): bool
    {
        return $payload->triggerType === $this->type();
    }
}
