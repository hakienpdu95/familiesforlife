<?php

namespace Modules\CoreIdeaExtractor\Features\CategoryFoundation\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\CoreIdeaExtractor\Features\CategoryFoundation\Data\CategoryFoundationData;
use Modules\CoreIdeaExtractor\Models\CategoryContentFoundation;
use Modules\Post\Models\PostCategory;

/**
 * spec/CoreIdeaExtractor.md §12 (v1.4) — đúng 1 bản ghi / category (unique post_category_id ở
 * migration), nên upsert theo post_category_id thay vì create/update tách rời. `created_by`
 * CHỈ set ở lần tạo đầu tiên (không ghi đè khi update lần sau bởi người khác).
 */
class UpsertCategoryFoundationAction
{
    use AsAction;

    public function handle(PostCategory $category, CategoryFoundationData $data, int $userId): CategoryContentFoundation
    {
        $foundation = CategoryContentFoundation::query()->firstOrNew([
            'post_category_id' => $category->id,
        ]);

        $foundation->fill([
            'core_focus'     => $data->core_focus,
            'unique_angle'   => $data->unique_angle,
            'content_goals'  => $data->content_goals,
            'pain_points'    => $data->pain_points,
            'rejected_ideas' => $data->rejected_ideas,
            'audience'       => $data->audience,
            'constraints'    => $data->constraints,
            'style_sample'   => $data->style_sample,
            'updated_by'     => $userId,
        ]);

        if (! $foundation->exists) {
            $foundation->created_by = $userId;
        }

        $foundation->save();

        return $foundation;
    }
}
