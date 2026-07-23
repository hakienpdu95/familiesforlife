<?php

namespace Modules\Post\Features\BreakingNews\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Features\BreakingNews\Data\BreakingNewsData;
use Modules\Post\Models\PostBreakingNews;

class CreateBreakingNewsAction
{
    use AsAction;

    public function handle(BreakingNewsData $data): PostBreakingNews
    {
        return PostBreakingNews::create([
            'article_id'        => $data->article_id,
            'headline_override' => $data->headline_override,
            'badge_label'       => $data->badge_label,
            'starts_at'         => $data->starts_at,
            'ends_at'           => $data->ends_at,
            'sort_order'        => $data->sort_order,
            'is_active'         => $data->is_active,
            'created_by'        => auth()->id(),
        ]);
    }
}
