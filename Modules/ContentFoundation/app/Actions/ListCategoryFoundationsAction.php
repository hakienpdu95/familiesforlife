<?php

namespace Modules\ContentFoundation\Actions;

use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentFoundation\Models\CategoryContentFoundation;
use Modules\Menu\Models\MenuItem;
use Modules\Post\Features\CategoryManagement\Queries\GetCategoryTreeHandler;
use Modules\Post\Features\CategoryManagement\Queries\GetCategoryTreeQuery;
use Modules\Post\Models\PostCategory;

/**
 * spec/CoreIdeaExtractor.md §12 — tách từ CoreIdeaExtractor. Dùng GetCategoryTreeHandler
 * (Post::CategoryManagement) để dựng cây chuyên mục AN TOÀN ở MỌI độ sâu (1 query + group theo
 * parent_id, không N+1/lazy-load) — KHÔNG dùng PostCategory::navTree(), method đó chỉ eager-load
 * ĐÚNG 1 cấp con, gây LazyLoadingViolationException khi flatten() đệ quy xuống cấp cháu trở đi.
 */
class ListCategoryFoundationsAction
{
    use AsAction;

    /**
     * @return array<int, array{category_id:int, uuid:string, name:string, depth:int, foundation: array{core_focus:?string, writer_insights:?string, unique_angle:?string, content_goals:?string, pain_points:?string, objections:?string, decision_criteria:?string, family_values_focus: string[], rejected_ideas:?string, audience:?string, constraints:?string, style_sample:?string, updated_at:?string, shared_with: array<int, array{uuid:string, name:string}>}|null}>
     */
    public function handle(): array
    {
        $tree = (new GetCategoryTreeHandler())->handle(new GetCategoryTreeQuery(activeOnly: true));

        // Đồng bộ danh sách trang Content Foundation theo Menu chính (dashboard/menu/items,
        // location=header) — chỉ giữ category (và tổ tiên của nó) đang được ÍT NHẤT 1
        // `menu_items.location=header` trỏ tới qua `category_id`, tránh lẫn category "chết" không
        // còn ai dùng để điều hướng.
        $referencedCategoryIds = MenuItem::query()
            ->where('location', 'header')
            ->whereNotNull('category_id')
            ->pluck('category_id')
            ->all();

        $tree = $this->pruneToMenuReferenced($tree, $referencedCategoryIds);

        $flat = PostCategory::flatten($tree);

        // 1 query cho toàn bộ foundation + category liên kết (N-N) — tránh N+1 khi build
        // `shared_with` cho từng category.
        $foundationByCategoryId = [];
        foreach (CategoryContentFoundation::query()->with(['categories' => function ($q) {
            $q->where('is_active', true)->select('post_categories.id', 'post_categories.uuid', 'post_categories.name');
        }])->get() as $foundation) {
            foreach ($foundation->categories as $linkedCategory) {
                $foundationByCategoryId[$linkedCategory->id] = $foundation;
            }
        }

        return array_map(function (array $node) use ($foundationByCategoryId): array {
            /** @var PostCategory $category */
            $category   = $node['category'];
            $foundation = $foundationByCategoryId[$category->id] ?? null;

            return [
                'category_id' => $category->id,
                'uuid'        => $category->uuid,
                'name'        => $category->name,
                'depth'       => $node['depth'],
                'foundation'  => $foundation ? [
                    'core_focus'      => $foundation->core_focus,
                    'writer_insights' => $foundation->writer_insights,
                    'unique_angle'   => $foundation->unique_angle,
                    'content_goals'  => $foundation->content_goals,
                    'pain_points'    => $foundation->pain_points,
                    'objections'     => $foundation->objections,
                    'decision_criteria' => $foundation->decision_criteria,
                    'family_values_focus' => $foundation->family_values_focus ?? [],
                    'rejected_ideas' => $foundation->rejected_ideas,
                    'audience'       => $foundation->audience,
                    'constraints'    => $foundation->constraints,
                    'style_sample'   => $foundation->style_sample,
                    'updated_at'     => $foundation->updated_at?->toIso8601String(),
                    'shared_with'    => $foundation->categories
                        ->reject(fn (PostCategory $linked) => $linked->id === $category->id)
                        ->map(fn (PostCategory $linked) => ['uuid' => $linked->uuid, 'name' => $linked->name])
                        ->values()
                        ->all(),
                ] : null,
            ];
        }, $flat);
    }

    /**
     * @param Collection<int, PostCategory> $nodes
     * @param int[] $referencedCategoryIds
     * @return Collection<int, PostCategory>
     */
    private function pruneToMenuReferenced(Collection $nodes, array $referencedCategoryIds): Collection
    {
        return $nodes
            ->map(function (PostCategory $node) use ($referencedCategoryIds) {
                $node->setRelation('children', $this->pruneToMenuReferenced($node->children, $referencedCategoryIds));

                return $node;
            })
            ->filter(fn (PostCategory $node) => in_array($node->id, $referencedCategoryIds, true) || $node->children->isNotEmpty())
            ->values();
    }
}
