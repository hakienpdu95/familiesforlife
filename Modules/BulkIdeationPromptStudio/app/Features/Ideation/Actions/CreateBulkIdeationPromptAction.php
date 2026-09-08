<?php

namespace Modules\BulkIdeationPromptStudio\Features\Ideation\Actions;

use Modules\BulkIdeationPromptStudio\Models\BulkIdeationPrompt;
use Modules\ContentFoundation\Models\CategoryContentFoundation;

class CreateBulkIdeationPromptAction
{
    public function __construct(private readonly BuildBulkIdeationPromptAction $buildPrompt) {}

    public function handle(
        string $label,
        string $rawInputs,
        ?string $businessGoal,
        ?int $postCategoryId,
        int $createdBy,
    ): BulkIdeationPrompt {
        $foundation = $postCategoryId
            ? CategoryContentFoundation::query()
                ->whereHas('categories', fn ($q) => $q->where('post_categories.id', $postCategoryId))
                ->first()
            : null;

        $renderedPrompt = $this->buildPrompt->handle($rawInputs, $businessGoal, $foundation);

        return BulkIdeationPrompt::create([
            'post_category_id' => $postCategoryId,
            'label' => $label,
            'raw_inputs' => $rawInputs,
            'business_goal' => $businessGoal,
            'rendered_prompt' => $renderedPrompt,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ]);
    }
}
