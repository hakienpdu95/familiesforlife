<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\PromptFrameworkStudio\Models\GeneratedPrompt;

/** spec/PromptFrameworkStudio_Technical_Specification.md §5.2 — luồng tạo mới. */
class CreateGeneratedPromptAction
{
    use AsAction;

    public function __construct(private readonly RenderPromptFromFrameworkAction $renderPrompt) {}

    public function handle(string $frameworkKey, string $label, array $fieldValues, int $createdBy): GeneratedPrompt
    {
        $renderedPrompt = $this->renderPrompt->handle($frameworkKey, $fieldValues);

        return GeneratedPrompt::create([
            'framework_key' => $frameworkKey,
            'label' => $label,
            'field_values' => $fieldValues,
            'rendered_prompt' => $renderedPrompt,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ]);
    }
}
