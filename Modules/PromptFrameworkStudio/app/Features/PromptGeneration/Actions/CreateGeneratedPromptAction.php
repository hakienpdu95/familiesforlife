<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Models\PostCategory;
use Modules\PromptFrameworkStudio\Features\Concerns\ResolvesCategoryFoundation;
use Modules\PromptFrameworkStudio\Models\GeneratedPrompt;

/** spec/PromptFrameworkStudio_Technical_Specification.md §5.2 — luồng tạo mới. */
class CreateGeneratedPromptAction
{
    use AsAction;
    use ResolvesCategoryFoundation;

    public function __construct(private readonly RenderPromptFromFrameworkAction $renderPrompt) {}

    /**
     * @param  array<string, string|null>  $fieldValues
     */
    public function handle(
        string $frameworkKey,
        string $label,
        array $fieldValues,
        int $createdBy,
        ?int $postCategoryId = null,
    ): GeneratedPrompt {
        $foundation = $this->resolveFoundation($postCategoryId);
        $categoryName = $postCategoryId ? PostCategory::find($postCategoryId)?->name : null;

        $renderedPrompt = $this->renderPrompt->handle($frameworkKey, $fieldValues, $foundation, $categoryName);

        return GeneratedPrompt::create([
            'framework_key' => $frameworkKey,
            'post_category_id' => $postCategoryId,
            'label' => $label,
            'field_values' => $fieldValues,
            'rendered_prompt' => $renderedPrompt,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ]);
    }
}
