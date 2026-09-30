<?php

namespace Modules\Post\Features\PublicReading\Actions;

use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostCategory;

class ResolveArticleCategoryHeaderAction
{
    use AsAction;

    /**
     * @return array{current: ?PostCategory, left: ?PostCategory, right: Collection<int, PostCategory>}
     */
    public function handle(PostArticle $article): array
    {
        $currentCategory = $article->categories->first(fn ($c) => (bool) $c->pivot->is_primary)
            ?? $article->categories->first();

        if (! $currentCategory) {
            return ['current' => null, 'left' => null, 'right' => collect()];
        }

        $leftCategory = $currentCategory;

        $rightCategories = PostCategory::active()
            ->where('parent_id', $currentCategory->parent_id ?? $currentCategory->id)
            ->where('id', '!=', $currentCategory->id)
            ->orderBy('sort_order')
            ->get();

        return ['current' => $currentCategory, 'left' => $leftCategory, 'right' => $rightCategories];
    }
}
