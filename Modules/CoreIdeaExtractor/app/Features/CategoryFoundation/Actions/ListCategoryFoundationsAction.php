<?php

namespace Modules\CoreIdeaExtractor\Features\CategoryFoundation\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\CoreIdeaExtractor\Models\CategoryContentFoundation;
use Modules\Post\Features\CategoryManagement\Queries\GetCategoryTreeHandler;
use Modules\Post\Features\CategoryManagement\Queries\GetCategoryTreeQuery;
use Modules\Post\Models\PostCategory;

/**
 * spec/CoreIdeaExtractor.md §12 (v1.4). Dùng GetCategoryTreeHandler (Post::CategoryManagement)
 * để dựng cây chuyên mục AN TOÀN ở MỌI độ sâu (1 query + group theo parent_id, không N+1/lazy-
 * load) — KHÔNG dùng PostCategory::navTree(), method đó chỉ eager-load ĐÚNG 1 cấp con (thiết kế
 * cho nav 2 cấp của trang chủ), gây LazyLoadingViolationException khi flatten() đệ quy xuống
 * cấp cháu trở đi. flatten() (PostCategory) vẫn dùng được bình thường sau khi có cây đầy đủ.
 */
class ListCategoryFoundationsAction
{
    use AsAction;

    /**
     * @return array<int, array{category_id:int, uuid:string, name:string, depth:int, foundation: array{core_focus:?string, unique_angle:?string, content_goals:?string, audience:?string, constraints:?string, style_sample:?string}|null}>
     */
    public function handle(): array
    {
        $tree = (new GetCategoryTreeHandler())->handle(new GetCategoryTreeQuery(activeOnly: true));
        $flat = PostCategory::flatten($tree);

        $foundationsByCategoryId = CategoryContentFoundation::query()
            ->get()
            ->keyBy('post_category_id');

        return array_map(function (array $node) use ($foundationsByCategoryId): array {
            /** @var PostCategory $category */
            $category   = $node['category'];
            $foundation = $foundationsByCategoryId->get($category->id);

            return [
                'category_id' => $category->id,
                'uuid'        => $category->uuid,
                'name'        => $category->name,
                'depth'       => $node['depth'],
                'foundation'  => $foundation ? [
                    'core_focus'    => $foundation->core_focus,
                    'unique_angle'  => $foundation->unique_angle,
                    'content_goals' => $foundation->content_goals,
                    'audience'      => $foundation->audience,
                    'constraints'   => $foundation->constraints,
                    'style_sample'  => $foundation->style_sample,
                ] : null,
            ];
        }, $flat);
    }
}
