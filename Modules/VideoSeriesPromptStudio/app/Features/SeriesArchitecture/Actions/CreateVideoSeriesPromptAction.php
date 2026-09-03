<?php

namespace Modules\VideoSeriesPromptStudio\Features\SeriesArchitecture\Actions;

use Modules\ContentFoundation\Models\CategoryContentFoundation;
use Modules\VideoSeriesPromptStudio\Models\VideoSeriesPrompt;

class CreateVideoSeriesPromptAction
{
    public function __construct(private readonly BuildSeriesArchitecturePromptAction $buildPrompt) {}

    public function handle(
        string $label,
        string $seriesTopic,
        ?string $pov,
        ?string $businessGoal,
        int $episodeCount,
        string $platform,
        ?int $postCategoryId,
        int $createdBy,
    ): VideoSeriesPrompt {
        $foundation = $postCategoryId
            ? CategoryContentFoundation::query()
                ->whereHas('categories', fn ($q) => $q->where('post_categories.id', $postCategoryId))
                ->first()
            : null;

        $renderedPrompt = $this->buildPrompt->handle($seriesTopic, $pov, $businessGoal, $episodeCount, $platform, $foundation);

        return VideoSeriesPrompt::create([
            'post_category_id' => $postCategoryId,
            'label' => $label,
            'series_topic' => $seriesTopic,
            'pov' => $pov,
            'business_goal' => $businessGoal,
            'episode_count' => $episodeCount,
            'platform' => $platform,
            'rendered_prompt' => $renderedPrompt,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ]);
    }
}
