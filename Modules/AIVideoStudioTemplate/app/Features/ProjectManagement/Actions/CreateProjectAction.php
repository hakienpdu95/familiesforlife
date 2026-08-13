<?php

namespace Modules\AIVideoStudioTemplate\Features\ProjectManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\AIVideoStudioTemplate\Features\ProjectManagement\Data\ProjectInputData;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioProject;

class CreateProjectAction
{
    use AsAction;

    public function handle(ProjectInputData $data, int $userId): AiVideoStudioProject
    {
        return AiVideoStudioProject::create([
            'name' => $data->name,
            'description' => $data->description,
            'objective' => $data->objective,
            'target_audience' => $data->targetAudience,
            'video_type' => $data->videoType,
            'video_formula' => $data->videoFormula,
            'core_message' => $data->coreMessage,
            'aspect_ratio' => $data->aspectRatio,
            'resolution' => $data->resolution,
            'default_subject' => $data->defaultSubject,
            'reference_image_url' => $data->referenceImageUrl,
            'reference_context_prompt' => $data->referenceContextPrompt,
            'default_style' => $data->defaultStyle,
            'default_constraints' => $data->defaultConstraints,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }
}
