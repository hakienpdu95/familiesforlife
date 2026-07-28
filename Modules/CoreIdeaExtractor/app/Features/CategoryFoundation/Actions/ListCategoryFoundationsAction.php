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
     * @return array<int, array{category_id:int, uuid:string, name:string, depth:int, foundation: array{core_focus:?string, unique_angle:?string, content_goals:?string, pain_points:?string, rejected_ideas:?string, audience:?string, constraints:?string, style_sample:?string, updated_at:?string, shared_with: array<int, array{uuid:string, name:string}>}|null}>
     */
    public function handle(): array
    {
        $tree = (new GetCategoryTreeHandler())->handle(new GetCategoryTreeQuery(activeOnly: true));
        $flat = PostCategory::flatten($tree);

        // 1 query cho toàn bộ foundation + category liên kết (§12.9, N-N) — tránh N+1 khi build
        // `shared_with` cho từng category (số lượng foundation thường nhỏ so với số category).
        $foundationByCategoryId = [];
        foreach (CategoryContentFoundation::query()->with('categories:id,uuid,name')->get() as $foundation) {
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
                    'core_focus'     => $foundation->core_focus,
                    'unique_angle'   => $foundation->unique_angle,
                    'content_goals'  => $foundation->content_goals,
                    'pain_points'    => $foundation->pain_points,
                    'rejected_ideas' => $foundation->rejected_ideas,
                    'audience'       => $foundation->audience,
                    'constraints'    => $foundation->constraints,
                    'style_sample'   => $foundation->style_sample,
                    // Trước đây có sẵn ở DB (timestamps()) nhưng chưa từng lộ ra API/UI — editor
                    // không có cách nào biết 1 foundation đã bao lâu chưa được ôn lại (context
                    // engineering: ngữ cảnh cần được xem là tài sản SỐNG, không phải cấu hình tĩnh
                    // viết 1 lần rồi bỏ quên — xem cảnh báo "stale" ở category-foundations.blade.php).
                    'updated_at'     => $foundation->updated_at?->toIso8601String(),
                    // §12.9 — các category KHÁC (ngoài category hiện tại) đang dùng chung đúng bộ
                    // tiêu chí này, để UI hiển thị "Dùng chung với: ..." và tiền chọn multi-select.
                    'shared_with'    => $foundation->categories
                        ->reject(fn (PostCategory $linked) => $linked->id === $category->id)
                        ->map(fn (PostCategory $linked) => ['uuid' => $linked->uuid, 'name' => $linked->name])
                        ->values()
                        ->all(),
                ] : null,
            ];
        }, $flat);
    }
}
